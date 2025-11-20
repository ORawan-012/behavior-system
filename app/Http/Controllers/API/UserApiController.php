<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Guardian;
use App\Models\ClassRoom;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class UserApiController extends Controller
{
    /**
     * Get all users with pagination and filters
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['student.classroom', 'teacher', 'guardian']);

            // Apply search filter
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->whereRaw("CONCAT(users_name_prefix, users_first_name, ' ', users_last_name) LIKE ?", ["%{$search}%"])
                      ->orWhere('users_email', 'LIKE', "%{$search}%")
                      ->orWhereHas('student', function($sq) use ($search) {
                          $sq->where('students_student_code', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('teacher', function($tq) use ($search) {
                          $tq->where('teachers_employee_code', 'LIKE', "%{$search}%");
                      });
                });
            }

            // Apply role filter
            if ($request->has('role') && $request->role !== '') {
                $query->where('users_role', $request->role);
            }

            // Apply status filter
            if ($request->has('status') && $request->status !== '') {
                // Expecting status to be one of: active, inactive, suspended
                $query->where('users_status', $request->status);
            }

            // Apply classroom filter (for students)
            if ($request->has('classroom') && $request->classroom !== '') {
                $query->whereHas('student', function($sq) use ($request) {
                    $sq->where('class_id', $request->classroom);
                });
            }

            // Get paginated results
            $users = $query->orderBy('users_first_name')->paginate(15);

            // Get enhanced stats
            $stats = [
                'total' => User::count(),
                'students' => User::where('users_role', 'student')->count(),
                'teachers' => User::where('users_role', 'teacher')->count(),
                'guardians' => User::where('users_role', 'guardian')->count(),
                'active' => User::where('users_status', 'active')->count(),
                'avgStudentScore' => Student::avg('students_current_score'),
                'homeroomTeachers' => Teacher::where('teachers_is_homeroom_teacher', true)->count(),
                'linkedStudents' => DB::table('tb_guardian_student')->count()
            ];

            return response()->json([
                'success' => true,
                'data' => $users,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการโหลดข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user details with enhanced relations
     */
    public function show($id)
    {
        try {
            $user = User::with([
                'student.classroom', 
                'teacher.assignedClass', 
                'guardian.students.user',
                'guardian.students.classroom'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'user' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลผู้ใช้'
            ], 404);
        }
    }

    /**
     * Update user information
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $rules = [
                'users_name_prefix' => 'nullable|string|max:20',
                'users_first_name' => 'required|string|max:100',
                'users_last_name' => 'required|string|max:100',
                // username field removed from schema, do not validate it
                'users_email' => 'nullable|email|max:150|unique:tb_users,users_email,' . $id . ',users_id',
                'users_phone_number' => 'nullable|string|max:30',
                'users_birthdate' => 'nullable|date',
                'users_active' => 'required|boolean',
                'users_status' => 'nullable|in:active,inactive,suspended',
                'new_password' => 'nullable|string|min:6',
            ];

            // Role-specific validation
            if ($user->users_role === 'student') {
                $rules['students_student_code'] = 'required|string|max:20|unique:tb_students,students_student_code,' . ($user->student->students_id ?? 0) . ',students_id';
                $rules['class_id'] = 'required|exists:tb_classes,classes_id';
                $rules['students_academic_year'] = 'nullable|string|max:20';
                $rules['stude,ntransferredts_gender'] = 'nullable|in:male,female,other';
                $rules['students_status'] = 'nullable|in:active,suspended,expelled,graduate, transferred';
                $rules['students_current_score'] = 'nullable|integer';
            } elseif ($user->users_role === 'teacher') {
                $rules['teachers_employee_code'] = 'nullable|string|max:20|unique:tb_teachers,teachers_employee_code,' . ($user->teacher->teachers_id ?? 0) . ',teachers_id';
                $rules['teachers_position'] = 'nullable|string|max:100';
                $rules['teachers_department'] = 'nullable|string|max:100';
                $rules['teachers_major'] = 'nullable|string|max:100';
                $rules['assigned_class_id'] = 'nullable|exists:tb_classes,classes_id';
                $rules['teachers_is_homeroom_teacher'] = 'nullable|boolean';
            }

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Update user
            $user->update([
                'users_name_prefix' => $request->users_name_prefix,
                'users_first_name' => $request->users_first_name,
                'users_last_name' => $request->users_last_name,
                'users_email' => $request->users_email,
                'users_phone_number' => $request->users_phone_number,
                'users_birthdate' => $request->users_birthdate,
                // Allow explicit status if provided; otherwise map boolean to enum
                'users_status' => $request->filled('users_status')
                    ? $request->users_status
                    : ($request->users_active ? 'active' : 'inactive'),
            ]);

            // Update password if provided
            if ($request->filled('new_password')) {
                $user->update([
                    'users_password' => Hash::make($request->new_password)
                ]);
            }

            // Update role-specific data
            if ($user->users_role === 'student' && $user->student) {
                $user->student->update([
                    'students_student_code' => $request->students_student_code,
                    'class_id' => $request->class_id,
                    'students_academic_year' => $request->students_academic_year,
                    'students_gender' => $request->students_gender,
                    'students_status' => $request->students_status,
                    'students_current_score' => $request->students_current_score,
                ]);
            } elseif ($user->users_role === 'teacher' && $user->teacher) {
                $user->teacher->update([
                    'teachers_employee_code' => $request->teachers_employee_code,
                    'teachers_position' => $request->teachers_position,
                    'teachers_department' => $request->teachers_department,
                    'teachers_major' => $request->teachers_major,
                    'assigned_class_id' => $request->assigned_class_id,
                    'teachers_is_homeroom_teacher' => (bool) $request->teachers_is_homeroom_teacher,
                ]);
            }

            // Optional: update guardian meta when role is guardian
            if ($user->users_role === 'guardian') {
                $guardian = $user->guardian;
                if ($guardian) {
                    $guardian->update([
                        'guardians_relationship_to_student' => $request->guardians_relationship_to_student,
                        'guardians_phone' => $request->guardians_phone,
                        'guardians_email' => $request->guardians_email,
                        'guardians_line_id' => $request->guardians_line_id,
                        'guardians_preferred_contact_method' => $request->guardians_preferred_contact_method,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'อัปเดตข้อมูลผู้ใช้เรียบร้อยแล้ว'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete user
     */
    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent admin from deleting themselves
            if ($user->users_id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถลบบัญชีของตัวเองได้'
                ], 400);
            }

            DB::beginTransaction();

            // Delete related records
            if ($user->student) {
                $user->student->delete();
            }
            if ($user->teacher) {
                $user->teacher->delete();
            }
            if ($user->guardian) {
                $user->guardian->delete();
            }

            $user->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ลบผู้ใช้เรียบร้อยแล้ว'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูล: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);

            // Prevent admin from deactivating themselves
            if ($user->users_id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถปิดใช้งานบัญชีของตัวเองได้'
                ], 400);
            }

            // Toggle between active and inactive (leave suspended unchanged unless reactivated)
            $user->users_status = $user->users_status === 'active' ? 'inactive' : 'active';
            $user->save();

            return response()->json([
                'success' => true,
                'message' => $user->users_status === 'active' ? 'เปิดใช้งานบัญชีแล้ว' : 'ปิดใช้งานบัญชีแล้ว',
                'status' => $user->users_status,
                'is_active' => $user->users_status === 'active'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการเปลี่ยนสถานะ: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin: Get students linked to a guardian user
     */
    public function getGuardianStudents($id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->users_role !== 'guardian') {
                return response()->json(['success' => false, 'message' => 'ผู้ใช้นี้ไม่ใช่ผู้ปกครอง'], 400);
            }
            $guardian = Guardian::firstOrCreate(['user_id' => $user->users_id]);
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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage()], 500);
        }
    }

    /**
     * Admin: Search students to link to guardian
     */
    public function searchStudents(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->users_role !== 'guardian') {
                return response()->json(['success' => false, 'message' => 'ผู้ใช้นี้ไม่ใช่ผู้ปกครอง'], 400);
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
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage()], 500);
        }
    }

    /**
     * Admin: Link a student to guardian
     */
    public function linkGuardianStudent(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->users_role !== 'guardian') {
                return response()->json(['success' => false, 'message' => 'ผู้ใช้นี้ไม่ใช่ผู้ปกครอง'], 400);
            }
            $request->validate([
                'student_id' => 'required|exists:tb_students,students_id'
            ]);
            $guardian = Guardian::firstOrCreate(['user_id' => $user->users_id]);
            $exists = DB::table('tb_guardian_student')
                ->where('guardian_id', $guardian->guardians_id)
                ->where('student_id', $request->student_id)
                ->exists();
            if ($exists) {
                return response()->json(['success' => true, 'message' => 'เชื่อมโยงแล้ว']);
            }
            DB::table('tb_guardian_student')->insert([
                'guardian_id' => $guardian->guardians_id,
                'student_id' => $request->student_id,
                'guardian_student_created_at' => now(),
            ]);
            return response()->json(['success' => true, 'message' => 'เชื่อมโยงนักเรียนสำเร็จ']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage()], 500);
        }
    }

    /**
     * Admin: Unlink a student from guardian
     */
    public function unlinkGuardianStudent($id, $studentId)
    {
        try {
            $user = User::findOrFail($id);
            if ($user->users_role !== 'guardian') {
                return response()->json(['success' => false, 'message' => 'ผู้ใช้นี้ไม่ใช่ผู้ปกครอง'], 400);
            }
            $guardian = Guardian::firstOrCreate(['user_id' => $user->users_id]);
            DB::table('tb_guardian_student')
                ->where('guardian_id', $guardian->guardians_id)
                ->where('student_id', $studentId)
                ->delete();
            return response()->json(['success' => true, 'message' => 'ยกเลิกการเชื่อมโยงแล้ว']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'เกิดข้อผิดพลาด: '.$e->getMessage()], 500);
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Reset to the standard onsite password so teachers can notify students directly
            $newPassword = '123456789';
            $user->users_password = Hash::make($newPassword);
            $user->save();

            return response()->json([
                'success' => true, 
                'message' => 'รีเซ็ตรหัสผ่านสำเร็จ',
                'new_password' => $newPassword
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false, 
                'message' => 'ไม่สามารถรีเซ็ตรหัสผ่านได้: ' . $e->getMessage()
            ], 500);
        }
    }

}
