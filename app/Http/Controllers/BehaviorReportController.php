<?php

namespace App\Http\Controllers;

use App\Models\BehaviorLog;
use App\Models\BehaviorReport;
use App\Models\Student;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BehaviorReportController extends Controller
{
    // ==================== Behavior Report Management ====================
    
    /**
     * บันทึกพฤติกรรมนักเรียนใหม่
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Start transaction
        DB::beginTransaction();

        try {
            // Validate input data
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:tb_students,students_id',
                'violation_id' => 'required|exists:tb_violations,violations_id',
                'violation_datetime' => 'required|date_format:Y-m-d H:i',
                'description' => 'nullable|string|max:1000',
                'evidence' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ], [
                'student_id.required' => 'กรุณาเลือกนักเรียน',
                'student_id.exists' => 'ไม่พบข้อมูลนักเรียนที่เลือก',
                'violation_id.required' => 'กรุณาเลือกประเภทการกระทำผิด',
                'violation_id.exists' => 'ไม่พบข้อมูลประเภทการกระทำผิด',
                'violation_datetime.required' => 'กรุณาระบุวันและเวลาที่เกิดเหตุ',
                'violation_datetime.date_format' => 'รูปแบบวันและเวลาไม่ถูกต้อง',
                'evidence.image' => 'ไฟล์ที่แนบต้องเป็นรูปภาพเท่านั้น',
                'evidence.mimes' => 'รองรับเฉพาะไฟล์รูปภาพนามสกุล: jpeg, png, jpg, gif',
                'evidence.max' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get authenticated user
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลผู้ใช้'
                ], 403);
            }

            // Verify user is teacher or admin
            if (!in_array($user->users_role, ['teacher', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์บันทึกรายงานพฤติกรรม'
                ], 403);
            }

            // Get teacher data or create admin teacher record
            $teacher = DB::table('tb_teachers')
                ->where('users_id', $user->users_id)
                ->first();
                
            // If user is admin and doesn't have teacher record, create one
            if (!$teacher && $user->users_role === 'admin') {
                $teacherId = DB::table('tb_teachers')->insertGetId([
                    'users_id' => $user->users_id,
                    'teachers_employee_code' => 'ADMIN_' . $user->users_id,
                    'teachers_position' => 'ผู้ดูแลระบบ',
                    'teachers_department' => 'งานบริหาร',
                    'teachers_major' => null,
                    'teachers_is_homeroom_teacher' => false,
                    'assigned_class_id' => null
                ]);
                
                $teacher = DB::table('tb_teachers')->where('teachers_id', $teacherId)->first();
            }
                
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถสร้างข้อมูลผู้บันทึกได้'
                ], 500);
            }

            // Get student data
            $student = DB::table('tb_students')
                ->where('students_id', $request->student_id)
                ->lockForUpdate() // Lock row for update
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ], 404);
            }

            // Get violation data
            $violation = DB::table('tb_violations')
                ->where('violations_id', $request->violation_id)
                ->first();

            if (!$violation) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลประเภทการกระทำผิด'
                ], 404);
            }

            // Handle evidence file upload
            $evidencePath = null;
            if ($request->hasFile('evidence')) {
                $file = $request->file('evidence');
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                
                // Store in storage/app/public/behavior_evidences
                $evidencePath = $file->storeAs('behavior_evidences', $filename, 'public');
                
                Log::info('Evidence file uploaded', [
                    'filename' => $filename,
                    'path' => $evidencePath,
                    'full_path' => storage_path('app/public/' . $evidencePath)
                ]);
            }

            // Create behavior report with snapshot points
            $snapshotPoints = abs($violation->violations_points_deducted ?? 0);
            $reportId = DB::table('tb_behavior_reports')->insertGetId([
                'student_id' => $request->student_id,
                'teacher_id' => $teacher->teachers_id,
                'violation_id' => $request->violation_id,
                'reports_points_deducted' => $snapshotPoints,
                'reports_description' => $request->description,
                'reports_evidence_path' => $evidencePath,
                'reports_report_date' => Carbon::parse($request->violation_datetime),
                'created_at' => now(),
            ]);

            // Update student's behavior score
            $oldScore = (int)($student->students_current_score ?? 100);
            $newScore = max(0, $oldScore - $snapshotPoints);
            DB::table('tb_students')
                ->where('students_id', $request->student_id)
                ->update([
                    'students_current_score' => $newScore,
                    'updated_at' => now()
                ]);

            // Auto-notify when score crosses below 50
            try {
                app(\App\Services\NotificationService::class)
                    ->sendLowScoreAlertIfCrossed((int)$request->student_id, $oldScore, (int)$newScore);
            } catch (\Throwable $e) {
                Log::warning('Failed to send low score alert (store)', [
                    'student_id' => (int)$request->student_id,
                    'old' => $oldScore,
                    'new' => (int)$newScore,
                    'error' => $e->getMessage()
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'บันทึกพฤติกรรมสำเร็จ',
                'data' => [
                    'report_id' => $reportId,
                    'reports_points_deducted' => $snapshotPoints,
                    'student_updated_score' => $newScore,
                    'evidence_path' => $evidencePath,
                    'evidence_url' => $evidencePath ? asset('storage/' . $evidencePath) : null
                ],
                'report' => [
                    'reports_id' => $reportId,
                    'evidence_path' => $evidencePath,
                    'evidence_url' => $evidencePath ? asset('storage/' . $evidencePath) : null
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error saving behavior report: ' . $e->getMessage());
            
            // If file was uploaded but transaction failed, we should delete the file
            if (isset($evidencePath) && Storage::disk('public')->exists($evidencePath)) {
                Storage::disk('public')->delete($evidencePath);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการบันทึกพฤติกรรม',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== Student Search ====================
    
    /**
     * ค้นหานักเรียนสำหรับฟอร์มบันทึกพฤติกรรม
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchStudents(Request $request)
    {
        try {
            $searchTerm = trim($request->input('term', ''));
            $classId = $request->input('class_id', '');
            
            // Sanitize input
            $searchTerm = strip_tags($searchTerm);
            $searchTerm = preg_replace('/[^ก-๙a-zA-Z0-9\s]/u', '', $searchTerm);

            $query = DB::table('tb_students as s')
                ->join('tb_users as u', 's.user_id', '=', 'u.users_id')
                ->leftJoin('tb_classes as c', 's.class_id', '=', 'c.classes_id')
                ->where('s.students_status', 'active') // เฉพาะนักเรียนที่ active
                ->select(
                    's.students_id',
                    's.students_current_score',
                    's.students_student_code',
                    'u.users_name_prefix',
                    'u.users_first_name',
                    'u.users_last_name',
                    'c.classes_level',
                    'c.classes_room_number'
                );
            
            // Apply search filters - ใช้ parameter binding เพื่อป้องกัน SQL Injection
            if (!empty($searchTerm)) {
                $query->where(function($q) use ($searchTerm) {
                    // แยกคำค้นหาออกเป็นคำๆ
                    $terms = explode(' ', $searchTerm);
                    
                    foreach ($terms as $term) {
                        if (empty(trim($term))) continue;
                        
                        $q->where(function($subQ) use ($term) {
                            $subQ->where('u.users_first_name', 'LIKE', '%' . $term . '%')
                                 ->orWhere('u.users_last_name', 'LIKE', '%' . $term . '%')
                                 ->orWhere('u.users_name_prefix', 'LIKE', '%' . $term . '%')
                                 ->orWhere('s.students_student_code', 'LIKE', '%' . $term . '%')
                                 // ใช้ whereRaw กับ parameter binding แทน DB::raw
                                 ->orWhereRaw("CONCAT(u.users_name_prefix, u.users_first_name, ' ', u.users_last_name) LIKE ?", ['%' . $term . '%'])
                                 ->orWhereRaw("CONCAT(u.users_first_name, ' ', u.users_last_name) LIKE ?", ['%' . $term . '%']);
                        });
                    }
                });
            }
            
            if (!empty($classId)) {
                $query->where('s.class_id', $classId);
            }
            
            // เรียงลำดับตามความเกี่ยวข้อง - ใช้ parameter binding
            $query->orderByRaw("
                CASE 
                    WHEN s.students_student_code LIKE ? THEN 1
                    WHEN u.users_first_name LIKE ? THEN 2
                    WHEN u.users_last_name LIKE ? THEN 3
                    ELSE 4
                END
            ", [$searchTerm . '%', $searchTerm . '%', $searchTerm . '%'])
            ->orderBy('u.users_first_name');
            
            $students = $query->limit(20)->get();
            
            // Format results
            $results = $students->map(function($student) {
                return [
                    'id' => $student->students_id,
                    'name' => trim(($student->users_name_prefix ?? '') . 
                             ($student->users_first_name ?? '') . ' ' . 
                             ($student->users_last_name ?? '')),
                    'student_id' => $student->students_student_code ?? 'ไม่มีรหัส',
                    'class' => $student->classes_level && $student->classes_room_number 
                              ? $student->classes_level . '/' . $student->classes_room_number 
                              : 'ไม่ระบุ',
                    'current_score' => $student->students_current_score ?? 100
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error searching students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการค้นหานักเรียน',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== Report Retrieval ====================
    
    /**
     * ดึงรายงานพฤติกรรมล่าสุด
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecentReports(Request $request)
    {
        try {
            $limit = $request->input('limit', 10);
            
            $reports = DB::table('tb_behavior_reports as br')
                ->join('tb_students as s', 'br.student_id', '=', 's.students_id')
                ->join('tb_users as su', 's.user_id', '=', 'su.users_id')
                ->join('tb_teachers as t', 'br.teacher_id', '=', 't.teachers_id')
                ->join('tb_users as tu', 't.users_id', '=', 'tu.users_id')
                ->join('tb_violations as v', 'br.violation_id', '=', 'v.violations_id')
                ->select(
                    'br.reports_id',
                    'br.reports_description',
                    'br.reports_evidence_path',
                    'br.reports_report_date',
                    'br.created_at',
                    'br.reports_points_deducted',
                    'su.users_name_prefix as student_prefix',
                    'su.users_first_name as student_first_name',
                    'su.users_last_name as student_last_name',
                    'tu.users_name_prefix as teacher_prefix',
                    'tu.users_first_name as teacher_first_name',
                    'tu.users_last_name as teacher_last_name',
                    'v.violations_name'
                )
                ->orderBy('br.created_at', 'desc')
                ->limit($limit)
                ->get();
            
            // Format results
            $results = $reports->map(function($report) {
                return [
                    'id' => $report->reports_id,
                    'student_name' => ($report->student_prefix ?? '') . 
                                    ($report->student_first_name ?? '') . ' ' . 
                                    ($report->student_last_name ?? ''),
                    'violation_name' => $report->violations_name ?? '',
                    'points_deducted' => $report->reports_points_deducted ?? 0,
                    'teacher_name' => ($report->teacher_prefix ?? '') . 
                                     ($report->teacher_first_name ?? '') . ' ' . 
                                     ($report->teacher_last_name ?? ''),
                    'report_date' => $report->reports_report_date,
                    'created_at' => Carbon::parse($report->created_at)->format('d/m/Y H:i'),
                    'description' => $report->reports_description ?? '',
                    'evidence_path' => $report->reports_evidence_path ?? null,
                    'evidence_url' => $report->reports_evidence_path 
                                    ? asset('storage/' . $report->reports_evidence_path)
                                    : null
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching recent reports: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลรายงาน',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==================== Report Details ====================
    
    /**
     * ดึงรายละเอียดรายงานพฤติกรรมตาม ID
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // Fetch report with joined data
            $report = DB::table('tb_behavior_reports as br')
                ->join('tb_students as s', 'br.student_id', '=', 's.students_id')
                ->join('tb_users as su', 's.user_id', '=', 'su.users_id')
                ->join('tb_teachers as t', 'br.teacher_id', '=', 't.teachers_id')
                ->join('tb_users as tu', 't.users_id', '=', 'tu.users_id')
                ->join('tb_violations as v', 'br.violation_id', '=', 'v.violations_id')
                ->leftJoin('tb_classes as c', 's.class_id', '=', 'c.classes_id')
                ->select(
                    'br.reports_id',
                    'br.reports_description',
                    'br.reports_evidence_path',
                    'br.reports_report_date',
                    'br.created_at',
                    'br.reports_points_deducted',
                    // Student info
                    'su.users_name_prefix as student_prefix',
                    'su.users_first_name as student_first_name',
                    'su.users_last_name as student_last_name',
                    's.students_student_code',
                    's.students_current_score',
                    // Teacher info
                    'tu.users_name_prefix as teacher_prefix',
                    'tu.users_first_name as teacher_first_name',
                    'tu.users_last_name as teacher_last_name',
                    // Violation info
                    'v.violations_name',
                    'v.violations_category',
                    'v.violations_description as violation_description',
                    // Class info
                    'c.classes_level',
                    'c.classes_room_number'
                )
                ->where('br.reports_id', $id)
                ->first();

            // Check if report exists
            if (!$report) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลรายงานพฤติกรรม'
                ], 404);
            }

            // Format result
            $result = [
                'id' => $report->reports_id,
                'student' => [
                    'name' => ($report->student_prefix ?? '') . 
                             ($report->student_first_name ?? '') . ' ' . 
                             ($report->student_last_name ?? ''),
                    'student_code' => $report->students_student_code ?? '',
                    'current_score' => $report->students_current_score ?? 100,
                    'class' => $report->classes_level && $report->classes_room_number 
                              ? $report->classes_level . '/' . $report->classes_room_number 
                              : 'ไม่ระบุ',
                    'avatar_url' => 'https://ui-avatars.com/api/?name=' . urlencode(($report->student_first_name ?? '') . ' ' . ($report->student_last_name ?? '')) . '&background=95A4D8&color=fff'
                ],
                'violation' => [
                    'name' => $report->violations_name ?? '',
                    'category' => $report->violations_category ?? '',
                    'points_deducted' => $report->reports_points_deducted ?? 0,
                    'description' => $report->violation_description ?? ''
                ],
                'teacher' => [
                    'name' => ($report->teacher_prefix ?? '') . 
                             ($report->teacher_first_name ?? '') . ' ' . 
                             ($report->teacher_last_name ?? '')
                ],
                'report' => [
                    'description' => $report->reports_description ?? '',
                    'evidence_path' => $report->reports_evidence_path ?? null,
                    'evidence_url' => $report->reports_evidence_path 
                                     ? asset('storage/' . $report->reports_evidence_path)
                                     : null,
                    'report_date' => Carbon::parse($report->reports_report_date)->format('j M Y, H:i น.'),
                    'report_date_thai' => Carbon::parse($report->reports_report_date)->locale('th')->format('j F Y, H:i น.'),
                    'report_datetime' => $report->reports_report_date, // Raw datetime for editing
                    'created_at' => Carbon::parse($report->created_at)->format('d/m/Y H:i')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching behavior report detail: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลรายละเอียดรายงาน',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ==================== Report Update ====================
    
    /**
     * อัปเดตรายงานพฤติกรรมนักเรียน
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // Find behavior report
            $behaviorReport = BehaviorReport::find($id);
            
            if (!$behaviorReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบรายงานพฤติกรรมที่ต้องการแก้ไข'
                ], 404);
            }

            // Verify edit permission (only the reporting teacher or admin)
            $user = Auth::user();
            
            // ตรวจสอบว่าผู้ใช้มีสิทธิ์หรือไม่
            $hasPermission = false;
            
            // กรณีที่เป็น admin มีสิทธิ์แก้ไขทุกรายการ
            if ($user->users_role === 'admin') {
                $hasPermission = true;
            } else {
                // กรณีที่เป็นครู ต้องเป็นครูที่เป็นเจ้าของรายงานเท่านั้น
                $currentTeacher = DB::table('tb_teachers')
                    ->where('users_id', $user->users_id)
                    ->first();
                    
                if ($currentTeacher && (int)$behaviorReport->teacher_id === (int)$currentTeacher->teachers_id) {
                    $hasPermission = true;
                }
            }
            
            // ถ้าไม่มีสิทธิ์ให้ return error
            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์แก้ไขรายงานนี้ มีเพียงครูผู้บันทึกหรือผู้ดูแลระบบเท่านั้นที่สามารถแก้ไขได้'
                ], 403);
            }

            // Validate input data
            $validator = Validator::make($request->all(), [
                'violation_id' => 'required|exists:tb_violations,violations_id',
                'report_datetime' => 'required|date_format:Y-m-d H:i:s',
                'description' => 'nullable|string|max:1000',
                'evidence' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ], [
                'violation_id.required' => 'กรุณาเลือกประเภทการกระทำผิด',
                'violation_id.exists' => 'ไม่พบข้อมูลประเภทการกระทำผิด',
                'report_datetime.required' => 'กรุณาระบุวันและเวลาที่เกิดเหตุ',
                'report_datetime.date_format' => 'รูปแบบวันและเวลาไม่ถูกต้อง',
                'evidence.image' => 'ไฟล์ที่แนบต้องเป็นรูปภาพเท่านั้น',
                'evidence.mimes' => 'รองรับเฉพาะไฟล์รูปภาพนามสกุล: jpeg, png, jpg, gif',
                'evidence.max' => 'ขนาดไฟล์รูปภาพต้องไม่เกิน 2MB'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Start database transaction
            DB::beginTransaction();

            // Get new violation data
            $violation = Violation::find($request->violation_id);
            
            // Store snapshot before update
            $before = [
                'violation_id' => $behaviorReport->violation_id,
                'reports_points_deducted' => $behaviorReport->reports_points_deducted,
                'reports_report_date' => (string)$behaviorReport->reports_report_date,
                'reports_description' => $behaviorReport->reports_description,
            ];

            // Handle evidence file upload (if provided)
            $evidencePath = $behaviorReport->reports_evidence_path;
            if ($request->hasFile('evidence')) {
                // Delete old file
                if ($evidencePath && Storage::disk('public')->exists($evidencePath)) {
                    Storage::disk('public')->delete($evidencePath);
                    Log::info('Deleted old evidence file', ['path' => $evidencePath]);
                }
                
                // Upload new file
                $file = $request->file('evidence');
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());
                
                // Store in storage/app/public/behavior_evidences
                $evidencePath = $file->storeAs('behavior_evidences', $filename, 'public');
                
                Log::info('New evidence file uploaded', [
                    'filename' => $filename,
                    'path' => $evidencePath,
                    'full_path' => storage_path('app/public/' . $evidencePath)
                ]);
            }

            // Update report with new violation points snapshot
            $behaviorReport->update([
                'violation_id' => $request->violation_id,
                'reports_points_deducted' => abs($violation->violations_points_deducted ?? 0),
                'reports_report_date' => $request->report_datetime,
                'reports_description' => $request->description,
                'reports_evidence_path' => $evidencePath,
            ]);

            // Recalculate student's total score from all report snapshots
            $student = Student::find($behaviorReport->student_id);
            if ($student) {
                $oldScore = (int)($student->students_current_score ?? 100);
                $totalDeducted = BehaviorReport::where('student_id', $student->students_id)
                    ->sum('reports_points_deducted');
                $newScore = max(0, 100 - (int)$totalDeducted);
                $student->students_current_score = $newScore;
                $student->save();

                // Auto-notify when score crosses below 50
                try {
                    app(\App\Services\NotificationService::class)
                        ->sendLowScoreAlertIfCrossed((int)$student->students_id, $oldScore, (int)$newScore);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send low score alert (update)', [
                        'student_id' => (int)$student->students_id,
                        'old' => $oldScore,
                        'new' => (int)$newScore,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Create behavior log entry
            BehaviorLog::create([
                'behavior_report_id' => $behaviorReport->reports_id,
                'action_type' => 'update',
                'performed_by' => $user->users_id,
                'before_change' => $before,
                'after_change' => [
                    'violation_id' => $behaviorReport->violation_id,
                    'reports_points_deducted' => $behaviorReport->reports_points_deducted,
                    'reports_report_date' => (string)$behaviorReport->reports_report_date,
                    'reports_description' => $behaviorReport->reports_description,
                ],
                'created_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'แก้ไขรายงานพฤติกรรมเรียบร้อยแล้ว',
                'data' => $behaviorReport
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error updating behavior report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการแก้ไขรายงาน',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // ==================== Report Deletion ====================
    
    /**
     * ลบรายงานพฤติกรรมนักเรียน
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Find behavior report
            $behaviorReport = BehaviorReport::find($id);
            
            if (!$behaviorReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบรายงานพฤติกรรมที่ต้องการลบ'
                ], 404);
            }

            // Verify delete permission (only the reporting teacher or admin)
            $user = Auth::user();
            
            // ตรวจสอบว่าผู้ใช้มีสิทธิ์หรือไม่
            $hasPermission = false;
            
            // กรณีที่เป็น admin มีสิทธิ์ลบทุกรายการ
            if ($user->users_role === 'admin') {
                $hasPermission = true;
            } else {
                // กรณีที่เป็นครู ต้องเป็นครูที่เป็นเจ้าของรายงานเท่านั้น
                $currentTeacher = DB::table('tb_teachers')
                    ->where('users_id', $user->users_id)
                    ->first();
                    
                if ($currentTeacher && (int)$behaviorReport->teacher_id === (int)$currentTeacher->teachers_id) {
                    $hasPermission = true;
                }
            }
            
            // ถ้าไม่มีสิทธิ์ให้ return error
            if (!$hasPermission) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณไม่มีสิทธิ์ลบรายงานนี้ มีเพียงครูผู้บันทึกหรือผู้ดูแลระบบเท่านั้นที่สามารถลบได้'
                ], 403);
            }

            // Start database transaction
            DB::beginTransaction();

            // Store snapshot before deletion for log
            $before = [
                'violation_id' => $behaviorReport->violation_id,
                'reports_points_deducted' => $behaviorReport->reports_points_deducted,
            ];

            // Create behavior log entry before deletion (avoid FK error)
            BehaviorLog::create([
                'behavior_report_id' => $behaviorReport->reports_id,
                'action_type' => 'delete',
                'performed_by' => $user->users_id,
                'before_change' => $before,
                'after_change' => null,
                'created_at' => now(),
            ]);

            // Delete evidence file (if exists)
            if ($behaviorReport->reports_evidence_path && Storage::disk('public')->exists($behaviorReport->reports_evidence_path)) {
                Storage::disk('public')->delete($behaviorReport->reports_evidence_path);
            }

            // Delete report
            $behaviorReport->delete();

            // Recalculate student's total score from remaining report snapshots
            $student = Student::find($behaviorReport->student_id);
            if ($student) {
                $oldScore = (int)($student->students_current_score ?? 100);
                $totalDeducted = BehaviorReport::where('student_id', $student->students_id)
                    ->sum('reports_points_deducted');
                $newScore = max(0, 100 - (int)$totalDeducted);
                $student->students_current_score = $newScore;
                $student->save();

                // Auto-notify when score crosses below 50
                try {
                    app(\App\Services\NotificationService::class)
                        ->sendLowScoreAlertIfCrossed((int)$student->students_id, $oldScore, (int)$newScore);
                } catch (\Throwable $e) {
                    Log::warning('Failed to send low score alert (destroy)', [
                        'student_id' => (int)$student->students_id,
                        'old' => $oldScore,
                        'new' => (int)$newScore,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ลบรายงานพฤติกรรมเรียบร้อยแล้ว'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error deleting behavior report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบรายงาน',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}