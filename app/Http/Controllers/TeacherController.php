<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    public function updateProfile(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'users_name_prefix' => 'required|string|max:10',
                'users_first_name' => 'required|string|max:100',
                'users_last_name' => 'required|string|max:100',
                'users_phone_number' => 'nullable|string|max:20',
                'users_birthdate' => 'nullable|date',
                'teachers_position' => 'nullable|string|max:100',
                'teachers_department' => 'nullable|string|max:100',
                'teachers_major' => 'nullable|string|max:100',
                'users_profile_image' => 'nullable|image|max:2048', // เปลี่ยนชื่อฟิลด์ตรงนี้
                'current_password' => 'nullable|string',
                'new_password' => 'nullable|string|min:8|confirmed',
            ]);

            // Get the authenticated user
            $user = Auth::user();

            // Handle password change if provided
            if ($request->filled('current_password') && $request->filled('new_password')) {
                if (!Hash::check($request->current_password, $user->users_password)) { // แก้เป็น users_password
                    return back()->withErrors(['current_password' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);
                }
                $user->users_password = Hash::make($request->new_password); // แก้เป็น users_password
            }

            // Update user info
            $user->users_name_prefix = $request->users_name_prefix;
            $user->users_first_name = $request->users_first_name;
            $user->users_last_name = $request->users_last_name;
            $user->users_phone_number = $request->users_phone_number;
            $user->users_birthdate = $request->users_birthdate;

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                // Delete old image if it exists
                if ($user->users_profile_image) {
                    Storage::delete('public/' . $user->users_profile_image);
                }
                
                // Make sure directory exists
                $directory = 'users_profiles';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                    \Log::info('Created directory: ' . $directory);
                }
                
                // Get file extension
                $extension = $request->file('profile_image')->getClientOriginalExtension();
                
                // Create custom filename with user ID
                $filename = 'user_' . $user->users_id . '_' . time() . '.' . $extension;
                
                // Store with custom filename in users directory
                try {
                    $path = $request->file('profile_image')->storeAs(
                        $directory,
                        $filename,
                        'public'
                    );
                    \Log::info('Stored file at: ' . $path);
                    
                    // Save the path to the database
                    $user->users_profile_image = $path;
                } catch (\Exception $e) {
                    \Log::error('File upload error: ' . $e->getMessage());
                    return back()->withErrors(['profile_image' => 'ไม่สามารถอัพโหลดรูปภาพได้: ' . $e->getMessage()]);
                }
            }

            $user->save();

            // Update teacher info if available
            if ($user->teacher) {
                $user->teacher->teachers_position = $request->teachers_position;
                $user->teacher->teachers_department = $request->teachers_department;
                $user->teacher->teachers_major = $request->teachers_major;
                $user->teacher->save();
            }

            return back()->with('success', 'โปรไฟล์ของคุณได้รับการอัปเดตเรียบร้อยแล้ว');
        } catch (\Exception $e) {
            \Log::error('Profile update error: ' . $e->getMessage());
            return back()->withErrors(['general' => 'เกิดข้อผิดพลาดในการอัปเดตโปรไฟล์: ' . $e->getMessage()]);
        }
    }

    /**
     * ดึงข้อมูลนักเรียนที่ไม่ใช่สถานะ active สำหรับแสดงในประวัติการเก็บข้อมูล
     */
    public function getArchivedStudents(Request $request)
    {
        try {
            $query = \DB::table('tb_students as s')
                ->join('tb_users as u', 's.user_id', '=', 'u.users_id')
                ->leftJoin('tb_classes as c', 's.class_id', '=', 'c.classes_id')
                ->where('s.students_status', '!=', 'active')
                ->select(
                    's.students_id',
                    's.students_student_code',
                    's.students_current_score',
                    's.students_status',
                    'u.users_name_prefix',
                    'u.users_first_name',
                    'u.users_last_name',
                    'u.users_profile_image',
                    'c.classes_level',
                    'c.classes_room_number'
                );

            // Apply filters
            if ($request->filled('status')) {
                $normalizedStatus = $this->normalizeStatusInput($request->status);
                if (is_array($normalizedStatus)) {
                    $query->whereIn('s.students_status', $normalizedStatus);
                } else {
                    $query->where('s.students_status', $normalizedStatus);
                }
            }

            if ($request->filled('level')) {
                $query->where('c.classes_level', $request->level);
            }

            if ($request->filled('room')) {
                $query->where('c.classes_room_number', $request->room);
            }

            if ($request->filled('score')) {
                $scoreRange = explode('-', $request->score);
                if (count($scoreRange) == 2) {
                    $query->whereBetween('s.students_current_score', [(int)$scoreRange[0], (int)$scoreRange[1]]);
                }
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('s.students_student_code', 'like', "%{$search}%")
                      ->orWhere('u.users_first_name', 'like', "%{$search}%")
                      ->orWhere('u.users_last_name', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 10);
            $currentPage = $request->get('page', 1);
            
            $total = $query->count();
            $students = $query->offset(($currentPage - 1) * $perPage)
                            ->limit($perPage)
                            ->orderBy('s.students_student_code')
                            ->get();

            // Format data
            $formattedStudents = $students->map(function ($student) {
                $fullName = ($student->users_name_prefix ?? '') . $student->users_first_name . ' ' . $student->users_last_name;
                
                // Avatar URL
                $avatarUrl = $student->users_profile_image 
                    ? asset('storage/' . $student->users_profile_image)
                    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=95A4D8&color=fff';

                return [
                    'students_id' => $student->students_id,
                    'students_student_code' => $student->students_student_code ?? '-',
                    'full_name' => $fullName,
                    'avatar_url' => $avatarUrl,
                    'class_level' => $student->classes_level ?? '-',
                    'class_room' => $student->classes_room_number ?? '-',
                    'final_score' => $student->students_current_score ?? 100,
                    'status' => $student->students_status,
                    'status_label' => $this->getStatusLabel($student->students_status)
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedStudents,
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching archived students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล'
            ], 500);
        }
    }

    /**
     * ดึงประวัติการบันทึกพฤติกรรมของนักเรียน
     */
    public function getStudentHistory($studentId)
    {
        try {
            // ข้อมูลนักเรียน
            $student = \DB::table('tb_students as s')
                ->join('tb_users as u', 's.user_id', '=', 'u.users_id')
                ->leftJoin('tb_classes as c', 's.class_id', '=', 'c.classes_id')
                ->where('s.students_id', $studentId)
                ->select(
                    's.students_id',
                    's.students_student_code',
                    's.students_current_score',
                    's.students_status',
                    'u.users_name_prefix',
                    'u.users_first_name',
                    'u.users_last_name',
                    'u.users_profile_image',
                    'c.classes_level',
                    'c.classes_room_number'
                )
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่พบข้อมูลนักเรียน'
                ], 404);
            }

            // ประวัติการบันทึกพฤติกรรม
            $behaviorHistory = \DB::table('tb_behavior_reports as br')
                ->join('tb_violations as v', 'br.violation_id', '=', 'v.violations_id')
                ->join('tb_teachers as t', 'br.teacher_id', '=', 't.teachers_id')
                ->join('tb_users as tu', 't.users_id', '=', 'tu.users_id')
                ->where('br.student_id', $studentId)
                ->select(
                    'br.reports_report_date',
                    'v.violations_name',
                    'v.violations_points_deducted',
                    'br.reports_description',
                    'tu.users_name_prefix as teacher_prefix',
                    'tu.users_first_name as teacher_first_name',
                    'tu.users_last_name as teacher_last_name'
                )
                ->orderBy('br.reports_report_date', 'desc')
                ->get();

            // คำนวณสถิติ
            $totalViolations = $behaviorHistory->count();
            $totalScoreDeducted = $behaviorHistory->sum('violations_points_deducted');
            
            // นับจำนวนปีการศึกษาที่มีการบันทึก
            $academicYears = $behaviorHistory->map(function($record) {
                $year = date('Y', strtotime($record->reports_report_date));
                // แปลงเป็นปีการศึกษาไทย (ปี พ.ศ. - 543 + 1)
                return ($year > 2000) ? $year + 543 : $year + 2543;
            })->unique()->count();

            $averageScorePerYear = $academicYears > 0 ? round($totalScoreDeducted / $academicYears, 1) : 0;

            // จัดรูปแบบข้อมูลนักเรียน
            $fullName = ($student->users_name_prefix ?? '') . $student->users_first_name . ' ' . $student->users_last_name;
            $avatarUrl = $student->users_profile_image 
                ? asset('storage/' . $student->users_profile_image)
                : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=95A4D8&color=fff';

            // จัดรูปแบบประวัติ
            $formattedHistory = $behaviorHistory->map(function($record) {
                $teacherName = ($record->teacher_prefix ?? '') . $record->teacher_first_name . ' ' . $record->teacher_last_name;
                $reportDate = date('d/m/Y', strtotime($record->reports_report_date));
                $academicYear = date('Y', strtotime($record->reports_report_date)) + 543;

                return [
                    'date' => $reportDate,
                    'violation_name' => $record->violations_name,
                    'points_deducted' => $record->violations_points_deducted,
                    'academic_year' => $academicYear,
                    'teacher_name' => $teacherName,
                    'description' => $record->reports_description
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'student' => [
                        'id' => $student->students_id,
                        'code' => $student->students_student_code ?? '-',
                        'name' => $fullName,
                        'avatar' => $avatarUrl,
                        'class' => ($student->classes_level ?? '-') . '/' . ($student->classes_room_number ?? '-'),
                        'status' => $this->getStatusLabel($student->students_status),
                        'final_score' => $student->students_current_score ?? 100
                    ],
                    'statistics' => [
                        'total_violations' => $totalViolations,
                        'total_score_deducted' => $totalScoreDeducted,
                        'average_score_per_year' => $averageScorePerYear,
                        'academic_years_count' => $academicYears
                    ],
                    'history' => $formattedHistory
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching student history: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลประวัติ'
            ], 500);
        }
    }

    /**
     * แปลงสถานะเป็น label ที่อ่านง่าย
     */
    private function getStatusLabel($status)
    {
        $statusLabels = [
            'graduate' => 'จบการศึกษา',
            'graduated' => 'จบการศึกษา',
            'transferred' => 'ย้ายโรงเรียน',
            'suspended' => 'พักการเรียน',
            'expelled' => 'ถูกไล่ออก'
        ];

        return $statusLabels[$status] ?? $status;
    }

    /**
     * ทำให้ค่าพารามิเตอร์สถานะจาก client เป็นรูปแบบที่ตรงกับฐานข้อมูล
     */
    private function normalizeStatusInput($status)
    {
        if (!$status) return $status;
        $map = [
            'graduated' => 'graduate',
            'graduate' => 'graduate',
            'expelled' => 'expelled',
            'suspended' => 'suspended',
            'transferred' => 'transferred',
        ];
        $key = strtolower(trim($status));
        return $map[$key] ?? $key;
    }
}
