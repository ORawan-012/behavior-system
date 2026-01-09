<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudentApiController;
use App\Http\Controllers\BehaviorReportController;
use App\Http\Controllers\ViolationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\API\StudentReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// เปลี่ยนจาก auth:sanctum เป็น auth เฉพาะสำหรับ PDF report
Route::get('/students/{id}/report', [StudentReportController::class, 'generatePDF'])->middleware('auth');

// Route สำหรับดึงประวัติของนักเรียนที่จบแล้ว (ใช้ web middleware)
Route::middleware(['auth'])->group(function () {
    Route::get('/students/{id}/graduated-history', [StudentApiController::class, 'getGraduatedHistory']);
});

// Student API routes อื่นๆ ใช้ auth สำหรับ web session
Route::middleware(['auth'])->group(function () {
    Route::get('/students/{id}', [StudentApiController::class, 'show']);
    Route::put('/students/{id}', [StudentApiController::class, 'update']);
    
    // Behavior Report routes
    Route::get('/behavior-reports/recent', [BehaviorReportController::class, 'getRecentReports']);
    Route::get('/behavior-reports/students/search', [BehaviorReportController::class, 'searchStudents']);
    Route::get('/behavior-reports/{id}', [BehaviorReportController::class, 'show']);
    Route::post('/behavior-reports', [BehaviorReportController::class, 'store']);
    
    // Violation routes
    Route::get('/violations/all', [ViolationController::class, 'getAll']);
    Route::get('/violations', [ViolationController::class, 'index']);
    Route::post('/violations', [ViolationController::class, 'store']);
    Route::get('/violations/{id}', [ViolationController::class, 'show']);
    Route::put('/violations/{id}', [ViolationController::class, 'update']);
    Route::delete('/violations/{id}', [ViolationController::class, 'destroy']);
        
        // Report filters
        Route::prefix('reports')->group(function () {
            Route::get('/available-months', [ReportController::class, 'availableMonths']);
        });

    Route::get('/dashboard/trends', [DashboardController::class, 'getMonthlyTrends']);
    Route::get('/dashboard/violations', [DashboardController::class, 'getViolationTypes']);
    Route::get('/dashboard/stats', [DashboardController::class, 'getMonthlyStats']);
    Route::get('/dashboard/laravel-log', [DashboardController::class, 'getLaravelLog'])->name('api.dashboard.laravel-log');
});