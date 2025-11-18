<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>ระบบสารสนเทศจัดการคะแนนนักเรียน - หน้านักเรียน</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font: Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- App CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <!-- Student Dashboard Specific CSS -->
    <link href="{{ asset('css/student.css') }}" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-app: #3b82f6;
            --secondary-app: #64748b;
            --accent-app: #06b6d4;
            --success-app: #10b981;
            --warning-app: #f59e0b;
            --danger-app: #ef4444;
            --light-app: #f8fafc;
            --dark-app: #1e293b;
        }

        body {
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
        }

        .btn-primary-app {
            background: var(--primary-app);
            border-color: var(--primary-app);
            color: white;
        }

        .btn-primary-app:hover {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
        }

        .text-primary-app {
            color: var(--primary-app) !important;
        }

        .bg-primary-app {
            background-color: var(--primary-app) !important;
        }
        
        .card {
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }

        .footer {
            background: var(--dark-app);
            color: white;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary-app" href="{{ route('student.dashboard') }}">
                <i class="fas fa-graduation-cap me-2"></i>
                ระบบจัดการคะแนนพฤติกรรม
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @auth
                        @if(Auth::user()->users_role === 'student')
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('student.dashboard') }}">
                                    <i class="fas fa-home me-1"></i>แดชบอร์ด
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('student.settings') }}">
                                    <i class="fas fa-user-cog me-1"></i>ตั้งค่าบัญชี
                                </a>
                            </li>
                        @endif
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i>
                                {{ Auth::user()->users_first_name }}
                            </a>
                            <ul class="dropdown-menu">
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="fas fa-sign-out-alt me-1"></i>ออกจากระบบ
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <div class="app-container" style="margin-top: 80px;">
        <!-- Header (Mobile only) -->
        <header class="dashboard-header text-white py-3 d-lg-none">
            <div class="container">
                <h1 class="h4 mb-0">ระบบสารสนเทศจัดการคะแนนพฤติกรรมนักเรียน</h1>
            </div>
        </header>

        <!-- Main Content -->
        <div class="container py-4">
            <!-- Student Info Card -->
            <div class="app-card student-info-card mb-4 p-3">
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <div class="student-avatar bg-primary-app text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-user-graduate fa-2x"></i>
                        </div>
                    </div>
                    <div>
                        <h2 class="h5 mb-1">สวัสดี {{ $user->users_name_prefix }}{{ $user->users_first_name }} {{ $user->users_last_name }}</h2>
                        <p class="text-muted mb-0">
                            @if($student)
                                {{ (!empty($student->classes_level) && !empty($student->classes_room_number)) ? '' . $student->classes_level . '/' . $student->classes_room_number : 'ไม่ระบุชั้นเรียน' }}
                                รหัสนักเรียน {{ $student->students_student_code ?? 'ไม่ระบุ' }}
                            @else
                                ข้อมูลนักเรียนไม่สมบูรณ์
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <!-- Desktop Layout -->
            <div class="desktop-grid">
                <!-- Left Column: Points and Rank -->
                <div class="metrics-area">
                    <!-- Points Score Card -->
                    <div class="app-card stats-card p-3">
                        <div class="text-center">
                            <h3 class="h5 text-primary-app mb-3">คะแนนความประพฤติ</h3>
                            <p class="display-4 fw-bold mb-2 stats-value" id="behavior-points">
                                {{ $stats['current_score'] }}
                            </p>
                            <span class="badge {{ $stats['rank_status']['badge'] }}">{{ $stats['rank_status']['label'] }}</span>
                        </div>
                    </div>
                    
                    <!-- Class Rank Card -->
                    <div class="app-card stats-card p-3 mt-4">
                        <div class="text-center">
                            <h3 class="h5 text-primary-app mb-3">อันดับในห้องเรียน</h3>
                            <p class="display-4 fw-bold mb-2 stats-value" id="class-rank">{{ $stats['class_rank'] }}<span class="fs-6">/{{ $stats['total_students'] }}</span></p>
                            <span class="badge bg-secondary-app text-dark">{{ $stats['rank_status']['group'] }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- Middle Column: Behavior Chart -->
                <div class="chart-area">
                    <div class="app-card h-100 p-4">
                        <h3 class="h5 text-primary-app mb-3">สรุปคะแนนพฤติกรรม</h3>
                        <div class="chart-container desktop-chart">
                            <canvas id="behaviorChart"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Recent Activities -->
                <div class="activities-area">
                    <div class="app-card p-4 h-100">
                        <h3 class="h5 text-primary-app mb-3">กิจกรรมล่าสุด</h3>
                        <div class="activity-list">
                            @forelse($recent_activities as $activity)
                            <div class="activity-item d-flex py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                <div class="me-3">
                                    <div class="{{ $activity['badge_color'] }} rounded-circle activity-icon d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas {{ $activity['is_positive'] ? 'fa-plus' : 'fa-minus' }} text-white"></i>
                                    </div>
                                </div>
                                <div class="activity-content">
                                    <p class="mb-0 fw-medium">{{ $activity['title'] }}</p>
                                    <p class="text-muted small mb-0">โดย {{ $activity['teacher'] }} - {{ $activity['date'] }}</p>
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-info-circle mb-2 fa-2x"></i>
                                <p>ยังไม่มีกิจกรรมล่าสุดในระบบ</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Layout (Row-based) -->
            <div class="mobile-grid d-lg-none">
                <!-- Points Score Card -->
                <div class="app-card stats-card p-3 mb-4">
                    <div class="text-center">
                        <h3 class="h5 text-primary-app mb-3">คะแนนความประพฤติ</h3>
                        <p class="display-4 fw-bold mb-2 stats-value">{{ $stats['current_score'] }}</p>
                        <span class="badge {{ $stats['rank_status']['badge'] }}">{{ $stats['rank_status']['label'] }}</span>
                    </div>
                </div>
                
                <!-- Class Rank Card -->
                <div class="app-card stats-card p-3 mb-4">
                    <div class="text-center">
                        <h3 class="h5 text-primary-app mb-3">อันดับในห้องเรียน</h3>
                        <p class="display-4 fw-bold mb-2 stats-value">{{ $stats['class_rank'] }}<span class="fs-6">/{{ $stats['total_students'] }}</span></p>
                        <span class="badge bg-secondary-app text-dark">{{ $stats['rank_status']['group'] }}</span>
                    </div>
                </div>
                
                <!-- Behavior Chart -->
                <div class="app-card mb-4 p-4">
                    <h3 class="h5 text-primary-app mb-3">สรุปคะแนนพฤติกรรม</h3>
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="behaviorChartMobile"></canvas>
                    </div>
                </div>
                
                <!-- Recent Activities -->
                <div class="app-card p-4">
                    <h3 class="h5 text-primary-app mb-3">กิจกรรมล่าสุด</h3>
                    <div class="activity-list mobile-activities">
                        @forelse($recent_activities as $activity)
                        <div class="activity-item d-flex py-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                            <div class="me-3">
                                <div class="{{ $activity['badge_color'] }} rounded-circle activity-icon d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas {{ $activity['is_positive'] ? 'fa-plus' : 'fa-minus' }} text-white"></i>
                                </div>
                            </div>
                            <div class="activity-content">
                                <p class="mb-0 fw-medium">{{ $activity['title'] }}</p>
                                <p class="text-muted small mb-0">โดย {{ $activity['teacher'] }} - {{ $activity['date'] }}</p>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-info-circle mb-2 fa-2x"></i>
                            <p>ยังไม่มีกิจกรรมล่าสุดในระบบ</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Additional Student Information Section -->
            <div class="section-title mt-4 mb-3">
                <h2 class="h4 text-primary-app fw-bold">ข้อมูลเพิ่มเติม</h2>
            </div>
            
            <!-- Classroom Details & Behavior Summary Cards -->
            <div class="row">
                <!-- Classroom Details Card -->
                <div class="col-md-6 mb-4">
                    <div class="app-card h-100">
                        <div class="card-header bg-primary-app text-white py-3">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-school me-2"></i>
                                ข้อมูลห้องเรียน {{ $classroom_details['name'] ?? 'ไม่ระบุ' }}
                            </h3>
                        </div>
                        <div class="card-body p-4">
                            @if($classroom_details)
                                <div class="row mb-3">
                                    <div class="col">
                                        <div class="d-flex">
                                            <div class="info-icon me-3">
                                                <i class="fas fa-user-tie text-primary-app"></i>
                                            </div>
                                            <div>
                                                <div class="small text-muted">ครูประจำชั้น</div>
                                                <div class="fw-bold">{{ $classroom_details['teacher_name'] }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <div class="d-flex">
                                            <div class="info-icon me-3">
                                                <i class="fas fa-calendar-alt text-primary-app"></i>
                                            </div>
                                            <div>
                                                <div class="small text-muted">ปีการศึกษา</div>
                                                <div class="fw-bold">{{ $classroom_details['academic_year'] ?? '-' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="d-flex">
                                            <div class="info-icon me-3">
                                                <i class="fas fa-users text-primary-app"></i>
                                            </div>
                                            <div>
                                                <div class="small text-muted">จำนวนนักเรียน</div>
                                                <div class="fw-bold">{{ $classroom_details['total_students'] }} คน</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 fw-bold text-success">{{ $classroom_details['highest_score'] }}</div>
                                            <div class="small text-muted">คะแนนสูงสุดในห้อง</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-center">
                                            <div class="h4 fw-bold text-primary-app">{{ $classroom_details['average_score'] }}</div>
                                            <div class="small text-muted">คะแนนเฉลี่ยในห้อง</div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle mb-2 fa-2x"></i>
                                    <p>ไม่พบข้อมูลห้องเรียน</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Behavior Summary Card -->
                <div class="col-md-6 mb-4">
                    <div class="app-card h-100">
                        <div class="card-header bg-primary-app text-white py-3">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-clipboard-check me-2"></i>
                                สรุปพฤติกรรมของคุณ
                            </h3>
                        </div>
                        <div class="card-body p-4">
                            @if($behavior_summary && $behavior_summary['total_reports'] > 0)
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 border rounded-3 bg-light h-100">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 text-danger">
                                                    <i class="fas fa-minus-circle fa-lg"></i>
                                                </div>
                                                <div>
                                                    <div class="small text-muted">คะแนนที่ถูกหักรวม</div>
                                                    <div class="h4 mb-0 text-danger">-{{ number_format($behavior_summary['total_negative_points']) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <div class="p-3 border rounded-3 bg-light h-100">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3 text-secondary">
                                                    <i class="fas fa-clipboard-list fa-lg"></i>
                                                </div>
                                                <div>
                                                    <div class="small text-muted">จำนวนครั้งที่ถูกหัก</div>
                                                    <div class="h4 mb-0">{{ number_format($behavior_summary['negative_reports']) }} ครั้ง</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle mb-2 fa-2x"></i>
                                    <p>คุณยังไม่มีรายงานพฤติกรรมในระบบ</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Violation Distribution & Top Students -->
            <div class="row">
                <!-- Violation Distribution -->
                <div class="col-md-6 mb-4">
                    <div class="app-card h-100">
                        <div class="card-header bg-primary-app text-white py-3">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                สัดส่วนประเภทพฤติกรรม
                            </h3>
                        </div>
                        <div class="card-body p-4">
                            @if(isset($violation_distribution) && count($violation_distribution['data']) > 0 && $violation_distribution['labels'][0] !== 'ไม่มีข้อมูล')
                                <div class="chart-container" style="height: 250px; position: relative;">
                                    <canvas id="violationChart"></canvas>
                                </div>
                            @else
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-chart-pie mb-2 fa-3x"></i>
                                    <h5>ยังไม่มีข้อมูลพฤติกรรม</h5>
                                    <p class="mb-0">เมื่อมีการบันทึกพฤติกรรมแล้ว กราฟจะแสดงที่นี่</p>
                                    <div class="chart-container mt-3" style="height: 250px; position: relative;">
                                        <canvas id="violationChart"></canvas>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Top Students -->
                <div class="col-md-6 mb-4">
                    <div class="app-card h-100">
                        <div class="card-header bg-primary-app text-white py-3">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-crown me-2"></i>
                                อันดับคะแนนสูงสุดในห้อง
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($top_students as $student)
                                <li class="list-group-item p-3 {{ $student['is_current'] ? 'bg-light' : '' }}">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3" style="width: 32px;">
                                            @if($student['rank'] <= 3)
                                                <i class="fas fa-medal fa-lg {{ $student['rank'] == 1 ? 'text-warning' : ($student['rank'] == 2 ? 'text-secondary' : 'text-bronze') }}"></i>
                                            @else
                                                <span class="fw-bold">{{ $student['rank'] }}</span>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <span class="fw-bold {{ $student['is_current'] ? 'text-primary-app' : '' }}">
                                                {{ $student['name'] }}
                                                @if($student['is_current'])
                                                    <span class="badge bg-primary-app ms-2">คุณ</span>
                                                @endif
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge bg-light text-dark p-2">{{ $student['score'] }}</span>
                                        </div>
                                    </div>
                                </li>
                                @empty
                                <li class="list-group-item text-center py-4">
                                    <i class="fas fa-info-circle mb-2 fa-2x text-muted"></i>
                                    <p class="text-muted mb-0">ไม่พบข้อมูลนักเรียน</p>
                                </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Notifications from Database -->
            <div class="row">
                <!-- Notifications -->
                <div class="col-12 mb-4">
                    <div class="app-card">
                        <div class="card-header bg-primary-app text-white py-3">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-bell me-2"></i>
                                การแจ้งเตือนล่าสุด
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                @forelse($notifications as $notification)
                                <li class="list-group-item p-3 {{ $notification['is_read'] ? '' : 'bg-light' }}">
                                    <div class="d-flex">
                                        <div class="me-3">
                                            @php
                                                $icon = 'fa-info-circle';
                                                $color = 'text-primary';
                                                
                                                if($notification['type'] == 'warning') {
                                                    $icon = 'fa-exclamation-triangle';
                                                    $color = 'text-warning';
                                                } elseif($notification['type'] == 'success') {
                                                    $icon = 'fa-check-circle';
                                                    $color = 'text-success';
                                                } elseif($notification['type'] == 'danger') {
                                                    $icon = 'fa-exclamation-circle';
                                                    $color = 'text-danger';
                                                }
                                            @endphp
                                            <i class="fas {{ $icon }} fa-lg {{ $color }}"></i>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="fw-bold">{{ $notification['title'] }}</span>
                                                @if(!$notification['is_read'])
                                                    <span class="badge bg-primary ms-2">ใหม่</span>
                                                @endif
                                            </div>
                                            <p class="mb-1">{{ $notification['message'] }}</p>
                                            <div class="small text-muted">{{ $notification['created_at'] }}</div>
                                        </div>
                                    </div>
                                </li>
                                @empty
                                <li class="list-group-item text-center py-4">
                                    <i class="fas fa-info-circle mb-2 fa-2x text-muted"></i>
                                    <p class="text-muted mb-0">ไม่มีการแจ้งเตือนในขณะนี้</p>
                                </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navbar (Mobile Only) -->
        <nav class="bottom-navbar d-lg-none">
            <div class="container">
                <div class="row text-center">
                    <div class="col">
                        <a href="{{ route('student.dashboard') }}" class="nav-link text-primary-app active">
                            <i class="fas fa-home"></i>
                            <span>หน้าหลัก</span>
                        </a>
                    </div>
                    <div class="col">
                        <a href="{{ route('student.settings') }}" class="nav-link text-muted">
                            <i class="fas fa-user-cog"></i>
                            <span>ตั้งค่าบัญชี</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- แยก JavaScript ที่ปรับปรุงแล้ว -->
    <script src="{{ asset('js/student-dashboard.js') }}"></script>
    
    <!-- ส่วนที่ต้องใช้ข้อมูล Blade จาก Laravel -->
    <script>
        // กำหนดตัวแปร global เพื่อให้สามารถเข้าถึงได้จาก student-dashboard.js
        window.chartData = {
            labels: @json($chart_data['labels']),
            datasets: @json($chart_data['datasets'])
        };
        
        // เพิ่มข้อมูลสำหรับ Violation Distribution Chart
        window.violationData = @json($violation_distribution ?? [
            'labels' => ['ไม่มีข้อมูล'],
            'data' => [1],
            'colors' => ['#e9ecef']
        ]);
    </script>

    <!-- Hidden logout form for client-side logout submission -->
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
        @csrf
    </form>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        (function() {
            // Debug: แสดงข้อมูล `current_student` (จาก controller) เพื่อยืนยันว่าตัวแปรถูกส่งมาจากเซิร์ฟเวอร์
            // ใช้ `current_student` ถ้ามี เพราะบางส่วนของ view อาจใช้ตัวแปรชื่อ `student` ในลูป (shadowing)
            console.log('dashboard: blade student ->', @json(($current_student ?? $student) ?? null));

            // รับค่าสถานะจากตัวแปร Blade (รองรับทั้ง array / object / null) โดยใช้ data_get
            var rawStatus = @json(data_get(($current_student ?? $student), 'students_status', null));

            // แสดงค่าใน console เพื่อตรวจสอบ (ช่วย debug ได้)
            console.log('dashboard: raw student status ->', rawStatus);

            if (!rawStatus) return;

            // Normalize: trim + lowercase เพื่อป้องกันช่องว่างหรือตัวพิมพ์ใหญ่/เล็ก
            var studentStatus = String(rawStatus).trim().toLowerCase();
            console.log('dashboard: normalized studentStatus ->', studentStatus);

            if (studentStatus === 'active') return; // ปกติ ให้เข้าต่อ

            var title = '';
            var text = '';
            var icon = 'warning';

            switch (studentStatus) {
                case 'suspended':
                    title = 'พักการเรียน';
                    text = 'บัญชีของคุณถูกพักการเรียน ไม่สามารถใช้งานหน้านี้ได้ชั่วคราว โปรดติดต่อครูประจำชั้นหรือผู้ดูแลระบบเพื่อตรวจสอบข้อมูล';
                    icon = 'warning';
                    break;
                case 'expelled':
                    title = 'พ้นสภาพนักเรียน';
                    text = 'บัญชีของคุณไม่ได้เป็นนักเรียนในระบบอีกต่อไป (พ้นสภาพหรือลาออก) หากมีข้อสงสัยกรุณาติดต่อโรงเรียน';
                    icon = 'error';
                    break;
                case 'graduate':
                    title = 'จบการศึกษา';
                    text = 'ยินดีด้วย คุณได้จบการศึกษาแล้ว ระบบจะออกจากระบบเพื่อความปลอดภัย';
                    icon = 'success';
                    break;
                case 'transferred':
                    title = 'ย้ายสถานศึกษา';
                    text = 'บัญชีของคุณถูกบันทึกว่าที่ย้ายไปยังสถานศึกษาอื่น จึงไม่สามารถใช้งานระบบนี้ได้';
                    icon = 'info';
                    break;
                default:
                    title = 'สถานะบัญชีไม่ปกติ';
                    text = 'สถานะบัญชีของคุณไม่อนุญาตให้เข้าถึงส่วนนี้ โปรดติดต่อผู้ดูแลระบบ';
                    icon = 'warning';
            }

            function showAndRedirect() {
                Swal.fire({
                    title: title,
                    text: text,
                    icon: icon,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    confirmButtonText: 'ตกลง'
                }).then(function() {
                    // Submit the logout form to ensure the user is logged out server-side
                    var f = document.getElementById('logout-form');
                    if (f) {
                        f.submit();
                    } else {
                        // Fallback: go to login page
                        window.location.href = "{{ route('login') }}";
                    }
                });
            }

            // ถ้า DOM ยังโหลดไม่เสร็จ ให้รันหลัง DOMContentLoaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showAndRedirect);
            } else {
                // ถ้าโหลดแล้ว ให้รันทันที.
                showAndRedirect();
            }
        })();
    </script>
</body>
</html>