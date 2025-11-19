<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AcademicYearService
{
    /**
     * ตรวจสอบและคืนค่าปีการศึกษาปัจจุบัน
     */
    public function getCurrentAcademicYear()
    {
        return config('academic.current_academic_year');
    }

    /**
     * ตรวจสอบและคืนค่าภาคเรียนปัจจุบัน
     */
    public function getCurrentSemester()
    {
        $today = Carbon::now();
        
        // ตรวจสอบว่าอยู่ในช่วงภาคเรียนใด
        $semester = $this->determineSemesterFromDate($today);
        
        return $semester;
    }

    /**
     * กำหนดภาคเรียนจากวันที่
     */
    public function determineSemesterFromDate(Carbon $date)
    {
        $month = $date->month;
        $day = $date->day;
        
        $semester1Config = config('academic.semester_periods.1');
        $semester2Config = config('academic.semester_periods.2');
        
        // ตรวจสอบภาคเรียนที่ 1 (16 พ.ค. - 31 ต.ค.)
        if (
            ($month == $semester1Config['start_month'] && $day >= $semester1Config['start_day']) ||
            ($month > $semester1Config['start_month'] && $month < $semester1Config['end_month']) ||
            ($month == $semester1Config['end_month'] && $day <= $semester1Config['end_day'])
        ) {
            return 1;
        }
        
        // ตรวจสอบภาคเรียนที่ 2 (1 พ.ย. - 15 พ.ค. ปีถัดไป)
        if (
            ($month == $semester2Config['start_month'] && $day >= $semester2Config['start_day']) ||
            ($month > $semester2Config['start_month']) ||
            ($month < $semester2Config['end_month']) ||
            ($month == $semester2Config['end_month'] && $day <= $semester2Config['end_day'])
        ) {
            return 2;
        }
        
        // หากไม่อยู่ในช่วงใดเลย ให้คืนค่าภาคเรียนที่ 1
        return 1;
    }

    /**
     * ตรวจสอบว่าควรเลื่อนปีการศึกษาหรือไม่
     */
    public function shouldPromoteAcademicYear()
    {
        if (!config('academic.auto_promotion.enabled')) {
            return false;
        }

        $today = Carbon::now();
        $promotionConfig = config('academic.auto_promotion.promotion_date');
        
        // ตรวจสอบว่าเป็นวันที่เลื่อนชั้นหรือไม่
        if ($today->month == $promotionConfig['month'] && $today->day == $promotionConfig['day']) {
            return true;
        }
        
        return false;
    }

    /**
     * ได้รับข้อมูลสถานะปีการศึกษาและภาคเรียนปัจจุบัน
     */
    public function getAcademicStatus()
    {
        $currentYear = $this->getCurrentAcademicYear();
        $currentSemester = $this->getCurrentSemester();
        
        return [
            'academic_year' => $currentYear,
            'semester' => $currentSemester,
            'semester_name' => config("academic.semester_periods.{$currentSemester}.name"),
            'display_text' => str_replace(
                [':year', ':semester'],
                [$currentYear, $currentSemester],
                config('academic.messages.current_academic_info')
            )
        ];
    }

    /**
     * ตรวจสอบการแจ้งเตือนต่างๆ
     */
    public function getNotifications()
    {
        $notifications = [];
        $today = Carbon::now();
        
        // ตรวจสอบการแจ้งเตือนใกล้สิ้นสุดภาคเรียน
        $semesterEndWarning = $this->checkSemesterEndWarning($today);
        if ($semesterEndWarning) {
            $notifications[] = $semesterEndWarning;
        }
        
        // ตรวจสอบการแจ้งเตือนใกล้เริ่มต้นภาคเรียนใหม่
        $semesterStartWarning = $this->checkSemesterStartWarning($today);
        if ($semesterStartWarning) {
            $notifications[] = $semesterStartWarning;
        }
        
        return $notifications;
    }

    /**
     * ตรวจสอบการแจ้งเตือนใกล้สิ้นสุดภาคเรียน
     */
    private function checkSemesterEndWarning(Carbon $today)
    {
        $currentSemester = $this->getCurrentSemester();
        $warningDays = config('academic.notifications.semester_end_warning_days');
        
        $semesterConfig = config("academic.semester_periods.{$currentSemester}");
        
        // คำนวณวันที่สิ้นสุดภาคเรียน
        $endDate = Carbon::create(
            $today->year,
            $semesterConfig['end_month'],
            $semesterConfig['end_day']
        );
        
        // หากเป็นภาคเรียนที่ 2 และเดือนสิ้นสุดน้อยกว่าเดือนปัจจุบัน แสดงว่าเป็นปีถัดไป
        if ($currentSemester == 2 && $semesterConfig['end_month'] < $today->month) {
            $endDate = $endDate->addYear();
        }
        
        $daysUntilEnd = $today->diffInDays($endDate, false);
        
        if ($daysUntilEnd > 0 && $daysUntilEnd <= $warningDays) {
            return [
                'type' => 'warning',
                'message' => str_replace(
                    [':semester', ':days'],
                    [$currentSemester, $daysUntilEnd],
                    config('academic.messages.semester_end_warning')
                ),
                'days_remaining' => $daysUntilEnd
            ];
        }
        
        return null;
    }

    /**
     * ตรวจสอบการแจ้งเตือนใกล้เริ่มต้นภาคเรียนใหม่
     */
    private function checkSemesterStartWarning(Carbon $today)
    {
        $currentSemester = $this->getCurrentSemester();
        $nextSemester = $currentSemester == 1 ? 2 : 1;
        $warningDays = config('academic.notifications.semester_start_warning_days');
        
        $nextSemesterConfig = config("academic.semester_periods.{$nextSemester}");
        
        // คำนวณวันที่เริ่มต้นภาคเรียนถัดไป
        $startDate = Carbon::create(
            $today->year,
            $nextSemesterConfig['start_month'],
            $nextSemesterConfig['start_day']
        );
        
        // หากเป็นการเปลี่ยนจากภาคเรียนที่ 1 ไป 2 และเดือนเริ่มต้นน้อยกว่าเดือนปัจจุบัน
        if ($currentSemester == 1 && $nextSemesterConfig['start_month'] < $today->month) {
            $startDate = $startDate->addYear();
        }
        
        $daysUntilStart = $today->diffInDays($startDate, false);
        
        if ($daysUntilStart > 0 && $daysUntilStart <= $warningDays) {
            return [
                'type' => 'info',
                'message' => str_replace(
                    [':semester', ':days'],
                    [$nextSemester, $daysUntilStart],
                    config('academic.messages.semester_start_warning')
                ),
                'days_remaining' => $daysUntilStart
            ];
        }
        
        return null;
    }

    /**
     * อัปเดตปีการศึกษาและภาคเรียนในการตั้งค่า
     */
    public function updateAcademicSettings($year = null, $semester = null)
    {
        if ($year) {
            Config::set('academic.current_academic_year', $year);
        }
        
        if ($semester) {
            Config::set('academic.current_semester', $semester);
        }
        
        // บันทึก Log
        Log::info('Academic settings updated', [
            'academic_year' => $year ?? $this->getCurrentAcademicYear(),
            'semester' => $semester ?? $this->getCurrentSemester(),
            'updated_at' => now()
        ]);
    }

    /**
     * ล้าง Cache ที่เกี่ยวข้องกับปีการศึกษา
     */
    public function clearAcademicCache()
    {
        Cache::forget('academic_status');
        Cache::forget('academic_notifications');
    }

    /**
     * ได้รับข้อมูลสถานะแบบ Cache
     */
    public function getCachedAcademicStatus()
    {
        return Cache::remember('academic_status', 3600, function () {
            return $this->getAcademicStatus();
        });
    }

    /**
     * ได้รับการแจ้งเตือนแบบ Cache
     */
    public function getCachedNotifications()
    {
        return Cache::remember('academic_notifications', 1800, function () {
            return $this->getNotifications();
        });
    }

    /**
     * แปลงเลขเดือนเป็นชื่อเดือนภาษาไทย
     */
    public function getThaiMonth($monthNumber)
    {
        $thaiMonths = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
        ];
        return $thaiMonths[(int)$monthNumber] ?? '';
    }
}
