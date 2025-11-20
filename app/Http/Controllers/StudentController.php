<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\BehaviorReport;
use App\Models\ClassRoom;
use App\Models\Teacher;
use App\Models\Violation;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentController extends Controller
{
    /**
     * แสดงหน้า Dashboard ของนักเรียน
     */
    public function dashboard()
    {
        try {
            // ดึงข้อมูลผู้ใช้ที่เข้าสู่ระบบ
            $user = Auth::user();
            
            if (!$user) {
                return redirect()->route('login')->with('error', 'กรุณาเข้าสู่ระบบ');
            }
            
            // ดึงข้อมูลนักเรียน
            $student = DB::table('tb_students')
                ->leftJoin('tb_classes', 'tb_students.class_id', '=', 'tb_classes.classes_id')
                ->leftJoin('tb_teachers', 'tb_classes.teachers_id', '=', 'tb_teachers.teachers_id')
                ->leftJoin('tb_users as teacher_users', 'tb_teachers.users_id', '=', 'teacher_users.users_id')
                ->where('tb_students.user_id', $user->users_id)
                ->select(
                    'tb_students.*',
                    'tb_classes.classes_level',
                    'tb_classes.classes_room_number',
                    'teacher_users.users_name_prefix as teacher_prefix',
                    'teacher_users.users_first_name as teacher_first_name',
                    'teacher_users.users_last_name as teacher_last_name'
                )
                ->first();
            
            if (!$student) {
                Log::warning('Student not found for user_id: ' . $user->users_id);
                return redirect()->route('login')->with('error', 'ไม่พบข้อมูลนักเรียนในระบบ');
            }
            
            // Fetch all behavior reports in one query to avoid N+1 problem
            $behaviorReports = DB::table('tb_behavior_reports')
                ->leftJoin('tb_violations', 'tb_behavior_reports.violation_id', '=', 'tb_violations.violations_id')
                ->leftJoin('tb_teachers', 'tb_behavior_reports.teacher_id', '=', 'tb_teachers.teachers_id')
                ->leftJoin('tb_users', 'tb_teachers.users_id', '=', 'tb_users.users_id')
                ->where('tb_behavior_reports.student_id', $student->students_id)
                ->select(
                    'tb_behavior_reports.*',
                    'tb_violations.violations_name',
                    'tb_violations.violations_category',
                    'tb_users.users_name_prefix',
                    'tb_users.users_first_name',
                    'tb_users.users_last_name'
                )
                ->orderBy('tb_behavior_reports.reports_report_date', 'desc')
                ->get();

            Log::info('Student found: ' . $student->students_id . ', Reports count: ' . $behaviorReports->count());
            
            // จัดเตรียมข้อมูลสำหรับ Dashboard
            $data = [
                'user' => $user,
                'student' => $student,
                'current_student' => $student,
                'stats' => [
                    'current_score' => $student->students_current_score ?? 100,
                    'class_rank' => $this->getStudentRank($student->students_id, $student->class_id),
                    'total_students' => $this->getClassTotalStudents($student->class_id),
                    'rank_status' => $this->getRankStatus($student->students_current_score ?? 100)
                ],
                'recent_activities' => $this->processRecentActivities($behaviorReports),
                'chart_data' => $this->processBehaviorChartData($behaviorReports, $student->students_current_score ?? 100),
                'classroom_details' => $this->getClassroomDetails($student->class_id),
                'behavior_summary' => $this->processBehaviorSummary($behaviorReports),
                'violation_distribution' => $this->processViolationDistribution($behaviorReports),
                'top_students' => $this->getTopStudentsInClass($student->class_id, $student->students_id),
                'notifications' => $this->getStudentNotifications($user->users_id)
            ];
            
            return view('student.dashboard', $data);
            
        } catch (\Exception $e) {
            Log::error('Error in dashboard: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return view('errors.500', ['message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูล Dashboard']);
        }
    }
    

    /**
     * Process recent activities from collection
     */
    private function processRecentActivities($reports)
    {
        if ($reports->isEmpty()) {
            return collect([]);
        }
        
        // Take first 5 items
        return $reports->take(5)->map(function ($report) {
            $pointsDeducted = (int)($report->reports_points_deducted ?? 0);
            $isPositive = false;
            $scoreChange = abs($pointsDeducted);
            
            // สร้างชื่อครู
            $teacherName = 'ระบบ';
            if ($report->users_first_name) {
                $teacherName = ($report->users_name_prefix ?? '') . $report->users_first_name . ' ' . ($report->users_last_name ?? '');
            }
            
            // สร้างข้อความกิจกรรม
            $title = "ถูกหักคะแนน -{$scoreChange} คะแนน";
            if ($report->violations_name) {
                $title .= " จาก " . $report->violations_name;
            }
            
            // กำหนดสี badge
            $badgeColor = 'bg-secondary';
            if ($isPositive) {
                $badgeColor = 'bg-success';
            } else {
                switch ($report->violations_category) {
                    case 'light': $badgeColor = 'bg-warning'; break;
                    case 'medium': $badgeColor = 'bg-danger'; break;
                    case 'severe': $badgeColor = 'bg-dark'; break;
                    default: $badgeColor = 'bg-danger';
                }
            }
            
            return [
                'id' => $report->reports_id,
                'title' => $title,
                'description' => $report->reports_description ?? '',
                'date' => Carbon::parse($report->reports_report_date)->locale('th')->format('d M Y'),
                'teacher' => $teacherName,
                'is_positive' => $isPositive,
                'badge_color' => $badgeColor,
                'score_change' => $scoreChange,
                'violation_category' => $report->violations_category ?? 'unknown'
            ];
        });
    }
    
    /**
     * Process behavior summary from collection
     */
    private function processBehaviorSummary($reports)
    {
        if ($reports->isEmpty()) return null;
        
        $totalReports = $reports->count();
        $negativeReports = $totalReports; // Assuming all are negative for now
        $positiveReports = 0;
        
        $totalNegativePoints = (int) $reports->sum('reports_points_deducted');
        $totalPositivePoints = 0;
        
        return [
            'total_reports' => $totalReports,
            'positive_reports' => $positiveReports,
            'negative_reports' => $negativeReports,
            'total_positive_points' => $totalNegativePoints, // Using negative points as total for now
            'total_negative_points' => $totalNegativePoints,
            'positive_ratio' => 0,
            'last_report_date' => $reports->first()->reports_report_date ?? null
        ];
    }
    
    /**
     * Process violation distribution from collection
     */
    private function processViolationDistribution($reports)
    {
        if ($reports->isEmpty()) {
            return [
                'labels' => ['ไม่มีข้อมูล'],
                'data' => [1],
                'colors' => ['#EEEEEE'],
            ];
        }
        
        $distribution = $reports->groupBy('violations_category')->map->count();
        
        $labels = [];
        $data = [];
        $colors = [];
        
        foreach ($distribution as $category => $count) {
            switch ($category) {
                case 'light':
                    $labels[] = 'เบา';
                    $colors[] = '#28a745';
                    break;
                case 'medium':
                    $labels[] = 'ปานกลาง';
                    $colors[] = '#ffc107';
                    break;
                case 'severe':
                    $labels[] = 'หนัก';
                    $colors[] = '#dc3545';
                    break;
                default:
                    $labels[] = 'อื่นๆ';
                    $colors[] = '#6c757d';
            }
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }
    
    /**
     * Process behavior chart data from collection
     */
    private function processBehaviorChartData($reports, $currentScore)
    {
        if ($reports->isEmpty()) {
            return $this->getEmptyChartData();
        }
        
        // Filter reports for current year and sort by date ascending
        $currentYear = date('Y');
        $sortedReports = $reports->filter(function($report) use ($currentYear) {
            return date('Y', strtotime($report->reports_report_date)) == $currentYear;
        })->sortBy('reports_report_date')->values();
        
        if ($sortedReports->isEmpty()) {
            return $this->getEmptyChartData();
        }
        
        $labels = [];
        $scores = [];
        $runningScore = 100; // Start with perfect score
        
        // Initial point
        $labels[] = 'เริ่มต้น';
        $scores[] = 100;
        
        foreach ($sortedReports as $report) {
            $pointsDeducted = abs((int)$report->reports_points_deducted);
            $runningScore = max(0, $runningScore - $pointsDeducted);
            
            $date = Carbon::parse($report->reports_report_date);
            $dateStr = $date->format('d/m');
            $violationName = $report->violations_name ?? 'หักคะแนน';
            
            if (mb_strlen($violationName) > 15) {
                $violationName = mb_substr($violationName, 0, 15) . '...';
            }
            
            $labels[] = $dateStr . ' - ' . $violationName;
            $scores[] = $runningScore;
        }
        
        // Sample data if too many points
        if (count($labels) > 10) {
            $sampled = $this->sampleChartData($labels, $scores, 10);
            $labels = $sampled['labels'];
            $scores = $sampled['scores'];
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'คะแนนความประพฤติ',
                    'data' => $scores,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.4,
                    'fill' => true,
                    'pointRadius' => 5,
                    'pointHoverRadius' => 7
                ]
            ]
        ];
    }

    /**
     * Sample chart data
     */
    private function sampleChartData($labels, $scores, $maxPoints)
    {
        $totalPoints = count($labels);
        if ($totalPoints <= $maxPoints) return ['labels' => $labels, 'scores' => $scores];

        $sampledLabels = [];
        $sampledScores = [];
        
        $sampledLabels[] = $labels[0];
        $sampledScores[] = $scores[0];
        
        $step = ($totalPoints - 2) / ($maxPoints - 2);
        
        for ($i = 1; $i < $maxPoints - 1; $i++) {
            $index = (int)round($i * $step);
            if ($index < $totalPoints - 1) {
                $sampledLabels[] = $labels[$index];
                $sampledScores[] = $scores[$index];
            }
        }
        
        $sampledLabels[] = $labels[$totalPoints - 1];
        $sampledScores[] = $scores[$totalPoints - 1];
        
        return ['labels' => $sampledLabels, 'scores' => $sampledScores];
    }
    
    private function getEmptyChartData()
    {
        $labels = [];
        $now = Carbon::now();
        for ($i = 3; $i >= 0; $i--) {
            $labels[] = $now->copy()->subMonths($i)->locale('th')->format('M Y');
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'คะแนน',
                    'data' => [100, 100, 100, 100],
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.3
                ]
            ]
        ];
    }
}