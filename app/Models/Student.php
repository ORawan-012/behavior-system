<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

/**
 * Student Model
 * 
 * จัดการข้อมูลนักเรียน
 * 
 * @property int $students_id
 * @property int $user_id
 * @property string $students_student_code
 * @property int|null $class_id
 * @property string $students_academic_year
 * @property int $students_current_score
 * @property string $students_status
 * @property string $students_gender
 * @property \Carbon\Carbon $students_created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property-read User $user
 * @property-read Classroom|null $classroom
 * @property-read \Illuminate\Database\Eloquent\Collection<Guardian> $guardians
 * @property-read \Illuminate\Database\Eloquent\Collection<BehaviorReport> $behaviorReports
 */
class Student extends Model
{
    use HasFactory;

    // Status Constants
    const STATUS_ACTIVE = 'active';
    const STATUS_GRADUATED = 'graduated'; // or 'graduate' based on DB usage, normalizing to 'graduated'
    const STATUS_TRANSFERRED = 'transferred';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_EXPELLED = 'expelled';

    /**
     * Get all available statuses
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_ACTIVE,
            self::STATUS_GRADUATED,
            self::STATUS_TRANSFERRED,
            self::STATUS_SUSPENDED,
            self::STATUS_EXPELLED,
        ];
    }

    /**
     * Get status label in Thai
     */
    public static function getStatusLabel($status)
    {
        $labels = [
            self::STATUS_ACTIVE => 'กำลังศึกษา',
            self::STATUS_GRADUATED => 'จบการศึกษา',
            'graduate' => 'จบการศึกษา', // Handle legacy/inconsistent data
            self::STATUS_TRANSFERRED => 'ย้ายโรงเรียน',
            self::STATUS_SUSPENDED => 'พักการเรียน',
            self::STATUS_EXPELLED => 'ถูกไล่ออก',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Normalize status input
     */
    public static function normalizeStatus($status)
    {
        if (!$status) return $status;
        
        $status = strtolower(trim($status));
        
        $map = [
            'graduate' => self::STATUS_GRADUATED,
            'graduated' => self::STATUS_GRADUATED,
            'expelled' => self::STATUS_EXPELLED,
            'suspended' => self::STATUS_SUSPENDED,
            'transferred' => self::STATUS_TRANSFERRED,
            'active' => self::STATUS_ACTIVE
        ];

        return $map[$status] ?? $status;
    }

    /**
     * ตารางที่เชื่อมโยง
     */
    protected $table = 'tb_students';
    
    /**
     * Primary key
     */
    protected $primaryKey = 'students_id';
    
    /**
     * ปิดการใช้ timestamps อัตโนมัติ
     */
    public $timestamps = false;
    
    /**
     * ฟิลด์ที่สามารถ mass assignment ได้
     */
    protected $fillable = [
        'user_id',
        'students_student_code',
        'class_id',
        'students_academic_year',
        'students_current_score',
        'students_status',
        'students_gender'
    ];

    /**
     * Cast attributes ให้เป็น type ที่เหมาะสม
     */
    protected $casts = [
        'students_id' => 'integer',
        'user_id' => 'integer',
        'class_id' => 'integer',
        'students_current_score' => 'integer',
        'students_created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * ความสัมพันธ์กับ User (Many-to-One)
     * 
     * @return BelongsTo<User, Student>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'users_id');
    }

    /**
     * ความสัมพันธ์กับ Classroom (Many-to-One)
     * 
     * @return BelongsTo<Classroom, Student>
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id', 'classes_id');
    }

    /**
     * ความสัมพันธ์กับ Guardians (Many-to-Many)
     * 
     * @return BelongsToMany<Guardian>
     */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(Guardian::class, 'tb_guardian_student', 'student_id', 'guardian_id')
                    ->withPivot('guardian_student_created_at');
    }

    /**
     * ความสัมพันธ์กับ Guardian หลัก (คนแรก)
     * สำหรับการเข้าถึงผู้ปกครองหลักได้ง่ายขึ้น
     * 
     * @return Guardian|null
     */
    public function getGuardianAttribute(): ?Guardian
    {
        return $this->guardians()->first();
    }

    /**
     * ความสัมพันธ์กับ BehaviorReports (One-to-Many)
     * 
     * @return HasMany<BehaviorReport>
     */
    public function behaviorReports(): HasMany
    {
        return $this->hasMany(BehaviorReport::class, 'student_id', 'students_id');
    }

    /**
     * ดึงผู้ปกครองหลัก (คนแรก)
     * 
     * @return Guardian|null
     */
    public function getMainGuardian(): ?Guardian
    {
        return $this->guardians()->first();
    }

    /**
     * Accessor: รูปโปรไฟล์ของนักเรียน
     * 
     * @return string|null
     */
    public function getUserProfileImageAttribute(): ?string
    {
        if ($this->user && $this->user->users_profile_image) {
            return $this->user->users_profile_image;
        }
        return null;
    }

    /**
     * Accessor: ชื่อเต็มของนักเรียน
     * 
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        if (!$this->user) {
            return 'ไม่มีข้อมูล';
        }
        
        $prefix = $this->user->users_name_prefix ?? '';
        $firstName = $this->user->users_first_name ?? '';
        $lastName = $this->user->users_last_name ?? '';
        
        return trim($prefix . $firstName . ' ' . $lastName) ?: 'ไม่มีข้อมูล';
    }

    /**
     * Accessor: ชื่อห้องเรียนแบบเต็ม
     * 
     * @return string
     */
    public function getClassroomNameAttribute(): string
    {
        if (!$this->classroom) {
            return 'ไม่ระบุห้องเรียน';
        }
        
        return $this->classroom->classes_level . '/' . $this->classroom->classes_room_number;
    }

    /**
     * Accessor: สถานะคะแนนความประพฤติ
     * 
     * @return string
     */
    public function getScoreStatusAttribute(): string
    {
        $score = $this->students_current_score ?? 100;
        
        if ($score >= 90) return 'ดีเยี่ยม';
        if ($score >= 80) return 'ดีมาก';
        if ($score >= 70) return 'ดี';
        if ($score >= 60) return 'พอใช้';
        if ($score >= 40) return 'ปรับปรุง';
        
        return 'ต้องปรับปรุงเร่งด่วน';
    }

    /**
     * Scope: นักเรียนที่มีสถานะ active
     * 
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('students_status', 'active');
    }

    /**
     * Scope: นักเรียนในห้องเรียนที่ระบุ
     * 
     * @param Builder $query
     * @param int $classId
     * @return Builder
     */
    public function scopeInClass(Builder $query, int $classId): Builder
    {
        return $query->where('class_id', $classId);
    }

    /**
     * Scope: นักเรียนที่มีคะแนนต่ำกว่าที่ระบุ
     * 
     * @param Builder $query
     * @param int $score
     * @return Builder
     */
    public function scopeLowScore(Builder $query, int $score = 60): Builder
    {
        return $query->where('students_current_score', '<', $score);
    }

    /**
     * Scope: นักเรียนตามปีการศึกษา
     * 
     * @param Builder $query
     * @param string $academicYear
     * @return Builder
     */
    public function scopeByAcademicYear(Builder $query, string $academicYear): Builder
    {
        return $query->where('students_academic_year', $academicYear);
    }

    /**
     * คำนวณอันดับในห้องเรียน
     * 
     * @return int
     */
    public function getClassRank(): int
    {
        if (!$this->class_id) {
            return 1;
        }

        return self::where('class_id', $this->class_id)
                   ->where('students_current_score', '>', $this->students_current_score ?? 0)
                   ->count() + 1;
    }

    /**
     * ตรวจสอบว่าต้องการการติดตามพิเศษหรือไม่
     * 
     * @return bool
     */
    public function needsSpecialAttention(): bool
    {
        $score = $this->students_current_score ?? 100;
        return $score < 40;
    }
}