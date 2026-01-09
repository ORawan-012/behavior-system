<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\NotificationController; // เพิ่มบรรทัดนี้
// Import/export functionality now uses Excel/CSV instead

// หน้าหลัก
Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

// เส้นทางสำหรับผู้ที่ไม่ได้เข้าสู่ระบบ
Route::middleware('guest')->group(function () {
    // หน้าเข้าสู่ระบบ
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Route ที่ไม่ต้อง authentication
Route::get('/classes/filters/all', [ClassroomController::class, 'getFilters']);

// เส้นทางสำหรับผู้ที่เข้าสู่ระบบแล้ว
Route::middleware('auth')->group(function () {
    // ออกจากระบบ
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    // หน้าแดชบอร์ดหลัก - จะเปลี่ยนเส้นทางตามบทบาท
    Route::get('/dashboard', function () {
        $user = Auth::user();
        switch ($user->users_role) {
            case 'student':
                return redirect()->route('student.dashboard');
            case 'admin':
            case 'teacher':
                return redirect()->route('teacher.dashboard');
            case 'guardian':
                return redirect()->route('parent.dashboard');
            default:
                return redirect('/');
        }
    })->name('dashboard');
    
    // หน้าแดชบอร์ดของนักเรียน
    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
    
    // ระบบจัดการรหัสผ่านของนักเรียน
    Route::prefix('student')->middleware('auth')->group(function () {
        Route::get('/settings', [App\Http\Controllers\StudentPasswordController::class, 'showSettings'])
            ->name('student.settings');
        Route::post('/password/change', [App\Http\Controllers\StudentPasswordController::class, 'changePassword'])
            ->name('student.password.change');
    });
    
    // หน้าแดชบอร์ดของครู
    Route::get('/teacher/dashboard', [AuthController::class, 'dashboard'])->name('teacher.dashboard');
    
    // ระบบจัดการรหัสผ่านของครู
    Route::prefix('api/teacher')->middleware('auth')->group(function () {
        Route::get('/check-permission/{student}', [App\Http\Controllers\TeacherPasswordController::class, 'checkPermission'])
            ->name('teacher.check-permission');
        Route::post('/student/{student}/reset-password', [App\Http\Controllers\TeacherPasswordController::class, 'resetPassword'])
            ->name('teacher.student.reset-password');
    });
    
    // Behavior Reports routes สำหรับการอัปเดตและลบ
    Route::post('/api/behavior-reports/{id}/update', [App\Http\Controllers\BehaviorReportController::class, 'update'])
        ->name('api.behavior-reports.update');
    Route::post('/api/behavior-reports/{id}/delete', [App\Http\Controllers\BehaviorReportController::class, 'destroy'])
        ->name('api.behavior-reports.delete');
    
    // หน้าแดชบอร์ดของผู้ปกครอง - ใช้ ParentController
    Route::get('/parent/dashboard', [ParentController::class, 'dashboard'])->name('parent.dashboard');
    
    // Parent API routes
    Route::prefix('api/parent')->group(function () {
        Route::get('/student/{id}/reports', [ParentController::class, 'getStudentBehaviorReports']);
        Route::get('/student/{id}/stats', [ParentController::class, 'getStudentBehaviorStats']);
        Route::get('/student/{id}/chart', [ParentController::class, 'getStudentScoreChart']);
    });
});

// API Routes
Route::prefix('api')->middleware('auth')->group(function () {
    // Student routes
    Route::get('/students/{id}', [App\Http\Controllers\StudentApiController::class, 'show']);
    Route::put('/students/{id}', [App\Http\Controllers\StudentApiController::class, 'update']);
    
    // Behavior Report routes
    Route::prefix('behavior-reports')->group(function () {
        Route::post('/', [App\Http\Controllers\BehaviorReportController::class, 'store']);
        Route::get('/students/search', [App\Http\Controllers\BehaviorReportController::class, 'searchStudents']);
        Route::get('/recent', [App\Http\Controllers\BehaviorReportController::class, 'getRecentReports']);
        Route::get('/{id}', [App\Http\Controllers\BehaviorReportController::class, 'show']);
    });
    
    // Violation routes
    Route::prefix('violations')->group(function () {
        Route::get('/', [ViolationController::class, 'index']);
        Route::get('/all', [ViolationController::class, 'getAll']);
        Route::post('/', [ViolationController::class, 'store']);
        Route::get('/{id}', [ViolationController::class, 'show']);
        Route::put('/{id}', [ViolationController::class, 'update']);
        Route::delete('/{id}', [ViolationController::class, 'destroy']);
    });
    
    // Class routes
    Route::prefix('classes')->group(function () {
    Route::get('/', [ClassroomController::class, 'index']);
    Route::get('/all', [ClassroomController::class, 'all']);
        Route::get('/{id}', [ClassroomController::class, 'show'])->where('id', '[0-9]+');
        Route::put('/{id}', [ClassroomController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/{id}', [ClassroomController::class, 'destroy'])->where('id', '[0-9]+');
        Route::get('/{id}/students', [ClassroomController::class, 'getStudents'])->where('id', '[0-9]+');
        Route::get('/teachers/all', [ClassroomController::class, 'getAllTeachers']);
        Route::get('/{id}/violations/stats', [ClassroomController::class, 'getViolationStatistics'])->where('id', '[0-9]+');
        Route::get('/{id}/export', [ClassroomController::class, 'exportClassReport'])->where('id', '[0-9]+');
    });
    
    // Dashboard statistics routes - เพิ่มส่วนนี้
    Route::prefix('dashboard')->group(function () {
        Route::get('/trends', [DashboardController::class, 'getMonthlyTrends']);
        Route::get('/violations', [DashboardController::class, 'getViolationTypes']);
        Route::get('/stats', [DashboardController::class, 'getMonthlyStats']);
    Route::get('/laravel-log', [DashboardController::class, 'getLaravelLog'])->name('api.dashboard.laravel-log');
    });

    // Report filters
    Route::prefix('reports')->group(function () {
        Route::get('/available-months', [App\Http\Controllers\ReportController::class, 'availableMonths']);
    });

    // Student status sync removed (legacy functionality)
    
    // เพิ่มบรรทัดนี้
    Route::get('/students/{id}/report', [App\Http\Controllers\API\StudentReportController::class, 'generatePDF'])->middleware('auth');
    
    // User Management API routes (Admin only)
    Route::middleware(['admin'])->prefix('users')->group(function () {
        Route::get('/', [App\Http\Controllers\API\UserApiController::class, 'index']);
        Route::get('/{id}', [App\Http\Controllers\API\UserApiController::class, 'show']);
        // Frontend uses POST for update
        Route::post('/{id}', [App\Http\Controllers\API\UserApiController::class, 'update']);
        Route::delete('/{id}', [App\Http\Controllers\API\UserApiController::class, 'destroy']);
        // Frontend uses POST for toggle
        Route::post('/{id}/toggle-status', [App\Http\Controllers\API\UserApiController::class, 'toggleStatus']);
        Route::post('/{id}/reset-password', [App\Http\Controllers\API\UserApiController::class, 'resetPassword']);

    // Guardian student linking (admin managing guardian accounts)
    Route::get('/{id}/guardian/students', [App\Http\Controllers\API\UserApiController::class, 'getGuardianStudents']);
    Route::get('/{id}/guardian/students/search', [App\Http\Controllers\API\UserApiController::class, 'searchStudents']);
    Route::post('/{id}/guardian/students', [App\Http\Controllers\API\UserApiController::class, 'linkGuardianStudent']);
    Route::delete('/{id}/guardian/students/{studentId}', [App\Http\Controllers\API\UserApiController::class, 'unlinkGuardianStudent']);
    });
});

// เพิ่ม Route สำหรับรายงาน
Route::prefix('reports')->middleware(['auth'])->group(function () {
    Route::get('/monthly', [App\Http\Controllers\ReportController::class, 'monthlyReport'])->name('reports.monthly');
    Route::get('/risk-students', [App\Http\Controllers\ReportController::class, 'riskStudentsReport'])->name('reports.risk-students');
    Route::get('/all-behavior-data', [App\Http\Controllers\ReportController::class, 'allBehaviorDataReport'])->name('reports.all-behavior-data');
});

// Profile update route
Route::put('/teacher/profile/update', [App\Http\Controllers\TeacherController::class, 'updateProfile'])
     ->name('teacher.profile.update')
     ->middleware('auth');

// Teacher API routes for archived students
Route::prefix('api/teacher')->middleware('auth')->group(function () {
    Route::get('/archived-students', [App\Http\Controllers\TeacherController::class, 'getArchivedStudents'])
         ->name('api.teacher.archived-students');
    Route::get('/student-history/{studentId}', [App\Http\Controllers\TeacherController::class, 'getStudentHistory'])
         ->name('api.teacher.student-history');
});

// เพิ่ม Route สำหรับการแจ้งเตือนผู้ปกครองที่นี่
Route::match(['get','post'], '/notifications/parent', [NotificationController::class, 'sendParentNotification'])
    ->middleware('auth')
    ->name('notifications.parent');

// Excel Import API (in Dashboard, admin/teacher only)
Route::prefix('api')->middleware('auth')->group(function () {
    Route::prefix('import')->group(function () {
        Route::post('/excel/preview', [App\Http\Controllers\DashboardController::class, 'previewExcelImport'])
             ->name('api.import.excel.preview');
    Route::post('/excel/commit', [App\Http\Controllers\DashboardController::class, 'importExcel'])
         ->name('api.import.excel.commit');
    });
});

// Parent notification API routes
Route::prefix('api/parent')->middleware('auth')->group(function () {
    Route::get('/notifications', [ParentController::class, 'getNotifications']);
    Route::get('/notifications/unread-count', [ParentController::class, 'getUnreadNotificationCount']);
    Route::put('/notifications/{id}/read', [ParentController::class, 'markNotificationAsRead']);
    Route::put('/notifications/mark-all-read', [ParentController::class, 'markAllNotificationsAsRead']);
    
    // existing routes...
    Route::get('/student/{id}/reports', [ParentController::class, 'getStudentBehaviorReports']);
    Route::get('/student/{id}/stats', [ParentController::class, 'getStudentBehaviorStats']);
    Route::get('/student/{id}/chart', [ParentController::class, 'getStudentScoreChart']);

    // Guardian linked students (read-only for parents)
    Route::get('/guardian/students', [ParentController::class, 'getGuardianStudents']);
});
