<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\BehaviorReport;
use App\Models\Notification;
use App\Models\ClassRoom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    /**
     * แสดงหน้า Dashboard สำหรับผู้ปกครอง
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        if ($user->users_role !== 'guardian') {
            return redirect()->route('login')->with('error', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        // ดึงข้อมูลนักเรียนที่ผู้ปกครองดูแล
        $guardian = Guardian::where('user_id', $user->users_id)->first();
        $studentsData = [];
        
        if ($guardian) {
            $students = Student::whereHas('guardians', function($query) use ($guardian) {
                $query->where('guardian_id', $guardian->guardians_id);
            })->with(['user', 'classroom', 'behaviorReports'])->get();

            foreach ($students as $student) {
                // คำนวณอันดับในชั้นจริง
                $classRankData = $this->getClassRank($student->students_id, $student->class_id);
                
                // คำนวณการเปลี่ยนแปลงคะแนนในสัปดาห์นี้
                $weeklyChange = $this->getWeeklyScoreChange($student->students_id);
                
                // ดึงข้อมูลครูประจำชั้น
                $homeroomTeacher = $this->getHomeRoomTeacher($student->class_id);
                
                // ดึงกิจกรรมล่าสุด
                $recentActivities = $this->getRecentActivities($student->students_id);
                
                $studentsData[] = [
                    'id' => $student->students_id,
                    'name_prefix' => $student->user->users_name_prefix ?? '',
                    'first_name' => $student->user->users_first_name ?? '',
                    'last_name' => $student->user->users_last_name ?? '',
                    'student_code' => $student->students_student_code ?? '-',
                    'class_level' => $student->classroom->classes_level ?? '-',
                    'class_room' => $student->classroom->classes_room_number ?? '-',
                    'current_score' => $student->students_current_score ?? 100,
                    'score_color' => $this->getScoreColor($student->students_current_score ?? 100),
                    'score_status' => $this->getScoreStatus($student->students_current_score ?? 100),
                    'class_rank' => $classRankData['rank'],
                    'total_students' => $classRankData['total'],
                    'weekly_change' => $weeklyChange,
                    'change_color' => $weeklyChange >= 0 ? 'success' : 'danger',
                    'change_direction' => $weeklyChange >= 0 ? 'up' : 'down',
                    'homeroom_teacher' => $homeroomTeacher,
                    'recent_activities' => $recentActivities
                ];
            }
        }

        // ดึงการแจ้งเตือนล่าสุดจากฐานข้อมูล
        $notifications = Notification::where('user_id', $user->users_id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $this->getNotificationTypeColor($notification->type),
                    'icon' => $this->getNotificationIcon($notification->type),
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'date' => $this->formatNotificationDate($notification->created_at),
                    'badge_class' => $notification->read_at ? 'bg-secondary' : 'bg-primary',
                    'badge_text' => $notification->read_at ? 'อ่านแล้ว' : 'ใหม่',
                    'is_read' => $notification->read_at ? true : false,
                    'created_at' => $notification->created_at
                ];
            });

        return view('parent.dashboard', compact('user', 'studentsData', 'notifications'));
    }

    // ========== Guardian manage students APIs ==========
    public function getGuardianStudents(Request $request)
    {
        $user = Auth::user();
        if ($user->users_role !== 'guardian') {
            return response()->json(['success' => false, 'message' => 'ไม่อนุญาต'], 403);
        }
        $guardian = Guardian::where('user_id', $user->users_id)->first();
        if (!$guardian) {
            return response()->json(['success' => true, 'data' => []]);
        }
        $students = Student::whereHas('guardians', function($q) use ($guardian){
                $q->where('guardian_id', $guardian->guardians_id);
            })
            ->with(['user','classroom'])
            ->orderBy('students_student_code')
            ->get()
            ->map(function($s){
                return [
                    'id' => $s->students_id,
                    'code' => $s->students_student_code,
                    'name' => ($s->user->users_name_prefix ?? '').($s->user->users_first_name ?? '').' '.($s->user->users_last_name ?? ''),
                    'class' => $s->classroom ? ($s->classroom->classes_level.'/'.$s->classroom->classes_room_number) : '-',
                ];
            });
        return response()->json(['success' => true, 'data' => $students]);
    }

    public function searchStudents(Request $request)
    {
        $user = Auth::user();
        if ($user->users_role !== 'guardian') {
            return response()->json(['success' => false, 'message' => 'ไม่อนุญาต'], 403);
        }
        $term = trim($request->get('q',''));
        if ($term === '') {
            return response()->json(['success' => true, 'data' => []]);
        }
        $students = Student::with(['user','classroom'])
            ->where(function($q) use ($term){
                $q->where('students_student_code', 'LIKE', "%$term%")
                  ->orWhereHas('user', function($uq) use ($term){
                      $uq->whereRaw("CONCAT(users_first_name,' ',users_last_name) LIKE ?", ["%$term%"])
                         ->orWhere('users_first_name', 'LIKE', "%$term%")
                         ->orWhere('users_last_name', 'LIKE', "%$term%")
                         ->orWhere('users_email', 'LIKE', "%$term%");
                  });
            })
            ->orderBy('students_student_code')
            ->limit(15)
            ->get()
            ->map(function($s){
                return [
                    'id' => $s->students_id,
                    'code' => $s->students_student_code,
                    'name' => ($s->user->users_name_prefix ?? '').($s->user->users_first_name ?? '').' '.($s->user->users_last_name ?? ''),
                    'class' => $s->classroom ? ($s->classroom->classes_level.'/'.$s->classroom->classes_room_number) : '-',
                ];
            });
        return response()->json(['success' => true, 'data' => $students]);
    }

    public function linkStudent(Request $request)
    {
        return response()->json([
            'success' => false,
            'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเพิ่มการเชื่อมโยงนักเรียนได้'
        ], 403);
    }

    public function unlinkStudent($studentId)
    {
        return response()->json([
            'success' => false,
            'message' => 'เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถลบนักเรียนที่เชื่อมโยงได้'
        ], 403);
    }

    /**
     * กำหนดสีของการแจ้งเตือนตามประเภท
     */
    private function getNotificationTypeColor($type)
    {
        switch ($type) {
            case 'behavior':
                return 'danger';
            case 'attendance':
                return 'warning';
            case 'meeting':
                return 'info';
            case 'achievement':
                return 'success';
            default:
                return 'primary';
        }
    }

    /**
     * กำหนดไอคอนของการแจ้งเตือนตามประเภท
     */
    private function getNotificationIcon($type)
    {
        switch ($type) {
            case 'behavior':
                return 'fas fa-exclamation-triangle';
            case 'attendance':
                return 'fas fa-calendar-times';
            case 'meeting':
                return 'fas fa-users';
            case 'achievement':
                return 'fas fa-trophy';
            default:
                return 'fas fa-bell';
        }
    }

    /**
     * จัดรูปแบบวันที่การแจ้งเตือน
     */
    private function formatNotificationDate($date)
    {
        if (!$date) {
            return 'ไม่ระบุเวลา';
        }
        
        try {
            $now = now();
            $carbon = Carbon::parse($date);
            
            // If date parsing failed or resulted in an invalid date
            if (!$carbon || !$carbon->isValid()) {
                return 'ไม่ระบุเวลา';
            }
            
            // Make sure we're working with dates in the past
            if ($carbon->gt($now)) {
                $carbon = $now;
            }
            
            $diffInMinutes = (int)$now->diffInMinutes($carbon, false);
            $diffInHours = (int)$now->diffInHours($carbon, false);
            $diffInDays = (int)$now->diffInDays($carbon, false);

            // Make sure differences are positive (absolute values)
            $diffInMinutes = abs($diffInMinutes);
            $diffInHours = abs($diffInHours);
            $diffInDays = abs($diffInDays);

            if ($diffInMinutes < 60) {
                return ($diffInMinutes == 0) ? 'เมื่อสักครู่' : $diffInMinutes . ' นาทีที่แล้ว';
            } elseif ($diffInHours < 24) {
                return $diffInHours . ' ชั่วโมงที่แล้ว';
            } elseif ($diffInDays < 7) {
                return $diffInDays . ' วันที่แล้ว';
            } else {
                return $carbon->format('d/m/Y H:i');
            }
        } catch (\Exception $e) {
            return 'ไม่ระบุเวลา';
        }
    }

    /**
     * คำนวณการเปลี่ยนแปลงคะแนนในสัปดาห์นี้
     */
    private function getWeeklyScoreChange($studentId)
    {
        $startOfWeek = Carbon::now()->startOfWeek();
        
        $weeklyChange = BehaviorReport::join('tb_violations', 'tb_behavior_reports.violation_id', '=', 'tb_violations.violations_id')
            ->where('tb_behavior_reports.student_id', $studentId)
            ->where('tb_behavior_reports.reports_report_date', '>=', $startOfWeek)
            ->sum('tb_violations.violations_points_deducted');
            
        // แปลงเป็นค่าลบเพื่อแสดงการเปลี่ยนแปลง (คะแนนลดลง = ค่าลบ)
        return $weeklyChange ? -(int) $weeklyChange : 0;
    }
    
    /**
     * คำนวณอันดับในชั้น
     */
    private function getClassRank($studentId, $classId)
    {
        if (!$classId) {
            return ['rank' => '-', 'total' => 0];
        }
        
        $classStudents = Student::where('class_id', $classId)
            ->orderBy('students_current_score', 'desc')
            ->get();
            
        $rank = '-';
        $total = $classStudents->count();
        
        if ($total > 0) {
            foreach ($classStudents as $index => $student) {
                if ($student->students_id == $studentId) {
                    $rank = $index + 1;
                    break;
                }
            }
        }
        
        return ['rank' => $rank, 'total' => $total];
    }
    
    /**
     * หาครูประจำชั้น
     */
    private function getHomeRoomTeacher($classId)
    {
        if (!$classId) {
            return [
                'name' => 'ไม่พบข้อมูล',
                'phone' => '-',
                'email' => '-'
            ];
        }
        
        $class = ClassRoom::with(['teacher.user'])->find($classId);
        
        if ($class && $class->teacher && $class->teacher->user) {
            return [
                'name' => ($class->teacher->user->users_name_prefix ?? '') . 
                         $class->teacher->user->users_first_name . ' ' . 
                         $class->teacher->user->users_last_name,
                'phone' => $class->teacher->user->users_phone_number ?? '-',
                'email' => $class->teacher->user->users_email ?? '-'
            ];
        }
        
        return [
            'name' => 'ยังไม่ได้กำหนด',
            'phone' => '-',
            'email' => '-'
        ];
    }
    
    /**
     * ดึงกิจกรรมล่าสุดของนักเรียน
     */
    private function getRecentActivities($studentId)
    {
        $activities = BehaviorReport::with(['violation', 'teacher.user'])
            ->where('student_id', $studentId)
            ->orderBy('reports_report_date', 'desc')
            ->limit(5)
            ->get();
            
        if ($activities->isEmpty()) {
            return [];
        }
            
        $formattedActivities = [];
        
        foreach ($activities as $activity) {
            if (!$activity->violation) {
                continue; // ข้ามถ้าไม่มีข้อมูล violation
            }
            
            $points = abs($activity->violation->violations_points_deducted);
            $teacherName = 'ไม่ระบุ';
            
            if ($activity->teacher && $activity->teacher->user) {
                $teacherName = ($activity->teacher->user->users_name_prefix ?? '') . 
                      $activity->teacher->user->users_first_name;
            }
            
            $formattedActivities[] = [
                'type' => 'negative',
                'icon' => 'fas fa-minus',
                'color' => $points >= 5 ? 'danger' : 'warning',
                'message' => "ถูกหักคะแนน {$points} จาก {$activity->violation->violations_name}",
                'teacher' => $teacherName,
                'date' => Carbon::parse($activity->reports_report_date)->locale('th')->format('j M Y'),
                'points' => -$points // แสดงเป็นค่าลบ
            ];
        }
        
        return $formattedActivities;
    }
    
    /**
     * กำหนดสีตามคะแนน
     */
    private function getScoreColor($score)
    {
        if ($score >= 90) return 'success';
        if ($score >= 80) return 'primary';
        if ($score >= 60) return 'warning';
        return 'danger';
    }
    
    /**
     * กำหนดสถานะตามคะแนน
     */
    private function getScoreStatus($score)
    {
        if ($score >= 90) return 'ดีเยี่ยม';
        if ($score >= 80) return 'ดีมาก';
        if ($score >= 70) return 'ดี';
        if ($score >= 60) return 'พอใช้';
        return 'ต้องปรับปรุง';
    }
    
    /**
     * ดึงการแจ้งเตือนล่าสุด
     */
    private function getRecentNotifications($studentIds)
    {
        if (empty($studentIds)) {
            return collect();
        }
        
        $recentReports = BehaviorReport::with(['student.user', 'violation', 'teacher.user'])
            ->whereIn('student_id', $studentIds)
            ->orderBy('reports_report_date', 'desc')
            ->limit(5)
            ->get();
            
        $notifications = collect();
        
        foreach ($recentReports as $report) {
            $points = abs($report->violations_points_deducted); // ใช้ค่าสัมบูรณ์เพื่อแสดงเป็นตัวเลขบวก
            $studentName = ($report->student->user->users_name_prefix ?? '') . 
                  $report->student->user->users_first_name;
            
            $type = $points >= 5 ? 'danger' : 'warning';
            $icon = $points >= 5 ? 'fas fa-exclamation-triangle' : 'fas fa-minus-circle';
            $badgeClass = $points >= 5 ? 'bg-danger' : 'bg-warning text-dark';
            $badgeText = $points >= 5 ? 'ด่วน' : 'แจ้งเตือน';
            $message = "{$studentName} ถูกหักคะแนน {$points} คะแนน เนื่องจาก{$report->violations_name}";
            
            $notifications->push([
            'type' => $type,
            'icon' => $icon,
            'message' => $message,
            'date' => Carbon::parse($report->reports_report_date)->locale('th')->diffForHumans(),
            'badge_class' => $badgeClass,
            'badge_text' => $badgeText,
            'student_name' => $studentName
            ]);
        }
        
        return $notifications;
    }
    
    /**
     * API: ดึงข้อมูลกราฟคะแนนของนักเรียน
     */
    public function getStudentScoreChart($studentId)
    {
        try {
            $student = Student::find($studentId);
            
            if (!$student) {
                return response()->json([
                    'error' => 'ไม่พบข้อมูลนักเรียน'
                ], 404);
            }
            
            $months = [];
            $scores = [];
            $currentScore = $student->students_current_score ?? 100;
            
            // สร้างข้อมูลกราฟ 6 เดือนย้อนหลัง
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $months[] = $date->locale('th')->format('M');
                
                if ($i == 0) {
                    $scores[] = $currentScore;
                } else {
                    // คำนวณคะแนนจาก behavior reports
                    $monthlyDeduction = BehaviorReport::join('tb_violations', 'tb_behavior_reports.violation_id', '=', 'tb_violations.violations_id')
                        ->where('tb_behavior_reports.student_id', $studentId)
                        ->whereYear('tb_behavior_reports.reports_report_date', $date->year)
                        ->whereMonth('tb_behavior_reports.reports_report_date', $date->month)
                        ->sum('tb_violations.violations_points_deducted');
                    
                    $estimatedScore = max(60, min(100, $currentScore + ($i * 2) - $monthlyDeduction));
                    $scores[] = $estimatedScore;
                }
            }
            
            return response()->json([
                'labels' => $months,
                'data' => $scores,
                'success' => true
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error generating student chart: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'เกิดข้อผิดพลาดในการสร้างกราฟ',
                'labels' => ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.'],
                'data' => [95, 92, 88, 90, 85, 100]
            ], 500);
        }
    }
    
    /**
     * ดึงการแจ้งเตือนทั้งหมด
     */
    public function getNotifications(Request $request)
    {
        $user = Auth::user();
        $filter = $request->get('filter', 'all');
        
        $query = Notification::where('user_id', $user->users_id)
            ->orderBy('created_at', 'desc');
        
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }
        
        $notifications = $query->get()->map(function($notification) {
            return [
                'id' => $notification->id,
                'type' => $this->getNotificationTypeColor($notification->type),
                'icon' => $this->getNotificationIcon($notification->type),
                'title' => $notification->title,
                'message' => $notification->message,
                'date' => $this->formatNotificationDate($notification->created_at),
                'badge_class' => $notification->read_at ? 'bg-secondary' : 'bg-primary',
                'badge_text' => $notification->read_at ? 'อ่านแล้ว' : 'ใหม่',
                'is_read' => $notification->read_at ? true : false,
                'created_at' => $notification->created_at
            ];
        });
        
        return response()->json([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * ดึงจำนวนการแจ้งเตือนที่ยังไม่อ่าน
     */
    public function getUnreadNotificationCount()
    {
        $user = Auth::user();
        $count = Notification::where('user_id', $user->users_id)
            ->whereNull('read_at')
            ->count();
        
        return response()->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * ทำเครื่องหมายการแจ้งเตือนว่าอ่านแล้ว
     */
    public function markNotificationAsRead($id)
    {
        $user = Auth::user();
        $notification = Notification::where('id', $id)
            ->where('user_id', $user->users_id)
            ->first();
        
        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบการแจ้งเตือน'
            ], 404);
        }
        
        $notification->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'ทำเครื่องหมายอ่านแล้วสำเร็จ'
        ]);
    }

    /**
     * ทำเครื่องหมายการแจ้งเตือนทั้งหมดว่าอ่านแล้ว
     */
    public function markAllNotificationsAsRead()
    {
        $user = Auth::user();
        
        Notification::where('user_id', $user->users_id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
        
        return response()->json([
            'success' => true,
            'message' => 'ทำเครื่องหมายอ่านทั้งหมดสำเร็จ'
        ]);
    }
}