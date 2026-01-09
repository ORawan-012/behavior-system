<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Classroom;
use App\Models\BehaviorReport;
use App\Services\AcademicYearService;
use Mpdf\Mpdf; // แทน use PDF; ด้วย Mpdf
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * สร้างรายงานพฤติกรรมประจำเดือนเป็น PDF
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function monthlyReport(Request $request)
    {
        // รับพารามิเตอร์จาก request
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $classId = $request->input('class_id');
        
        // สร้างวันแรกและวันสุดท้ายของเดือน
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        // Query ข้อมูลห้องเรียน
        $classroom = null;
        if ($classId) {
            $classroom = Classroom::with('teacher.user')->find($classId);
        }
        
        // Query ข้อมูลนักเรียนและพฤติกรรม
        $query = Student::with(['user', 'classroom', 'behaviorReports' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('reports_report_date', [$startDate, $endDate])
                  ->with('violation');
        }]);
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        $students = $query->get();
        
        // เรียงลำดับนักเรียนตามชั้นเรียนและรหัสนักเรียน
        $students = $students->sortBy(function($student) {
            return [$student->classroom ? $student->classroom->classes_level : 'zzz',
                    $student->classroom ? $student->classroom->classes_room_number : 'zzz',
                    $student->students_student_code];
        });
        
        // คำนวณคะแนนพฤติกรรมประจำเดือน
        foreach ($students as $student) {
            $totalPointsDeducted = 0;
            foreach ($student->behaviorReports as $report) {
                $totalPointsDeducted += $report->violation ? $report->violation->violations_points_deducted : 0;
            }
            $student->monthly_deducted_points = $totalPointsDeducted;
            $student->monthly_score = max(0, 100 - $totalPointsDeducted);
        }
        
        // สร้างข้อมูลสำหรับรายงาน
        $data = [
            'title' => 'รายงานพฤติกรรมประจำเดือน',
            'month' => $month,
            'year' => $year,
            'monthName' => Carbon::createFromDate($year, $month, 1)->locale('th')->translatedFormat('F'),
            'classroom' => $classroom,
            'students' => $students,
            'generatedAt' => Carbon::now(),
            'reportPeriod' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
        ];
        
        // กำหนดชื่อไฟล์
        $fileName = 'รายงานพฤติกรรมประจำเดือน_' . 
                    $data['monthName'] . 
                    ($classroom ? '_' . $classroom->classes_level . '-' . $classroom->classes_room_number : '') . 
                    '.pdf';
        
        // สร้าง PDF ด้วย mPDF แทน
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 
            'format' => 'A4', 
            'orientation' => 'P',
            'default_font' => 'thsarabun',
            'default_font_size' => 14,
            'tempDir' => storage_path('app/public/temp'),
        ]);

        // ตั้งค่าเพิ่มเติมสำหรับ mPDF
        $mpdf->useAdobeCJK = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        // สร้าง HTML จาก view
        $html = view('reports.monthly_report', $data)->render();
        
        // เขียน HTML ลงใน PDF
        $mpdf->WriteHTML($html);
        
        // ส่งไฟล์ PDF กลับไป
        return response($mpdf->Output($fileName, \Mpdf\Output\Destination::DOWNLOAD))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    /**
     * สร้างรายงานสรุปนักเรียนที่มีความเสี่ยงเป็น PDF
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function riskStudentsReport(Request $request)
    {
        // รับพารามิเตอร์จาก request
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $classId = $request->input('class_id');
        $riskLevel = $request->input('risk_level', 'all'); // all, high, medium, low
        
        // สร้างวันแรกและวันสุดท้ายของเดือน
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        // Query ข้อมูลห้องเรียน
        $classroom = null;
        if ($classId) {
            $classroom = Classroom::with('teacher.user')->find($classId);
        }
        
        // Query ข้อมูลนักเรียนและพฤติกรรม
        $query = Student::with(['user', 'classroom', 'guardians.user', 'behaviorReports' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('reports_report_date', [$startDate, $endDate])
                  ->with('violation');
        }]);
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        $students = $query->get();
        
        // คำนวณคะแนนพฤติกรรมและระดับความเสี่ยง
        $riskStudents = collect();
        foreach ($students as $student) {
            $totalPointsDeducted = 0;
            $violationCount = $student->behaviorReports->count();
            
            foreach ($student->behaviorReports as $report) {
                $totalPointsDeducted += $report->violation ? $report->violation->violations_points_deducted : 0;
            }
            
            $student->monthly_deducted_points = $totalPointsDeducted;
            $student->monthly_score = max(0, 100 - $totalPointsDeducted);
            $student->violation_count = $violationCount;
            
            // กำหนดระดับความเสี่ยง
            $riskLevelText = '';
            $riskColor = '';
            
            if ($student->monthly_score <= 50 || $violationCount >= 5) {
                $riskLevelText = 'สูงมาก';
                $riskColor = 'danger';
                $student->risk_level = 'very_high';
            } elseif ($student->monthly_score <= 60 || $violationCount >= 4) {
                $riskLevelText = 'สูง';
                $riskColor = 'danger';
                $student->risk_level = 'high';
            } elseif ($student->monthly_score <= 75 || $violationCount >= 3) {
                $riskLevelText = 'ปานกลาง';
                $riskColor = 'warning';
                $student->risk_level = 'medium';
            } elseif ($student->monthly_score <= 85 || $violationCount >= 2) {
                $riskLevelText = 'ต่ำ';
                $riskColor = 'info';
                $student->risk_level = 'low';
            }
            
            $student->risk_level_text = $riskLevelText;
            $student->risk_color = $riskColor;
            
            // เก็บเฉพาะนักเรียนที่มีความเสี่ยง (มีการกระทำผิดหรือคะแนนต่ำ)
            if ($violationCount > 0 || $student->monthly_score < 90) {
                // กรองตามระดับความเสี่ยงที่เลือก
                if ($riskLevel == 'all' || 
                    ($riskLevel == 'high' && in_array($student->risk_level, ['very_high', 'high'])) ||
                    ($riskLevel == 'medium' && $student->risk_level == 'medium') ||
                    ($riskLevel == 'low' && $student->risk_level == 'low')) {
                    $riskStudents->push($student);
                }
            }
        }
        
        // เรียงลำดับตามระดับความเสี่ยงและคะแนน (ความเสี่ยงสูงสุดก่อน)
        $riskStudents = $riskStudents->sortBy([
            ['risk_level', 'asc'],
            ['monthly_score', 'asc'],
            ['violation_count', 'desc']
        ]);
        
        // สร้างข้อมูลสำหรับรายงาน
        $data = [
            'title' => 'รายงานสรุปนักเรียนที่มีความเสี่ยง',
            'month' => $month,
            'year' => $year,
            'monthName' => Carbon::createFromDate($year, $month, 1)->locale('th')->translatedFormat('F'),
            'classroom' => $classroom,
            'students' => $riskStudents,
            'totalStudents' => $students->count(),
            'riskLevel' => $riskLevel,
            'riskLevelText' => $this->getRiskLevelText($riskLevel),
            'generatedAt' => Carbon::now(),
            'reportPeriod' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
            'summary' => [
                'very_high' => $riskStudents->where('risk_level', 'very_high')->count(),
                'high' => $riskStudents->where('risk_level', 'high')->count(),
                'medium' => $riskStudents->where('risk_level', 'medium')->count(),
                'low' => $riskStudents->where('risk_level', 'low')->count(),
            ]
        ];
        
        // กำหนดชื่อไฟล์
        $fileName = 'รายงานนักเรียนความเสี่ยง_' . 
                    $data['monthName'] . 
                    ($classroom ? '_' . $classroom->classes_level . '-' . $classroom->classes_room_number : '') . 
                    '.pdf';
        
        // สร้าง PDF ด้วย mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 
            'format' => 'A4', 
            'orientation' => 'P',
            'default_font' => 'thsarabun',
            'default_font_size' => 14,
            'tempDir' => storage_path('app/public/temp'),
        ]);

        // ตั้งค่าเพิ่มเติมสำหรับ mPDF
        $mpdf->useAdobeCJK = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        // สร้าง HTML จาก view
        $html = view('reports.risk_students_report', $data)->render();
        
        // เขียน HTML ลงใน PDF
        $mpdf->WriteHTML($html);
        
        // ส่งไฟล์ PDF กลับไป
        return response($mpdf->Output($fileName, \Mpdf\Output\Destination::DOWNLOAD))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    /**
     * ส่งออกข้อมูลพฤติกรรมทั้งหมดเป็น PDF
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function allBehaviorDataReport(Request $request)
    {
        // รับพารามิเตอร์จาก request
        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);
        $classId = $request->input('class_id');
        
        // สร้างวันแรกและวันสุดท้ายของเดือน
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        
        // Query ข้อมูลห้องเรียน
        $classroom = null;
        if ($classId) {
            $classroom = Classroom::with('teacher.user')->find($classId);
        }
        
        // Query ข้อมูลนักเรียนและพฤติกรรม
        $query = Student::with(['user', 'classroom', 'behaviorReports' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('reports_report_date', [$startDate, $endDate])
                  ->with(['violation', 'teacher.user']);
        }]);
        
        if ($classId) {
            $query->where('class_id', $classId);
        }
        
        $students = $query->get();
        
        // เรียงลำดับนักเรียนตามชั้นเรียนและรหัสนักเรียน
        $students = $students->sortBy(function($student) {
            return [$student->classroom ? $student->classroom->classes_level : 'zzz',
                    $student->classroom ? $student->classroom->classes_room_number : 'zzz',
                    $student->students_student_code];
        });
        
        // เก็บข้อมูลรายงานพฤติกรรมทั้งหมด
        $allReports = collect();
        $totalReports = 0;
        $totalPoints = 0;
        $categoryStats = [
            'light' => 0,
            'medium' => 0,
            'severe' => 0
        ];
        
        // วนลูปเพื่อเก็บข้อมูลและคำนวณคะแนน
        foreach ($students as $student) {
            $totalPointsDeducted = 0;
            
            foreach ($student->behaviorReports as $report) {
                $points = $report->violation ? $report->violation->violations_points_deducted : 0;
                $category = $report->violation ? $report->violation->violations_category : 'light';
                
                $totalPointsDeducted += $points;
                $totalPoints += $points;
                $totalReports++;
                
                // นับสถิติตามประเภท
                if (isset($categoryStats[$category])) {
                    $categoryStats[$category]++;
                }
                
                // เพิ่มข้อมูลรายงานในรายการ
                $allReports->push([
                    'student' => $student,
                    'report' => $report,
                    'points' => $points,
                    'category' => $category
                ]);
            }
            
            $student->monthly_deducted_points = $totalPointsDeducted;
            $student->monthly_score = max(0, 100 - $totalPointsDeducted);
        }
        
        // เรียงลำดับรายงานตามวันที่
        $allReports = $allReports->sortByDesc(function($item) {
            return $item['report']->reports_report_date;
        });
        
        // สร้างข้อมูลสำหรับรายงาน
        $data = [
            'title' => 'รายงานข้อมูลพฤติกรรมนักเรียนทั้งหมด',
            'month' => $month,
            'year' => $year,
            'monthName' => Carbon::createFromDate($year, $month, 1)->locale('th')->translatedFormat('F'),
            'classroom' => $classroom,
            'students' => $students,
            'allReports' => $allReports,
            'totalReports' => $totalReports,
            'totalPoints' => $totalPoints,
            'categoryStats' => $categoryStats,
            'generatedAt' => Carbon::now(),
            'reportPeriod' => $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y'),
        ];
        
        // กำหนดชื่อไฟล์
        $fileName = 'รายงานข้อมูลพฤติกรรมทั้งหมด_' . 
                    $data['monthName'] . 
                    ($classroom ? '_' . $classroom->classes_level . '-' . $classroom->classes_room_number : '') . 
                    '.pdf';
        
        // สร้าง PDF ด้วย mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8', 
            'format' => 'A4', 
            'orientation' => 'P',
            'default_font' => 'thsarabun',
            'default_font_size' => 14,
            'tempDir' => storage_path('app/public/temp'),
        ]);

        // ตั้งค่าเพิ่มเติมสำหรับ mPDF
        $mpdf->useAdobeCJK = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        // สร้าง HTML จาก view
        $html = view('reports.all_behavior_data_report', $data)->render();
        
        // เขียน HTML ลงใน PDF
        $mpdf->WriteHTML($html);
        
        // ส่งไฟล์ PDF กลับไป
        return response($mpdf->Output($fileName, \Mpdf\Output\Destination::DOWNLOAD))
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'"');
    }

    /**
     * แปลงระดับความเสี่ยงเป็นข้อความ
     */
    private function getRiskLevelText($riskLevel)
    {
        switch ($riskLevel) {
            case 'high':
                return 'ความเสี่ยงสูง';
            case 'medium':
                return 'ความเสี่ยงปานกลาง';
            case 'low':
                return 'ความเสี่ยงต่ำ';
            default:
                return 'ทุกระดับ';
        }
    }

    /**
     * คืนค่าเดือนที่มีรายงานพฤติกรรมภายในปีการศึกษาปัจจุบัน
     */
    public function availableMonths(Request $request, AcademicYearService $academicYearService)
    {
        try {
            $academicYearBE = $academicYearService->getCurrentAcademicYear();
            $academicYearAD = $academicYearBE - 543; // เก็บเป็น ค.ศ. สำหรับ Carbon

            $semester1 = config('academic.semester_periods.1');
            $semester2 = config('academic.semester_periods.2');

            // วันที่เริ่มปีการศึกษา (ภาคเรียน 1)
            $startDate = Carbon::createFromDate(
                $academicYearAD,
                $semester1['start_month'],
                $semester1['start_day']
            )->startOfDay();

            // วันที่สิ้นสุดปีการศึกษา (สิ้นสุดภาคเรียน 2)
            $endDate = Carbon::createFromDate(
                $academicYearAD,
                $semester2['end_month'],
                $semester2['end_day']
            )->endOfDay();

            // หากเดือนสิ้นสุดอยู่ก่อนเดือนเริ่ม ให้ขยับไปปีถัดไป (ครอบคลุมปีการศึกษาที่กิน 2 ปี)
            if ($endDate->lt($startDate)) {
                $endDate->addYear();
            }

            $availableMonths = BehaviorReport::query()
                ->selectRaw('YEAR(reports_report_date) as year, MONTH(reports_report_date) as month, COUNT(*) as total_reports')
                ->whereBetween('reports_report_date', [$startDate, $endDate])
                ->groupBy('year', 'month')
                ->orderByDesc('year')
                ->orderByDesc('month')
                ->get()
                ->map(function ($row) {
                    return [
                        'year' => (int) $row->year,
                        'month' => (int) $row->month,
                        'total_reports' => (int) $row->total_reports,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'ดึงข้อมูลเดือนที่มีรายงานสำเร็จ',
                'data' => [
                    'academic_year_be' => $academicYearBE,
                    'academic_year_ad' => $academicYearAD,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'months' => $availableMonths,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถดึงข้อมูลเดือนที่มีรายงานได้',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}