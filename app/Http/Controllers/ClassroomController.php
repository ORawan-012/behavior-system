<?php

namespace App\Http\Controllers;

use App\Models\ClassRoom; // ตรวจสอบว่ามีบรรทัดนี้
use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    /**
     * แสดงรายการห้องเรียนทั้งหมด (เฉพาะห้องที่มีนักเรียน)
     * - ครู: เห็นได้เฉพาะห้องของตัวเอง
     * - แอดมิน: เห็นได้ทุกห้อง
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */    public function index(Request $request)
    {
        try {
            // ระบุให้โหลด teacher และ teacher.user มาด้วย
            $query = ClassRoom::with(['teacher', 'teacher.user'])
                               ->has('students'); // แสดงเฉพาะห้องที่มีนักเรียน
            
            $searchTerm = $request->get('search', '');
            $academicYear = $request->get('academicYear', '');
            $level = $request->get('level', '');
            $perPage = $request->get('perPage', 10);

            // แยกสิทธิ์การเห็นข้อมูล
            $user = auth()->user();
            if ($user && $user->users_role === 'teacher') {
                // หา teacher id จาก users_id
                $teacher = Teacher::where('users_id', $user->users_id)->first();
                if ($teacher) {
                    $query->where('teachers_id', $teacher->teachers_id);
                } else {
                    // ถ้าไม่พบ teacher mapping ให้คืนลิสต์ว่าง
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'data' => [],
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => (int)$perPage,
                            'total' => 0
                        ],
                        'message' => 'ดึงข้อมูลสำเร็จ'
                    ]);
                }
            }
            
            // ค้นหาตาม search term
            if (!empty($searchTerm)) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('classes_level', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('classes_room_number', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('teacher.user', function($subQ) use ($searchTerm) {
                          $subQ->where(DB::raw("CONCAT(users_first_name, ' ', users_last_name)"), 'LIKE', "%{$searchTerm}%");
                      });
                });
            }
            
            // กรองตามระดับชั้น
            if (!empty($level)) {
                $query->where('classes_level', $level);
            }
            
            // เรียงตามระดับชั้น (กำหนดลำดับแน่นอน) แล้วตามเลขห้อง (ตัวเลข)
            $classes = $query
                               ->orderByRaw("FIELD(classes_level, 'ม.1','ม.2','ม.3','ม.4','ม.5','ม.6')")
                               ->orderByRaw('CAST(classes_room_number AS UNSIGNED)')
                               ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $classes,
                'message' => 'ดึงข้อมูลสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียน',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // store() removed: add-class feature is fully disabled

    /**
     * Return all classrooms with students, without pagination.
     * Sorted by level and room number for use in dropdowns.
     */
    public function all()
    {
        try {
            $classes = ClassRoom::has('students') // เฉพาะห้องที่มีนักเรียน
                ->orderByRaw("FIELD(classes_level, 'ม.1','ม.2','ม.3','ม.4','ม.5','ม.6')")
                ->orderByRaw('CAST(classes_room_number AS UNSIGNED)')
                ->get(['classes_id','classes_level','classes_room_number']);

            return response()->json([
                'success' => true,
                'data' => $classes,
                'message' => 'ดึงข้อมูลห้องเรียนทั้งหมดสำเร็จ'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลห้องเรียนทั้งหมด',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * แสดงข้อมูลห้องเรียนตาม ID
     * - ครูดูได้เฉพาะห้องของตัวเอง
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // แก้จาก Classroom เป็น ClassRoom
            $classroom = ClassRoom::with(['teacher', 'teacher.user'])
                                  ->withCount('students')
                                  ->findOrFail($id);

            // เฉพาะครูให้ดูได้เฉพาะห้องตัวเอง
            $user = auth()->user();
            if ($user && $user->users_role === 'teacher') {
                $teacher = Teacher::where('users_id', $user->users_id)->first();
                if (!$teacher || $classroom->teachers_id !== $teacher->teachers_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลห้องเรียนนี้'
                    ], 403);
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $classroom,
                'message' => 'ดึงข้อมูลสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่พบข้อมูลห้องเรียน',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * อัพเดทข้อมูลห้องเรียน (เฉพาะแอดมิน) — อนุญาตให้เปลี่ยนครูประจำชั้นเท่านั้น
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // เฉพาะ admin เท่านั้น
            if (!auth()->check() || auth()->user()->users_role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสิทธิ์ดำเนินการ'
                ], 403);
            }

            // อนุญาตเฉพาะ teacher_id
            $validator = Validator::make($request->all(), [
                'teacher_id' => 'required|exists:tb_teachers,teachers_id',
            ], [
                'teacher_id.required' => 'กรุณาเลือกครูประจำชั้น',
                'teacher_id.exists' => 'ไม่พบข้อมูลครูที่เลือก',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ข้อมูลไม่ถูกต้อง',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            // แก้จาก Classroom เป็น ClassRoom
            $classroom = ClassRoom::findOrFail($id);

            $oldTeacherId = $classroom->teachers_id; // เก็บครูเก่า (เผื่ออนาคตต้องใช้)
            $newTeacherId = (int) $request->teacher_id;

            // อัพเดทครูประจำชั้นในตารางห้องเรียน
            $classroom->teachers_id = $newTeacherId;
            $classroom->save();

            // หากมี flag ขอให้ตั้งเป็นครูประจำชั้น -> ตั้งค่า teachers_is_homeroom_teacher = 1
            if ($request->boolean('set_homeroom_for_teacher')) {
                $teacher = Teacher::find($newTeacherId);
                if ($teacher) {
                    // (1) ยกเลิกสถานะครูประจำชั้นของครูคนเก่าที่ผูกกับห้องนี้ (ถ้าไม่ต้องการให้หลายคนเป็น homeroom พร้อมกัน)
                    if ($oldTeacherId && $oldTeacherId !== $newTeacherId) {
                        Teacher::where('teachers_id', $oldTeacherId)
                            ->where('teachers_is_homeroom_teacher', true)
                            ->update(['teachers_is_homeroom_teacher' => false]);
                    }

                    // (2) อัปเดต assigned_class_id ให้สอดคล้อง (เผื่อ UI ส่วนอื่นใช้)
                    if ($teacher->assigned_class_id !== $classroom->classes_id) {
                        $teacher->assigned_class_id = $classroom->classes_id;
                    }

                    // (3) ตั้งสถานะ homeroom ให้ครูใหม่
                    if (!$teacher->teachers_is_homeroom_teacher) {
                        $teacher->teachers_is_homeroom_teacher = true;
                    }
                    $teacher->save();
                }
            }

            // หากต้องการป้องกันไม่ให้ครูคนอื่นยังคงเป็น homeroom ของห้องนี้ (กรณีข้อมูลสกปรก) สามารถเพิ่ม cleanup เพิ่มเติมได้

            // โหลดความสัมพันธ์ครูใหม่สำหรับ response
            $classroom->load(['teacher', 'teacher.user']);

            return response()->json([
                'success' => true,
                'data' => [
                    'classroom' => $classroom,
                    'updated_teacher_id' => $newTeacherId,
                    'homeroom_flag_applied' => $request->boolean('set_homeroom_for_teacher')
                ],
                'message' => 'อัพเดทข้อมูลห้องเรียนสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการอัพเดทข้อมูลห้องเรียน',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ลบห้องเรียน (เฉพาะแอดมิน)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // เฉพาะ admin เท่านั้น
            if (!auth()->check() || auth()->user()->users_role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่มีสิทธิ์ดำเนินการ'
                ], 403);
            }
            // แก้จาก Classroom เป็น ClassRoom
            $classroom = ClassRoom::findOrFail($id);
            
            // ตรวจสอบว่ามีนักเรียนในห้องหรือไม่
            $studentsCount = $classroom->students()->count();
            if ($studentsCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "ไม่สามารถลบห้องเรียนได้ เนื่องจากยังมีนักเรียนในห้องเรียนจำนวน {$studentsCount} คน",
                    'data' => [
                        'students_count' => $studentsCount
                    ]
                ], 422);
            }
            
            $classroom->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'ลบข้อมูลห้องเรียนสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการลบข้อมูลห้องเรียน',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงข้อมูลนักเรียนในห้องเรียน
     * - ครูดูได้เฉพาะห้องของตัวเอง
     *
     * @param int $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStudents($id, Request $request)
    {
        try {
            $classroom = ClassRoom::findOrFail($id); // Ensure ClassRoom model is used

            // เฉพาะครูให้ดูได้เฉพาะห้องตัวเอง
            $user = auth()->user();
            if ($user && $user->users_role === 'teacher') {
                $teacher = Teacher::where('users_id', $user->users_id)->first();
                if (!$teacher || $classroom->teachers_id !== $teacher->teachers_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'ไม่มีสิทธิ์เข้าถึงข้อมูลห้องเรียนนี้'
                    ], 403);
                }
            }
            $searchTerm = $request->get('search', '');
            $perPage = $request->get('perPage', 10); // Default to 10 students per page
            
            // Use eager loading for the 'user' relationship
            $query = Student::with('user')->where('class_id', $id);
        
            if (!empty($searchTerm)) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('students_student_code', 'LIKE', "%{$searchTerm}%")
                      // Search within the related 'user' table
                      ->orWhereHas('user', function($userQuery) use ($searchTerm) {
                          $userQuery->where('users_first_name', 'LIKE', "%{$searchTerm}%")
                                    ->orWhere('users_last_name', 'LIKE', "%{$searchTerm}%")
                                    ->orWhere(DB::raw("CONCAT(IFNULL(users_name_prefix, ''), users_first_name, ' ', users_last_name)"), 'LIKE', "%{$searchTerm}%");
                      });
                });
            }
            
            // Order by student's first name via the user relationship
            $query->join('tb_users', 'tb_students.user_id', '=', 'tb_users.users_id')
                  ->orderBy('tb_users.users_first_name')
                  ->orderBy('tb_users.users_last_name')
                  ->select('tb_students.*'); // Select only student columns after join for ordering

            $students = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $students, // This will now include the nested 'user' object for each student
                'message' => 'ดึงข้อมูลนักเรียนสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching students for class ' . $id . ': ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลนักเรียน',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * ดึงข้อมูลครูทั้งหมดสำหรับ dropdown
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllTeachers()
    {
        try {
            $teachers = Teacher::join('tb_users', 'tb_teachers.users_id', '=', 'tb_users.users_id')
                             ->select(
                                 'tb_teachers.teachers_id', 
                                 'tb_users.users_first_name',
                                 'tb_users.users_last_name',
                                 'tb_users.users_name_prefix',
                                 'tb_teachers.teachers_position',
                                 'tb_users.users_profile_image'
                             )
                             ->orderBy('tb_users.users_first_name')
                             ->get();
                             
            return response()->json([
                'success' => true,
                'data' => $teachers,
                'message' => 'ดึงข้อมูลครูทั้งหมดสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลครู',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * ดึงข้อมูลระดับชั้นทั้งหมดสำหรับตัวกรอง
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFilters()
    {
        try {
            $levels = ClassRoom::select('classes_level')
                             ->distinct()
                             ->orderBy('classes_level')
                             ->pluck('classes_level');
                             
            return response()->json([
                'success' => true,
                'data' => [
                    'levels' => $levels
                ],
                'message' => 'ดึงข้อมูลตัวกรองสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลตัวกรอง',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ดึงสถิติการกระทำผิดของห้องเรียน
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getViolationStatistics($id)
    {
        try {
            // แก้จาก Classroom เป็น ClassRoom
            $classroom = ClassRoom::findOrFail($id);
            
            $stats = DB::table('tb_behavior_reports as br')
                ->join('tb_students as s', 'br.student_id', '=', 's.students_id')
                ->join('tb_violations as v', 'br.violation_id', '=', 'v.violations_id')
                ->where('s.class_id', $id)
                ->select('v.violations_id', 'v.violations_name as name', 
                         DB::raw('count(*) as count'),
                         DB::raw('avg(s.students_current_score) as avg_score'))
                ->groupBy('v.violations_id', 'v.violations_name')
                ->orderByDesc('count')
                ->limit(5)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'ดึงข้อมูลสถิติสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูลสถิติ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ส่งออกรายงานห้องเรียนเป็น PDF
     * 
     * @param int $id
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function exportClassReport($id)
    {
        try {
            // แก้จาก Classroom เป็น ClassRoom
            $classroom = ClassRoom::with(['teacher.user', 'students.user'])
                            ->withCount('students')
                            ->findOrFail($id);
            
            // ดึงสถิติการกระทำผิด
            $violations = DB::table('tb_behavior_reports as br')
                ->join('tb_students as s', 'br.student_id', '=', 's.students_id')
                ->join('tb_violations as v', 'br.violation_id', '=', 'v.violations_id')
                ->where('s.class_id', $id)
                ->select('v.violations_name', DB::raw('count(*) as count'))
                ->groupBy('v.violations_name')
                ->orderByDesc('count')
                ->get();
            
            // ส่งกลับข้อมูล JSON แทน PDF ก่อน
            return response()->json([
                'success' => true,
                'data' => [
                    'classroom' => $classroom,
                    'violations' => $violations,
                    'date' => now()->format('d/m/Y')
                ],
                'message' => 'ดึงข้อมูลรายงานสำเร็จ'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'เกิดข้อผิดพลาดในการสร้างรายงาน',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}