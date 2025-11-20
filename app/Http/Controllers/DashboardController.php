<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\BehaviorReport;
use App\Models\Violation;
use App\Models\Student;
use App\Models\ClassRoom;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Guardian;
use DateTime;
use DateInterval;

class DashboardController extends Controller
{
    /**
     * อ่าน Laravel log (tail) และส่งกลับในรูปแบบ JSON
     * Query params: lines (จำนวนบรรทัดล่าสุดที่ต้องการ, ค่าเริ่มต้น 500, สูงสุด 2000)
     */
    public function getLaravelLog(Request $request)
    {
        try {
            $path = storage_path('logs/laravel.log');

            if (!file_exists($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบไฟล์ log'
                ], 200);
            }

            if (!is_readable($path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถอ่านไฟล์ log ได้'
                ], 200);
            }

            $maxLines = 2000;
            $defaultLines = 500;
            $lines = (int) $request->query('lines', $defaultLines);
            if ($lines <= 0) { $lines = $defaultLines; }
            if ($lines > $maxLines) { $lines = $maxLines; }

            [$content, $linesShown] = $this->tailFile($path, $lines);
            $fileSize = @filesize($path) ?: 0;

            return response()->json([
                'success' => true,
                'content' => $content,
                'file_size' => $fileSize,
                'lines_shown' => $linesShown,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error reading laravel.log: '.$e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดระหว่างอ่านไฟล์ log'
            ], 200);
        }
    }

    /**
     * อ่าน N บรรทัดสุดท้ายของไฟล์แบบมีประสิทธิภาพ
     * @return array{0:string,1:int} [content, linesShown]
     */
    private function tailFile(string $filename, int $lines): array
    {
        $f = @fopen($filename, 'rb');
        if ($f === false) {
            return ['', 0];
        }

        $buffer = '';
        $chunkSize = 4096;
        $pos = -1;
        $lineCount = 0;

        fseek($f, 0, SEEK_END);
        $fileSize = ftell($f);
        $cursor = $fileSize;

        while ($cursor > 0 && $lineCount <= $lines) {
            $readSize = ($cursor - $chunkSize) >= 0 ? $chunkSize : $cursor;
            $cursor -= $readSize;
            fseek($f, $cursor);
            $chunk = fread($f, $readSize);
            $buffer = $chunk . $buffer;
            // นับจำนวนบรรทัดอย่างเร็ว
            $lineCount = substr_count($buffer, "\n");
        }

        fclose($f);

        // แยกบรรทัดและตัดเฉพาะท้ายสุดตามจำนวนที่ขอ
        $linesArr = preg_split("/\r?\n/", rtrim($buffer, "\r\n"));
        if ($linesArr === false) { $linesArr = []; }
        $linesShown = 0;
        if (!empty($linesArr)) {
            $linesArr = array_slice($linesArr, -$lines);
            $linesShown = count($linesArr);
        }

        return [implode("\n", $linesArr), $linesShown];
    }

    /**
     * ดึงข้อมูลแนวโน้มการบันทึกพฤติกรรมประจำเดือน
     *
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * ดึงข้อมูลแนวโน้มการบันทึกพฤติกรรมประจำเดือน
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyTrends()
    {
        try {
            $user = Auth::user();
            $currentMonth = Carbon::now()->startOfMonth();
            $daysInMonth = Carbon::now()->daysInMonth;

            // เตรียมข้อมูลสำหรับทุกวันในเดือน (เริ่มจาก 1 ถึงวันสุดท้ายของเดือน)
            $dailyCountsByDay = [];
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $dailyCountsByDay[$i] = 0;
            }

            // ตรวจสอบว่ามีตาราง BehaviorReport หรือไม่
            if (!class_exists('App\Models\BehaviorReport')) {
                 return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบ Model BehaviorReport'
                ], 500);
            }

            // ตรวจสอบว่ามีตาราง tb_behavior_reports หรือไม่
            $reports = DB::table('tb_behavior_reports')
                ->join('tb_students as s', 'tb_behavior_reports.student_id', '=', 's.students_id')
                ->leftJoin('tb_classes as c', 's.class_id', '=', 'c.classes_id')
                ->whereMonth('tb_behavior_reports.reports_report_date', $currentMonth->month)
                ->whereYear('tb_behavior_reports.reports_report_date', $currentMonth->year)
                ->selectRaw('DAY(tb_behavior_reports.reports_report_date) as day, COUNT(*) as count')
                ->groupBy(DB::raw('DAY(tb_behavior_reports.reports_report_date)'))
                ->get();

            // เติมข้อมูลจำนวนรายงานต่อวัน
            foreach ($reports as $report) {
                $dailyCountsByDay[$report->day] = $report->count;
            }

            // ข้อมูลสำหรับกราฟ
            $data = [
                'labels' => array_keys($dailyCountsByDay),
                'datasets' => [
                    [
                        'label' => 'พฤติกรรมที่ถูกบันทึก',
                        'data' => array_values($dailyCountsByDay),
                        'borderColor' => 'rgb(16, 32, 173)',
                        'backgroundColor' => 'rgba(16, 32, 173, 0.1)',
                        'tension' => 0.4,
                        'fill' => true
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'ดึงข้อมูลแนวโน้มประจำเดือนสำเร็จ'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching monthly trends: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลแนวโน้มประจำเดือน',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงข้อมูลประเภทการกระทำผิด
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getViolationTypes()
    {
        try {
            $user = Auth::user();
            $currentMonth = Carbon::now()->startOfMonth();

            $violationStats = DB::table('tb_violations as v')
                ->leftJoin('tb_behavior_reports as br', 'v.violations_id', '=', 'br.violation_id')
                ->leftJoin('tb_students as s', 'br.student_id', '=', 's.students_id')
                ->leftJoin('tb_classes as c', 's.class_id', '=', 'c.classes_id')
                ->whereMonth('br.reports_report_date', $currentMonth->month)
                ->whereYear('br.reports_report_date', $currentMonth->year)
                ->select('v.violations_id', 'v.violations_name as name', 
                            DB::raw('COUNT(br.reports_id) as count'),
                            'v.violations_category as category')
                ->groupBy('v.violations_id', 'v.violations_name', 'v.violations_category')
                ->having('count', '>', 0)
                ->orderByDesc('count')
                ->limit(5)
                ->get();

            // สร้างสีสำหรับแต่ละประเภทพฤติกรรม
            $colors = [
                'severe' => '#dc3545', // สีแดงสำหรับความผิดร้ายแรง
                'medium' => '#ffc107', // สีเหลืองสำหรับความผิดปานกลาง
                'light' => '#17a2b8',  // สีฟ้าสำหรับความผิดเล็กน้อย
            ];

            $defaultColors = ['#dc3545', '#ffc107', '#17a2b8', '#fd7e14', '#6c757d'];

            $labels = [];
            $data = [];
            $backgroundColor = [];

            foreach ($violationStats as $index => $violation) {
                $labels[] = $violation->name;
                $data[] = $violation->count;
                
                // กำหนดสีตามประเภทความรุนแรง หรือใช้สีเริ่มต้นถ้าไม่พบ
                if (isset($colors[$violation->category])) {
                    $backgroundColor[] = $colors[$violation->category];
                } else {
                    $backgroundColor[] = $defaultColors[$index % count($defaultColors)];
                }
            }

            // ข้อมูลสำหรับกราฟ
            $chartData = [
                'labels' => $labels,
                'datasets' => [
                    [
                        'data' => $data,
                        'backgroundColor' => $backgroundColor,
                        'borderWidth' => 0
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $chartData,
                'message' => 'ดึงข้อมูลประเภทพฤติกรรมสำเร็จ'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching violation types: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลประเภทพฤติกรรม',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงข้อมูลสถิติประจำเดือน
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMonthlyStats()
    {
        try {
            $user = Auth::user();
            $currentMonth = Carbon::now()->startOfMonth();
            $previousMonth = Carbon::now()->subMonth()->startOfMonth();

            // ดึงข้อมูลเดือนปัจจุบัน
            $currentViolationCount = DB::table('tb_behavior_reports')
                ->whereMonth('reports_report_date', $currentMonth->month)
                ->whereYear('reports_report_date', $currentMonth->year)
                ->count();

            $currentStudentsCount = DB::table('tb_behavior_reports')
                ->distinct('student_id')
                ->whereMonth('reports_report_date', $currentMonth->month)
                ->whereYear('reports_report_date', $currentMonth->year)
                ->count('student_id');

            $currentSevereCount = DB::table('tb_behavior_reports as br')
                ->join('tb_violations as v', 'br.violation_id', '=', 'v.violations_id')
                ->where('v.violations_category', 'severe')
                ->whereMonth('br.reports_report_date', $currentMonth->month)
                ->whereYear('br.reports_report_date', $currentMonth->year)
                ->count();

            $avgScore = DB::table('tb_students')->avg('students_current_score') ?? 100;

            // ดึงข้อมูลเดือนก่อน
            $previousViolationCount = DB::table('tb_behavior_reports')
                ->whereMonth('reports_report_date', $previousMonth->month)
                ->whereYear('reports_report_date', $previousMonth->year)
                ->count();

            $previousStudentsCount = DB::table('tb_behavior_reports')
                ->distinct('student_id')
                ->whereMonth('reports_report_date', $previousMonth->month)
                ->whereYear('reports_report_date', $previousMonth->year)
                ->count('student_id');

            $previousSevereCount = DB::table('tb_behavior_reports as br')
                ->join('tb_violations as v', 'br.violation_id', '=', 'v.violations_id')
                ->where('v.violations_category', 'severe')
                ->whereMonth('br.reports_report_date', $previousMonth->month)
                ->whereYear('br.reports_report_date', $previousMonth->year)
                ->count();

            $previousAvgScore = 100; // ค่าเริ่มต้น

            // คำนวณการเปลี่ยนแปลง (%)
            $calculateTrend = function($current, $previous) {
                if ($previous == 0) return $current > 0 ? 100 : 0;
                return round((($current - $previous) / $previous) * 100, 1);
            };

            $stats = [
                'violation_count' => $currentViolationCount,
                'students_count' => $currentStudentsCount,
                'severe_count' => $currentSevereCount,
                'avg_score' => round($avgScore, 1),

                'violation_trend' => $calculateTrend($currentViolationCount, $previousViolationCount),
                'students_trend' => $calculateTrend($currentStudentsCount, $previousStudentsCount),
                'severe_trend' => $calculateTrend($currentSevereCount, $previousSevereCount),
                'score_trend' => round($avgScore - $previousAvgScore, 1)
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'ดึงข้อมูลสถิติประจำเดือนสำเร็จ'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching monthly stats: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติประจำเดือน',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * พรีวิวข้อมูลจากไฟล์ Excel/CSV และคืนผลตรวจสอบ
     */
    public function previewExcelImport(Request $request)
    {
        try {
            if (!auth()->check() || !in_array(auth()->user()->users_role, ['admin', 'teacher'])) {
                return response()->json(['success' => false, 'error' => 'ไม่ได้รับอนุญาต'], 403);
            }

            // ถ้าเป็นการส่งข้อมูล preview_data มาตรวจสอบ (สำหรับตรวจสอบข้อมูลซ้ำ)
            if ($request->has('preview_data')) {
                $sheetType = $request->input('sheet_type', 'student');
                $previewData = $request->input('preview_data', []);

                if (empty($previewData)) {
                    return response()->json(['success' => false, 'error' => 'ไม่มีข้อมูลสำหรับตรวจสอบ'], 422);
                }

                // ตรวจสอบและแยกข้อมูลซ้ำ
                $validData = [];
                $duplicateData = [];
                $errorData = [];

                foreach ($previewData as $item) {
                    $validation = $this->validateRowData($item['data'], $item['row_number']);
                    
                    if (!empty($validation['errors'])) {
                        $errorData[] = array_merge($item, ['errors' => $validation['errors']]);
                    } elseif ($validation['is_duplicate']) {
                        $duplicateData[] = array_merge($item, ['duplicate_fields' => $validation['duplicate_fields']]);
                    } else {
                        $validData[] = $item;
                    }
                }

                return response()->json([
                    'success' => true,
                    'valid_data' => $validData,
                    'duplicate_data' => $duplicateData,
                    'error_data' => $errorData,
                    'total_rows' => count($previewData)
                ]);
            }

            // ถ้าเป็นการอัปโหลดไฟล์ (เดิม)
            $request->validate([
                'file' => 'required|file|mimes:xlsx,csv,txt|max:20480', // สูงสุด ~20MB
                'sheet' => 'nullable|in:students,teachers,guardians'
            ]);

            $sheetType = $request->input('sheet', 'students');

            $uploaded = $request->file('file');
            $path = $uploaded->getRealPath();
            $ext = strtolower($uploaded->getClientOriginalExtension());

            $rawData = [];
            if (in_array($ext, ['csv', 'txt'])) {
                $handle = fopen($path, 'r');
                if ($handle === false) { throw new \Exception('ไม่สามารถเปิดไฟล์ได้'); }
                $rowIndex = 0;
                while (($data = fgetcsv($handle)) !== false) {
                    // Normalize encoding and trim
                    $row = [];
                    foreach ($data as $cell) {
                        if (is_string($cell)) { $cell = trim($cell); }
                        $row[] = $cell;
                    }
                    // Skip completely empty rows
                    $allEmpty = true; foreach ($row as $c) { if ($c !== null && $c !== '') { $allEmpty = false; break; } }
                    if (!$allEmpty) { $rawData[] = $row; }
                    $rowIndex++;
                    if ($rowIndex > 5000) { break; } // safety limit
                }
                fclose($handle);
            } else {
                return response()->json(['success' => false, 'error' => 'ขณะนี้รองรับเฉพาะไฟล์ .csv หรือ .txt เท่านั้น กรุณาแปลงไฟล์ Excel เป็น CSV ก่อนนำเข้า'], 422);
            }

            if (count($rawData) < 2) {
                return response()->json(['success' => false, 'error' => 'ไฟล์ต้องมีอย่างน้อย 2 แถว (หัวตารางและข้อมูล)'], 422);
            }

            $processed = $this->processAndValidateData($rawData, $sheetType);

            return response()->json([
                'success' => true,
                'data' => $processed,
                'total_rows' => count($processed['valid_data']) + count($processed['duplicate_data']) + count($processed['error_data'])
            ]);
        } catch (\Throwable $e) {
            Log::error('Excel preview error: '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'เกิดข้อผิดพลาด: '.$e->getMessage()], 500);
        }
    }

    /**
     * นำเข้าข้อมูลที่เลือกจากพรีวิว
     */
    public function importExcel(Request $request)
    {
        try {
            if (!auth()->check() || !in_array(auth()->user()->users_role, ['admin', 'teacher'])) {
                return response()->json(['success' => false, 'error' => 'ไม่ได้รับอนุญาต'], 403);
            }

            $selectedData = $request->input('selected_data', []);
            if (empty($selectedData) || !is_array($selectedData)) {
                return response()->json(['success' => false, 'error' => 'ไม่มีข้อมูลที่เลือกสำหรับการนำเข้า'], 422);
            }

            $results = $this->importDataToDatabase($selectedData);
            // ปรับ error message กรณี duplicate entry
            if (!empty($results['errors'])) {
                $results['errors'] = array_map(function($msg) {
                    if (strpos($msg, '1062 Duplicate entry') !== false) {
                        // พยายามจับ email หรือ field ที่ซ้ำ
                        if (preg_match("/'([^']+)' for key '([^']+)'/", $msg, $m)) {
                            $value = $m[1];
                            $key = $m[2];
                            $field = '';
                            if (strpos($key, 'email') !== false) $field = 'อีเมล';
                            elseif (strpos($key, 'student_code') !== false || strpos($key, 'student_id') !== false) $field = 'รหัสนักเรียน';
                            elseif (strpos($key, 'teacher_id') !== false) $field = 'รหัสครู';
                            else $field = $key;
                            return "ข้อมูลซ้ำ: {$field} '{$value}' ถูกใช้ไปแล้วในระบบ กรุณาตรวจสอบข้อมูลก่อนนำเข้า";
                        }
                        return 'ข้อมูลซ้ำ: พบข้อมูลที่มีอยู่แล้วในระบบ กรุณาตรวจสอบข้อมูลก่อนนำเข้า';
                    }
                    return $msg;
                }, $results['errors']);
            }
            return response()->json(['success' => true, 'results' => $results]);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (strpos($msg, '1062 Duplicate entry') !== false) {
                if (preg_match("/'([^']+)' for key '([^']+)'/", $msg, $m)) {
                    $value = $m[1];
                    $key = $m[2];
                    $field = '';
                    if (strpos($key, 'email') !== false) $field = 'อีเมล';
                    elseif (strpos($key, 'student_code') !== false || strpos($key, 'student_id') !== false) $field = 'รหัสนักเรียน';
                    elseif (strpos($key, 'teacher_id') !== false) $field = 'รหัสครู';
                    else $field = $key;
                    $msg = "ข้อมูลซ้ำ: {$field} '{$value}' ถูกใช้ไปแล้วในระบบ กรุณาตรวจสอบข้อมูลก่อนนำเข้า";
                } else {
                    $msg = 'ข้อมูลซ้ำ: พบข้อมูลที่มีอยู่แล้วในระบบ กรุณาตรวจสอบข้อมูลก่อนนำเข้า';
                }
            }
            Log::error('Excel import error: '.$e->getMessage());
            return response()->json(['success' => false, 'error' => 'เกิดข้อผิดพลาด: '.$msg], 500);
        }
    }


    private function getColumnMapping($sheetType)
    {
        $baseMapping = [
            'first_name' => ['ชื่อจริง', 'ชื่อ', 'first_name', 'firstname', 'fname', 'f_name', 'name'],
            'last_name' => ['นามสกุล', 'last_name', 'lastname', 'surname', 'lname', 'l_name'],
            'email' => ['อีเมลล์', 'อีเมล', 'email', 'e-mail', 'mail'],
            'phone' => ['โทร', 'เบอร์โทรศัพท์', 'เบอร์โทร', 'เบอร์', 'phone', 'telephone', 'mobile']
        ];

        switch ($sheetType) {
            case 'students':
                return array_merge($baseMapping, [
                    'student_id' => ['รหัสนักเรียน', 'student_id', 'รหัส', 'id', 'รหัสประจำตัว'],
                    'date_of_birth' => ['วันเกิด', 'date_of_birth', 'birthday', 'birth_date', 'dob'],
                    'gender' => ['เพศ', 'gender', 'sex'],
                    'grade_level' => ['ระดับชั้น', 'ระดับ', 'ชั้น', 'grade', 'level', 'class_level'],
                    'classroom' => ['ห้อง', 'ห้องเรียน', 'classroom', 'class', 'room'],
                    'status' => ['สถานะ', 'status']
                ]);
            case 'teachers':
                return array_merge($baseMapping, [
                    'teacher_id' => ['รหัสครู', 'teacher_id', 'รหัส', 'id', 'รหัสประจำตัว'],
                    'title' => ['คำนำหน้า', 'title', 'prefix', 'คำนำหน้าชื่อ'],
                    'position' => ['ตำแหน่ง', 'position', 'job_title', 'title_position'],
                    'subject_group' => ['กลุ่มสาระการเรียนรู้', 'กลุ่มสาระ', 'subject_group', 'learning_area'],
                    'subjects' => ['วิชาที่สอน', 'วิชา', 'subject', 'subjects', 'teaching_subject']
                ]);
            case 'guardians':
                return array_merge($baseMapping, [
                    'title' => ['คำนำหน้า', 'คำนำหน้าชื่อ', 'title', 'prefix', 'นาย', 'นาง', 'นางสาว'],
                    'guardian_id' => ['รหัสผู้ปกครอง', 'guardian_id', 'รหัส', 'id', 'รหัสประจำตัว'],
                    'relationship' => ['ความสัมพันธ์', 'relationship', 'relation'],
                    'line_id' => ['ไอดีไลน์', 'line_id', 'line', 'ไลน์', 'ID Line'],
                    'contact_method' => ['ช่องทางติดต่อที่ใช้บ่อยที่สุด', 'ช่องทางติดต่อที่ง่ายที่สุด', 'contact_method', 'preferred_contact', 'ช่องทางติดต่อ'],
                    // ใส่คำที่เฉพาะเจาะจงก่อน เพื่อไม่ให้สับสนกับ student_id ของนักเรียน
                    'student_codes' => ['รหัสนักเรียนที่ดูแล', 'รหัสนักเรียนภายใต้ความดูแล', 'student_codes', 'รหัสลูก', 'รหัสบุตร', 'รหัสบุตรหลาน']
                ]);
            default:
                return $baseMapping;
        }
    }

    private function getRequiredFields($sheetType)
    {
        switch ($sheetType) {
            case 'students':
            case 'teachers':
            case 'guardians':
                return ['first_name', 'last_name'];
            default:
                return ['first_name', 'last_name'];
        }
    }

    private function fillMissingData($rowData, $sheetType, $index)
    {
        switch ($sheetType) {
            case 'students':
                if (!isset($rowData['role']) || empty($rowData['role'])) { $rowData['role'] = 'student'; }
                if (!isset($rowData['status']) || empty($rowData['status'])) { $rowData['status'] = 'active'; }

                // Normalize DOB (support Thai Buddhist years) and compute age
                $birthYmd = null; $age = null;
                if (!empty($rowData['date_of_birth'])) {
                    $birthYmd = $this->normalizeThaiBirthDate($rowData['date_of_birth']);
                    if ($birthYmd) { 
                        $rowData['date_of_birth'] = $birthYmd; // อัปเดตค่าที่แปลงแล้วกลับไปใน rowData
                        try { $age = (new \DateTime())->diff(new \DateTime($birthYmd))->y; } catch (\Exception $e) { $age = null; } 
                    } else {
                        $rowData['date_of_birth'] = null; // ถ้าแปลงไม่ได้ให้เป็น null
                    }
                }
                // Normalize gender tokens
                if (isset($rowData['gender'])) {
                    $g = strtolower(trim($rowData['gender']));
                    $map = ['male'=>'male','m'=>'male','ชาย'=>'male','เพศชาย'=>'male','female'=>'female','f'=>'female','หญิง'=>'female','เพศหญิง'=>'female'];
                    $rowData['gender'] = $map[$g] ?? $rowData['gender'];
                }
                // Set title from age/gender when missing or empty
                if (!isset($rowData['title']) || empty($rowData['title'])) {
                    $rowData['title'] = $this->choosePrefixByAgeGender($rowData['gender'] ?? null, $birthYmd, /*adultAt15*/ true);
                }
                if (isset($rowData['student_id']) && !empty($rowData['student_id'])) {
                    $studentCode = (string) $rowData['student_id'];
                    if (strlen($studentCode) >= 2) { $yearCode = substr($studentCode, 0, 2); $rowData['academic_year'] = '25' . $yearCode; }
                    else { $rowData['academic_year'] = date('Y'); }
                } else { $rowData['academic_year'] = date('Y'); }
                if (!isset($rowData['email']) || empty($rowData['email'])) {
                    if (!empty($rowData['student_id'])) { $rowData['email'] = $rowData['student_id'] . '@student.school.ac.th'; }
                    else {
                        $firstName = $rowData['first_name'] ?? ''; $lastName = $rowData['last_name'] ?? '';
                        if (!empty($firstName) && !empty($lastName)) {
                            $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . $lastName));
                            $rowData['email'] = $email . ($index + 2) . '@student.school.ac.th';
                        }
                    }
                }
                break;

            case 'teachers':
                if (!isset($rowData['status']) || empty($rowData['status'])) { $rowData['status'] = 'active'; }
                if (!isset($rowData['role']) || empty($rowData['role'])) { $rowData['role'] = 'teacher'; }
                if (!isset($rowData['title']) || empty($rowData['title'])) { $rowData['title'] = 'นาย'; }
                else { $title = trim($rowData['title']); if (in_array($title, ['นาย', 'Mr.', 'Mr', 'มร.'])) { $rowData['title'] = 'นาย'; } elseif (in_array($title, ['นางสาว', 'Ms.', 'Ms', 'Miss', 'นส.'])) { $rowData['title'] = 'นางสาว'; } else { $rowData['title'] = 'นาย'; } }
                if (!isset($rowData['email']) || empty($rowData['email'])) {
                    if (!empty($rowData['teacher_id'])) { $rowData['email'] = strtolower($rowData['teacher_id']) . '@teacher.school.ac.th'; }
                    else { $firstName = $rowData['first_name'] ?? ''; $lastName = $rowData['last_name'] ?? ''; if (!empty($firstName) && !empty($lastName)) { $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . $lastName)); $rowData['email'] = $email . ($index + 2) . '@teacher.school.ac.th'; } }
                }
                if (!isset($rowData['position']) || empty($rowData['position'])) { $rowData['position'] = 'ครู'; }
                if (!isset($rowData['subject_group'])) { $rowData['subject_group'] = null; }
                if (!isset($rowData['subjects'])) { $rowData['subjects'] = null; }
                break;

            case 'guardians':
                if (!isset($rowData['role']) || empty($rowData['role'])) { $rowData['role'] = 'guardian'; }
                if (!isset($rowData['email']) || empty($rowData['email'])) {
                    $firstName = $rowData['first_name'] ?? ''; $lastName = $rowData['last_name'] ?? '';
                    if (!empty($firstName) && !empty($lastName)) { $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName . $lastName)); $rowData['email'] = $email . ($index + 2) . '@guardian.school.ac.th'; }
                }
                // Fallback title for guardians when not provided (map by relationship if possible)
                if (!isset($rowData['title']) || empty($rowData['title'])) {
                    $rel = isset($rowData['relationship']) ? mb_strtolower(trim($rowData['relationship'])) : '';
                    // Common Thai labels for mother vs father
                    $femaleRels = ['มารดา','แม่','female','หญิง','ผู้ปกครองหญิง'];
                    $rowData['title'] = in_array($rel, $femaleRels, true) ? 'นาง' : 'นาย';
                }
                $rowData['date_of_birth'] = null;
                if (!isset($rowData['phone']) || empty($rowData['phone'])) { $rowData['phone'] = null; }
                if (!isset($rowData['line_id']) || empty($rowData['line_id'])) { $rowData['line_id'] = null; }
                // Normalize preferred contact method (support Thai synonyms)
                if (!isset($rowData['contact_method']) || empty($rowData['contact_method'])) { $rowData['contact_method'] = 'phone'; }
                else {
                    $rawMethod = mb_strtolower(trim($rowData['contact_method']));
                    $mapping = [
                        'โทรศัพท์' => 'phone', 'เบอร์' => 'phone', 'เบอร์โทร' => 'phone', 'phone' => 'phone', 'tel' => 'phone', 'telephone' => 'phone', 'สายตรง' => 'phone',
                        'อีเมล' => 'email', 'เมล' => 'email', 'email' => 'email', 'e-mail' => 'email',
                        'ไลน์' => 'line', 'line' => 'line', 'lineid' => 'line', 'line id' => 'line', 'id line' => 'line', 'ไอดีไลน์' => 'line'
                    ];
                    if (isset($mapping[$rawMethod])) { $rowData['contact_method'] = $mapping[$rawMethod]; }
                    else { $valid = ['phone','email','line']; $rowData['contact_method'] = in_array($rawMethod,$valid) ? $rawMethod : 'phone'; }
                }
                // Clean and normalize list of student codes (allow comma/semicolon separated)
                if (isset($rowData['student_codes']) && !empty($rowData['student_codes'])) {
                    Log::info('Raw student_codes before processing', [
                        'raw_value' => $rowData['student_codes'],
                        'type' => gettype($rowData['student_codes'])
                    ]);
                    
                    $codes = preg_split('/[;,，]+/', $rowData['student_codes']);
                    $codes = array_unique(array_filter(array_map(function($c){ return trim(preg_replace('/[^0-9]/','',$c)); }, $codes)));
                    $rowData['student_codes'] = implode(',', $codes);
                    
                    Log::info('Processed student_codes', [
                        'original' => $rowData['student_codes'],
                        'split_codes' => $codes,
                        'final' => $rowData['student_codes']
                    ]);
                }
                break;
        }

        return $rowData;
    }

    private function processAndValidateData($rawData, $sheetType = 'students')
    {
        if (empty($rawData)) {
            return ['valid_data' => [], 'duplicate_data' => [], 'error_data' => []];
        }

        $headers = $rawData[0];
        $dataRows = array_slice($rawData, 1);

        $columnMapping = $this->getColumnMapping($sheetType);

        // Log original headers for debugging
        Log::info('Processing sheet data', [
            'sheet_type' => $sheetType,
            'original_headers' => $headers,
            'header_count' => count($headers)
        ]);

        $mappedHeaders = [];
        foreach ($columnMapping as $standardName => $possibleNames) {
            $found = false;
            
            // First pass: exact match only
            foreach ($headers as $header) {
                $normalizedHeader = strtolower(trim($header));
                foreach ($possibleNames as $possibleName) {
                    if (strtolower($possibleName) === $normalizedHeader) {
                        $mappedHeaders[$standardName] = $header; 
                        $found = true; 
                        break 2;
                    }
                }
            }
            
            // Second pass: partial match (only if exact match not found)
            if (!$found) {
                foreach ($headers as $header) {
                    $normalizedHeader = strtolower(trim($header));
                    foreach ($possibleNames as $possibleName) {
                        if (strpos($normalizedHeader, strtolower($possibleName)) !== false) {
                            $mappedHeaders[$standardName] = $header; 
                            $found = true; 
                            break 2;
                        }
                    }
                }
            }
            
            if (!$found) {
                $idx = array_search($standardName, array_keys($columnMapping));
                if ($idx !== false && isset($headers[$idx])) { $mappedHeaders[$standardName] = $headers[$idx]; }
            }
        }

        $requiredFields = $this->getRequiredFields($sheetType);
        
        // Log mapped headers for debugging
        Log::info('Column mapping result', [
            'sheet_type' => $sheetType,
            'mapped_headers' => $mappedHeaders,
            'has_student_codes' => isset($mappedHeaders['student_codes']),
            'student_codes_header' => $mappedHeaders['student_codes'] ?? 'NOT_MAPPED'
        ]);
        
        $missingFields = [];
        foreach ($requiredFields as $field) { if (!isset($mappedHeaders[$field])) { $missingFields[] = $field; } }
        if (!empty($missingFields)) {
            foreach ($missingFields as $missing) {
                foreach ($headers as $header) { if (!in_array($header, $mappedHeaders)) { $mappedHeaders[$missing] = $header; break; } }
            }
        }
        $finalMissing = [];
        foreach ($requiredFields as $field) { if (!isset($mappedHeaders[$field])) { $finalMissing[] = $field; } }
        if (!empty($finalMissing)) {
            throw new \Exception('ไม่พบคอลัมน์ที่จำเป็น: ' . implode(', ', $finalMissing) . '\nคอลัมน์ที่พบ: ' . implode(', ', $headers));
        }

        $validData = []; $duplicateData = []; $errorData = [];
        foreach ($dataRows as $index => $row) {
            try {
                if (count($row) !== count($headers)) { $row = array_pad(array_slice($row, 0, count($headers)), count($headers), ''); }
                $originalRowData = array_combine($headers, $row);

                $rowData = [];
                foreach ($mappedHeaders as $standardName => $originalHeader) {
                    $value = isset($originalRowData[$originalHeader]) ? trim((string) $originalRowData[$originalHeader]) : '';
                    if ($standardName === 'date_of_birth' && !empty($value)) {
                        if (filter_var($value, FILTER_VALIDATE_EMAIL) || strpos($value, '@') !== false || strlen($value) > 20 || preg_match('/[a-zA-Z]{3,}/', $value)) { 
                            $value = null; 
                        } else {
                            // Log วันเกิดก่อนแปลง (สำหรับ debug)
                            if ($sheetType === 'students' && ($index + 2) <= 5) {
                                Log::info('Date of birth processing', [
                                    'row_number' => $index + 2,
                                    'original_value' => $value,
                                    'is_numeric' => is_numeric($value),
                                    'value_type' => gettype($value)
                                ]);
                            }
                        }
                    }
                    $rowData[$standardName] = $value === '' ? null : $value;
                }
                
                // Log guardian student_codes for debugging
                if ($sheetType === 'guardians' && ($index + 2) <= 5) { // Log first 3 rows only
                    Log::info('Guardian row processing', [
                        'row_number' => $index + 2,
                        'student_codes_raw' => isset($originalRowData[$mappedHeaders['student_codes'] ?? 'N/A']) ? $originalRowData[$mappedHeaders['student_codes'] ?? 'N/A'] : 'NOT_FOUND',
                        'student_codes_mapped' => $rowData['student_codes'] ?? 'null',
                        'all_row_data_keys' => array_keys($rowData)
                    ]);
                }

                $rowData = $this->fillMissingData($rowData, $sheetType, $index);
                
                // Log หลังจาก fillMissingData สำหรับวันเกิด
                if ($sheetType === 'students' && ($index + 2) <= 5 && isset($rowData['date_of_birth'])) {
                    Log::info('Date of birth after normalization', [
                        'row_number' => $index + 2,
                        'normalized_value' => $rowData['date_of_birth']
                    ]);
                }

                $validation = $this->validateRowData($rowData, $index + 2);
                if (!empty($validation['errors'])) {
                    $errorData[] = ['row_number' => $index + 2, 'data' => $rowData, 'original_data' => $originalRowData, 'errors' => $validation['errors']];
                } elseif ($validation['is_duplicate']) {
                    $duplicateData[] = ['row_number' => $index + 2, 'data' => $rowData, 'original_data' => $originalRowData, 'duplicate_fields' => $validation['duplicate_fields']];
                } else {
                    $validData[] = ['row_number' => $index + 2, 'data' => $rowData, 'original_data' => $originalRowData];
                }
            } catch (\Exception $e) {
                $errorData[] = ['row_number' => $index + 2, 'data' => $row, 'errors' => ['เกิดข้อผิดพลาดในการประมวลผล: ' . $e->getMessage()]];
            }
        }

        return ['valid_data' => $validData, 'duplicate_data' => $duplicateData, 'error_data' => $errorData];
    }

    private function validateRowData($data, $rowNumber)
    {
        $errors = []; $isDuplicate = false; $duplicateFields = [];
        if (empty($data['first_name'])) { $errors[] = 'ชื่อเป็นฟิลด์ที่จำเป็น'; }
        if (empty($data['last_name'])) { $errors[] = 'นามสกุลเป็นฟิลด์ที่จำเป็น'; }

        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง'; }
            else { $existingUser = User::where('users_email', $data['email'])->first(); if ($existingUser) { $isDuplicate = true; $duplicateFields[] = 'email'; } }
        }

        $role = isset($data['role']) ? strtolower(trim($data['role'])) : '';
        $roleMapping = [
            'admin' => 'admin', 'administrator' => 'admin', 'ผู้ดูแลระบบ' => 'admin',
            'teacher' => 'teacher', 'ครู' => 'teacher', 'อาจารย์' => 'teacher',
            'student' => 'student', 'นักเรียน' => 'student', 'ผู้เรียน' => 'student',
            'guardian' => 'guardian', 'ผู้ปกครอง' => 'guardian', 'พ่อแม่' => 'guardian', 'parent' => 'guardian'
        ];
        $mappedRole = null; if (!empty($role)) { foreach ($roleMapping as $input => $output) { if (strtolower($input) === $role || strpos($role, strtolower($input)) !== false) { $mappedRole = $output; break; } } }
        if (!$mappedRole) { $errors[] = 'บทบาทไม่ถูกต้อง (ต้องเป็น admin, teacher, student, หรือ guardian)'; }
        else { $data['role'] = $mappedRole; }

        if ($mappedRole === 'student') {
            if (isset($data['student_id']) && !empty($data['student_id'])) { $existingStudent = Student::where('students_student_code', $data['student_id'])->first(); if ($existingStudent) { $isDuplicate = true; $duplicateFields[] = 'student_id'; } }
            if (isset($data['gender']) && !empty($data['gender'])) {
                $gender = strtolower(trim($data['gender']));
                $genderMapping = ['male' => 'male', 'female' => 'female', 'ชาย' => 'male', 'หญิง' => 'female', 'เพศชาย' => 'male', 'เพศหญิง' => 'female', 'm' => 'male', 'f' => 'female'];
                $mappedGender = $genderMapping[$gender] ?? null; if (!$mappedGender) { $errors[] = 'เพศไม่ถูกต้อง (ต้องเป็น ชาย/หญิง หรือ male/female)'; } else { $data['gender'] = $mappedGender; }
            }
            if (isset($data['date_of_birth']) && !empty($data['date_of_birth'])) {
                $originalDate = trim($data['date_of_birth']);
                if (filter_var($originalDate, FILTER_VALIDATE_EMAIL) || strpos($originalDate, '@') !== false || strlen($originalDate) > 20 || preg_match('/[a-zA-Z]{3,}/', $originalDate)) { $data['date_of_birth'] = null; }
                else {
                    $dateFormats = ['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'Y-n-j', 'j/n/Y', 'd/m/y', 'j/n/y']; $validDate = false;
                    foreach ($dateFormats as $format) { try { $dateObj = DateTime::createFromFormat($format, $originalDate); if ($dateObj && $dateObj->format($format) === $originalDate) { $data['date_of_birth'] = $dateObj->format('Y-m-d'); $validDate = true; break; } } catch (\Exception $e) { continue; } }
                    if (!$validDate) { try { if (is_numeric($originalDate) && $originalDate > 0 && $originalDate < 100000) { $excelEpoch = new DateTime('1900-01-01'); $excelEpoch->add(new DateInterval('P' . intval($originalDate - 2) . 'D')); $data['date_of_birth'] = $excelEpoch->format('Y-m-d'); $validDate = true; } } catch (\Exception $e) {}
                    }
                    if (!$validDate) { $data['date_of_birth'] = null; }
                }
            }
            if (isset($data['status']) && !empty($data['status'])) { $validStatuses = ['active', 'suspended', 'expelled', 'graduated', 'transferred']; if (!in_array($data['status'], $validStatuses)) { $data['status'] = 'active'; } }
        } elseif ($mappedRole === 'teacher') {
            if (isset($data['teacher_id']) && !empty($data['teacher_id'])) { $existingTeacher = Teacher::where('teachers_employee_code', $data['teacher_id'])->first(); if ($existingTeacher) { $isDuplicate = true; $duplicateFields[] = 'teacher_id'; } }
        } elseif ($mappedRole === 'guardian') {
            if (isset($data['contact_method']) && !empty($data['contact_method'])) { $validMethods = ['phone', 'email', 'line']; $method = strtolower(trim($data['contact_method'])); if (!in_array($method, $validMethods)) { $data['contact_method'] = 'phone'; } }
            
            // Validate student codes if provided
            if (isset($data['student_codes']) && !empty($data['student_codes'])) {
                Log::info('Validating student_codes for guardian', [
                    'student_codes' => $data['student_codes'],
                    'row_number' => $rowNumber
                ]);
                
                $studentCodesArray = explode(',', $data['student_codes']);
                $validCodes = [];
                $invalidCodes = [];
                
                foreach ($studentCodesArray as $code) {
                    $cleanCode = trim($code);
                    if (!empty($cleanCode)) {
                        $student = Student::where('students_student_code', $cleanCode)->first();
                        if ($student) {
                            $validCodes[] = $cleanCode;
                        } else {
                            $invalidCodes[] = $cleanCode;
                        }
                    }
                }
                
                if (!empty($invalidCodes)) {
                    $errors[] = 'ไม่พบรหัสนักเรียนในระบบ: ' . implode(', ', $invalidCodes);
                }
                
                Log::info('Student codes validation result', [
                    'valid_codes' => $validCodes,
                    'invalid_codes' => $invalidCodes,
                    'row_number' => $rowNumber
                ]);
            }
        }

        if (isset($data['phone']) && !empty($data['phone'])) { $phone = preg_replace('/[^\d]/', '', $data['phone']); if (strlen($phone) < 9 || strlen($phone) > 15) { $errors[] = 'เบอร์โทรศัพท์ต้องมี 9-15 หลัก'; } }
        if (isset($data['line_id']) && !empty($data['line_id']) && strlen($data['line_id']) > 100) { $errors[] = 'Line ID ยาวเกินไป (ไม่เกิน 100 ตัวอักษร)'; }

        return ['errors' => $errors, 'is_duplicate' => $isDuplicate, 'duplicate_fields' => $duplicateFields];
    }

    private function importDataToDatabase($selectedData)
    {
        $successCount = 0; $errorCount = 0; $errors = [];

        // จัดลำดับให้ student มาก่อน guardian เพื่อให้สร้างความสัมพันธ์ได้ (ภายใน transaction มองเห็นได้)
        $rolePriority = ['student' => 1, 'teacher' => 2, 'admin' => 2, 'guardian' => 3];
        usort($selectedData, function($a, $b) use ($rolePriority) {
            $ra = $a['data']['role'] ?? 'z';
            $rb = $b['data']['role'] ?? 'z';
            $pa = $rolePriority[$ra] ?? 99; $pb = $rolePriority[$rb] ?? 99;
            if ($pa === $pb) { return 0; }
            return $pa <=> $pb;
        });

        Log::info('Import ordering summary', [
            'total' => count($selectedData),
            'students' => collect($selectedData)->where('data.role','student')->count(),
            'teachers' => collect($selectedData)->where('data.role','teacher')->count(),
            'guardians' => collect($selectedData)->where('data.role','guardian')->count()
        ]);
        $chunkSize = config('import.google_sheets.chunk_size', 50);
        $chunks = array_chunk($selectedData, $chunkSize);
        foreach ($chunks as $chunkIndex => $chunk) {
            DB::beginTransaction();
            try {
                foreach ($chunk as $item) {
                    try {
                        $userData = $item['data'];
                        // Final guardian normalization (ป้องกันกรณี preview ไม่ normalize)
                        if (($userData['role'] ?? null) === 'guardian') {
                            // Map Thai contact method values to enum
                            if (!empty($userData['contact_method'])) {
                                $cm = mb_strtolower(trim($userData['contact_method']));
                                $map = [
                                    'โทร' => 'phone','โทรศัพท์' => 'phone','เบอร์' => 'phone','เบอร์โทร' => 'phone','phone' => 'phone','tel'=>'phone','telephone'=>'phone',
                                    'อีเมล' => 'email','เมล' => 'email','email'=>'email','e-mail'=>'email',
                                    'ไลน์'=>'line','line'=>'line','lineid'=>'line','line id'=>'line','id line'=>'line','ไอดีไลน์'=>'line'
                                ];
                                $userData['contact_method'] = $map[$cm] ?? (in_array($cm,['phone','email','line']) ? $cm : 'phone');
                            } else { $userData['contact_method'] = 'phone'; }
                            // Recover student_codes if misplaced into guardian_id field
                            if (empty($userData['student_codes']) && !empty($userData['guardian_id'])) {
                                $candidate = trim($userData['guardian_id']);
                                // Check if it looks like student code(s) - single or multiple
                                if (preg_match('/^[\d\s,;，]+$/u', $candidate)) {
                                    // Split by delimiters (comma, semicolon, Thai comma, space)
                                    $recovered = preg_split('/[;,，\s]+/', $candidate);
                                    $recovered = array_filter(array_map(fn($c)=>trim($c), $recovered));
                                    // Heuristic: codes length 5-12 digits typical
                                    $filtered = [];
                                    foreach ($recovered as $code) {
                                        $numeric = preg_replace('/[^0-9]/','',$code);
                                        if (strlen($numeric) >= 5 && strlen($numeric) <= 12) { 
                                            $filtered[] = $numeric; 
                                        }
                                    }
                                    if (count($filtered) >= 1) {
                                        $userData['student_codes'] = implode(',', array_unique($filtered));
                                        Log::info('Recovered student_codes from guardian_id field', [
                                            'row' => $item['row_number'],
                                            'guardian_original_id_value' => $candidate,
                                            'recovered_codes' => $userData['student_codes'],
                                            'codes_count' => count($filtered)
                                        ]);
                                    }
                                }
                            }
                        }
                        // ปรับปรุงการจัดการวันเกิด - รองรับทุก role และตรวจสอบความถูกต้อง
                        $userBirthdate = null;
                        if (isset($userData['date_of_birth']) && !empty($userData['date_of_birth']) && $userData['date_of_birth'] !== null) {
                            $dateValue = $userData['date_of_birth'];
                            // ตรวจสอบว่าไม่ใช่อีเมลหรือข้อความยาวเกินไป
                            if (!filter_var($dateValue, FILTER_VALIDATE_EMAIL) && 
                                strpos($dateValue, '@') === false && 
                                strlen($dateValue) <= 20 && 
                                !preg_match('/[a-zA-Z]{3,}/', $dateValue)) { 
                                // ถ้าเป็นรูปแบบวันที่ที่ถูกต้องแล้ว (YYYY-MM-DD) ใช้ค่านั้นได้เลย
                                // ถ้าไม่ใช่ ให้แปลงอีกครั้ง
                                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateValue)) {
                                    $userBirthdate = $dateValue;
                                } else {
                                    $userBirthdate = $this->normalizeThaiBirthDate($dateValue);
                                }
                            }
                        }
                        $user = User::create([
                            // Ensure name prefix is not null (final safety net)
                            'users_name_prefix' => (function($data){
                                $title = $data['title'] ?? null;
                                if (!empty($title)) { return $title; }
                                $birth = $data['date_of_birth'] ?? null;
                                $gender = $data['gender'] ?? null;
                                $norm = $birth ? $this->normalizeThaiBirthDate($birth) : null;
                                return $this->choosePrefixByAgeGender($gender, $norm, true);
                            })->call($this, $userData),
                            'users_first_name' => $userData['first_name'],
                            'users_last_name' => $userData['last_name'],
                            'users_email' => $userData['email'],
                            'users_phone_number' => $userData['phone'] ?? null,
                            'users_password' => config('import.defaults.password'),
                            'users_role' => $userData['role'],
                            'users_birthdate' => $userBirthdate,
                            'users_created_at' => now(),
                            'users_updated_at' => now(),
                            'users_status' => $userData['status'] ?? config('import.defaults.status', 'active')
                        ]);

                        // Normalize gender for student before insert
                        if ($userData['role'] === 'student' && isset($userData['gender'])) {
                            $gender = strtolower(trim($userData['gender']));
                            $genderMapping = [
                                'male' => 'male', 'm' => 'male', 'ชาย' => 'male', 'เพศชาย' => 'male',
                                'female' => 'female', 'f' => 'female', 'หญิง' => 'female', 'เพศหญิง' => 'female',
                                'other' => 'other', 'อื่น' => 'other', 'ไม่ระบุ' => 'other'
                            ];
                            $userData['gender'] = $genderMapping[$gender] ?? null;
                        }

                        $this->createRoleSpecificData($user, $userData);
                        $successCount++;
                    } catch (\Exception $e) {
                        $errorCount++;
                        $friendly = $this->translateCommitError($e, $item['row_number'], isset($userData)?$userData:[]);
                        $errors[] = $friendly;
                        Log::error('Import row error', [
                            'row' => $item['row_number'],
                            'raw_error' => $e->getMessage(),
                            'friendly_error' => $friendly,
                            'data' => isset($userData) ? $userData : null
                        ]);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Chunk failed', ['error' => $e->getMessage(), 'chunk_size' => count($chunk)]);
                foreach ($chunk as $item) { $errorCount++; $errors[] = "แถว {$item['row_number']}: ไม่สามารถบันทึกข้อมูลได้เนื่องจากข้อผิดพลาดในระบบ"; }
            }
            if ($chunkIndex < count($chunks) - 1) { usleep(config('import.google_sheets.chunk_delay', 100000)); }
        }

        // Second pass: ensure guardian relationships (เผื่อบาง guardian อยู่คนละ chunk กับ student)
        try {
            $guardianItems = array_filter($selectedData, function($item){ return ($item['data']['role'] ?? null) === 'guardian' && !empty($item['data']['student_codes']); });
            $linked = 0; $missing = [];
            if (empty($guardianItems)) {
                Log::info('Second-pass: no guardian items with student_codes found', [
                    'guardian_total_in_selection' => collect($selectedData)->where('data.role','guardian')->count()
                ]);
            }
            foreach ($guardianItems as $gItem) {
                $codes = explode(',', $gItem['data']['student_codes']);
                $guardianUser = User::where('users_email', $gItem['data']['email'])->first();
                if (!$guardianUser) { continue; }
                $guardianModel = Guardian::where('user_id', $guardianUser->users_id)->first();
                Log::info('Second-pass linking guardian', [
                    'guardian_email' => $gItem['data']['email'],
                    'codes' => $codes,
                    'guardian_model_found' => (bool)$guardianModel
                ]);
                if (!$guardianModel) { continue; }
                foreach ($codes as $code) {
                    $code = trim($code); if ($code==='') continue;
                    $student = Student::where('students_student_code', $code)->first();
                    if ($student) {
                        $exists = DB::table('tb_guardian_student')->where('guardian_id',$guardianModel->guardians_id)->where('student_id',$student->students_id)->exists();
                        if (!$exists) {
                            DB::table('tb_guardian_student')->insert([
                                'guardian_id' => $guardianModel->guardians_id,
                                'student_id' => $student->students_id,
                                'guardian_student_created_at' => now()
                            ]);
                            $linked++;
                        }
                    } else { $missing[] = $code; }
                }
            }
            Log::info('Guardian relationship second-pass summary', [
                'processed_guardians' => count($guardianItems),
                'relationships_created' => $linked,
                'missing_student_codes' => array_values(array_unique($missing))
            ]);
        } catch (\Exception $e) {
            Log::error('Guardian relationship second-pass failed', ['error' => $e->getMessage()]);
        }

        return ['success_count' => $successCount, 'error_count' => $errorCount, 'errors' => $errors];
    }

    // --- Helpers for DOB and prefix selection ---
    private function normalizeThaiBirthDate($raw)
    {
        // Accept formats: YYYY-MM-DD, DD/MM/YYYY, DD-MM-YYYY, Buddhist years (พ.ศ.), Excel serial date
        $val = trim((string)$raw);
        if ($val === '') { return null; }
        
        // ตรวจสอบว่าเป็น Excel serial date number หรือไม่ (ตัวเลขล้วน 5 หลักขึ้นไป)
        if (is_numeric($val) && $val > 25569) { // 25569 = 1970-01-01 in Excel serial
            try {
                // Excel serial date: days since 1900-01-01 (with bug where 1900 is leap year)
                // Use Excel's epoch: 1899-12-30 (accounting for the 1900 bug)
                $excelEpoch = new \DateTime('1899-12-30');
                $excelEpoch->modify('+' . intval($val) . ' days');
                return $excelEpoch->format('Y-m-d');
            } catch (\Exception $e) {
                // If fails, continue to other methods
            }
        }
        
        // Replace Thai delimiters
        $v = str_replace(['.', ' '], ['-', ''], $val);
        // Try common separators
        $candidates = [$v, str_replace('/', '-', $v)];
        foreach ($candidates as $cand) {
            // Detect D-M-Y vs Y-M-D by first token length
            $parts = explode('-', $cand);
            if (count($parts) === 3) {
                [$a,$b,$c] = $parts;
                // If looks like D-M-Y
                if (strlen($a) <= 2 && strlen($c) >= 2) {
                    $d = (int)$a; $m = (int)$b; $y = (int)$c;
                    // Convert Buddhist year to Gregorian (>=2400 assume พ.ศ.)
                    if ($y >= 2400) { $y -= 543; }
                    // Also handle 2-digit years (rare)
                    if ($y < 100) { $y += ($y >= 50 ? 1900 : 2000); }
                    $ymd = sprintf('%04d-%02d-%02d', $y, $m, $d);
                    try { $dt = new \DateTime($ymd); return $dt->format('Y-m-d'); } catch (\Exception $e) { /* try next */ }
                }
                // If looks like Y-M-D
                if (strlen($a) >= 2) {
                    $y = (int)$a; $m = (int)$b; $d = (int)$c;
                    if ($y >= 2400) { $y -= 543; }
                    if ($y < 100) { $y += ($y >= 50 ? 1900 : 2000); }
                    $ymd = sprintf('%04d-%02d-%02d', $y, $m, $d);
                    try { $dt = new \DateTime($ymd); return $dt->format('Y-m-d'); } catch (\Exception $e) { /* try next */ }
                }
            }
        }
        // As a last resort, let DateTime try
        try { $dt = new \DateTime($val); return $dt->format('Y-m-d'); } catch (\Exception $e) { return null; }
    }

    private function choosePrefixByAgeGender($gender = null, $birthYmd = null, $adultAt15 = true)
    {
        $age = null;
        if ($birthYmd) { try { $age = (new \DateTime())->diff(new \DateTime($birthYmd))->y; } catch (\Exception $e) { $age = null; } }
        $isAdult = null;
        if ($age !== null) {
            // ผู้ใช้ขอ: อายุ < 15 → เด็กชาย/เด็กหญิง, อายุ >= 15 → นาย/นางสาว
            $isAdult = ($age >= 15);
        }
        $g = $gender ? strtolower($gender) : null;
        if ($isAdult === true) {
            if ($g === 'female' || $g === 'หญิง') { return 'นางสาว'; }
            return 'นาย';
        }
        if ($isAdult === false) {
            if ($g === 'female' || $g === 'หญิง') { return 'เด็กหญิง'; }
            return 'เด็กชาย';
        }
        // Unknown age: pick sane default by gender
        if ($g === 'female' || $g === 'หญิง') { return 'นางสาว'; }
        return 'นาย';
    }

    private function createRoleSpecificData($user, $userData)
    {
        switch ($userData['role']) {
            case 'student':
                $classroom = null;
                if (!empty($userData['grade_level']) && !empty($userData['classroom'])) {
                    $gradeLevel = 'ม.' . $userData['grade_level'];
                    $roomNumber = $userData['classroom'];
                    $classroom = ClassRoom::where('classes_level', $gradeLevel)->where('classes_room_number', $roomNumber)->first();
                    if (!$classroom) {
                        $classroom = ClassRoom::create([
                            'classes_level' => $gradeLevel,
                            'classes_room_number' => $roomNumber,
                            'classes_academic_year' => $userData['academic_year'] ?? date('Y'),
                            'teachers_id' => null
                        ]);
                    }
                }
                Student::create([
                    'user_id' => $user->users_id,
                    'students_student_code' => $userData['student_id'] ?? null,
                    'class_id' => $classroom ? $classroom->classes_id : null,
                    'students_academic_year' => $userData['academic_year'] ?? date('Y'),
                    'students_current_score' => config('import.defaults.student_score', 100),
                    'students_status' => $userData['status'] ?? config('import.defaults.status', 'active'),
                    'students_gender' => $userData['gender'] ?? null,
                    'students_created_at' => now()
                ]);
                break;
            case 'teacher':
            case 'admin':
                Teacher::create([
                    'users_id' => $user->users_id,
                    'teachers_employee_code' => $userData['teacher_id'] ?? null,
                    'teachers_position' => $userData['position'] ?? 'ครู',
                    'teachers_department' => $userData['subject_group'] ?? null,
                    'teachers_major' => $userData['subjects'] ?? null,
                    'teachers_is_homeroom_teacher' => false,
                    'assigned_class_id' => null
                ]);
                break;
            case 'guardian':
                Log::info('Creating guardian role-specific data', [
                    'user_id' => $user->users_id,
                    'incoming_student_codes' => $userData['student_codes'] ?? null,
                    'raw_user_data_keys' => array_keys($userData)
                ]);
                $guardian = Guardian::create([
                    'user_id' => $user->users_id,
                    'guardians_relationship_to_student' => 'ผู้ปกครอง',
                    'guardians_phone' => $userData['phone'] ?? null,
                    'guardians_email' => $userData['email'] ?? null,
                    'guardians_line_id' => $userData['line_id'] ?? null,
                    'guardians_preferred_contact_method' => $userData['contact_method'] ?? config('import.defaults.contact_method', 'phone'),
                    'guardians_created_at' => now(),
                    'updated_at' => now()
                ]);

                // Process student codes to create relationships
                if (!empty($userData['student_codes'])) {
                    $studentCodesRaw = $userData['student_codes'];
                    Log::info('Processing student codes for guardian', [
                        'guardian_id' => $guardian->guardians_id,
                        'raw_codes' => $studentCodesRaw,
                        'user_data' => $userData
                    ]);
                    
                    // Split by comma and clean up codes
                    $studentCodesArray = array_map('trim', explode(',', $studentCodesRaw));
                    $studentCodesArray = array_filter($studentCodesArray, function($code) {
                        return !empty($code) && strlen(trim($code)) > 0;
                    });
                    
                    Log::info('Cleaned student codes array', [
                        'guardian_id' => $guardian->guardians_id,
                        'codes_array' => $studentCodesArray,
                        'count' => count($studentCodesArray)
                    ]);
                    
                    foreach ($studentCodesArray as $studentCode) {
                        $cleanCode = trim($studentCode);
                        if (!empty($cleanCode)) {
                            $student = Student::where('students_student_code', $cleanCode)->first();
                            
                            Log::info('Looking for student', [
                                'code' => $cleanCode,
                                'found' => $student ? true : false,
                                'student_id' => $student ? $student->students_id : null
                            ]);
                            
                            if ($student) {
                                $exists = DB::table('tb_guardian_student')
                                    ->where('guardian_id', $guardian->guardians_id)
                                    ->where('student_id', $student->students_id)
                                    ->exists();
                                    
                                if (!$exists) {
                                    DB::table('tb_guardian_student')->insert([
                                        'guardian_id' => $guardian->guardians_id,
                                        'student_id' => $student->students_id,
                                        'guardian_student_created_at' => now()
                                    ]);
                                    
                                    Log::info('Created guardian-student relationship', [
                                        'guardian_id' => $guardian->guardians_id,
                                        'student_id' => $student->students_id,
                                        'student_code' => $cleanCode
                                    ]);
                                } else {
                                    Log::info('Guardian-student relationship already exists', [
                                        'guardian_id' => $guardian->guardians_id,
                                        'student_id' => $student->students_id,
                                        'student_code' => $cleanCode
                                    ]);
                                }
                            } else {
                                Log::warning('Student not found for code', [
                                    'code' => $cleanCode,
                                    'guardian_id' => $guardian->guardians_id
                                ]);
                            }
                        }
                    }
                } else {
                    Log::info('No student codes provided for guardian', [
                        'guardian_id' => $guardian->guardians_id,
                        'user_data_keys' => array_keys($userData)
                    ]);
                }
                break;
        }
    }

    // End createRoleSpecificData method

    private function translateCommitError(\Exception $e, $rowNumber, $userData = [])
        {
            $msg = $e->getMessage();
            $lower = mb_strtolower($msg);

            if (strpos($lower, 'guardians_preferred_contact_method') !== false && strpos($lower, 'data truncated') !== false) {
                return "แถว {$rowNumber}: ช่องทางการติดต่อของผู้ปกครองไม่ถูกต้อง (รองรับ: โทรศัพท์, อีเมล, ไลน์) ระบบตั้งค่าเป็นค่าเริ่มต้น 'โทรศัพท์' กรุณาตรวจสอบไฟล์.";
            }
            if (strpos($lower, 'duplicate') !== false && strpos($lower, 'entry') !== false) {
                if (preg_match('/Duplicate entry \'([^\']+)\' for key \'([^\']+)\'/i', $msg, $m)) {
                    $value = $m[1]; $key = $m[2];
                    if (strpos($key,'users_email') !== false || strpos($key,'email') !== false) {
                        return "แถว {$rowNumber}: อีเมล '{$value}' มีอยู่แล้วในระบบ";
                    }
                    if (strpos($key,'students_student_code') !== false || strpos($key,'student') !== false) {
                        return "แถว {$rowNumber}: รหัสนักเรียน '{$value}' มีอยู่แล้วในระบบ";
                    }
                }
                return "แถว {$rowNumber}: ข้อมูลซ้ำกับที่มีอยู่ในระบบ กรุณาตรวจสอบ";
            }
            if (strpos($lower,'sqlstate') !== false || strpos($lower,'sql: insert') !== false) {
                return "แถว {$rowNumber}: ไม่สามารถบันทึกข้อมูล (รูปแบบข้อมูลไม่ถูกต้องหรือมีข้อมูลที่ขัดแย้ง)";
            }
            return "แถว {$rowNumber}: ไม่สามารถบันทึกข้อมูล - " . $msg;
        }
    }