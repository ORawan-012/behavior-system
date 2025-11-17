<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการพฤติกรรม | แดชบอร์ดครู</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Google Font: Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js: ไม่มีไฟล์ CSS ที่จำเป็น จึงเอา link ที่โหลดไม่ได้ออก -->
    <!-- App CSS -->
    <link href="/css/app.css" rel="stylesheet">
    <!-- Dashboard CSS -->
    <link href="/css/teacher-dashboard.css" rel="stylesheet">
    <link href="/css/loading-effects.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <!-- Teacher Dashboard Custom Styles -->
    <link href="/css/teacher-dashboard-styles.css" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        /* Slightly wider than modal-lg without being full xl */
        .modal-lg-plus { max-width: 940px; }
        /* Minimal, formal user detail layout */
        #userDetailSlider .modal-content { border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); }
        #userDetailSlider .profile-header { padding: 24px; border-bottom: 1px solid #eee; text-align: center; }
        #userDetailSlider .profile-header img { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        #userDetailSlider .profile-header h5 { margin: 8px 0 4px; font-weight: 600; }
        #userDetailSlider .profile-header .badge { font-weight: 500; }
        #userDetailSlider .section { padding: 24px; }
        #userDetailSlider .section-title { font-size: 14px; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 12px; }
        #userDetailSlider .kv { display: grid; grid-template-columns: 160px 1fr; gap: 8px 16px; align-items: center; font-size: 14px; }
        #userDetailSlider .kv .k { color: #6c757d; }
        #userDetailSlider .kv .v { color: #212529; }
        #userDetailSlider .actions { padding: 16px 24px 28px; display: grid; gap: 8px; }
        #userDetailSlider .card { transition: all 0.3s ease; }
        #userDetailSlider .card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        #userDetailSlider .form-control:focus, #userDetailSlider .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25); }
        #userDetailSlider .btn { border-radius: 8px; font-weight: 500; }
        #userDetailSlider .form-check-input:checked { background-color: #198754; border-color: #198754; }
        @media (max-width: 576px){
            #userDetailSlider .kv { grid-template-columns: 1fr; }
            #userDetailSlider .modal-lg-plus { max-width: 95%; }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar (Desktop) -->
        <div class="sidebar d-none d-lg-flex">
            <div class="sidebar-header">
                <div class="logo-container">
                    <img src="{{ asset('images/logo.png') }}" alt="โลโก้โรงเรียน" class="logo"
                        onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzE2M0FENyIvPgo8cGF0aCBkPSJNMjAgMTBMMjUgMTcuNU0yMCAxMEwxNSAxNy41TTIwIDEwVjI1TTIwIDI1SDI1VjMwSDIwVjI1Wk0yMCAyNUgxNVYzMEgyMFYyNVoiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+Cjwvc3ZnPgo='">
                    <h5 class="mb-0 ms-2">ระบบจัดการพฤติกรรม</h5>
                </div>
            </div>
            <div class="sidebar-menu">
                <a href="#overview" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>ภาพรวม</span>
                </a>
                <a href="#students" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>รายชื่อนักเรียน</span>
                </a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#newViolationModal" class="menu-item">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>บันทึกพฤติกรรม</span>
                </a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#violationTypesModal" class="menu-item">
                    <i class="fas fa-list-ul"></i>
                    <span>จัดการประเภทพฤติกรรม</span>
                </a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#classManagementModal" class="menu-item">
                    <i class="fas fa-school"></i>
                    <span>จัดการห้องเรียน</span>
                </a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#importExportModal" class="menu-item">
                    <i class="fas fa-file-import"></i>
                    <span>ส่งออกรายงาน</span>
                </a>
                <a href="javascript:void(0);" onclick="openArchivedStudentsSidebar(); return false;" class="menu-item">
                    <i class="fas fa-archive"></i>
                    <span>ประวัติการเก็บข้อมูล</span>
                </a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#profileModal" class="menu-item">
                    <i class="fas fa-user-circle"></i>
                    <span>โปรไฟล์</span>
                </a>
                <a href="javascript:void(0);" onclick="document.getElementById('logout-form').submit();"
                    class="menu-item mt-auto">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>ออกจากระบบ</span>
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Mobile Header -->
            <div class="mobile-header d-flex d-lg-none">
                <div class="d-flex justify-content-between align-items-center w-100 px-3">
                    <div class="d-flex align-items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="โลโก้โรงเรียน" class="logo"
                            onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iNDAiIHZpZXdCb3g9IjAgMCA0MCA0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjQwIiBoZWlnaHQ9IjQwIiByeD0iOCIgZmlsbD0iIzE2M0FENyIvPgo8cGF0aCBkPSJNMjAgMTBMMjUgMTcuNU0yMCAxMEwxNSAxNy41TTIwIDEwVjI1TTIwIDI1SDI1VjMwSDIwVjI1Wk0yMCAyNUgxNVYzMEgyMFYyNVoiIHN0cm9rZT0id2hpdGUiIHN0cm9rZS13aWR0aD0iMiIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+Cjwvc3ZnPgo='">
                        <h5 class="mb-0 ms-2">ระบบจัดการพฤติกรรม</h5>
                    </div>
                    <div class="dropdown">
                        <img src="https://ui-avatars.com/api/?name=ครูใจดี&background=1020AD&color=fff"
                            class="rounded-circle" width="40" height="40" data-bs-toggle="dropdown">
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="#" data-bs-toggle="modal"
                                data-bs-target="#profileModal">โปรไฟล์</a>
                            <a class="dropdown-item" href="javascript:void(0);"
                                onclick="document.getElementById('logout-form').submit();">ออกจากระบบ</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="content-wrapper">
                <div class="container-fluid">
                    <!-- Academic Year Info & Notifications -->
                    <!-- Welcome Section -->
                    <div class="welcome-section d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h1 class="fw-bold">สวัสดี, {{ $user->users_name_prefix }}{{ $user->users_first_name }}
                                {{ $user->users_last_name }}
                            </h1>
                            <p class="text-muted">วันนี้คือวันที่ <span class="current-date">{{ date('d F Y') }}</span>
                            </p>
                        </div>
                        <div class="d-none d-md-flex align-items-center">
                            <button class="btn btn-primary-app me-3 shadow-sm" data-bs-toggle="modal"
                                data-bs-target="#newViolationModal">
                                <i class="fas fa-plus me-2"></i> บันทึกพฤติกรรม
                            </button>
                            @if(auth()->user()->users_role === 'admin')
                            <div class="btn-group">
                                <button class="btn btn-secondary dropdown-toggle shadow-sm" 
                                        data-bs-toggle="dropdown" 
                                        aria-expanded="false"
                                        style="border-radius: 8px; background-color:#6c757d !important; border-color:#6c757d !important; color:#fff !important;">
                                    <i class="fas fa-sync-alt me-2"></i>เครื่องมือผู้ดูแล
                                </button>
                                <ul class="dropdown-menu shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
                                    <li>
                                        <a class="dropdown-item py-3 px-4" href="#" data-bs-toggle="modal" data-bs-target="#userManagementModal" onclick="showUserManagement()">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary rounded-circle p-2 me-3">
                                                    <i class="fas fa-users text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark">จัดการผู้ใช้</div>
                                                    <small class="text-muted">จัดการบัญชีผู้ใช้ทั้งหมด</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-2"></li>
                                    <li>
                                        <a class="dropdown-item py-3 px-4" href="#" data-bs-toggle="modal" data-bs-target="#excelImportModal">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-success rounded-circle p-2 me-3">
                                                    <i class="fas fa-file-excel text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark">นำเข้าข้อมูล</div>
                                                    <small class="text-muted">จากไฟล์ Excel/CSV</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-2"></li>
                                    <li>
                                        <a class="dropdown-item py-3 px-4" href="#" id="btnViewLog">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-warning rounded-circle p-2 me-3">
                                                    <i class="fas fa-file-alt text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark">Log</div>
                                                    <small class="text-muted">ดูไฟล์ Laravel Log</small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            @endif
                        </div>
                        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const btn = document.getElementById('btnSyncStudentStatus');
                                    
                                    if (!btn) return;
                                    
                                    btn.addEventListener('click', function(e){
                                        e.preventDefault();
                                        if (this.dataset.loading === '1') return;
                                        
                                        // Show loading state with beautiful animation
                                        this.dataset.loading = '1';
                                        const originalContent = this.querySelector('div').innerHTML;
                                        this.querySelector('div').innerHTML = `
                                            <div class="d-flex align-items-center">
                                                <div class="bg-warning rounded-circle p-2 me-3">
                                                    <i class="fas fa-spinner fa-spin text-white"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark">กำลังซิงค์...</div>
                                                    <small class="text-muted">กรุณารอสักครู่</small>
                                                </div>
                                            </div>
                                        `;
                                        
                                        Swal.fire({
                                            title: 'กำลังซิงค์สถานะ...',
                                            html: '<div class="py-2 text-muted small">นำเข้าข้อมูลจากไฟล์ Excel/CSV และตรวจสอบความแตกต่าง</div>',
                                            allowOutsideClick: false,
                                            didOpen: () => { Swal.showLoading(); }
                                        });

                                        fetch('/api/students/status-sync', {
                                            method: 'POST',
                                            credentials: 'same-origin', // ส่งคุกกี้ session ไปด้วย
                                            headers: {
                                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json'
                                            }
                                        }).then(async r=>{
                                            // แปลง response เป็น JSON ถ้าเป็นไปได้ มิฉะนั้นโยนข้อความดิบ
                                            let data;
                                            try { data = await r.json(); } catch(e) { data = { success:false, message: 'ไม่สามารถอ่านผลลัพธ์จากเซิร์ฟเวอร์ได้' }; }
                                            if (!r.ok && !data.success) {
                                                throw new Error(data.message || ('เกิดข้อผิดพลาด ('+r.status+')'));
                                            }
                                            return data;
                                        }).then(data=>{
                                            if (data.success) {
                                                const s = data.summary;
                                                const details = data.details || {};
                                                const updatedRows = (details.updated_details || []).map((u,i)=>`<tr>
                                                    <td class='text-center'>${i+1}</td>
                                                    <td><code>${u.code}</code></td>
                                                    <td>${u.name || '-'}</td>
                                                    <td><span class='badge bg-secondary'>${u.old ?? '-'}</span></td>
                                                    <td><span class='badge bg-success'>${u.new}</span></td>
                                                </tr>`).join('');
                                                const updatedTable = updatedRows ? `
                                                    <div class='mt-3 text-start'>
                                                        <h6 class='fw-semibold mb-2'>รายการที่อัปเดต (${details.updated_details.length})</h6>
                                                        <div class='table-responsive' style='max-height:240px; overflow:auto; border:1px solid #eee; border-radius:6px;'>
                                                            <table class='table table-sm table-hover mb-0'>
                                                                <thead class='table-light position-sticky top-0'>
                                                                    <tr>
                                                                        <th style='width:40px'>#</th>
                                                                        <th>รหัส</th>
                                                                        <th>ชื่อ</th>
                                                                        <th>เดิม</th>
                                                                        <th>ใหม่</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>${updatedRows}</tbody>
                                                            </table>
                                                        </div>
                                                    </div>` : '<div class="mt-3 text-muted small">ไม่มีรายการที่ต้องอัปเดต</div>';

                                                Swal.fire({
                                                    icon: 'success',
                                                    title: 'ซิงค์สำเร็จ',
                                                    width: 760,
                                                    html: `
                                                        <div class='row g-2 text-start small'>
                                                            <div class='col-6 col-lg-3'><div class='p-2 bg-light rounded border'><div class='text-muted'>อัปเดต</div><div class='fs-5 fw-semibold text-success'>${s.updated}</div></div></div>
                                                            <div class='col-6 col-lg-3'><div class='p-2 bg-light rounded border'><div class='text-muted'>ไม่เปลี่ยน</div><div class='fs-5 fw-semibold'>${s.unchanged}</div></div></div>
                                                            <div class='col-6 col-lg-3'><div class='p-2 bg-light rounded border'><div class='text-muted'>ไม่พบ</div><div class='fs-5 fw-semibold text-warning'>${s.not_found}</div></div></div>
                                                            <div class='col-6 col-lg-3'><div class='p-2 bg-light rounded border'><div class='text-muted'>ไม่ถูกต้อง</div><div class='fs-5 fw-semibold text-danger'>${s.invalid_status}</div></div></div>
                                                        </div>
                                                        ${updatedTable}
                                                    `,
                                                    confirmButtonText: 'ปิด',
                                                });

                                                this.querySelector('div').innerHTML = originalContent;
                                            } else {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'ซิงค์ไม่สำเร็จ',
                                                    html: `<div class='text-danger small'>${data.message || 'ไม่สามารถซิงค์ได้'}</div>`,
                                                });
                                                this.querySelector('div').innerHTML = originalContent;
                                            }
                                        }).catch(err=>{
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'เกิดข้อผิดพลาด',
                                                html: `<div class='text-danger small'>${err.message}</div>`
                                            });
                                            this.querySelector('div').innerHTML = originalContent;
                                        }).finally(()=>{
                                            this.dataset.loading='0';
                                        });
                                    });
                                });
                            </script>
                            </button>
                        </div>
                    </div>

                    <!-- Stats Overview -->
                    <div class="row mb-4" id="overview">
                        <div class="col-12">
                            <h5 class="section-title">ภาพรวมประจำเดือน
                                {{ now()->locale('th')->translatedFormat('F Y') }}
                            </h5>
                        </div>
                        @php
                            $stats = $monthlyStats ?? [];
                            $vc = $stats['violation_count'] ?? 0; $vt = (int)($stats['violation_trend'] ?? 0);
                            $sc = $stats['students_count'] ?? 0; $st = (int)($stats['students_trend'] ?? 0);
                            $sev = $stats['severe_count'] ?? 0; $sevt = (int)($stats['severe_trend'] ?? 0);
                            $avg = $stats['avg_score'] ?? 0; $avgDisp = number_format($avg,1);
                            $scoreT = (float)($stats['score_trend'] ?? 0);
                            function trendClass($v){ return $v>0?'increase':($v<0?'decrease':'no-change'); }
                            function trendIcon($v){ return $v>0?'fa-arrow-up':($v<0?'fa-arrow-down':'fa-equals'); }
                            function trendTextPercent($v){ return ($v>0?'+':'').$v.'% จากเดือนก่อน'; }
                            function trendTextScore($v){ return ($v>0?'-':'').$v.' คะแนนจากเดือนก่อน'; }
                        @endphp
                        <div class="col-12 col-md-6 col-xl-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-primary-app me-3">
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <div>
                                            <h6 class="stat-title">พฤติกรรมที่บันทึกเดือนนี้</h6>
                                            <h4 class="stat-value">{{ $vc }}</h4>
                                            <p class="stat-change mb-0 {{ trendClass($vt) }}">
                                                <i class="fas {{ trendIcon($vt) }} me-1"></i>
                                                {{ trendTextPercent($vt) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-warning me-3">
                                            <i class="fas fa-users"></i>
                                        </div>
                                        <div>
                                            <h6 class="stat-title">นักเรียนที่ถูกบันทึก</h6>
                                            <h4 class="stat-value">{{ $sc }}</h4>
                                            <p class="stat-change mb-0 {{ trendClass($st) }}">
                                                <i class="fas {{ trendIcon($st) }} me-1"></i>
                                                {{ trendTextPercent($st) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-danger me-3">
                                            <i class="fas fa-fire"></i>
                                        </div>
                                        <div>
                                            <h6 class="stat-title">พฤติกรรมรุนแรง</h6>
                                            <h4 class="stat-value">{{ $sev }}</h4>
                                            <p class="stat-change mb-0 {{ trendClass($sevt) }}">
                                                <i class="fas {{ trendIcon($sevt) }} me-1"></i>
                                                {{ trendTextPercent($sevt) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="stat-icon bg-success me-3">
                                            <i class="fas fa-award"></i>
                                        </div>
                                        <div>
                                            <h6 class="stat-title">คะแนนเฉลี่ย</h6>
                                            <h4 class="stat-value">{{ $avgDisp }}</h4>
                                            <p class="stat-change mb-0 {{ trendClass($scoreT) }}">
                                                <i class="fas {{ trendIcon($scoreT) }} me-1"></i>
                                                {{ trendTextScore($scoreT) }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Violation Trends Chart -->
                    <div class="row mb-4">
                        <div class="col-12 col-lg-8 mb-3">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">แนวโน้มการบันทึกพฤติกรรมประจำเดือน</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="violationTrend" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-lg-4 mb-3">
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h5 class="card-title mb-0">ประเภทการกระทำผิด</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="violationTypes" height="250"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Violations -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">การบันทึกพฤติกรรมล่าสุด</h5>
                                    <button class="btn btn-sm btn-outline-primary" onclick="loadRecentReports()">
                                        <i class="fas fa-sync-alt"></i> รีเฟรช
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>นักเรียน</th>
                                                    <th>ประเภทพฤติกรรม</th>
                                                    <th>คะแนนที่หัก</th>
                                                    <th>วันที่บันทึก</th>
                                                    <th>บันทึกโดย</th>
                                                    <th>การดำเนินการ</th>
                                                </tr>
                                            </thead>
                                            <tbody id="recentViolationsTable">
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">
                                                        <div class="text-muted">
                                                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                            <p>ไม่มีข้อมูลการบันทึกพฤติกรรม</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student List -->
                    <div class="row mb-4" id="students">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">รายชื่อนักเรียน <span id="studentCountLabel" class="text-muted small">({{ $studentsTotal }} คน)</span></h5>
                                    <div class="d-flex">
                                        <form id="studentSearchForm" method="GET" action="{{ route('teacher.dashboard') }}" class="d-flex">
                                            <div class="input-group me-2" style="width: 300px;">
                                                <input type="text" id="studentSearchInput" name="search" class="form-control form-control-sm"
                                                       value="{{ request('search') }}" placeholder="ค้นหานักเรียน..." autocomplete="off">
                                                <button type="button" id="studentSearchBtn" class="btn btn-sm btn-primary-app">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </form>
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal"
                                            data-bs-target="#studentFilterModal">
                                            <i class="fas fa-filter"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>รหัสนักเรียน</th>
                                                    <th>ชื่อ-นามสกุล</th>
                                                    <th>ชั้นเรียน</th>
                                                    <th>คะแนนคงเหลือ</th>
                                                    <th>สถิติการกระทำผิด</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($students as $student)
                                                                <tr data-student-row="1">
                                                                    <td>{{ $student->students_student_code ?? '-' }}</td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            @php
                                                                                $studentName = ($student->user->users_name_prefix ?? '') . ($student->user->users_first_name ?? '') . ' ' . ($student->user->users_last_name ?? '');
                                                                                $avatarUrl = $student->user->users_profile_image
                                                                                    ? asset('storage/' . $student->user->users_profile_image)
                                                                                    : 'https://ui-avatars.com/api/?name=' . urlencode($studentName) . '&background=95A4D8&color=fff';
                                                                            @endphp
                                                                            <img src="{{ $avatarUrl }}" class="rounded-circle me-2"
                                                                                width="32" height="32" alt="{{ $studentName }}">
                                                                            <span>{{ $studentName }}</span>
                                                                        </div>
                                                                    </td>
                                                                    <td>
                                                                        @if($student->classroom)
                                                                            {{ $student->classroom->classes_level }}/{{ $student->classroom->classes_room_number }}
                                                                        @else
                                                                            -
                                                                        @endif
                                                                    </td>
                                                                    <td>
                                                                        @php
                                                                            $score = $student->students_current_score ?? 100;
                                                                            $progressClass = 'bg-success';
                                                                            if ($score <= 50) {
                                                                                $progressClass = 'bg-danger';
                                                                            } elseif ($score <= 75) {
                                                                                $progressClass = 'bg-warning';
                                                                            }
                                                                        @endphp
                                                                        <div style="margin-bottom: 5px; margin-top: 10px;">
                                                                            <div class="progress"
                                                                                style="height: 8px; width: 100px; position: relative; margin-top: 10px;">
                                                                                <div class="progress-bar {{ $progressClass }}"
                                                                                    role="progressbar" style="width: {{ $score }}%">
                                                                                </div>
                                                                                @if($score >= 90)
                                                                                    <div
                                                                                        style="position: absolute; left: {{ $score }}%; top: -10px; transform: translateX(-50%); 
                                                                                                                    background-color: white; width: 24px; height: 24px; 
                                                                                                                    border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,0.3); 
                                                                                                                    display: flex; align-items: center; justify-content: center; 
                                                                                                                    border: 2px solid white; z-index: 10;">
                                                                                        <img src="{{ asset('images/smile.png') }}"
                                                                                            style="height: 16px; width: 16px;" alt="👍">
                                                                                    </div>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        <span class="small">{{ $score }}/100</span>
                                                    </div>
                                                    </td>
                                                    <td>
                                                        @php
                                                            // นับจำนวนการกระทำผิดของนักเรียน
                                                            $violationCount = App\Models\BehaviorReport::where('student_id', $student->students_id)->count();
                                                        @endphp
                                                        {{ $violationCount }} ครั้ง
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary-app" data-bs-toggle="modal"
                                                            data-bs-target="#studentDetailModal"
                                                            data-student-id="{{ $student->students_id }}">
                                                            <i class="fas fa-user me-1"></i> ดูข้อมูล
                                                        </button>
                                                    </td>
                                                    </tr>
                                                @empty
                                        <tr data-empty-row="1">
                                            <td colspan="6" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                                                    <p>ไม่พบข้อมูลนักเรียน</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                    </tbody>
                                    </table>
                                    <!-- Quick search and AJAX pagination logic moved to /js/student-filter.js -->
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <nav id="studentsPagination">
                                    {{ $students->appends(request()->query())->links('pagination::bootstrap-4') }}
                                </nav>
                            </div>
                            <!-- Pagination AJAX logic moved to /js/student-filter.js -->
                        </div>
                    </div>

                    <!-- User Management Modal (Admin Only) -->
                    @if(auth()->user()->users_role === 'admin')
                    <div class="modal fade" id="userManagementModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                                    <!-- Enhanced Header (no gradient) -->
                                    <div class="modal-header border-0 bg-primary-app text-white" style="border-radius: 16px 16px 0 0;">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                                            <i class="fas fa-users-cog fs-4"></i>
                                        </div>
                                        <div>
                                            <h5 class="modal-title mb-0 fw-bold">จัดการบัญชีผู้ใช้</h5>
                                            <small class="opacity-75">ระบบจัดการข้อมูลผู้ใช้งานทั้งหมด</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <!-- Enhanced User Stats -->
                                    <div class="row g-3 mb-4 minimal-stat-grid">
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <div class="minimal-card h-100">
                                                <div class="minimal-card-icon text-primary">
                                                    <i class="fas fa-users"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <p class="minimal-card-label">ผู้ใช้ทั้งหมด</p>
                                                    <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                                        <span id="totalUsersCount" class="minimal-card-value">0</span>
                                                        <span id="activeUsersCount" class="minimal-card-chip">0 คนใช้งาน</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <div class="minimal-card h-100">
                                                <div class="minimal-card-icon text-info">
                                                    <i class="fas fa-graduation-cap"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <p class="minimal-card-label">นักเรียน</p>
                                                    <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                                        <span id="studentsUserCount" class="minimal-card-value">0</span>
                                                    </div>
                                                    <span id="avgStudentScore" class="minimal-card-subtext">คะแนนเฉลี่ย: -</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <div class="minimal-card h-100">
                                                <div class="minimal-card-icon text-warning">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <p class="minimal-card-label">ครู</p>
                                                    <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                                        <span id="teachersUserCount" class="minimal-card-value">0</span>
                                                    </div>
                                                    <span id="homeroomTeacherCount" class="minimal-card-subtext">0 ครูประจำชั้น</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-sm-6 col-xl-3">
                                            <div class="minimal-card h-100">
                                                <div class="minimal-card-icon text-success">
                                                    <i class="fas fa-user-friends"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <p class="minimal-card-label">ผู้ปกครอง</p>
                                                    <div class="d-flex align-items-baseline gap-2 flex-wrap">
                                                        <span id="guardiansUserCount" class="minimal-card-value">0</span>
                                                    </div>
                                                    <span id="linkedStudentsCount" class="minimal-card-subtext">0 นักเรียนที่เชื่อมโยง</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Minimal User Management Panel -->
                                    <div class="card border-0 minimal-panel">
                                        <div class="card-header bg-transparent border-0 pb-0">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                                <div>
                                                    <h6 class="card-title mb-1 fw-semibold text-dark">รายชื่อผู้ใช้ทั้งหมด</h6>
                                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                                        <span class="minimal-meta text-muted">จัดการบัญชีและสิทธิ์การใช้งาน</span>
                                                        <span class="minimal-count" id="userCountBadge">0</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 flex-wrap minimal-toolbar">
                                                    <div class="input-group minimal-input-group" style="min-width: 240px;">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-search text-muted"></i>
                                                        </span>
                                                        <input type="text" id="userSearchInput" class="form-control"
                                                               placeholder="ค้นหาชื่อ, อีเมล, รหัส..." autocomplete="off">
                                                        <button type="button" id="userSearchBtn" class="btn btn-primary">
                                                            ค้นหา
                                                        </button>
                                                    </div>
                                                    <button class="btn btn-outline-secondary" onclick="showUserFilter()" id="filterToggleBtn">
                                                        <i class="fas fa-filter me-1"></i>ตัวกรอง
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body minimal-filter" id="userFilterBar" style="display: none;">
                                            <div class="row g-3">
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold text-muted">บทบาท</label>
                                                    <select id="roleFilter" class="form-select form-select-sm">
                                                        <option value="">ทุกบทบาท</option>
                                                        <option value="admin">👨‍💼 ผู้ดูแลระบบ</option>
                                                        <option value="teacher">👩‍🏫 ครู</option>
                                                        <option value="student">🎓 นักเรียน</option>
                                                        <option value="guardian">👪 ผู้ปกครอง</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold text-muted">สถานะ</label>
                                                    <select id="statusFilter" class="form-select form-select-sm">
                                                        <option value="">ทุกสถานะ</option>
                                                        <option value="active">🟢 ใช้งาน</option>
                                                        <option value="inactive">🔴 ปิดใช้งาน</option>
                                                        <option value="suspended">⏸️ ถูกพัก</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold text-muted">ชั้นเรียน</label>
                                                    <select id="classroomFilter" class="form-select form-select-sm">
                                                        <option value="">ทุกชั้นเรียน</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold text-muted">วันที่สมัคร</label>
                                                    <input type="date" id="dateFromFilter" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold text-muted">ถึงวันที่</label>
                                                    <input type="date" id="dateToFilter" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-md-1">
                                                    <label class="form-label small">&nbsp;</label>
                                                    <div class="d-grid gap-1">
                                                        <button class="btn btn-sm btn-primary" onclick="applyUserFilters()">
                                                            <i class="fas fa-search"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-secondary" onclick="clearUserFilters()">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Enhanced User Table -->
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle mb-0" id="usersTable">
                                                    <thead class="minimal-table-head">
                                                        <tr>
                                                            <th class="border-0 fw-semibold">ข้อมูลผู้ใช้</th>
                                                            <th class="border-0 fw-semibold">รหัส/ตำแหน่ง</th>
                                                            <th class="border-0 fw-semibold">บทบาท</th>
                                                            <th class="border-0 fw-semibold">ชั้น/แผนก</th>
                                                            <th class="border-0 fw-semibold">สถานะ</th>
                                                            <th class="border-0 fw-semibold text-center">การดำเนินการ</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="usersTableBody">
                                                        <!-- Content will be loaded via AJAX -->
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Enhanced Loading State -->
                                            <div id="usersLoading" class="text-center py-5" style="display: none;">
                                                <div class="d-flex flex-column align-items-center">
                                                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                                                        <span class="visually-hidden">กำลังโหลด...</span>
                                                    </div>
                                                    <h6 class="text-muted">กำลังโหลดข้อมูลผู้ใช้...</h6>
                                                    <small class="text-muted">โปรดรอสักครู่</small>
                                                </div>
                                            </div>
                                            
                                            <!-- Empty State -->
                                            <div id="usersEmptyState" class="text-center py-5" style="display: none;">
                                                <div class="d-flex flex-column align-items-center">
                                                    <i class="fas fa-users-slash fa-4x text-muted mb-3"></i>
                                                    <h6 class="text-muted">ไม่พบข้อมูลผู้ใช้</h6>
                                                    <small class="text-muted">ลองปรับเปลี่ยนเงื่อนไขการค้นหาหรือตัวกรอง</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Enhanced Footer with Pagination -->
                                        <div class="card-footer bg-transparent border-0 minimal-footer">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                                <div class="d-flex align-items-center mb-2 mb-md-0">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        แสดง <span id="usersShowingFrom">0</span>-<span id="usersShowingTo">0</span> 
                                                        จากทั้งหมด <span id="usersTotalCount">0</span> รายการ
                                                    </small>
                                                </div>
                                                <nav id="usersPagination" aria-label="User pagination">
                                                    <!-- Pagination will be loaded via AJAX -->
                                                </nav>
                                                <div class="d-flex align-items-center">
                                                    <small class="text-muted me-2">แสดงผล:</small>
                                                    <select id="usersPerPage" class="form-select form-select-sm" style="width: auto;">
                                                        <option value="10">10</option>
                                                        <option value="25" selected>25</option>
                                                        <option value="50">50</option>
                                                        <option value="100">100</option>
                                                    </select>
                                                    <small class="text-muted ms-2">รายการ</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mobile Bottom Nav -->
        <div class="bottom-navbar d-lg-none">
            <div class="row g-0">
                <div class="col">
                    <a href="#overview" class="nav-link text-center text-primary-app">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>ภาพรวม</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#students" class="nav-link text-center">
                        <i class="fas fa-users"></i>
                        <span>นักเรียน</span>
                    </a>
                </div>
                <div class="col">
                    <a href="javascript:void(0);" class="nav-link text-center" onclick="openArchivedStudentsSidebar(); return false;">
                        <i class="fas fa-archive"></i>
                        <span>ประวัติ</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="nav-link text-center" data-bs-toggle="modal" data-bs-target="#newViolationModal">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>บันทึก</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="nav-link text-center" data-bs-toggle="modal"
                        data-bs-target="#violationTypesModal">
                        <i class="fas fa-list-ul"></i>
                        <span>ประเภท</span>
                    </a>
                </div>
                <div class="col">
                    <a href="#" class="nav-link text-center" data-bs-toggle="modal" data-bs-target="#profileModal">
                        <i class="fas fa-user-circle"></i>
                        <span>โปรไฟล์</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- MODALS -->

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">โปรไฟล์ของฉัน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form action="{{ route('teacher.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="modal-body">
                        @if ($errors->any())
                            <div class="alert alert-danger mb-3">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success mb-3">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="row">
                            <!-- คอลัมน์ซ้าย: รูปและข้อมูลพื้นฐาน -->
                            <div class="col-md-4 mb-3">
                                <div class="text-center mb-4">
                                    <div class="position-relative d-inline-block">
                                        @if($user->users_profile_image)
                                            <img src="{{ asset('storage/' . $user->users_profile_image) }}"
                                                class="rounded-circle" width="100" height="100" id="profile-preview">
                                        @else
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($user->users_first_name) }}&background=1020AD&color=fff"
                                                class="rounded-circle" width="100" height="100" id="profile-preview">
                                        @endif
                                        <label for="profile_image"
                                            class="btn btn-sm btn-primary-app position-absolute bottom-0 end-0 rounded-circle"
                                            style="cursor: pointer;">
                                            <i class="fas fa-camera"></i>
                                        </label>
                                        <input type="file" name="profile_image" id="profile_image"
                                            style="display: none;" accept="image/*">
                                    </div>
                                    <h5 class="mt-3 mb-1">{{ $user->users_name_prefix }}{{ $user->users_first_name }}
                                        {{ $user->users_last_name }}
                                    </h5>
                                    <p class="text-muted">
                                        @if($user->teacher && $user->teacher->teachers_position)
                                            {{ $user->teacher->teachers_position }}
                                        @else
                                            ครู
                                        @endif
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">อีเมล</label>
                                    <input type="email" class="form-control" name="users_email"
                                        value="{{ $user->users_email }}" disabled>
                                    <div class="form-text">อีเมลไม่สามารถแก้ไขได้</div>
                                </div>
                            </div>

                            <!-- คอลัมน์ขวา: แท็บข้อมูลและการตั้งค่า -->
                            <div class="col-md-8">
                                <ul class="nav nav-tabs mb-3" id="profileTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="personal-tab" data-bs-toggle="tab"
                                            data-bs-target="#personal" type="button" role="tab"
                                            aria-selected="true">ข้อมูลส่วนตัว</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="work-tab" data-bs-toggle="tab"
                                            data-bs-target="#work" type="button" role="tab"
                                            aria-selected="false">ข้อมูลการทำงาน</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="password-tab" data-bs-toggle="tab"
                                            data-bs-target="#password" type="button" role="tab"
                                            aria-selected="false">รหัสผ่าน</button>
                                    </li>
                                </ul>

                                <div class="tab-content" id="profileTabContent">
                                    <!-- แท็บข้อมูลส่วนตัว -->
                                    <div class="tab-pane fade show active" id="personal" role="tabpanel"
                                        aria-labelledby="personal-tab">
                                        <div class="row">
                                            <div class="col-4 mb-3">
                                                <label class="form-label">คำนำหน้า</label>
                                                <select class="form-select" name="users_name_prefix">
                                                    <option value="นาย" {{ $user->users_name_prefix == 'นาย' ? 'selected' : '' }}>นาย</option>
                                                    <option value="นาง" {{ $user->users_name_prefix == 'นาง' ? 'selected' : '' }}>นาง</option>
                                                    <option value="นางสาว" {{ $user->users_name_prefix == 'นางสาว' ? 'selected' : '' }}>นางสาว</option>
                                                    <option value="อาจารย์" {{ $user->users_name_prefix == 'อาจารย์' ? 'selected' : '' }}>อาจารย์</option>
                                                    <option value="ดร." {{ $user->users_name_prefix == 'ดร.' ? 'selected' : '' }}>ดร.</option>
                                                </select>
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label class="form-label">ชื่อ</label>
                                                <input type="text" class="form-control" name="users_first_name"
                                                    value="{{ $user->users_first_name }}">
                                            </div>
                                            <div class="col-4 mb-3">
                                                <label class="form-label">นามสกุล</label>
                                                <input type="text" class="form-control" name="users_last_name"
                                                    value="{{ $user->users_last_name }}">
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">เบอร์โทรศัพท์</label>
                                                <input type="tel" class="form-control" name="users_phone_number"
                                                    value="{{ $user->users_phone_number }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">วันเกิด</label>
                                                <input type="date" class="form-control" name="users_birthdate"
                                                    value="{{ \Carbon\Carbon::parse($user->users_birthdate)->format('Y-m-d') }}">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- แท็บข้อมูลการทำงาน -->
                                    <div class="tab-pane fade" id="work" role="tabpanel" aria-labelledby="work-tab">
                                        @if($user->teacher)
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">ตำแหน่ง</label>
                                                    <input type="text" class="form-control" name="teachers_position"
                                                        value="{{ $user->teacher->teachers_position }}"
                                                        autocomplete="organization-title">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">รหัสประจำตัวครู</label>
                                                    <input type="text" class="form-control"
                                                        value="{{ $user->teacher->teachers_employee_code }}" disabled>
                                                    <div class="form-text">รหัสประจำตัวไม่สามารถแก้ไขได้</div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">กลุ่มสาระ/ฝ่าย</label>
                                                    <input type="text" class="form-control" name="teachers_department"
                                                        value="{{ $user->teacher->teachers_department }}"
                                                        autocomplete="organization">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">วิชาที่สอน</label>
                                                    <input type="text" class="form-control" name="teachers_major"
                                                        value="คอมพิวเตอร์" autocomplete="off">
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-info">ไม่พบข้อมูลการทำงาน</div>
                                        @endif
                                    </div>

                                    <!-- แท็บเปลี่ยนรหัสผ่าน -->
                                    <div class="tab-pane fade" id="password" role="tabpanel"
                                        aria-labelledby="password-tab">
                                        <div class="mb-3">
                                            <label class="form-label">รหัสผ่านเดิม</label>
                                            <input type="password" class="form-control" name="current_password"
                                                autocomplete="current-password" placeholder="ใส่รหัสผ่านเดิม">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">รหัสผ่านใหม่</label>
                                            <input type="password" class="form-control" name="new_password"
                                                autocomplete="new-password" placeholder="ใส่รหัสผ่านใหม่">
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">ยืนยันรหัสผ่านใหม่</label>
                                            <input type="password" class="form-control" name="new_password_confirmation"
                                                autocomplete="new-password" placeholder="ยืนยันรหัสผ่านใหม่">
                                        </div>
                                        <div class="form-text">เว้นว่างถ้าไม่ต้องการเปลี่ยนรหัสผ่าน</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary-app">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Excel/CSV Import Modal (Admin/Teacher) -->
    <div class="modal fade" id="excelImportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.08);">
                <div class="modal-header" style="border: none; padding: 32px 32px 0 32px;">
                    <h5 class="modal-title" style="font-weight: 600; color: #1a1a1a; font-size: 20px;">
                        <i class="fas fa-file-excel me-2" style="color: #10b981;"></i> นำเข้าข้อมูล Excel/CSV
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" 
                            style="background: #f3f4f6; border-radius: 8px; opacity: 1; padding: 8px;">
                    </button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <div class="alert" style="background: #f0f9ff; border: 1px solid #e0f2fe; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                        <i class="fas fa-info-circle me-2" style="color: #0ea5e9;"></i>
                        <span style="color: #0c4a6e; font-size: 14px;">รองรับไฟล์ .xls, .xlsx, .csv, .txt (เลือก sheet ได้ถ้ามีหลายแผ่นงาน)</span>
                    </div>
                    <form id="excelImportForm" enctype="multipart/form-data" autocomplete="off">
                        <div class="mb-4">
                            <label for="excelFile" class="form-label" style="font-weight: 500; color: #374151; margin-bottom: 8px;">เลือกไฟล์ Excel/CSV</label>
                            <input type="file" class="form-control" id="excelFile" name="file" accept=".xls,.xlsx,.csv,.txt" required
                                   style="border: 2px dashed #d1d5db; border-radius: 12px; padding: 20px; background: #fafafa; transition: all 0.2s ease;">
                        </div>
                        <div class="mb-4 d-none" id="sheetSelectorGroup">
                            <label for="sheetSelector" class="form-label" style="font-weight: 500; color: #374151; margin-bottom: 8px;">เลือกประเภทข้อมูล (Sheet)</label>
                            <select class="form-select" id="sheetSelector" 
                                    style="border: 1px solid #d1d5db; border-radius: 8px; padding: 12px 16px; background: white;">
                            </select>
                        </div>
                    </form>
                    <div id="excelLoading" class="d-none text-center my-5">
                        <div class="spinner-border" role="status" style="width: 3rem; height: 3rem; color: #10b981; border-width: 3px;">
                        </div>
                        <div class="mt-3" style="color: #6b7280; font-size: 14px;">กำลังอ่านไฟล์และเตรียมข้อมูล...</div>
                    </div>
                    <div id="excelPreviewContainer" class="d-none mt-4">
                        <div class="mb-3">
                            <h6 style="font-weight: 600; color: #374151; margin-bottom: 12px;">ตัวอย่างข้อมูล</h6>
                            <!-- Tab Navigation -->
                <ul class="nav nav-tabs chip-tabs" id="dataTabsNav" role="tablist">
                                <li class="nav-item" role="presentation">
                    <button class="nav-link chip-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-data" type="button" role="tab">
                                        <i class="fas fa-list me-2"></i>ข้อมูลทั้งหมด <span id="allDataCount" class="badge bg-secondary ms-2">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                    <button class="nav-link chip-link" id="valid-tab" data-bs-toggle="tab" data-bs-target="#valid-data" type="button" role="tab">
                                        <i class="fas fa-check-circle me-2" style="color: #10b981;"></i>ข้อมูลที่ไม่ซ้ำ <span id="validDataCount" class="badge bg-success ms-2">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                    <button class="nav-link chip-link" id="duplicate-tab" data-bs-toggle="tab" data-bs-target="#duplicate-data" type="button" role="tab">
                                        <i class="fas fa-exclamation-triangle me-2" style="color: #f59e0b;"></i>ข้อมูลที่ซ้ำ <span id="duplicateDataCount" class="badge bg-warning ms-2">0</span>
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                    <button class="nav-link chip-link" id="error-tab" data-bs-toggle="tab" data-bs-target="#error-data" type="button" role="tab">
                                        <i class="fas fa-times-circle me-2" style="color: #ef4444;"></i>ข้อมูลที่ผิดพลาด <span id="errorDataCount" class="badge bg-danger ms-2">0</span>
                                    </button>
                                </li>
                            </ul>
                        </div>
                        
                        <!-- Tab Content -->
                        <div class="tab-content" id="dataTabsContent">
                            <!-- Tab 1: ข้อมูลทั้งหมด -->
                            <div class="tab-pane fade show active" id="all-data" role="tabpanel">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: white;">
                                    <table class="table table-sm align-middle mb-0" id="excelPreviewTable" style="background: white; min-width: 600px;">
                                        <thead style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center" id="excelPagination" style="gap: 4px;">
                                    </ul>
                                </nav>
                            </div>
                            
                            <!-- Tab 2: ข้อมูลที่ไม่ซ้ำ -->
                            <div class="tab-pane fade" id="valid-data" role="tabpanel">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: white;">
                                    <table class="table table-sm align-middle mb-0" id="validDataTable" style="background: white; min-width: 600px;">
                                        <thead style="background: #ecfdf5; border-bottom: 1px solid #10b981;">
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center" id="validDataPagination" style="gap: 4px;">
                                    </ul>
                                </nav>
                            </div>
                            
                            <!-- Tab 3: ข้อมูลที่ซ้ำ -->
                            <div class="tab-pane fade" id="duplicate-data" role="tabpanel">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: white;">
                                    <table class="table table-sm align-middle mb-0" id="duplicateDataTable" style="background: white; min-width: 600px;">
                                        <thead style="background: #fef3c7; border-bottom: 1px solid #f59e0b;">
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center" id="duplicateDataPagination" style="gap: 4px;">
                                    </ul>
                                </nav>
                            </div>
                            
                            <!-- Tab 4: ข้อมูลที่ผิดพลาด -->
                            <div class="tab-pane fade" id="error-data" role="tabpanel">
                                <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: white;">
                                    <table class="table table-sm align-middle mb-0" id="errorDataTable" style="background: white; min-width: 600px;">
                                        <thead style="background: #fef2f2; border-bottom: 1px solid #ef4444;">
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                                <nav class="mt-4">
                                    <ul class="pagination justify-content-center" id="errorDataPagination" style="gap: 4px;">
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4 pt-3" style="border-top: 1px solid #e5e7eb;">
                        <div class="d-flex align-items-center gap-2">
                            <div style="color: #6b7280; font-size: 14px;">
                                <i class="fas fa-check-circle me-1" style="color: #10b981;"></i>
                                เลือกแถวที่ต้องการนำเข้า
                            </div>
                            <button id="selectAllDataBtn" class="btn btn-sm d-none" 
                                    style="background: #e0f2fe; color: #0891b2; border: 1px solid #38bdf8; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500;">
                                <i class="fas fa-check-double me-1"></i>เลือกทั้งหมด
                            </button>
                            <button id="clearSelectionBtn" class="btn btn-sm d-none" 
                                    style="background: #fef2f2; color: #dc2626; border: 1px solid #f87171; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500;">
                                <i class="fas fa-times me-1"></i>ล้างการเลือก
                            </button>
                        </div>
                        <div class="d-flex gap-3">
                            <button id="importValidOnlyBtn" class="btn d-none" 
                                    style="background: #10b981; color: white; border: none; border-radius: 10px; padding: 10px 20px; font-weight: 600; font-size: 13px; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);">
                                <i class="fas fa-shield-check me-2"></i>นำเข้าข้อมูลที่ปลอดภัย
                            </button>
                            <button id="importExcelBtn" class="btn d-none" 
                                    style="background: #3b82f6; color: white; border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; font-size: 14px; box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);">
                                <i class="fas fa-download me-2"></i>นำเข้าข้อมูลที่เลือก
                            </button>
                        </div>
                    </div>
                </div>
</div> <!-- ปิด .modal-content, .modal-dialog, .modal, ... -->

    <!-- Duplicate Data Modal -->
    <div class="modal fade" id="duplicateDataModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.08);">
                <div class="modal-header" style="border: none; padding: 32px 32px 0 32px;">
                    <h5 class="modal-title" style="font-weight: 600; color: #1a1a1a; font-size: 20px;">
                        <i class="fas fa-exclamation-triangle me-2" style="color: #f59e0b;"></i> ข้อมูลที่ซ้ำกัน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" 
                            style="background: #f3f4f6; border-radius: 8px; opacity: 1; padding: 8px;">
                    </button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <div class="alert" style="background: #fef3c7; border: 1px solid #fcd34d; border-radius: 12px; padding: 16px; margin-bottom: 24px;">
                        <i class="fas fa-info-circle me-2" style="color: #92400e;"></i>
                        <span style="color: #92400e; font-size: 14px;">พบข้อมูลที่ซ้ำกันในระบบ กรุณาตรวจสอบก่อนนำเข้า</span>
                    </div>
                    
                    <div id="duplicateDataContent">
                        <div id="duplicateDataLoading" class="text-center py-4">
                            <div class="spinner-border" style="color: #f59e0b;" role="status"></div>
                            <div class="mt-2" style="color: #6b7280; font-size: 14px;">กำลังตรวจสอบข้อมูลซ้ำ...</div>
                        </div>
                        
                        <div id="duplicateDataResults" class="d-none">
                            <div class="table-responsive">
                                <table class="table table-sm align-middle" style="background: white;">
                                    <thead style="background: #f9fafb; border-bottom: 1px solid #e5e7eb;">
                                        <tr>
                                            <th style="padding: 12px 16px; font-weight: 500; color: #374151;">แถว</th>
                                            <th style="padding: 12px 16px; font-weight: 500; color: #374151;">ชื่อ-นามสกุล</th>
                                            <th style="padding: 12px 16px; font-weight: 500; color: #374151;">ข้อมูลที่ซ้ำ</th>
                                            <th style="padding: 12px 16px; font-weight: 500; color: #374151;">รายละเอียด</th>
                                            <th style="padding: 12px 16px; font-weight: 500; color: #374151;">การจัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody id="duplicateDataTableBody">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border: none; padding: 24px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn" id="importWithoutDuplicatesBtn" style="background: #10b981; color: white;">
                        <i class="fas fa-download me-2"></i>นำเข้าเฉพาะข้อมูลที่ไม่ซ้ำ
                    </button>
                </div>
            </div>
        </div>
    </div>

<!-- SheetJS & Excel Preview Script (ย้ายออกมานอก modal-content) -->
</div> <!-- ปิด .modal-content, .modal-dialog, .modal, ... -->

<!-- SheetJS & Excel Preview Script (ย้ายออกมานอก modal-content) -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
// Dynamic Excel/CSV preview with loading, sheet selector, pagination, and smart data type UI
document.addEventListener('DOMContentLoaded', function () {
    // Variables และ DOM elements สำหรับการจัดการข้อมูล
    const excelFileInput = document.getElementById('excelFile');
    const previewContainer = document.getElementById('excelPreviewContainer');
    const previewTable = document.getElementById('excelPreviewTable');
    const validDataTable = document.getElementById('validDataTable');
    const duplicateDataTable = document.getElementById('duplicateDataTable');
    const errorDataTable = document.getElementById('errorDataTable');
    const importBtn = document.getElementById('importExcelBtn');
    const importValidOnlyBtn = document.getElementById('importValidOnlyBtn');
    const loadingDiv = document.getElementById('excelLoading');
    const sheetSelectorGroup = document.getElementById('sheetSelectorGroup');
    const sheetSelector = document.getElementById('sheetSelector');
    const pagination = document.getElementById('excelPagination');
    
    // ข้อมูลแยกตามประเภท
    let allData = [];
    let validData = [];
    let duplicateData = [];
    let errorData = [];
    let selectedRows = new Set();
    
    let previewData = [];
    let currentPage = 1;
    let pageSize = 20;
    let totalPages = 1;
    let workbook = null;
    let sheetNames = [];

    // ฟังก์ชันรีเซ็ต modal ให้อยู่สภาพเดิมเมื่อปิด
    function resetExcelImportModal() {
        // เคลียร์ไฟล์ที่เลือก
        if (excelFileInput) {
            excelFileInput.value = '';
            excelFileInput.style.borderColor = '#d1d5db';
            excelFileInput.style.background = '#fafafa';
        }
        // ซ่อนตัวอย่าง/ตัวเลือก sheet / ปุ่มต่างๆ
        previewContainer.classList.add('d-none');
        sheetSelectorGroup.classList.add('d-none');
        if (importBtn) importBtn.classList.add('d-none');
        if (importValidOnlyBtn) importValidOnlyBtn.classList.add('d-none');
        const selectAllBtn = document.getElementById('selectAllDataBtn');
        if (selectAllBtn) selectAllBtn.classList.add('d-none');
        const clearSelectionBtn = document.getElementById('clearSelectionBtn');
        if (clearSelectionBtn) clearSelectionBtn.classList.add('d-none');
        // รีเซ็ตข้อมูลและตัวแปรทั้งหมด
        allData = [];
        validData = [];
        duplicateData = [];
        errorData = [];
        previewData = [];
        selectedRows.clear();
        currentPage = 1;
        totalPages = 1;
        workbook = null;
        sheetNames = [];
        // ล้างตารางทุกตัว
        if (previewTable) { previewTable.querySelector('thead').innerHTML=''; previewTable.querySelector('tbody').innerHTML=''; }
        if (validDataTable) { validDataTable.querySelector('thead').innerHTML=''; validDataTable.querySelector('tbody').innerHTML=''; }
        if (duplicateDataTable) { duplicateDataTable.querySelector('thead').innerHTML=''; duplicateDataTable.querySelector('tbody').innerHTML=''; }
        if (errorDataTable) { errorDataTable.querySelector('thead').innerHTML=''; errorDataTable.querySelector('tbody').innerHTML=''; }
        // รีเซ็ต badge count
        const counters = ['allDataCount','validDataCount','duplicateDataCount','errorDataCount'];
        counters.forEach(id => { const el = document.getElementById(id); if (el) el.textContent = '0'; });
        // รีเซ็ตปุ่ม import text
        if (importBtn) importBtn.textContent = 'นำเข้าข้อมูลที่เลือก';
    }

    // ผูก event เมื่อ modal ปิด
    const excelImportModalEl = document.getElementById('excelImportModal');
    if (excelImportModalEl) {
        excelImportModalEl.addEventListener('hidden.bs.modal', function() {
            resetExcelImportModal();
        });
    }

    if (excelFileInput) {
        // เพิ่ม styling เมื่อ hover และ drag
        excelFileInput.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#10b981';
            this.style.background = '#f0f9ff';
        });
        excelFileInput.addEventListener('dragleave', function(e) {
            this.style.borderColor = '#d1d5db';
            this.style.background = '#fafafa';
        });
        excelFileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (!file) return;
            loadingDiv.classList.remove('d-none');
            previewContainer.classList.add('d-none');
            importBtn.classList.add('d-none');
            sheetSelectorGroup.classList.add('d-none');
            if (importValidOnlyBtn) importValidOnlyBtn.classList.add('d-none');
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            if (clearSelectionBtn) clearSelectionBtn.classList.add('d-none');
            const reader = new FileReader();
            reader.onload = function (evt) {
                let data = evt.target.result;
                try {
                    if (file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                        // อ่าน Excel โดยไม่แปลงวันที่อัตโนมัติ - เก็บค่าดิบ (raw)
                        workbook = XLSX.read(data, { 
                            type: 'binary',
                            cellDates: false,  // ไม่แปลงเป็น Date object
                            cellNF: false,     // ไม่ใช้ number format
                            raw: true          // เก็บค่าดิบ
                        });
                    } else {
                        workbook = XLSX.read(data, { type: 'string', raw: true });
                    }
                } catch (err) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถอ่านไฟล์นี้ได้', 'error');
                    loadingDiv.classList.add('d-none');
                    return;
                }
                sheetNames = workbook.SheetNames;
                sheetSelector.innerHTML = '';
                sheetNames.forEach((name, idx) => {
                    sheetSelector.innerHTML += `<option value="${name}">${name}</option>`;
                });
                sheetSelectorGroup.classList.remove('d-none');
                loadSheet(sheetNames[0]);
                // รอ 2 วินาทีให้ผู้ใช้รู้สึกว่าระบบกำลังประมวลผล จากนั้นวิเคราะห์อัตโนมัติ
                setTimeout(() => {
                    analyzeData();
                }, 2000);
            };
            if (file.name.endsWith('.xls') || file.name.endsWith('.xlsx')) {
                reader.readAsBinaryString(file);
            } else {
                reader.readAsText(file);
            }
        });
    }

    if (sheetSelector) {
        sheetSelector.addEventListener('change', function () {
            // แสดง loading ใหม่และซ่อนทุกอย่างระหว่างเปลี่ยน sheet
            selectedRows.clear();
            previewContainer.classList.add('d-none');
            if (importBtn) importBtn.classList.add('d-none');
            if (importValidOnlyBtn) importValidOnlyBtn.classList.add('d-none');
            const selectAllBtn = document.getElementById('selectAllDataBtn');
            if (selectAllBtn) selectAllBtn.classList.add('d-none');
            const clearSelectionBtn = document.getElementById('clearSelectionBtn');
            if (clearSelectionBtn) clearSelectionBtn.classList.add('d-none');
            loadingDiv.classList.remove('d-none');
            // โหลดข้อมูล raw ของ sheet ที่เลือก (ยังไม่ render จนกว่าวิเคราะห์เสร็จ)
            loadSheet(sheetSelector.value);
            // หน่วง 300ms ป้องกัน UX กระตุก แล้ววิเคราะห์ใหม่
            setTimeout(() => {
                analyzeData();
            }, 300);
        });
    }

    function loadSheet(sheetName) {
        const sheet = workbook.Sheets[sheetName];
        
        // ดึงข้อมูลทุก row แบบดิบ (raw) โดยไม่ให้ SheetJS แปลงวันที่
        let allRows = XLSX.utils.sheet_to_json(sheet, { 
            header: 1,
            raw: false,        // ไม่ใช้ค่าดิบ แต่จะให้แปลงเป็น string
            dateNF: 'yyyy-mm-dd'  // ถ้ามีการแปลงวันที่ ให้ใช้รูปแบบนี้
        });
        
        // แต่เราจะจัดการกับ cell ที่เป็น date number เอง
        const range = XLSX.utils.decode_range(sheet['!ref']);
        for (let R = range.s.r; R <= range.e.r; ++R) {
            for (let C = range.s.c; C <= range.e.c; ++C) {
                const cellAddress = XLSX.utils.encode_cell({r: R, c: C});
                const cell = sheet[cellAddress];
                
                if (cell && cell.t === 'n' && cell.w) {
                    // ถ้าเป็นตัวเลข (n) และมีค่า formatted (w)
                    // ตรวจสอบว่าเป็น date format หรือไม่
                    if (cell.v > 25569 && cell.v < 60000) { // Excel date range
                        // ใช้ค่า formatted string แทน
                        if (allRows[R] && allRows[R][C] !== undefined) {
                            allRows[R][C] = cell.w || cell.v;
                        }
                    }
                }
            }
        }
        
        // กรองเฉพาะ row ที่มีข้อมูลจริง (ไม่นับ row ที่ว่างเปล่าทั้งแถว)
        previewData = allRows.filter((row, idx) => {
            if (idx === 0) return true; // header always keep
            // ถ้ามี cell ใด cell หนึ่งใน row นี้ไม่ว่าง ให้แสดง row นี้
            return row.some(cell => cell !== undefined && cell !== null && String(cell).trim() !== '');
        });
        currentPage = 1;
        selectedRows.clear(); // ล้างการเลือกเมื่อโหลด sheet ใหม่
        // ไม่แสดง preview และปุ่มต่างๆ ทันที รอให้ analysis เสร็จก่อน
    }
    
    // ฟังก์ชันวิเคราะห์ข้อมูลและแยกประเภท
    function analyzeData() {
        const sheetName = sheetSelector.value;
        const mapping = getColumnMapping(sheetName);
        
        allData = [];
        validData = [];
        duplicateData = [];
        errorData = [];
        
        // เตรียมข้อมูลสำหรับส่งไป backend วิเคราะห์
        const dataToAnalyze = [];
        for (let i = 1; i < previewData.length; i++) {
            const rowData = previewData[i];
            const mappedData = mapRowData(rowData, previewData[0], mapping, sheetName);
            
            // Debug: แสดงข้อมูล 3 แถวแรกเพื่อตรวจสอบการแปลงวันที่
            if (i <= 3) {
                console.log('Row', i + 1, '- Original:', rowData);
                console.log('Row', i + 1, '- Mapped:', mappedData);
                console.log('Row', i + 1, '- Date of birth:', mappedData?.date_of_birth);
            }
            
            if (mappedData) {
                allData.push({
                    row_number: i + 1,
                    data: mappedData,
                    original_row: rowData
                });
                dataToAnalyze.push({
                    row_number: i + 1,
                    data: mappedData,
                    original_row: rowData
                });
            } else {
                errorData.push({
                    row_number: i + 1,
                    data: rowData,
                    errors: ['ข้อมูลไม่ครบถ้วน (ขาดชื่อหรือนามสกุล)']
                });
            }
        }
        
        // ส่งข้อมูลไป backend วิเคราะห์
        fetch('/api/import/excel/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                sheet_type: sheetName,
                preview_data: dataToAnalyze
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // แยกข้อมูลตามผลการวิเคราะห์
                if (data.valid_data) {
                    validData = data.valid_data;
                }
                if (data.duplicate_data) {
                    duplicateData = data.duplicate_data;
                }
                if (data.error_data) {
                    errorData = [...errorData, ...data.error_data];
                }
                
                // อัปเดต badge count
                updateTabCounts();
                
                // แสดงข้อมูลใน tabs
                renderTabData();
                
                // แสดงปุ่ม import
                if (validData.length > 0) {
                    importValidOnlyBtn.classList.remove('d-none');
                }
                importBtn.classList.remove('d-none');
                
                // แสดง preview และปุ่มต่างๆ หลังวิเคราะห์เสร็จ
                renderPreviewTable();
                previewContainer.classList.remove('d-none');
                updateImportButtonText();
                
                // แสดงปุ่มเสริม
                const selectAllBtn = document.getElementById('selectAllDataBtn');
                const clearSelectionBtn = document.getElementById('clearSelectionBtn');
                if (selectAllBtn) selectAllBtn.classList.remove('d-none');
                if (clearSelectionBtn) clearSelectionBtn.classList.remove('d-none');
                
                // ซ่อน loading หลังแสดงข้อมูลเสร็จ
                loadingDiv.classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('Analysis error:', error);
            // ซ่อน loading เมื่อเกิดข้อผิดพลาด
            loadingDiv.classList.add('d-none');
            Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการวิเคราะห์ข้อมูล', 'error');
        });
    }
    
    // ฟังก์ชันอัปเดตจำนวนข้อมูลใน tabs
    function updateTabCounts() {
        document.getElementById('allDataCount').textContent = allData.length;
        document.getElementById('validDataCount').textContent = validData.length;
        document.getElementById('duplicateDataCount').textContent = duplicateData.length;
        document.getElementById('errorDataCount').textContent = errorData.length;
    }
    
    // ฟังก์ชันแสดงข้อมูลใน tabs
    function renderTabData() {
        // Tab ข้อมูลที่ไม่ซ้ำ
        renderDataTable(validDataTable, validData, 'valid');
        
        // Tab ข้อมูลที่ซ้ำ
        renderDataTable(duplicateDataTable, duplicateData, 'duplicate');
        
        // Tab ข้อมูลที่ผิดพลาด
        renderDataTable(errorDataTable, errorData, 'error');
    }
    
    // ฟังก์ชันแสดงข้อมูลในตาราง
    function renderDataTable(table, data, type) {
        if (!data || data.length === 0) {
            table.querySelector('thead').innerHTML = '';
            table.querySelector('tbody').innerHTML = `
                <tr><td colspan="100%" class="text-center py-4 text-muted">ไม่มีข้อมูล</td></tr>
            `;
            return;
        }
        
        // สร้าง header
        const headers = previewData[0] || [];
        let thead = '<tr>';
        if (type !== 'error') {
            thead += '<th style="width:40px; padding: 12px 8px;"><input type="checkbox" class="selectAllTab" style="transform: scale(1.1);"></th>';
        }
        thead += '<th style="width:60px; padding: 12px 8px;">แถว</th>';
        headers.forEach(header => {
            thead += `<th style="padding: 12px 16px; font-weight: 500; font-size: 13px;">${header || ''}</th>`;
        });
        if (type === 'duplicate') {
            thead += '<th style="padding: 12px 16px;">ฟิลด์ที่ซ้ำ</th>';
        }
        if (type === 'error') {
            thead += '<th style="padding: 12px 16px;">ข้อผิดพลาด</th>';
        }
        thead += '</tr>';
        table.querySelector('thead').innerHTML = thead;
        
        // สร้าง body
        let tbody = '';
        data.forEach(item => {
            tbody += '<tr style="border-bottom: 1px solid #f3f4f6;">';
            if (type !== 'error') {
                tbody += `<td style="padding: 12px 8px;"><input type="checkbox" class="rowCheckbox" data-row="${item.row_number}" style="transform: scale(1.1);"></td>`;
            }
            tbody += `<td style="padding: 12px 8px; font-weight: 500;">${item.row_number}</td>`;
            
            const rowData = item.original_row || item.data;
            if (Array.isArray(rowData)) {
                rowData.forEach(cell => {
                    tbody += `<td style="padding: 12px 16px; font-size: 13px;">${cell || ''}</td>`;
                });
            } else if (typeof rowData === 'object') {
                headers.forEach(header => {
                    const value = rowData[header] || '';
                    tbody += `<td style="padding: 12px 16px; font-size: 13px;">${value}</td>`;
                });
            }
            
            if (type === 'duplicate' && item.duplicate_fields) {
                const duplicateText = item.duplicate_fields.map(field => {
                    const fieldNames = {
                        'email': 'อีเมล',
                        'student_id': 'รหัสนักเรียน',
                        'teacher_id': 'รหัสครู',
                        'phone': 'เบอร์โทรศัพท์'
                    };
                    return fieldNames[field] || field;
                }).join(', ');
                tbody += `<td style="padding: 12px 16px;"><span class="badge bg-warning">${duplicateText}</span></td>`;
            }
            
            if (type === 'error' && item.errors) {
                tbody += `<td style="padding: 12px 16px;"><span class="text-danger small">${item.errors.join(', ')}</span></td>`;
            }
            
            tbody += '</tr>';
        });
        table.querySelector('tbody').innerHTML = tbody;
    }

    // ใช้ selectedRows ที่ประกาศไว้ตอนต้น (ลบการประกาศซ้ำเพื่อป้องกัน SyntaxError)

    function renderPreviewTable() {
        previewTable.querySelector('thead').innerHTML = '';
        previewTable.querySelector('tbody').innerHTML = '';
        if (!previewData.length) return;
        // pagination
        totalPages = Math.ceil((previewData.length - 1) / pageSize) || 1;
        // เพิ่ม checkbox header
        let thead = '<tr>';
        thead += '<th style="width:40px; padding: 12px 8px; border-bottom: 1px solid #e5e7eb;"><input type="checkbox" id="selectAllRows" style="transform: scale(1.1); accent-color: #10b981;"></th>';
        previewData[0].forEach(cell => {
            thead += `<th style="padding: 12px 16px; font-weight: 500; color: #374151; font-size: 13px; border-bottom: 1px solid #e5e7eb;">${cell ?? ''}</th>`;
        });
        thead += '</tr>';
        previewTable.querySelector('thead').innerHTML = thead;
        let tbody = '';
        const start = 1 + (currentPage - 1) * pageSize;
        const end = Math.min(start + pageSize, previewData.length);
        for (let i = start; i < end; i++) {
            const rowId = i; // ใช้ index จริงใน previewData
            tbody += '<tr style="border-bottom: 1px solid #f3f4f6;">';
            tbody += `<td style="padding: 12px 8px;"><input type="checkbox" class="rowCheckbox" data-row="${rowId}" ${selectedRows.has(rowId) ? 'checked' : ''} style="transform: scale(1.1); accent-color: #10b981;"></td>`;
            previewData[i].forEach(cell => {
                tbody += `<td style="padding: 12px 16px; color: #374151; font-size: 13px;">${cell ?? ''}</td>`;
            });
            tbody += '</tr>';
        }
        previewTable.querySelector('tbody').innerHTML = tbody;
        // handle select all
        const selectAll = previewTable.querySelector('#selectAllRows');
        if (selectAll) {
            // ถ้าเลือกครบทุก row ในหน้านี้ ให้ selectAll เป็น checked
            let allChecked = true;
            for (let i = start; i < end; i++) {
                if (!selectedRows.has(i)) { allChecked = false; break; }
            }
            selectAll.checked = allChecked;
            selectAll.addEventListener('change', function() {
                for (let i = start; i < end; i++) {
                    if (this.checked) selectedRows.add(i);
                    else selectedRows.delete(i);
                }
                renderPreviewTable();
                updateImportButtonText();
            });
        }
        // handle row checkbox
        previewTable.querySelectorAll('.rowCheckbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const rowIdx = parseInt(this.getAttribute('data-row'));
                if (this.checked) selectedRows.add(rowIdx);
                else selectedRows.delete(rowIdx);
                // ไม่ต้อง render ใหม่เพื่อความลื่นไหล
                updateImportButtonText();
            });
        });
        renderPagination();
    }

    // ฟังก์ชันอัปเดตข้อความปุ่ม import
    function updateImportButtonText() {
        if (selectedRows.size > 0) {
            importBtn.textContent = `นำเข้าข้อมูล ${selectedRows.size} รายการ`;
            importBtn.classList.remove('d-none');
        } else {
            importBtn.textContent = 'นำเข้าข้อมูลที่เลือก';
            importBtn.classList.add('d-none');
        }
    }

    // เพิ่ม event listener สำหรับปุ่ม import
    if (importBtn) {
        importBtn.addEventListener('click', function() {
            if (selectedRows.size === 0) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกข้อมูลที่ต้องการนำเข้า', 'warning');
                return;
            }
            
            performImport();
        });
    }

    // ปุ่มยกเลิกการเลือกทั้งหมด
    const clearSelectionBtn = document.getElementById('clearSelectionBtn');
    if (clearSelectionBtn) {
        clearSelectionBtn.addEventListener('click', function() {
            selectedRows.clear();
            updateImportButtonText();
            renderPreviewTable();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'ล้างการเลือกทั้งหมดแล้ว',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
        });
    }
    
    // เพิ่ม event listener สำหรับปุ่มนำเข้าเฉพาะข้อมูลที่ไม่ซ้ำ
    if (importValidOnlyBtn) {
        importValidOnlyBtn.addEventListener('click', function() {
            performImport(validData);
        });
    }
    
    // เพิ่ม event listener สำหรับปุ่มเลือกทั้งหมด
    const selectAllDataBtn = document.getElementById('selectAllDataBtn');
    if (selectAllDataBtn) {
        selectAllDataBtn.addEventListener('click', function() {
            for (let i = 1; i < previewData.length; i++) {
                selectedRows.add(i);
            }
            renderPreviewTable();
            updateImportButtonText();
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: `เลือกทั้งหมด ${previewData.length - 1} แถว`,
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
        });
    }

    // เพิ่ม event listener สำหรับปุ่มตรวจสอบข้อมูลซ้ำ
    const checkDuplicatesBtn = document.getElementById('checkDuplicatesBtn');
    if (checkDuplicatesBtn) {
        checkDuplicatesBtn.addEventListener('click', function() {
            checkForDuplicates();
        });
    }

    // ฟังก์ชันตรวจสอบข้อมูลซ้ำ
    function checkForDuplicates() {
        const sheetName = sheetSelector.value;
        const mapping = getColumnMapping(sheetName);
        
        // เตรียมข้อมูลทั้งหมดสำหรับตรวจสอบ
        const allData = [];
        for (let i = 1; i < previewData.length; i++) {
            const rowData = previewData[i];
            const mappedData = mapRowData(rowData, previewData[0], mapping, sheetName);
            
            if (mappedData) {
                allData.push({
                    row_number: i + 1,
                    data: mappedData,
                    original_row: rowData
                });
            }
        }

        // แสดง modal และส่งข้อมูลไปตรวจสอบ
        const duplicateModal = new bootstrap.Modal(document.getElementById('duplicateDataModal'));
        duplicateModal.show();
        
        // แสดง loading
        document.getElementById('duplicateDataLoading').classList.remove('d-none');
        document.getElementById('duplicateDataResults').classList.add('d-none');
        
        // ส่งข้อมูลไปตรวจสอบที่ backend
        fetch('/api/import/excel/preview', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                sheet_type: sheetName,
                preview_data: allData
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('duplicateDataLoading').classList.add('d-none');
            
            if (data.success && data.duplicate_data && data.duplicate_data.length > 0) {
                displayDuplicateData(data.duplicate_data);
                document.getElementById('duplicateDataResults').classList.remove('d-none');
            } else {
                document.getElementById('duplicateDataContent').innerHTML = `
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5>ไม่พบข้อมูลซ้ำ</h5>
                        <p class="text-muted">ข้อมูลทั้งหมดสามารถนำเข้าได้</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Duplicate check error:', error);
            document.getElementById('duplicateDataLoading').classList.add('d-none');
            document.getElementById('duplicateDataContent').innerHTML = `
                <div class="text-center py-4 text-danger">
                    <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                    <p>เกิดข้อผิดพลาดในการตรวจสอบข้อมูลซ้ำ</p>
                </div>
            `;
        });
    }

    // ฟังก์ชันแสดงข้อมูลซ้ำ
    function displayDuplicateData(duplicates) {
        const tbody = document.getElementById('duplicateDataTableBody');
        tbody.innerHTML = '';
        
        duplicates.forEach(item => {
            const row = document.createElement('tr');
            row.style.borderBottom = '1px solid #f3f4f6';
            
            const duplicateFields = item.duplicate_fields || [];
            const duplicateText = duplicateFields.map(field => {
                const fieldNames = {
                    'email': 'อีเมล',
                    'student_id': 'รหัสนักเรียน',
                    'teacher_id': 'รหัสครู',
                    'phone': 'เบอร์โทรศัพท์'
                };
                return fieldNames[field] || field;
            }).join(', ');
            
            row.innerHTML = `
                <td style="padding: 12px 16px; color: #374151;">${item.row_number}</td>
                <td style="padding: 12px 16px; color: #374151;">
                    ${item.data.first_name || ''} ${item.data.last_name || ''}
                </td>
                <td style="padding: 12px 16px;">
                    <span class="badge" style="background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 6px; font-size: 11px;">
                        ${duplicateText}
                    </span>
                </td>
                <td style="padding: 12px 16px;">
                    <button class="btn btn-sm btn-outline-info" onclick="showDuplicateDetails(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                        <i class="fas fa-eye"></i> ดูรายละเอียด
                    </button>
                </td>
                <td style="padding: 12px 16px;">
                    <button class="btn btn-sm btn-outline-danger" onclick="removeDuplicateRow(${item.row_number - 1})">
                        <i class="fas fa-times"></i> ไม่นำเข้า
                    </button>
                </td>
            `;
            
            tbody.appendChild(row);
        });
    }

    // ฟังก์ชันแสดงรายละเอียดข้อมูลซ้ำ
    window.showDuplicateDetails = function(item) {
        const details = Object.entries(item.data)
            .filter(([key, value]) => value)
            .map(([key, value]) => `<strong>${key}:</strong> ${value}`)
            .join('<br>');
            
        Swal.fire({
            title: `รายละเอียดแถว ${item.row_number}`,
            html: details,
            icon: 'info',
            width: '600px'
        });
    };

    // ฟังก์ชันลบ row ที่ซ้ำออกจากการเลือก
    window.removeDuplicateRow = function(rowIndex) {
        selectedRows.delete(rowIndex);
        renderPreviewTable();
        updateImportButtonText();
        
        Swal.fire({
            title: 'ลบออกจากการเลือกแล้ว',
            text: `แถว ${rowIndex + 1} จะไม่ถูกนำเข้า`,
            icon: 'success',
            timer: 1500,
            showConfirmButton: false
        });
    };

    // ฟังก์ชันดำเนินการ import
    function performImport(dataToImport = null) {
        let selectedData = [];
        
        if (dataToImport) {
            // ใช้ข้อมูลที่ส่งมา (เช่น validData)
            selectedData = dataToImport.map(item => ({
                row_number: item.row_number,
                data: item.data
            }));
        } else {
            // ใช้ข้อมูลที่เลือกจาก table หลัก
            const sheetName = sheetSelector.value;
            const mapping = getColumnMapping(sheetName);
            
            selectedRows.forEach(rowIdx => {
                if (rowIdx > 0 && rowIdx < previewData.length) { // ไม่รวม header
                    const rowData = previewData[rowIdx];
                    const mappedData = mapRowData(rowData, previewData[0], mapping, sheetName);
                    
                    if (mappedData) {
                        selectedData.push({
                            row_number: rowIdx + 1,
                            data: mappedData
                        });
                    }
                }
            });
        }
        
        if (selectedData.length === 0) {
            Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลที่ถูกต้องสำหรับการนำเข้า', 'error');
            return;
        }
        
        // แสดง loading และส่งข้อมูล
        Swal.fire({
            title: 'กำลังนำเข้าข้อมูล...',
            html: `กำลังประมวลผล ${selectedData.length} รายการ`,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // ส่งข้อมูลไป backend
        fetch('/api/import/excel/commit', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                selected_data: selectedData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.results.success_count === 0) {
                    Swal.fire({
                        title: 'ไม่สามารถนำเข้าข้อมูลได้',
                        html: `<div class="text-danger mb-2"><i class="fas fa-times-circle"></i> ไม่สำเร็จ: 0 รายการ<br>กรุณาตรวจสอบข้อมูลนำเข้าอีกครั้ง</div>
                        ${data.results.errors && data.results.errors.length > 0 ? '<div class="mt-2"><small>' + data.results.errors.slice(0, 5).join('<br>') + '</small></div>' : ''}`,
                        icon: 'error',
                        confirmButtonText: 'ตกลง'
                    });
                } else {
                    Swal.fire({
                        title: 'นำเข้าข้อมูลสำเร็จ!',
                        html: `
                            <div class="text-success mb-2">
                                <i class="fas fa-check-circle"></i> สำเร็จ: ${data.results.success_count} รายการ
                            </div>
                            ${data.results.error_count > 0 ? 
                                `<div class="text-danger">
                                    <i class="fas fa-exclamation-circle"></i> ข้อผิดพลาด: ${data.results.error_count} รายการ
                                </div>` : ''
                            }
                            ${data.results.errors && data.results.errors.length > 0 ? 
                                '<div class="mt-2"><small>' + data.results.errors.slice(0, 5).join('<br>') + '</small></div>' : ''
                            }
                        `,
                        icon: 'success',
                        confirmButtonText: 'ตกลง'
                    }).then(() => {
                        // ปิด modal และรีเฟรชหน้า
                        const modal = bootstrap.Modal.getInstance(document.getElementById('excelImportModal'));
                        modal.hide();
                        location.reload();
                    });
                }
            } else {
                Swal.fire('ข้อผิดพลาด', data.error || 'เกิดข้อผิดพลาดในการนำเข้าข้อมูล', 'error');
            }
        })
        .catch(error => {
            console.error('Import error:', error);
            Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        });
    }

    // เพิ่ม event listener สำหรับปุ่มนำเข้าเฉพาะข้อมูลที่ไม่ซ้ำ
    const importWithoutDuplicatesBtn = document.getElementById('importWithoutDuplicatesBtn');
    if (importWithoutDuplicatesBtn) {
        importWithoutDuplicatesBtn.addEventListener('click', function() {
            // ปิด duplicate modal ก่อน
            const duplicateModal = bootstrap.Modal.getInstance(document.getElementById('duplicateDataModal'));
            duplicateModal.hide();
            
            // เอาเฉพาะข้อมูลที่ไม่ซ้ำมา import
            performImport();
        });
    }

    function renderPagination() {
        pagination.innerHTML = '';
        if (totalPages <= 1) return;
        let html = '';
        html += `<li class="page-item${currentPage === 1 ? ' disabled' : ''}">
                    <a class="page-link" href="#" data-page="prev" 
                       style="border: 1px solid #e5e7eb; border-radius: 6px; margin-right: 4px; padding: 8px 12px; color: #6b7280; text-decoration: none;">
                       &laquo;
                    </a>
                 </li>`;
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || Math.abs(i - currentPage) <= 2) {
                const isActive = i === currentPage;
                html += `<li class="page-item${isActive ? ' active' : ''}">
                            <a class="page-link" href="#" data-page="${i}" 
                               style="border: 1px solid ${isActive ? '#10b981' : '#e5e7eb'}; 
                                      border-radius: 6px; margin-right: 4px; padding: 8px 12px; 
                                      color: ${isActive ? 'white' : '#6b7280'}; 
                                      background: ${isActive ? '#10b981' : 'white'}; 
                                      text-decoration: none;">
                               ${i}
                            </a>
                         </li>`;
            } else if (i === 2 && currentPage > 4) {
                html += '<li class="page-item disabled"><span class="page-link" style="border: none; color: #9ca3af;">...</span></li>';
            } else if (i === totalPages - 1 && currentPage < totalPages - 3) {
                html += '<li class="page-item disabled"><span class="page-link" style="border: none; color: #9ca3af;">...</span></li>';
            }
        }
        html += `<li class="page-item${currentPage === totalPages ? ' disabled' : ''}">
                    <a class="page-link" href="#" data-page="next" 
                       style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px 12px; color: #6b7280; text-decoration: none;">
                       &raquo;
                    </a>
                 </li>`;
        pagination.innerHTML = html;
        // event
        pagination.querySelectorAll('a.page-link').forEach(a => {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                let page = this.getAttribute('data-page');
                if (page === 'prev' && currentPage > 1) currentPage--;
                else if (page === 'next' && currentPage < totalPages) currentPage++;
                else if (!isNaN(parseInt(page))) currentPage = parseInt(page);
                renderPreviewTable();
            });
            // เพิ่ม hover effect
            a.addEventListener('mouseenter', function() {
                if (!this.closest('.disabled') && !this.closest('.active')) {
                    this.style.background = '#f3f4f6';
                }
            });
            a.addEventListener('mouseleave', function() {
                if (!this.closest('.active')) {
                    this.style.background = 'white';
                }
            });
        });
    }

    // ฟังก์ชัน mapping ข้อมูลตาม column
    function getColumnMapping(sheetName) {
        const baseMapping = {
            'first_name': ['ชื่อจริง', 'ชื่อ', 'first_name', 'firstname', 'fname', 'f_name', 'name'],
            'last_name': ['นามสกุล', 'last_name', 'lastname', 'surname', 'lname', 'l_name'],
            'email': ['อีเมลล์', 'อีเมล', 'email', 'e-mail', 'mail'],
            'phone': ['โทร', 'เบอร์โทรศัพท์', 'เบอร์โทร', 'เบอร์', 'phone', 'telephone', 'mobile']
        };

        const roleMapping = {
            'student': Object.assign({}, baseMapping, {
                'student_id': ['รหัสนักเรียน', 'student_id', 'รหัส', 'id', 'รหัสประจำตัว'],
                'date_of_birth': ['วันเกิด', 'date_of_birth', 'birthday', 'birth_date', 'dob'],
                'gender': ['เพศ', 'gender', 'sex'],
                'grade_level': ['ระดับชั้น', 'ระดับ', 'ชั้น', 'grade', 'level', 'class_level'],
                'classroom': ['ห้อง', 'ห้องเรียน', 'classroom', 'class', 'room'],
                'status': ['สถานะ', 'status']
            }),
            'teacher': Object.assign({}, baseMapping, {
                'teacher_id': ['รหัสครู', 'teacher_id', 'รหัส', 'id', 'รหัสประจำตัว'],
                'title': ['คำนำหน้า', 'title', 'prefix', 'คำนำหน้าชื่อ'],
                'position': ['ตำแหน่ง', 'position', 'job_title', 'title_position'],
                'subject_group': ['กลุ่มสาระการเรียนรู้', 'กลุ่มสาระ', 'subject_group', 'learning_area'],
                'subjects': ['วิชาที่สอน', 'วิชา', 'subject', 'subjects', 'teaching_subject']
            }),
            'guardian': Object.assign({}, baseMapping, {
                'title': ['คำนำหน้า', 'คำนำหน้าชื่อ', 'title', 'prefix', 'นาย', 'นาง', 'นางสาว'],
                'guardian_id': ['รหัสผู้ปกครอง', 'guardian_id', 'รหัส', 'id', 'รหัสประจำตัว'],
                'relationship': ['ความสัมพันธ์', 'relationship', 'relation'],
                'line_id': ['ไอดีไลน์', 'line_id', 'line', 'ไลน์', 'ID Line'],
                'contact_method': ['ช่องทางติดต่อที่ใช้บ่อยที่สุด', 'ช่องทางติดต่อที่ง่ายที่สุด', 'contact_method', 'preferred_contact', 'ช่องทางติดต่อ'],
                'student_codes': ['รหัสนักเรียนที่ดูแล', 'รหัสนักเรียน', 'student_codes', 'student_id', 'รหัสลูก', 'รหัสบุตร', 'รหัสนักเรียนภายใต้ความดูแล']
            })
        };

        // ตรวจจาก sheet name ว่าเป็นประเภทไหน
        const sheetLower = sheetName.toLowerCase();
        if (sheetLower.includes('student') || sheetLower.includes('นักเรียน')) {
            return roleMapping.student;
        } else if (sheetLower.includes('teacher') || sheetLower.includes('ครู')) {
            return roleMapping.teacher;
        } else if (sheetLower.includes('guardian') || sheetLower.includes('ผู้ปกครอง') || sheetLower.includes('parent')) {
            return roleMapping.guardian;
        }
        
        // default เป็น student
        return roleMapping.student;
    }

    // ฟังก์ชัน map ข้อมูลแต่ละแถวตาม header
    function mapRowData(rowData, headers, mapping, sheetName) {
        const mappedData = {};
        
        // กำหนด role ตาม sheet name
        const sheetLower = sheetName.toLowerCase();
        if (sheetLower.includes('teacher') || sheetLower.includes('ครู')) {
            mappedData.role = 'teacher';
        } else if (sheetLower.includes('guardian') || sheetLower.includes('ผู้ปกครอง') || sheetLower.includes('parent')) {
            mappedData.role = 'guardian';
        } else {
            mappedData.role = 'student';
        }

        // map แต่ละ column
        headers.forEach((header, index) => {
            if (header && rowData[index] !== undefined && rowData[index] !== null && String(rowData[index]).trim() !== '') {
                const headerLower = header.toLowerCase().trim();
                
                // หาใน mapping ว่า header นี้ตรงกับ field ไหน
                for (const [field, aliases] of Object.entries(mapping)) {
                    if (aliases.some(alias => alias.toLowerCase() === headerLower || headerLower.includes(alias.toLowerCase()))) {
                        let value = String(rowData[index]).trim();
                        
                        // แปลง Excel serial date สำหรับ date_of_birth
                        if (field === 'date_of_birth') {
                            // ตรวจสอบว่าเป็นรูปแบบวันที่ที่ถูกต้องอยู่แล้วหรือไม่
                            const datePattern = /^\d{4}-\d{2}-\d{2}$/;
                            const datePattern2 = /^\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}$/;
                            
                            if (datePattern.test(value)) {
                                // ถ้าเป็น YYYY-MM-DD อยู่แล้ว ใช้เลย
                                mappedData[field] = value;
                            } else if (datePattern2.test(value)) {
                                // ถ้าเป็น DD/MM/YYYY หรือ DD-MM-YYYY ใช้เลย (ให้ PHP แปลงต่อ)
                                mappedData[field] = value;
                            } else if (!isNaN(value) && Number(value) > 25569 && Number(value) < 60000) {
                                // ถ้าเป็น Excel serial date number
                                const excelEpoch = new Date(1899, 11, 30);
                                const daysToAdd = parseInt(value);
                                const date = new Date(excelEpoch.getTime() + daysToAdd * 24 * 60 * 60 * 1000);
                                
                                const year = date.getFullYear();
                                const month = String(date.getMonth() + 1).padStart(2, '0');
                                const day = String(date.getDate()).padStart(2, '0');
                                mappedData[field] = `${year}-${month}-${day}`;
                            } else {
                                // ส่งค่าไปให้ PHP จัดการ
                                mappedData[field] = value;
                            }
                        } else {
                            mappedData[field] = value;
                        }
                        break;
                    }
                }
            }
        });

        // ตรวจสอบข้อมูลขั้นต้น
        if (!mappedData.first_name || !mappedData.last_name) {
            return null; // ข้อมูลไม่ครบ
        }

        return mappedData;
    }
});
</script>
</script>
            </div>
        </div>
    </div>

    <!-- New Violation Modal -->
    <div class="modal fade" id="newViolationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">บันทึกพฤติกรรมนักเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="violationForm">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">ค้นหานักเรียน <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="behaviorStudentSearch"
                                    placeholder="พิมพ์ชื่อหรือรหัสนักเรียน..." autocomplete="off">
                                <div id="studentResults" class="list-group mt-2" style="display: none;"></div>
                                <input type="hidden" id="selectedStudentId" name="student_id" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">กรองตามห้อง</label>
                                <select class="form-select" id="classFilter">
                                    <option value="">ทุกห้อง</option>
                                    <!-- ตัวเลือกจะถูกเพิ่มด้วย JavaScript -->
                                </select>
                            </div>
                        </div>

                        <div id="selectedStudentInfo" class="alert alert-info" style="display: none;">
                            <h6 class="mb-1">นักเรียนที่เลือก:</h6>
                            <div id="studentInfoDisplay"></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">ประเภทพฤติกรรม <span class="text-danger">*</span></label>
                                <select class="form-select" id="violationType" name="violation_id" data-violation-select
                                    required>
                                    <option value="">เลือกประเภทพฤติกรรม</option>
                                    <!-- ตัวเลือกจะถูกเพิ่มด้วย JavaScript -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">คะแนนที่หัก <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="pointsDeducted" min="0" max="100"
                                    value="0" readonly>
                                <div class="form-text">คะแนนจะกำหนดตามประเภทที่เลือก</div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label">วันที่เกิดเหตุการณ์ <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="violationDate" name="violation_date"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">เวลาที่เกิดเหตุการณ์ <span
                                        class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="violationTime" name="violation_time"
                                    required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">รายละเอียดเพิ่มเติม</label>
                            <textarea class="form-control" id="violationDescription" name="description" rows="3"
                                placeholder="อธิบายรายละเอียดของเหตุการณ์..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">แนบหลักฐาน (ถ้ามี)</label>
                            <input type="file" class="form-control" id="evidenceFile" name="evidence" accept="image/*">
                            <div class="form-text">รองรับไฟล์ภาพเท่านั้น (JPG, PNG, GIF)</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary-app" id="saveViolationBtn">
                        <i class="fas fa-save me-1"></i> บันทึกพฤติกรรม
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Violation Types Modal -->
    <div class="modal fade" id="violationTypesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content animate__animated animate__fadeInUp animate__faster">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">จัดการประเภทพฤติกรรม</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- การค้นหาและการเพิ่มใหม่ -->
                    <div class="d-flex justify-content-between mb-3 animate__animated animate__fadeIn"
                        style="animation-delay: 0.1s">
                        <div class="input-group" style="max-width: 300px;">
                            <input type="text" class="form-control" id="violationTypeSearch"
                                placeholder="ค้นหาประเภทพฤติกรรม...">
                            <button class="btn btn-primary-app" type="button"><i class="fas fa-search"></i></button>
                        </div>
                        <button class="btn btn-primary-app" id="btnShowAddViolationType">
                            <i class="fas fa-plus me-2"></i>เพิ่มประเภทพฤติกรรมใหม่
                        </button>
                    </div>

                    <!-- ส่วนแสดงรายการประเภทพฤติกรรม -->
                    <div id="violationTypesList" class="mb-4 animate__animated animate__fadeIn"
                        style="animation-delay: 0.2s">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 35%">ชื่อพฤติกรรม</th>
                                        <th style="width: 15%" class="text-center">ระดับความรุนแรง</th>
                                        <th style="width: 15%" class="text-center">คะแนนที่หัก</th>
                                        <th style="width: 25%">รายละเอียด</th>
                                        <th style="width: 10%" class="text-center">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- ข้อมูลจะถูกเติมด้วย JavaScript -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <nav aria-label="Violation types pagination">
                            <ul class="pagination pagination-sm justify-content-center mt-3 mb-0">
                                <!-- การแบ่งหน้าจะถูกสร้างด้วย JavaScript -->
                            </ul>
                        </nav>
                    </div>

                    <!-- ฟอร์มเพิ่ม/แก้ไขประเภทพฤติกรรม (ซ่อนไว้ก่อน) -->
                    <div class="card d-none" id="violationTypeForm">
                        <div class="card-body">
                            <!-- เนื้อหาฟอร์มจะถูกเติมด้วย JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Violation Type Modal -->
    <div class="modal fade" id="addViolationTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content animate__animated animate__fadeInUp animate__faster">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">เพิ่มประเภทพฤติกรรมใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addViolationTypeForm" class="needs-validation" novalidate>
                        <input type="hidden" id="violation_id" name="id">

                        <div class="mb-3">
                            <label for="violation_name" class="form-label">ชื่อพฤติกรรม <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="violation_name" name="name"
                                placeholder="ระบุชื่อพฤติกรรม เช่น มาสาย, ไม่ทำการบ้าน" required>
                            <div class="invalid-feedback">กรุณาระบุชื่อพฤติกรรม</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="violation_category" class="form-label">ระดับความรุนแรง <span
                                        class="text-danger">*</span></label>
                                <select class="form-select" id="violation_category" name="category" required>
                                    <option value="" selected disabled>เลือกระดับความรุนแรง</option>
                                    <option value="light">เบา</option>
                                    <option value="medium">ปานกลาง</option>
                                    <option value="severe">หนัก</option>
                                </select>
                                <div class="invalid-feedback">กรุณาเลือกระดับความรุนแรง</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="violation_points" class="form-label">คะแนนที่หัก <span
                                        class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="violation_points" name="points_deducted"
                                    min="0" max="100" required placeholder="ระบุคะแนนที่หัก">
                                <div class="invalid-feedback">กรุณาระบุคะแนนที่หัก (0-100)</div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="violation_description" class="form-label">รายละเอียด</label>
                            <textarea class="form-control" id="violation_description" name="description" rows="3"
                                placeholder="อธิบายรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                        </div>

                        <div class="alert alert-success save-success d-none">
                            <i class="fas fa-check-circle me-2"></i>
                            บันทึกข้อมูลสำเร็จ
                        </div>

                        <div class="alert alert-danger save-error d-none">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span class="error-message">เกิดข้อผิดพลาดในการบันทึกข้อมูล</span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary-app" id="btnSaveViolationType">
                        <i class="fas fa-save me-1"></i> บันทึกข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Violation Confirmation Modal -->
    <div class="modal fade" id="deleteViolationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">ยืนยันการลบ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5>ยืนยันการลบประเภทพฤติกรรมนี้?</h5>
                    <p class="text-muted">การลบประเภทพฤติกรรมนี้อาจส่งผลกระทบต่อข้อมูลพฤติกรรมที่บันทึกไว้แล้ว</p>
                    <input type="hidden" id="deleteViolationId">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteViolation">ยืนยันการลบ</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import/Export Modal -->
    <div class="modal fade" id="importExportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">นำเข้าและส่งออกข้อมูล</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">ส่งออกรายงาน</h5>
                            <p class="card-text text-muted">เลือกรูปแบบรายงานที่ต้องการส่งออก</p>
                            <div class="d-grid gap-2">
                                <button
                                    class="btn btn-outline-success d-flex justify-content-between align-items-center"
                                    id="generateMonthlyReport" onclick="generateMonthlyReport()">
                                    <span>รายงานพฤติกรรมประจำเดือน</span>
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button
                                    class="btn btn-outline-primary d-flex justify-content-between align-items-center"
                                    id="generateRiskStudentsReport" onclick="generateRiskStudentsReport()">
                                    <span>รายงานสรุปนักเรียนที่มีความเสี่ยง</span>
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <button
                                    class="btn btn-outline-warning d-flex justify-content-between align-items-center"
                                    id="generateAllBehaviorDataReport" onclick="generateAllBehaviorDataReport()">
                                    <span>ส่งออกข้อมูลพฤติกรรมทั้งหมด</span>
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Violation Detail Modal -->
    <div class="modal fade" id="violationDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">รายละเอียดการกระทำผิด</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="violationDetailContent">
                    <!-- Loading State -->
                    <div id="violationDetailLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">กำลังโหลด...</span>
                        </div>
                        <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                    </div>

                    <!-- Content will be loaded here -->
                    <div id="violationDetailData" style="display: none;">
                        <div class="d-flex align-items-center mb-3" id="studentInfo">
                            <!-- Student info will be loaded here -->
                        </div>

                        <div class="card mb-3">
                            <div class="card-body" id="violationInfo">
                                <!-- Violation details will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Error State -->
                    <div id="violationDetailError" class="text-center py-4 text-danger" style="display: none;">
                        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                        <p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-danger me-auto" id="deleteReportBtn"
                        style="display: none;">
                        ลบบันทึก
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-primary-app" id="editReportBtn" style="display: none;">
                        แก้ไข
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Class Management Modal -->
    <div class="modal fade" id="classManagementModal" tabindex="-1" aria-labelledby="classManagementModalLabel"
        role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-md modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header border-0 pb-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-school text-primary me-2 fs-5"></i>
                        <h5 class="modal-title mb-0" id="classManagementModalLabel">จัดการห้องเรียน</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Navigation Tabs -->
                    <div class="border-bottom bg-light px-4 py-3">
                        <ul class="nav nav-pills nav-fill" id="classManagementTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="classroom-list-tab" data-bs-toggle="pill" 
                                    data-bs-target="#classroom-list-panel" type="button" role="tab">
                                    <i class="fas fa-list me-2"></i>รายการห้องเรียน
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="classroom-detail-tab" data-bs-toggle="pill" 
                                    data-bs-target="#classroom-detail-panel" type="button" role="tab" disabled>
                                    <i class="fas fa-info-circle me-2"></i>รายละเอียดห้องเรียน
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="classroom-form-tab" data-bs-toggle="pill" 
                                    data-bs-target="#classroom-form-panel" type="button" role="tab" disabled>
                                    <i class="fas fa-edit me-2"></i>แก้ไขห้องเรียน
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content" id="classManagementTabContent">
                        
                        <!-- Classroom List Panel -->
                        <div class="tab-pane fade show active" id="classroom-list-panel" role="tabpanel">
                            <div class="p-4">
                                <!-- Quick Actions Bar -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0" id="classroomSearch" 
                                                placeholder="ค้นหาห้องเรียน ครูประจำชั้น..." autocomplete="off">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" id="filterLevel" autocomplete="off">
                                            <option value="">ทุกระดับชั้น</option>
                                            <option value="ม.1">ม.1</option>
                                            <option value="ม.2">ม.2</option>
                                            <option value="ม.3">ม.3</option>
                                            <option value="ม.4">ม.4</option>
                                            <option value="ม.5">ม.5</option>
                                            <option value="ม.6">ม.6</option>
                                        </select>
                                    </div>
                                    
                                </div>

                                <!-- Classrooms Grid -->
                                <div id="classroomList">
                                    <div class="row g-3" id="classroomGrid">
                                        <!-- จะถูกเติมโดย JavaScript -->
                                    </div>
                                    
                                    <!-- Pagination -->
                                    <nav class="mt-4">
                                        <ul class="pagination justify-content-center mb-0">
                                            <!-- จะถูกเติมโดย JavaScript -->
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <!-- Classroom Detail Panel -->
                        <div class="tab-pane fade" id="classroom-detail-panel" role="tabpanel">
                            <div class="p-4">
                                <!-- Classroom Header -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-0 bg-primary text-white">
                                            <div class="card-body text-dark">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h4 class="card-title mb-1" id="detail-classroom-name">ม.1/1</h4>
                                                        <p class="card-text mb-0 opacity-75" id="detail-teacher-name">ครูประจำชั้น: ยังไม่ได้กำหนด</p>
                                                    </div>
                                                    @if(auth()->user()->users_role === 'admin')
                                                    <div class="col-md-4 text-end">
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-light btn-sm" id="btnEditClassFromDetail">
                                                                <i class="fas fa-edit me-1"></i>แก้ไข
                                                            </button>
                                                            <button class="btn btn-outline-light btn-sm" id="btnDeleteClassFromDetail">
                                                                <i class="fas fa-trash me-1"></i>ลบ
                                                            </button>
                                                        </div>
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Statistics Cards -->
                                <div class="row mb-4">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <div class="text-primary mb-2">
                                                    <i class="fas fa-users fa-2x"></i>
                                                </div>
                                                <h5 class="card-title mb-1" id="detail-student-count">0</h5>
                                                <p class="card-text text-muted small mb-0">นักเรียนทั้งหมด</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <div class="text-success mb-2">
                                                    <i class="fas fa-chart-line fa-2x"></i>
                                                </div>
                                                <h5 class="card-title mb-1" id="detail-avg-score">100</h5>
                                                <p class="card-text text-muted small mb-0">คะแนนเฉลี่ย</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <div class="text-warning mb-2">
                                                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                                                </div>
                                                <h5 class="card-title mb-1" id="detail-violations-month">0</h5>
                                                <p class="card-text text-muted small mb-0">การกระทำผิดเดือนนี้</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-body text-center">
                                                <div class="text-info mb-2">
                                                    <i class="fas fa-medal fa-2x"></i>
                                                </div>
                                                <h5 class="card-title mb-1" id="detail-good-behavior">0</h5>
                                                <p class="card-text text-muted small mb-0">พฤติกรรมดีเดือนนี้</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Students List Card -->
                                <div class="card shadow-sm border-0">
                                    <div class="card-header bg-white border-0">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <h6 class="mb-0">
                                                <i class="fas fa-user-graduate me-2 text-primary"></i>รายชื่อนักเรียน
                                            </h6>
                                            <div class="input-group input-group-sm" style="max-width: 320px;">
                                                <input type="text" class="form-control" id="studentSearchInDetail" placeholder="ค้นหาชื่อหรือละหัดนักเรียน">
                                                <button class="btn btn-outline-primary" id="btnStudentSearch" type="button">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 20px;">#</th>
                                                    <th style="width: 100px;">รหัสนักเรียน</th>
                                                    <th class="text-center">ชื่อ - สกุล</th>
                                                    <th style="width: 50x;">คะแนน</th>
                                                    <th class="text-center" style="width: 140px;">การทำงาน</th>
                                                </tr>
                                            </thead>
                                            <tbody id="detail-students-list">
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted py-4">ไม่มีนักเรียน</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer bg-white">
                                        <nav class="d-flex justify-content-center">
                                            <ul class="pagination pagination-sm mb-0" id="detail-student-pagination"></ul>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Classroom Form Panel -->
                        <div class="tab-pane fade" id="classroom-form-panel" role="tabpanel">
                            <div class="p-4">
                                <div class="row justify-content-center">
                                    <div class="col-lg-8">
                                        <div class="card shadow-sm border-0">
                                            <div class="card-header bg-white border-bottom">
                                                <h6 class="mb-0" id="formClassTitle">
                                                    <i class="fas fa-edit me-2 text-primary"></i>แก้ไขห้องเรียน
                                                </h6>
                                            </div>
                                            <div class="card-body">
                                                <form id="formClassroom">
                                                    <input type="hidden" id="classId" name="classes_id">

                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <label for="classes_level" class="form-label">
                                                                ระดับชั้น <span class="text-danger">*</span>
                                                            </label>
                                                            <select class="form-select" id="classes_level" name="classes_level" required>
                                                                <option value="" selected disabled>เลือกระดับชั้น</option>
                                                                <option value="ม.1">ม.1</option>
                                                                <option value="ม.2">ม.2</option>
                                                                <option value="ม.3">ม.3</option>
                                                                <option value="ม.4">ม.4</option>
                                                                <option value="ม.5">ม.5</option>
                                                                <option value="ม.6">ม.6</option>
                                                            </select>
                                                            <div class="invalid-feedback">กรุณาเลือกระดับชั้น</div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label for="classes_room_number" class="form-label">
                                                                ห้อง <span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text" class="form-control" id="classes_room_number"
                                                                name="classes_room_number" placeholder="ระบุเลขห้อง เช่น 1, 2, 3"
                                                                required maxlength="5">
                                                            <div class="invalid-feedback">กรุณาระบุเลขห้อง</div>
                                                        </div>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label for="teacher_id" class="form-label">
                                                            ครูประจำชั้น <span class="text-danger">*</span>
                                                        </label>
                                                        <select class="form-select" id="teacher_id" name="teacher_id" required>
                                                            <option value="" selected disabled>เลือกครูประจำชั้น</option>
                                                            <!-- จะถูกเติมโดย JavaScript -->
                                                        </select>
                                                        <div class="invalid-feedback">กรุณาเลือกครูประจำชั้น</div>
                                                    </div>

                                                    <div class="d-flex justify-content-between">
                                                        <button type="button" class="btn btn-outline-secondary" id="btnBackToList">
                                                            <i class="fas fa-arrow-left me-2"></i>กลับสู่รายการ
                                                        </button>
                                                        <div>
                                                            <button type="button" class="btn btn-light me-2" id="btnCancelClassForm">
                                                                ยกเลิก
                                                            </button>
                                                            <button type="submit" class="btn btn-primary" id="btnSaveClass">
                                                                <i class="fas fa-save me-2"></i>บันทึก
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer border-0 pt-2 bg-light">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>ปิด
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Class Confirmation Modal -->
    <div class="modal fade" id="deleteClassModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>ยืนยันการลบ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-3">
                        <i class="fas fa-trash-alt text-danger" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="mb-2">ยืนยันการลบห้องเรียน</h6>
                    <p class="text-muted mb-3" id="deleteClassMessage">
                        คุณต้องการลบห้องเรียน <strong id="deleteClassName"></strong> หรือไม่?
                    </p>
                    <div class="alert alert-warning text-start">
                        <small><i class="fas fa-info-circle me-1"></i> การลบห้องเรียนอาจส่งผลกระทบต่อข้อมูลนักเรียนและข้อมูลพฤติกรรมที่บันทึกไว้</small>
                    </div>
                    <input type="hidden" id="deleteClassId">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteClass">
                        <i class="fas fa-trash me-1"></i>ยืนยันการลบ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Detail Modal -->
    <div class="modal fade" id="studentDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-0 pb-2">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-user-graduate text-primary me-2 fs-5"></i>
                        <h5 class="modal-title mb-0" id="studentDetailModalLabel">ข้อมูลนักเรียน</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Student Info Display Mode -->
                    <div id="studentDisplayMode">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-0 bg-primary text-white">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h4 class="card-title mb-1" id="display-student-name">-</h4>
                                                <p class="card-text mb-0 opacity-75">รหัสนักเรียน: <span id="display-student-code">-</span></p>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <button class="btn btn-light btn-sm" id="btnEditStudent">
                                                    <i class="fas fa-edit me-1"></i>แก้ไข
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">ชื่อ</label>
                                <p class="h6" id="display-first-name">-</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">นามสกุล</label>
                                <p class="h6" id="display-last-name">-</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">อีเมล</label>
                                <p class="h6" id="display-email">-</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">เบอร์โทรศัพท์</label>
                                <p class="h6" id="display-phone">-</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">คะแนนปัจจุบัน</label>
                                <p class="h6" id="display-score">-</p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">ห้องเรียน</label>
                                <p class="h6" id="display-classroom">-</p>
                            </div>
                        </div>
                    </div>

                    <!-- Student Edit Mode is now in a separate modal -->
                </div>
            </div>
        </div>
    </div>

    <!-- Student Edit Sidebar (Offcanvas) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="studentEditSidebar" aria-labelledby="studentEditSidebarLabel" style="width: 480px;">
        <div class="offcanvas-header bg-primary-app text-white">
            <div class="d-flex align-items-center">
                <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                    <i class="fas fa-user-edit fs-5"></i>
                </div>
                <div>
                    <h5 class="offcanvas-title mb-0" id="studentEditSidebarLabel">แก้ไขข้อมูลนักเรียน</h5>
                    <small class="opacity-75" id="se-student-name-header">-</small>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            <!-- Student Profile Summary -->
            <div class="bg-light border-bottom p-3">
                <div class="d-flex align-items-center">
                    <div class="bg-primary-app bg-opacity-10 rounded-circle p-2 me-3">
                        <i class="fas fa-user text-primary-app fs-4"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted d-block">รหัสนักเรียน</small>
                                <span class="fw-semibold" id="se-header-student-code">-</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">คะแนนปัจจุบัน</small>
                                <span class="fw-semibold">
                                    <span id="se-header-score">-</span>/100
                                    <i class="fas fa-circle text-success ms-1" style="font-size: 0.5rem;" id="se-score-indicator"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <form id="studentEditForm" class="p-3">
                <input type="hidden" id="se-student-id">

                <!-- Personal Information Section -->
                <div class="mb-4">
                    <h6 class="text-primary-app mb-3">
                        <i class="fas fa-user me-2"></i>ข้อมูลส่วนตัว
                    </h6>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <label for="se-first-name" class="form-label fw-semibold">
                                ชื่อ <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="se-first-name" name="users_first_name" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="se-last-name" class="form-label fw-semibold">
                                นามสกุล <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="se-last-name" name="users_last_name" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information Section -->
                <div class="mb-4">
                    <h6 class="text-primary-app mb-3">
                        <i class="fas fa-address-book me-2"></i>ข้อมูลติดต่อ
                    </h6>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="se-email" class="form-label fw-semibold">อีเมล</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="se-email" name="users_email" placeholder="example@email.com">
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="se-phone" class="form-label fw-semibold">เบอร์โทรศัพท์</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                <input type="tel" class="form-control" id="se-phone" name="users_phone_number" placeholder="08X-XXX-XXXX">
                            </div>
                        </div>
                    </div>
                </div>

                @if(auth()->user()->users_role === 'admin')
                <!-- Status Section - Admin Only -->
                <div class="mb-4">
                    <h6 class="text-primary-app mb-3">
                        <i class="fas fa-cog me-2"></i>การจัดการสถานะ
                    </h6>
                    
                    <div class="col-12">
                        <label for="se-status" class="form-label fw-semibold">สถานะนักเรียน</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                            <select class="form-select" id="se-status" name="students_status">
                                <option value="active">ศึกษาอยู่</option>
                                <option value="suspended">พักการเรียน</option>
                                <option value="expelled">พ้นสภาพ/ลาออก</option>
                                <option value="transferred">ย้ายสถานศึกษา</option>
                                <option value="graduate">จบการศึกษา</option>
                            </select>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-info-circle me-1"></i>เฉพาะแอดมินเท่านั้นที่สามารถเปลี่ยนสถานะได้
                        </small>
                    </div>
                </div>
                @else
                <!-- Status Display - Teacher View -->
                <div class="mb-4">
                    <h6 class="text-primary-app mb-3">
                        <i class="fas fa-eye me-2"></i>สถานะนักเรียน
                    </h6>
                    
                    <div class="col-12">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                            <input type="text" class="form-control bg-light" id="se-status-readonly" readonly disabled>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-lock me-1"></i>เฉพาะแอดมินเท่านั้นที่สามารถเปลี่ยนสถานะได้
                        </small>
                    </div>
                </div>
                @endif

                <!-- Read-only Information -->
                <div class="mb-4">
                    <h6 class="text-muted mb-3">
                        <i class="fas fa-lock me-2"></i>ข้อมูลที่ไม่สามารถแก้ไขได้
                    </h6>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <label for="se-student-code" class="form-label fw-semibold text-muted">รหัสนักเรียน</label>
                            <input type="text" class="form-control bg-light border-0" id="se-student-code" readonly disabled>
                        </div>
                        <div class="col-6">
                            <label for="se-score" class="form-label fw-semibold text-muted">คะแนนปัจจุบัน</label>
                            <input type="text" class="form-control bg-light border-0" id="se-score" readonly>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i>รหัสนักเรียนและคะแนนจะถูกคำนวณโดยระบบอัตโนมัติ
                    </small>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-primary btn-lg" id="btnSaveStudent">
                        <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                    <button type="button" class="btn btn-light" data-bs-dismiss="offcanvas">
                        <i class="fas fa-times me-2"></i>ยกเลิก
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Monthly Report Modal -->
    <div class="modal fade" id="monthlyReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        รายงานพฤติกรรมประจำเดือน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="monthlyReportForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="report_month" class="form-label">เดือน</label>
                                <select class="form-select" id="report_month" required>
                                    <option value="1">มกราคม</option>
                                    <option value="2">กุมภาพันธ์</option>
                                    <option value="3">มีนาคม</option>
                                    <option value="4">เมษายน</option>
                                    <option value="5">พฤษภาคม</option>
                                    <option value="6">มิถุนายน</option>
                                    <option value="7">กรกฎาคม</option>
                                    <option value="8">สิงหาคม</option>
                                    <option value="9">กันยายน</option>
                                    <option value="10">ตุลาคม</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="report_year" class="form-label">ปี (พ.ศ.)</label>
                                <select class="form-select" id="report_year" required>
                                    @for($y = date('Y') + 543; $y >= date('Y') + 540; $y--)
                                        <option value="{{ $y - 543 }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="report_class_id" class="form-label">ชั้นเรียน (เฉพาะ)</label>
                            <select class="form-select" id="report_class_id">
                                <option value="">ทุกชั้นเรียน</option>
                                <!-- จะถูกเติมด้วย JavaScript หรือ Blade -->
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="downloadMonthlyReport()">
                        <i class="fas fa-file-pdf me-1"></i> สร้างรายงาน PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Filter Modal -->
    <div class="modal fade" id="studentFilterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">กรองข้อมูลนักเรียน</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="studentFilterForm">
                        <!-- ค้นหาจากชื่อ/รหัสนักเรียน -->
                        <div class="mb-3">
                            <label for="filter_name" class="form-label">ชื่อหรือรหัสนักเรียน</label>
                            <input type="text" class="form-control" id="filter_name"
                                placeholder="พิมพ์ชื่อหรือรหัสนักเรียน...">
                        </div>

                        <!-- กรองตามระดับชั้น -->
                        <div class="mb-3">
                            <label for="filter_class_level" class="form-label">ระดับชั้น</label>
                            <select class="form-select" id="filter_class_level">
                                <option value="">ทุกระดับชั้น</option>
                                <option value="ม.1">ม.1</option>
                                <option value="ม.2">ม.2</option>
                                <option value="ม.3">ม.3</option>
                                <option value="ม.4">ม.4</option>
                                <option value="ม.5">ม.5</option>
                                <option value="ม.6">ม.6</option>
                            </select>
                        </div>

                        <!-- กรองตามห้อง -->
                        <div class="mb-3">
                            <label for="filter_class_room" class="form-label">ห้อง</label>
                            <select class="form-select" id="filter_class_room">
                                <option value="">ทุกห้อง</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                            </select>
                        </div>

                        <!-- กรองตามคะแนนคงเหลือ -->
                        <div class="mb-3">
                            <label class="form-label">คะแนนคงเหลือ</label>
                            <div class="d-flex gap-2 align-items-center">
                                <select class="form-select" id="filter_score_operator">
                                    <option value="any">ไม่ระบุ</option>
                                    <option value="less">น้อยกว่า</option>
                                    <option value="more">มากกว่า</option>
                                    <option value="equal">เท่ากับ</option>
                                </select>
                                <input type="number" class="form-control" id="filter_score_value" min="0" max="100"
                                    value="75" disabled>
                            </div>
                        </div>

                        <!-- กรองตามจำนวนครั้งที่ทำผิด -->
                        <div class="mb-3">
                            <label class="form-label">จำนวนครั้งที่ทำผิด</label>
                            <div class="d-flex gap-2 align-items-center">
                                <select class="form-select" id="filter_violation_operator">
                                    <option value="any">ไม่ระบุ</option>
                                    <option value="less">น้อยกว่า</option>
                                    <option value="more">มากกว่า</option>
                                    <option value="equal">เท่ากับ</option>
                                </select>
                                <input type="number" class="form-control" id="filter_violation_value" min="0" value="5"
                                    disabled>
                            </div>
                        </div>

                        <!-- กรองตามสถานะความเสี่ยง -->
                        <div class="mb-3">
                            <label class="form-label">สถานะความเสี่ยง</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter_risk_high" value="high">
                                <label class="form-check-label" for="filter_risk_high">
                                    <span class="badge bg-danger">ความเสี่ยงสูง</span> (คะแนนต่ำกว่า 60)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter_risk_medium" value="medium">
                                <label class="form-check-label" for="filter_risk_medium">
                                    <span class="badge bg-warning text-dark">ความเสี่ยงปานกลาง</span> (คะแนน 60-75)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="filter_risk_low" value="low">
                                <label class="form-check-label" for="filter_risk_low">
                                    <span class="badge bg-success">ความเสี่ยงต่ำ</span> (คะแนนมากกว่า 75)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-link text-secondary" id="resetFilterBtn">รีเซ็ตตัวกรอง</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary-app" id="applyFilterBtn">
                        <i class="fas fa-filter me-1"></i> ใช้ตัวกรอง
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Parent Notification Modal -->
    <div class="modal fade" id="parentNotificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">แจ้งเตือนผู้ปกครอง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="notification-student-info" class="alert alert-light border mb-3"></div>

                    <div id="notification-warning" class="alert alert-danger d-none">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>คะแนนความประพฤติต่ำกว่าเกณฑ์</strong>
                        </div>
                        <p class="mb-0">นักเรียนมีคะแนนความประพฤติต่ำมาก จำเป็นต้องได้รับการดูแลและติดตามอย่างใกล้ชิด
                        </p>
                    </div>

                    <form id="notification-form">
                        <input type="hidden" id="notification-student-id">
                        <input type="hidden" id="notification-score">
                        <input type="hidden" id="notification-phone">

                        <div class="mb-3">
                            <label for="notification-type" class="form-label">ประเภทการแจ้งเตือน</label>
                            <select class="form-select" id="notification-type" onchange="updateNotificationTemplate()">
                                <option value="behavior">พฤติกรรมเบี่ยงเบน</option>
                                <option value="attendance">การขาดเรียน</option>
                                <option value="meeting">เชิญประชุมผู้ปกครอง</option>
                                <option value="custom">กำหนดเอง</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="notification-message" class="form-label">ข้อความ</label>
                            <textarea class="form-control" id="notification-message" rows="5" required></textarea>
                            <div class="form-text">
                                <span id="message-suggestion" class="text-primary cursor-pointer d-none"
                                    onclick="applyMessageSuggestion()">
                                    <i class="fas fa-lightbulb"></i> ใช้ข้อความแนะนำ
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notification-method" class="form-label">วิธีการแจ้งเตือน</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notification-system" checked>
                                <label class="form-check-label" for="notification-system">
                                    <i class="fas fa-bell me-1"></i> ระบบแจ้งเตือน
                                </label>
                            </div>
                        </div>
                    </form>

                    <div id="notification-success" class="alert alert-success d-none">
                        <i class="fas fa-check-circle me-2"></i> ส่งการแจ้งเตือนสำเร็จแล้ว
                    </div>

                    <div id="notification-error" class="alert alert-danger d-none">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span id="notification-error-message">เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง</span>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" id="send-notification-btn"
                        onclick="sendParentNotification()">
                        <i class="fas fa-paper-plane me-1"></i> ส่งการแจ้งเตือน
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Students Report Modal -->
    <div class="modal fade" id="riskStudentsReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        รายงานสรุปนักเรียนที่มีความเสี่ยง
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="riskStudentsReportForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="risk_report_month" class="form-label">เดือน</label>
                                <select class="form-select" id="risk_report_month" required>
                                    <option value="1">มกราคม</option>
                                    <option value="2">กุมภาพันธ์</option>
                                    <option value="3">มีนาคม</option>
                                    <option value="4">เมษายน</option>
                                    <option value="5">พฤษภาคม</option>
                                    <option value="6">มิถุนายน</option>
                                    <option value="7">กรกฎาคม</option>
                                    <option value="8">สิงหาคม</option>
                                    <option value="9">กันยายน</option>
                                    <option value="10">ตุลาคม</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="risk_report_year" class="form-label">ปี (พ.ศ.)</label>
                                <select class="form-select" id="risk_report_year" required>
                                    @for($y = date('Y') + 543; $y >= date('Y') + 540; $y--)
                                        <option value="{{ $y - 543 }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="risk_report_level" class="form-label">ระดับความเสี่ยง</label>
                                <select class="form-select" id="risk_report_level">
                                    <option value="all">ทุกระดับ</option>
                                    <option value="high">ความเสี่ยงสูง</option>
                                    <option value="medium">ความเสี่ยงปานกลาง</option>
                                    <option value="low">ความเสี่ยงต่ำ</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="risk_report_class_id" class="form-label">ชั้นเรียน (เฉพาะ)</label>
                                <select class="form-select" id="risk_report_class_id">
                                    <option value="">ทุกชั้นเรียน</option>
                                    <!-- จะถูกเติมด้วย JavaScript หรือ Blade -->
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>หมายเหตุ:</strong>
                            รายงานนี้จะแสดงเฉพาะนักเรียนที่มีพฤติกรรมผิดระเบียบหรือมีคะแนนความประพฤติต่ำกว่า 90 คะแนน
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-warning" onclick="downloadRiskStudentsReport()">
                        <i class="fas fa-file-pdf me-1"></i> สร้างรายงาน PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- All Behavior Data Report Modal -->
    <div class="modal fade" id="allBehaviorDataReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        รายงานข้อมูลพฤติกรรมทั้งหมด
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="allBehaviorDataReportForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="all_data_report_month" class="form-label">เดือน</label>
                                <select class="form-select" id="all_data_report_month" required>
                                    <option value="1">มกราคม</option>
                                    <option value="2">กุมภาพันธ์</option>
                                    <option value="3">มีนาคม</option>
                                    <option value="4">เมษายน</option>
                                    <option value="5">พฤษภาคม</option>
                                    <option value="6">มิถุนายน</option>
                                    <option value="7">กรกฎาคม</option>
                                    <option value="8">สิงหาคม</option>
                                    <option value="9">กันยายน</option>
                                    <option value="10">ตุลาคม</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="all_data_report_year" class="form-label">ปี พ.ศ.</label>
                                <select class="form-select" id="all_data_report_year" required>
                                    <option value="2023">2566</option>
                                    <option value="2024">2567</option>
                                    <option value="2025">2568</option>
                                    <option value="2026">2569</option>
                                    <option value="2027">2570</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="all_data_report_class_id" class="form-label">ชั้นเรียน (เฉพาะ)</label>
                            <select class="form-select" id="all_data_report_class_id">
                                <option value="">ทุกชั้นเรียน</option>
                                <!-- จะถูกเติมด้วย JavaScript หรือ Blade -->
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            รายงานนี้จะแสดงข้อมูลพฤติกรรมนักเรียนทั้งหมดในเดือนที่เลือก
                            รวมถึงรายละเอียดการบันทึกแต่ละครั้ง และสถิติสรุป
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="downloadAllBehaviorDataReport()">
                        <i class="fas fa-file-pdf me-1"></i> สร้างรายงาน PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Dashboard JS -->
    <script src="/js/teacher-dashboard.js"></script>
    <script src="/js/violation-manager.js?v={{ filemtime(public_path('js/violation-manager.js')) }}"></script>
    <script src="/js/class-manager.js?v={{ filemtime(public_path('js/class-manager.js')) }}"></script>
    <script>
        // เพิ่มตัวแปรบทบาทผู้ใช้สำหรับใช้ใน JS อื่นๆ
        window.authRole = '{{ auth()->user()->users_role }}';
    </script>
    <!-- Risk Students Report Modal -->
    <div class="modal fade" id="riskStudentsReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        รายงานสรุปนักเรียนที่มีความเสี่ยง
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="riskStudentsReportForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="risk_report_month" class="form-label">เดือน</label>
                                <select class="form-select" id="risk_report_month" required>
                                    <option value="1">มกราคม</option>
                                    <option value="2">กุมภาพันธ์</option>
                                    <option value="3">มีนาคม</option>
                                    <option value="4">เมษายน</option>
                                    <option value="5">พฤษภาคม</option>
                                    <option value="6">มิถุนายน</option>
                                    <option value="7">กรกฎาคม</option>
                                    <option value="8">สิงหาคม</option>
                                    <option value="9">กันยายน</option>
                                    <option value="10">ตุลาคม</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="risk_report_year" class="form-label">ปี (พ.ศ.)</label>
                                <select class="form-select" id="risk_report_year" required>
                                    @for($y = date('Y') + 543; $y >= date('Y') + 540; $y--)
                                        <option value="{{ $y - 543 }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="risk_report_level" class="form-label">ระดับความเสี่ยง</label>
                                <select class="form-select" id="risk_report_level">
                                    <option value="all">ทุกระดับ</option>
                                    <option value="high">ความเสี่ยงสูง</option>
                                    <option value="medium">ความเสี่ยงปานกลาง</option>
                                    <option value="low">ความเสี่ยงต่ำ</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="risk_report_class_id" class="form-label">ชั้นเรียน (เฉพาะ)</label>
                                <select class="form-select" id="risk_report_class_id">
                                    <option value="">ทุกชั้นเรียน</option>
                                    <!-- จะถูกเติมด้วย JavaScript หรือ Blade -->
                                </select>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>หมายเหตุ:</strong>
                            รายงานนี้จะแสดงเฉพาะนักเรียนที่มีพฤติกรรมผิดระเบียบหรือมีคะแนนความประพฤติต่ำกว่า 90 คะแนน
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-warning" onclick="downloadRiskStudentsReport()">
                        <i class="fas fa-file-pdf me-1"></i> สร้างรายงาน PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Report Modal -->
    <div class="modal fade" id="monthlyReportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        รายงานพฤติกรรมประจำเดือน
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="monthlyReportForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="report_month" class="form-label">เดือน</label>
                                <select class="form-select" id="report_month" required>
                                    <option value="1">มกราคม</option>
                                    <option value="2">กุมภาพันธ์</option>
                                    <option value="3">มีนาคม</option>
                                    <option value="4">เมษายน</option>
                                    <option value="5">พฤษภาคม</option>
                                    <option value="6">มิถุนายน</option>
                                    <option value="7">กรกฎาคม</option>
                                    <option value="8">สิงหาคม</option>
                                    <option value="9">กันยายน</option>
                                    <option value="10">ตุลาคม</option>
                                    <option value="11">พฤศจิกายน</option>
                                    <option value="12">ธันวาคม</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="report_year" class="form-label">ปี (พ.ศ.)</label>
                                <select class="form-select" id="report_year" required>
                                    @for($y = date('Y') + 543; $y >= date('Y') + 540; $y--)
                                        <option value="{{ $y - 543 }}">{{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="report_class_id" class="form-label">ชั้นเรียน (เฉพาะ)</label>
                            <select class="form-select" id="report_class_id">
                                <option value="">ทุกชั้นเรียน</option>
                                <!-- จะถูกเติมด้วย JavaScript หรือ Blade -->
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="downloadMonthlyReport()">
                        <i class="fas fa-file-pdf me-1"></i> สร้างรายงาน PDF
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.js"
        integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>

    <script src="/js/class-detail.js?v={{ filemtime(public_path('js/class-detail.js')) }}"></script>
    <script>
        // Ensure only one modal/offcanvas is visible at a time for a clean UI
        document.addEventListener('DOMContentLoaded', function(){
            document.addEventListener('show.bs.modal', function (event) {
                var newModal = event.target;
                document.querySelectorAll('.modal.show').forEach(function(m){
                    if(m !== newModal){
                        var inst = bootstrap.Modal.getInstance(m) || bootstrap.Modal.getOrCreateInstance(m);
                        inst.hide();
                    }
                });
                // Hide any open offcanvas when a modal opens
                document.querySelectorAll('.offcanvas.show').forEach(function(oc){
                    var off = bootstrap.Offcanvas.getInstance(oc) || bootstrap.Offcanvas.getOrCreateInstance(oc);
                    off.hide();
                });
            });
            document.addEventListener('show.bs.offcanvas', function(event){
                var newCanvas = event.target;
                // Hide open modals when opening an offcanvas
                document.querySelectorAll('.modal.show').forEach(function(m){
                    var inst = bootstrap.Modal.getInstance(m) || bootstrap.Modal.getOrCreateInstance(m);
                    inst.hide();
                });
                // Hide other offcanvas panels as well
                document.querySelectorAll('.offcanvas.show').forEach(function(oc){
                    if(oc !== newCanvas){
                        var off = bootstrap.Offcanvas.getInstance(oc) || bootstrap.Offcanvas.getOrCreateInstance(oc);
                        off.hide();
                    }
                });
            });
        });
    </script>
    <!-- เพิ่ม behavior report script -->
    <script src="/js/behavior-report.js?v={{ filemtime(public_path('js/behavior-report.js')) }}"></script>
    <!-- Reports JS -->
    <script src="/js/reports.js?v={{ filemtime(public_path('js/reports.js')) }}"></script>
    <script src="/js/student-filter.js?v={{ filemtime(public_path('js/student-filter.js')) }}"></script>
    <script src="/js/parent-notification.js"></script>
    <!-- Archived Students JS -->
    <script>
        // Inject canonical API URLs from server-side routes to avoid hard-coded paths
        window.ARCHIVED_STUDENTS_API_URL = "{{ route('api.teacher.archived-students') }}";
        // Base URL for student history; JS will append the studentId
        window.STUDENT_HISTORY_API_BASE = "{{ url('/api/teacher/student-history') }}";
    </script>
    <script src="/js/archived-students.js"></script>
    <!-- User Management JS (Admin only) -->
    @if(auth()->user()->users_role === 'admin')
    <script src="/js/user-management.js?v={{ filemtime(public_path('js/user-management.js')) }}"></script>
    <script>
        // Set auth user ID for user management
        window.authUserId = {{ auth()->id() }};
    </script>
    @endif


    <!-- Archived Students Sidebar -->
    <div id="archivedStudentsSidebar" class="sidebar-overlay">
        <div class="sidebar-content">
            <div class="sidebar-header">
                <h5 class="sidebar-title">
                    <i class="fas fa-archive me-2"></i>ประวัติการเก็บข้อมูลนักเรียน
                </h5>
                <button type="button" class="btn-close-sidebar" onclick="closeArchivedStudentsSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="sidebar-body">
                <!-- Filter Section -->
                <div class="filter-section mb-3">
                    <h6 class="filter-title">
                        <i class="fas fa-filter me-1"></i>ตัวกรองข้อมูล
                    </h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-6">
                            <label for="archivedStatusFilter" class="form-label">สถานะ</label>
                            <select id="archivedStatusFilter" class="form-select form-select-sm">
                                <option value="">ทั้งหมด</option>
                                <option value="graduate">จบการศึกษา</option>
                                <option value="transferred">ย้ายโรงเรียน</option>
                                <option value="suspended">พักการเรียน</option>
                                <option value="expelled">ถูกไล่ออก</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="archivedLevelFilter" class="form-label">ชั้น</label>
                            <select id="archivedLevelFilter" class="form-select form-select-sm">
                                <option value="">ทั้งหมด</option>
                                <option value="ม.1">ม.1</option>
                                <option value="ม.2">ม.2</option>
                                <option value="ม.3">ม.3</option>
                                <option value="ม.4">ม.4</option>
                                <option value="ม.5">ม.5</option>
                                <option value="ม.6">ม.6</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="archivedRoomFilter" class="form-label">ห้อง</label>
                            <select id="archivedRoomFilter" class="form-select form-select-sm">
                                <option value="">ทั้งหมด</option>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                                <option value="4">4</option>
                                <option value="5">5</option>
                                <option value="6">6</option>
                                <option value="7">7</option>
                                <option value="8">8</option>
                                <option value="9">9</option>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label for="archivedScoreFilter" class="form-label">คะแนน</label>
                            <select id="archivedScoreFilter" class="form-select form-select-sm">
                                <option value="">ทั้งหมด</option>
                                <option value="90-100">90-100 คะแนน</option>
                                <option value="75-89">75-89 คะแนน</option>
                                <option value="50-74">50-74 คะแนน</option>
                                <option value="0-49">0-49 คะแนน</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label for="archivedSearchInput" class="form-label">ค้นหา</label>
                            <div class="input-group">
                                <input type="text" id="archivedSearchInput" class="form-control form-control-sm"
                                    placeholder="รหัสนักเรียนหรือชื่อ...">
                                <button class="btn btn-primary-app btn-sm" type="button"
                                    onclick="searchArchivedStudents()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between mb-3">
                        <button class="btn btn-secondary btn-sm" onclick="clearFilters()">
                            <i class="fas fa-times me-1"></i>ล้างตัวกรอง
                        </button>
                        <button class="btn btn-success btn-sm" onclick="exportArchivedData()">
                            <i class="fas fa-download me-1"></i>ส่งออกข้อมูล
                        </button>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="archivedDataLoading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="mt-2 text-muted small">กำลังโหลดข้อมูล...</p>
                </div>

                <!-- Students List -->
                <div id="archivedDataContainer">
                    <div id="archivedStudentsList">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle fa-2x mb-3"></i>
                            <p>กรุณาคลิกค้นหาเพื่อโหลดข้อมูล</p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="archivedPagination" class="d-flex justify-content-center mt-3">
                    <!-- Pagination will be dynamically generated -->
                </div>
            </div>
        </div>
    </div>

    <!-- Student History Detail Sidebar -->
    <div id="studentHistorySidebar" class="sidebar-overlay">
        <div class="sidebar-content sidebar-detail">
            <div class="sidebar-header">
                <h5 class="sidebar-title">
                    <i class="fas fa-history me-2"></i>ประวัติการบันทึกพฤติกรรม
                </h5>
                <div class="sidebar-actions">
                    <button type="button" class="btn-back-sidebar me-2" onclick="backToArchivedStudents()">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <button type="button" class="btn-close-sidebar" onclick="closeStudentHistorySidebar()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            <div class="sidebar-body">
                <!-- Student Info Card -->
                <div id="studentInfoSection" class="student-info-card mb-3">
                    <div class="student-info-header">
                        <div class="student-details">
                            <h6 class="student-name mb-2" id="studentName">กำลังโหลด...</h6>
                            
                            <!-- Student Meta Information -->
                            <div class="student-meta-grid">
                                <div class="meta-item">
                                    <div class="meta-label">
                                        <i class="fas fa-id-card me-1"></i>รหัสนักเรียน
                                    </div>
                                    <div class="meta-value" id="studentCodeView">-</div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">
                                        <i class="fas fa-graduation-cap me-1"></i>ชั้นเรียน
                                    </div>
                                    <div class="meta-value" id="studentClassView">-</div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">
                                        <i class="fas fa-user-check me-1"></i>สถานะ
                                    </div>
                                    <div class="meta-value">
                                        <span class="badge" id="studentStatus">-</span>
                                    </div>
                                </div>
                                <div class="meta-item">
                                    <div class="meta-label">
                                        <i class="fas fa-star me-1"></i>คะแนนปัจจุบัน
                                    </div>
                                    <div class="meta-value">
                                        <span class="badge" id="studentScore">-/100</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Behavior Statistics -->
                <div class="behavior-stats mb-3">
                    <div class="row">
                        <div class="col-4">
                            <div class="stat-card stat-violations">
                                <div class="stat-icon">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number" id="totalViolations">0</span>
                                    <span class="stat-label">การทำผิด</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card stat-score">
                                <div class="stat-icon">
                                    <i class="fas fa-minus-circle"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number" id="totalScoreDeducted">0</span>
                                    <span class="stat-label">คะแนนหัก</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-card stat-average">
                                <div class="stat-icon">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="stat-info">
                                    <span class="stat-number" id="averageScore">0</span>
                                    <span class="stat-label">เฉลี่ย/ปี</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Loading -->
                <div id="historyLoading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="mt-2 text-muted small">กำลังโหลดประวัติ...</p>
                </div>

                <!-- History List -->
                <div id="historyContainer">
                    <h6 class="section-title">ประวัติการบันทึกพฤติกรรม</h6>
                    <div id="behaviorHistoryList">
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-history fa-2x mb-3"></i>
                            <p>ไม่มีประวัติการบันทึกพฤติกรรม</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Violation Report Sidebar -->
    <div id="editViolationSidebar" class="sidebar-overlay">
        <div class="sidebar-content">
            <div class="sidebar-header">
                <h5 class="sidebar-title">
                    <i class="fas fa-edit me-2"></i>แก้ไขรายงานพฤติกรรม
                </h5>
                <button type="button" class="btn-close-sidebar" onclick="closeEditViolationSidebar()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="sidebar-body">
                <!-- Loading State -->
                <div id="editViolationLoading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="mt-2 text-muted small">กำลังโหลดข้อมูล...</p>
                </div>

                <!-- Error State -->
                <div id="editViolationError" class="alert alert-danger" style="display: none;">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="editViolationErrorMessage">เกิดข้อผิดพลาดในการโหลดข้อมูล</span>
                </div>

                <!-- Edit Form -->
                <div id="editViolationForm" style="display: none;">
                    <form id="violationEditForm">
                        <input type="hidden" id="editReportId">
                        
                        <!-- Student Info Display -->
                        <div id="editStudentInfo" class="alert alert-info mb-3">
                            <h6 class="mb-1">ข้อมูลนักเรียน:</h6>
                            <div id="editStudentInfoDisplay"></div>
                        </div>

                        <!-- Violation Type -->
                        <div class="mb-3">
                            <label for="editViolationType" class="form-label">ประเภทพฤติกรรม <span class="text-danger">*</span></label>
                            <select class="form-select" id="editViolationType" name="violation_id" required>
                                <option value="">เลือกประเภทพฤติกรรม</option>
                            </select>
                        </div>

                        <!-- Points Deducted Display -->
                        <div class="mb-3">
                            <label class="form-label">คะแนนที่หัก</label>
                            <div class="input-group">
                                <span class="form-control" id="editPointsDeducted">0</span>
                                <span class="input-group-text">คะแนน</span>
                            </div>
                        </div>

                        <!-- Date and Time -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="editViolationDate" class="form-label">วันที่ <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="editViolationDate" name="violation_date" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editViolationTime" class="form-label">เวลา <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="editViolationTime" name="violation_time" required>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="editViolationDescription" class="form-label">รายละเอียดเพิ่มเติม</label>
                            <textarea class="form-control" id="editViolationDescription" name="description" rows="3" placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                        </div>

                        <!-- Current Evidence -->
                        <div class="mb-3" id="currentEvidenceSection" style="display: none;">
                            <label class="form-label">หลักฐานปัจจุบัน</label>
                            <div class="border rounded p-2">
                                <img id="currentEvidenceImage" src="" alt="หลักฐานปัจจุบัน" class="img-fluid rounded" style="max-height: 200px;">
                                <div class="mt-2">
                                    <small class="text-muted">หลักฐานที่อัปโหลดไว้แล้ว</small>
                                </div>
                            </div>
                        </div>

                        <!-- New Evidence -->
                        <div class="mb-3">
                            <label for="editEvidenceFile" class="form-label">แนบหลักฐานใหม่ (ถ้าต้องการเปลี่ยน)</label>
                            <input type="file" class="form-control" id="editEvidenceFile" name="evidence" accept="image/*">
                            <div class="form-text">รองรับไฟล์ภาพเท่านั้น (JPG, PNG, GIF) - ถ้าไม่เลือกจะใช้หลักฐานเดิม</div>
                        </div>

                        <!-- Success/Error Messages -->
                        <div id="editViolationSuccess" class="alert alert-success" style="display: none;">
                            <i class="fas fa-check-circle me-2"></i>
                            <span id="editViolationSuccessMessage">บันทึกการแก้ไขเรียบร้อยแล้ว</span>
                        </div>

                        <div id="editViolationFormError" class="alert alert-danger" style="display: none;">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span id="editViolationFormErrorMessage">เกิดข้อผิดพลาดในการบันทึก</span>
                        </div>
                    </form>
                </div>

                <!-- Action Buttons -->
                <div id="editViolationActions" class="mt-4" style="display: none;">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="button" class="btn btn-outline-danger me-auto" id="deleteEditViolationBtn">
                            <i class="fas fa-trash-alt me-1"></i> ลบบันทึก
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeEditViolationSidebar()">
                            ยกเลิก
                        </button>
                        <button type="button" class="btn btn-primary-app" id="saveEditViolationBtn">
                            <i class="fas fa-save me-1"></i> บันทึกการแก้ไข
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Laravel Log Modal -->
    <div class="modal fade" id="laravelLogModal" tabindex="-1" aria-labelledby="laravelLogModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                <div class="modal-header" style="color: black; border-radius: 12px 12px 0 0; border: none;">
                    <h5 class="modal-title" id="laravelLogModalLabel" style="font-weight: 500;">
                        <i class="fas fa-code me-2"></i>System Logs
                    </h5>
                    <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="background: #f8f9fa;">
                    <div class="d-flex justify-content-between align-items-center px-4 py-3" style="background: white; border-bottom: 1px solid #e9ecef;">
                        <div class="text-muted">
                            <small id="logInfo" style="font-size: 13px;">กำลังโหลด...</small>
                        </div>
                        <button type="button" class="btn btn-sm" id="refreshLogBtn" style="background: #f1f3f4; border: 1px solid #dadce0; color: #5f6368; border-radius: 6px; font-size: 13px;">
                            <i class="fas fa-sync-alt me-1"></i> รีเฟรช
                        </button>
                    </div>
                    <div id="logContainer" style="height: 400px; overflow-y: auto; background: #0d1117; color: #e6edf3; font-family: 'SF Mono', Monaco, Consolas, monospace; font-size: 13px; padding: 20px; line-height: 1.5; border-radius: 0 0 12px 12px;">
                        <div class="text-center" style="color: #7d8590; margin-top: 100px;">
                            <div style="font-size: 24px; margin-bottom: 12px;">⚡</div>
                            <div style="font-size: 14px;">กำลังโหลดข้อมูล...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced User Detail/Edit Modal -->
    <div class="modal fade" id="userDetailSlider" tabindex="-1" aria-labelledby="userDetailSliderLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
                <div class="modal-header border-0 bg-primary-app text-white">
                    <div class="d-flex align-items-center text-white">
                        <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                            <i class="fas fa-user-circle fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="userDetailSliderLabel">ข้อมูลผู้ใช้</h5>
                            <small class="opacity-75">รายละเอียดและการจัดการบัญชี</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
            <!-- Enhanced Loading State -->
            <div id="userDetailLoading" class="text-center py-5">
                <div class="d-flex flex-column align-items-center">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <h6 class="text-muted">กำลังโหลดข้อมูลผู้ใช้...</h6>
                    <small class="text-muted">โปรดรอสักครู่</small>
                </div>
            </div>

            <!-- Error State -->
            <div id="userDetailError" class="alert alert-danger m-4" style="display: none;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger me-3"></i>
                    <div>
                        <h6 class="mb-1">เกิดข้อผิดพลาด</h6>
                        <span id="userDetailErrorMessage">ไม่สามารถโหลดข้อมูลได้</span>
                    </div>
                </div>
            </div>

            <!-- Enhanced View Mode -->
            <div id="userDetailView" style="display: none;">
                <!-- Enhanced User Profile Section -->
                <div class="bg-light p-4 border-bottom">
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center">
                            <div class="position-relative d-inline-block">
                          <img id="userAvatar" src="" class="rounded-circle border border-4 border-white shadow" 
                              width="120" height="120" alt="รูปโปรไฟล์" 
                              style="object-fit: cover; background: #e9ecef;">
                                <div class="position-absolute bottom-0 end-0 bg-white rounded-circle p-1 shadow">
                                    <span id="userStatusIcon" class="badge rounded-circle" style="width: 20px; height: 20px;"></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h4 id="userFullName" class="mb-2 fw-bold text-dark"></h4>
                                    <div class="d-flex flex-wrap gap-2 mb-2">
                                        <span id="userRoleBadge" class="badge fs-6"></span>
                                        <span id="userStatusBadge" class="badge fs-6"></span>
                                    </div>
                                    <div class="text-muted">
                                        <i class="fas fa-envelope me-2"></i><span id="userEmailDisplay">-</span>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-phone me-2"></i><span id="userPhoneDisplay">-</span>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted d-block">สมาชิกเมื่อ</small>
                                    <span id="userJoinDate" class="fw-semibold">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Information Sections -->
                <div class="p-4">
                    <!-- General Information Card -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="mb-0 fw-bold text-primary">
                                <i class="fas fa-user me-2"></i>ข้อมูลทั่วไป
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-envelope text-primary me-2"></i>อีเมล
                                        </label>
                                        <div class="info-value" id="userEmail">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-phone text-success me-2"></i>เบอร์โทรศัพท์
                                        </label>
                                        <div class="info-value" id="userPhone">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-birthday-cake text-warning me-2"></i>วันเกิด
                                        </label>
                                        <div class="info-value" id="userBirthdate">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-toggle-on text-info me-2"></i>สถานะบัญชี
                                        </label>
                                        <div class="info-value" id="userStatus">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Student Information Card -->
                    <div id="studentDetails" class="card border-0 shadow-sm mb-4" style="display: none;">
                        <div class="card-header bg-info bg-opacity-10 border-0 py-3">
                            <h6 class="mb-0 fw-bold text-info">
                                <i class="fas fa-graduation-cap me-2"></i>ข้อมูลนักเรียน
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-id-card text-info me-2"></i>รหัสนักเรียน
                                        </label>
                                        <div class="info-value" id="studentCode">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-school text-info me-2"></i>ชั้นเรียน
                                        </label>
                                        <div class="info-value" id="studentClassroom">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-venus-mars text-info me-2"></i>เพศ
                                        </label>
                                        <div class="info-value" id="studentGender">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-star text-warning me-2"></i>คะแนนปัจจุบัน
                                        </label>
                                        <div class="info-value">
                                            <span id="studentScore" class="badge bg-primary fs-6">-</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-flag text-danger me-2"></i>สถานะนักเรียน
                                        </label>
                                        <div class="info-value" id="studentStatus">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-calendar text-secondary me-2"></i>ปีการศึกษา
                                        </label>
                                        <div class="info-value" id="studentAcademicYear">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Teacher Information Card -->
                    <div id="teacherDetails" class="card border-0 shadow-sm mb-4" style="display: none;">
                        <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
                            <h6 class="mb-0 fw-bold text-warning">
                                <i class="fas fa-chalkboard-teacher me-2"></i>ข้อมูลครู
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-id-badge text-warning me-2"></i>รหัสพนักงาน
                                        </label>
                                        <div class="info-value" id="teacherEmployeeId">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-user-tie text-warning me-2"></i>ตำแหน่ง
                                        </label>
                                        <div class="info-value" id="teacherPosition">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-building text-warning me-2"></i>แผนก
                                        </label>
                                        <div class="info-value" id="teacherDepartment">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-book text-warning me-2"></i>สาขา/วิชาเอก
                                        </label>
                                        <div class="info-value" id="teacherMajor">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-users text-warning me-2"></i>ชั้นที่รับผิดชอบ
                                        </label>
                                        <div class="info-value" id="teacherAssignedClass">-</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-star text-warning me-2"></i>สถานะพิเศษ
                                        </label>
                                        <div class="info-value" id="teacherHomeroomStatus">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Guardian Information Card -->
                    <div id="guardianDetails" class="card border-0 shadow-sm mb-4" style="display: none;">
                        <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                            <h6 class="mb-0 fw-bold text-success">
                                <i class="fas fa-user-friends me-2"></i>ข้อมูลผู้ปกครอง
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-birthday-cake text-success me-2"></i>วันเกิด
                                        </label>
                                        <div class="info-value" id="guardianBirthdate">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-heart text-success me-2"></i>ความสัมพันธ์
                                        </label>
                                        <div class="info-value" id="guardianRelationship">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-phone text-success me-2"></i>เบอร์ติดต่อ
                                        </label>
                                        <div class="info-value" id="guardianPhone">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-envelope text-success me-2"></i>อีเมลผู้ปกครอง
                                        </label>
                                        <div class="info-value" id="guardianEmail">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fab fa-line text-success me-2"></i>Line ID
                                        </label>
                                        <div class="info-value" id="guardianLineId">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-comments text-success me-2"></i>ช่องทางติดต่อที่สะดวก
                                        </label>
                                        <div class="info-value" id="guardianPreferredContact">-</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="info-label">
                                            <i class="fas fa-graduation-cap text-success me-2"></i>นักเรียนที่ดูแล
                                        </label>
                                        <div class="info-value" id="guardianStudentsCount">
                                            <span class="badge bg-success">0 คน</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Linked Students Display -->
                            <div id="guardianLinkedStudentsDisplay" style="display: none;">
                                <hr class="my-4">
                                <h6 class="mb-3">
                                    <i class="fas fa-link me-2 text-success"></i>นักเรียนที่เชื่อมโยง
                                </h6>
                                <div id="guardianStudentsList" class="row g-3">
                                    <!-- Students will be displayed here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Actions -->
                <div class="bg-light p-4 border-top">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-primary" onclick="switchToEditMode()">
                                <i class="fas fa-edit me-2"></i>แก้ไขข้อมูล
                            </button>
                            <button class="btn btn-outline-info" onclick="toggleUserStatus()">
                                <i class="fas fa-toggle-on me-2"></i>เปลี่ยนสถานะ
                            </button>
                            <button class="btn btn-outline-warning" onclick="resetUserPassword()">
                                <i class="fas fa-key me-2"></i>รีเซ็ตรหัสผ่าน
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-outline-danger" id="deleteUserBtn" onclick="confirmDeleteUser()">
                                <i class="fas fa-trash me-2"></i>ลบผู้ใช้
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Mode -->
            <div id="userDetailEdit" style="display: none;">
                <form id="userEditForm" class="p-0">
                    <input type="hidden" id="editUserId">

                    <!-- Header Section removed to avoid double header; title comes from modal header -->

                    <!-- Form Content -->
                    <div class="p-4">
                        <!-- ข้อมูลพื้นฐาน Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-0 py-3">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-user me-2"></i>ข้อมูลพื้นฐาน
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-signature me-1 text-primary"></i>ชื่อ 
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="editUserFirstName" name="users_first_name" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-signature me-1 text-primary"></i>นามสกุล 
                                            <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="editUserLastName" name="users_last_name" required>
                                    </div>
                                    <div class="col-6" style="display:none;">
                                        <label class="form-label">ชื่อผู้ใช้ *</label>
                                        <input type="text" class="form-control" id="editUserUsernameField" name="users_username">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-envelope me-1 text-primary"></i>อีเมล
                                        </label>
                                        <input type="email" class="form-control" id="editUserEmailField" name="users_email" placeholder="example@email.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-phone me-1 text-primary"></i>เบอร์โทรศัพท์
                                        </label>
                                        <input type="text" class="form-control" id="editUserPhone" name="users_phone_number" placeholder="08X-XXX-XXXX">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-calendar me-1 text-primary"></i>วันเกิด
                                        </label>
                                        <input type="date" class="form-control" id="editUserBirthdate" name="users_birthdate">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-key me-1 text-primary"></i>รหัสผ่านใหม่
                                        </label>
                                        <input type="password" class="form-control" id="editUserPassword" name="new_password" placeholder="เว้นว่างหากไม่ต้องการเปลี่ยน">
                                        <small class="text-muted">หากไม่ต้องการเปลี่ยนรหัสผ่าน ให้เว้นว่างไว้</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- สถานะบัญชี Card -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-light border-0 py-3">
                                <h6 class="mb-0 text-primary">
                                    <i class="fas fa-toggle-on me-2"></i>สถานะบัญชี
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="editUserActive" name="users_active" value="1" style="transform: scale(1.2);">
                                    <label class="form-check-label fw-semibold ms-2" for="editUserActive">
                                        <i class="fas fa-user-check me-1 text-success"></i>เปิดใช้งานบัญชี
                                    </label>
                                </div>
                                <small class="text-muted">เมื่อปิดใช้งาน ผู้ใช้จะไม่สามารถเข้าสู่ระบบได้</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Role-specific edit fields -->
                    <div id="editStudentFields" style="display: none;">
                        <div class="p-4 pt-0">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-info bg-opacity-10 border-0 py-3">
                                    <h6 class="mb-0 text-info">
                                        <i class="fas fa-graduation-cap me-2"></i>ข้อมูลนักเรียน
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-id-card me-1 text-info"></i>รหัสนักเรียน 
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control" id="editStudentCode" name="students_student_code" placeholder="เช่น 6500142">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-school me-1 text-info"></i>ชั้นเรียน 
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="editStudentClassroom" name="class_id">
                                                <option value="">เลือกชั้นเรียน</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-venus-mars me-1 text-info"></i>เพศ
                                            </label>
                                            <select class="form-select" id="editStudentGender" name="students_gender">
                                                <option value="">ไม่ระบุ</option>
                                                <option value="male">ชาย</option>
                                                <option value="female">หญิง</option>
                                                <option value="other">อื่นๆ</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-user-check me-1 text-info"></i>สถานะนักเรียน
                                            </label>
                                            <select class="form-select" id="editStudentStatus" name="students_status">
                                                <option value="active">กำลังศึกษา</option>
                                                <option value="suspended">พักการศึกษา</option>
                                                <option value="expelled">พ้นสภาพ/ลาออก</option>
                                                <option value="graduate">จบการศึกษา</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-star me-1 text-info"></i>คะแนนปัจจุบัน
                                            </label>
                                            <input type="number" class="form-control" id="editStudentScore" name="students_current_score" min="0" step="1" placeholder="เช่น 100">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="editTeacherFields" style="display: none;">
                        <div class="p-4 pt-0">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-warning bg-opacity-10 border-0 py-3">
                                    <h6 class="mb-0 text-warning">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>ข้อมูลครู
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-id-badge me-1 text-warning"></i>รหัสพนักงาน
                                            </label>
                                            <input type="text" class="form-control" id="editTeacherEmployeeId" name="teachers_employee_code" placeholder="เช่น T001">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-user-tie me-1 text-warning"></i>ตำแหน่ง
                                            </label>
                                            <input type="text" class="form-control" id="editTeacherPosition" name="teachers_position" placeholder="เช่น ครูชำนาญการ">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-building me-1 text-warning"></i>แผนก
                                            </label>
                                            <input type="text" class="form-control" id="editTeacherDepartment" name="teachers_department" placeholder="เช่น ฝ่ายวิชาการ">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-book me-1 text-warning"></i>สาขา/วิชาเอก
                                            </label>
                                            <input type="text" class="form-control" id="editTeacherMajor" name="teachers_major" placeholder="เช่น คณิตศาสตร์">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-users me-1 text-warning"></i>ชั้นเรียนที่รับผิดชอบ
                                            </label>
                                            <select class="form-select" id="editTeacherAssignedClass" name="assigned_class_id">
                                                <option value="">ไม่กำหนด</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold d-block">
                                                <i class="fas fa-star me-1 text-warning"></i>สถานะพิเศษ
                                            </label>
                                            <div class="form-check form-switch mt-2">
                                                <input class="form-check-input" type="checkbox" id="editTeacherIsHomeroom" name="teachers_is_homeroom_teacher" value="1" style="transform: scale(1.2);">
                                                <label class="form-check-label fw-semibold ms-2" for="editTeacherIsHomeroom">
                                                    <i class="fas fa-home me-1 text-success"></i>ครูประจำชั้น
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="editGuardianFields" style="display: none;">
                        <div class="p-4 pt-0">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-success bg-opacity-10 border-0 py-3">
                                    <h6 class="mb-0 text-success">
                                        <i class="fas fa-user-friends me-2"></i>ข้อมูลผู้ปกครอง
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-heart me-1 text-success"></i>ความสัมพันธ์กับนักเรียน
                                            </label>
                                            <input type="text" class="form-control" id="editGuardianRelationship" name="guardians_relationship_to_student" placeholder="เช่น บิดา มารดา ผู้ปกครอง">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-envelope me-1 text-success"></i>อีเมลผู้ปกครอง
                                            </label>
                                            <input type="email" class="form-control" id="editGuardianEmail" name="guardians_email" placeholder="parent@email.com">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-phone me-1 text-success"></i>เบอร์โทรผู้ปกครอง
                                            </label>
                                            <input type="text" class="form-control" id="editGuardianPhone" name="guardians_phone" placeholder="08X-XXX-XXXX">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fab fa-line me-1 text-success"></i>Line ID
                                            </label>
                                            <input type="text" class="form-control" id="editGuardianLineId" name="guardians_line_id" placeholder="@line_id">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">
                                                <i class="fas fa-comments me-1 text-success"></i>ช่องทางติดต่อที่สะดวก
                                            </label>
                                            <select class="form-select" id="editGuardianPreferredContact" name="guardians_preferred_contact_method">
                                                <option value="">ไม่ระบุ</option>
                                                <option value="phone">โทรศัพท์</option>
                                                <option value="email">อีเมล</option>
                                                <option value="line">LINE</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="border-top pt-3">
                                        <label class="form-label fw-semibold">
                                            <i class="fas fa-graduation-cap me-1 text-success"></i>นักเรียนที่ดูแล
                                        </label>
                                        <div class="mb-3 d-flex flex-wrap gap-2" id="guardianLinkedStudents"></div>
                                        <div class="position-relative">
                                            <input type="text" class="form-control" id="guardianStudentSearch" placeholder="พิมพ์รหัสหรือชื่อเพื่อค้นหานักเรียน...">
                                            <div id="guardianStudentDropdown" class="list-group position-absolute w-100" style="z-index:1056; display:none; max-height:240px; overflow:auto;"></div>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>สามารถเพิ่มได้หลายคน กดเลือกจากรายการที่ค้นหา
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="p-4 bg-light rounded-bottom">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg shadow-sm">
                                <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="switchToViewMode()">
                                <i class="fas fa-eye me-2"></i>กลับไปดูข้อมูล
                            </button>
                        </div>
                    </div>
                </form>
            </div>
                </div>
                <div class="modal-footer d-none"></div>
            </div>
        </div>
    </div>

    <!-- Minimal CSS for User Management -->
    <style>
        .modal-xl {
            max-width: 1200px;
        }

        .modal-lg-plus {
            max-width: 900px;
        }

        #userManagementModal .minimal-card {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1.4rem 1.6rem;
            border-radius: 20px;
            border: 1px solid rgba(13, 110, 253, 0.12);
            background: #ffffff;
            box-shadow: 0 12px 34px rgba(15, 23, 42, 0.06);
        }

        #userManagementModal .minimal-stat-grid {
            margin-bottom: 2.25rem;
        }

        #userManagementModal .minimal-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
        }

        #userManagementModal .minimal-card-icon.text-primary {
            background: rgba(13, 110, 253, 0.14);
        }

        #userManagementModal .minimal-card-icon.text-info {
            background: rgba(13, 202, 240, 0.16);
        }

        #userManagementModal .minimal-card-icon.text-warning {
            background: rgba(255, 193, 7, 0.18);
        }

        #userManagementModal .minimal-card-icon.text-success {
            background: rgba(25, 135, 84, 0.16);
        }

        #userManagementModal .minimal-card-label {
            margin: 0;
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #6c757d;
        }

        #userManagementModal .minimal-card-value {
            font-size: 2rem;
            font-weight: 600;
            color: #1f2937;
        }

        #userManagementModal .minimal-card-chip {
            padding: 0.25rem 0.9rem;
            border-radius: 999px;
            background: rgba(13, 110, 253, 0.15);
            color: #0d6efd;
            font-size: 0.78rem;
            font-weight: 500;
        }

        #userManagementModal .minimal-card-subtext {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        #userManagementModal .minimal-panel {
            border-radius: 24px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 20px 46px rgba(15, 23, 42, 0.07);
            background: #ffffff;
        }

        #userManagementModal .minimal-panel .card-header {
            padding: 1.6rem 1.8rem 1.2rem;
        }

        #userManagementModal .minimal-panel .card-body {
            padding: 1.6rem 1.8rem;
        }

        #userManagementModal .minimal-panel .card-body.p-0 {
            padding: 0;
        }

        #userManagementModal .minimal-filter {
            background: #f8f9fb;
            border-top: 1px solid rgba(15, 23, 42, 0.05);
            border-bottom: 1px solid rgba(15, 23, 42, 0.05);
        }

        #userManagementModal .minimal-filter .form-control,
        #userManagementModal .minimal-filter .form-select {
            border-radius: 12px;
            border-color: rgba(13, 110, 253, 0.15);
            box-shadow: none;
        }

        #userManagementModal .minimal-filter .btn {
            border-radius: 12px;
        }

        #userManagementModal .minimal-toolbar .btn {
            border-radius: 999px;
            padding-inline: 1.2rem;
        }

        #userManagementModal .minimal-toolbar .btn-outline-secondary {
            border-color: rgba(15, 23, 42, 0.12);
            color: #0f172a;
        }

        #userManagementModal .minimal-toolbar .btn-outline-secondary:hover,
        #userManagementModal #filterToggleBtn.active {
            background: #0d6efd;
            color: #ffffff;
            border-color: #0d6efd;
        }

        #userManagementModal .minimal-input-group {
            background: #ffffff;
            border-radius: 999px;
            border: 1px solid rgba(13, 110, 253, 0.18);
            overflow: hidden;
        }

        #userManagementModal .minimal-input-group .input-group-text {
            background: transparent;
            border: none;
        }

        #userManagementModal .minimal-input-group .form-control {
            border: none;
            box-shadow: none;
        }

        #userManagementModal .minimal-input-group .btn {
            border: none;
            border-radius: 0 999px 999px 0;
        }

        #userManagementModal .minimal-meta {
            font-size: 0.85rem;
            color: #64748b;
        }

        #userManagementModal .minimal-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.6rem;
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            background: rgba(13, 110, 253, 0.14);
            color: #0d6efd;
            font-weight: 600;
        }

        #userManagementModal .minimal-table-head th {
            background: #f5f7fb;
            color: #1f2937;
            border-bottom: none;
            font-size: 0.85rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 1rem 0.75rem;
        }

        #userManagementModal #usersTable tbody td {
            padding: 1rem 0.75rem;
            border-top: 1px solid rgba(15, 23, 42, 0.05);
        }

        #userManagementModal #usersTable tbody tr:hover {
            background: rgba(13, 110, 253, 0.05);
        }

        #userManagementModal .minimal-footer {
            padding: 1.25rem 1.8rem 1.6rem;
        }

        #userManagementModal .minimal-footer select {
            border-radius: 12px;
            border-color: rgba(15, 23, 42, 0.12);
        }

        /* Detail slider info items */
        .info-item {
            padding: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid rgba(15, 23, 42, 0.05);
            transition: all 0.2s ease;
        }

        .info-item:hover {
            background: #eef2ff;
            border-color: rgba(102, 126, 234, 0.4);
            transform: translateY(-1px);
        }

        .info-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 4px;
            display: block;
        }

        .info-value {
            font-size: 0.95rem;
            color: #212529;
            font-weight: 500;
        }

        .spinner-border {
            border-width: 3px;
        }

        .status-active { background: #28a745; }
        .status-inactive { background: #dc3545; }
        .status-suspended { background: #ffc107; color: #212529; }

        .role-admin { background: #6f42c1; }
        .role-teacher { background: #fd7e14; }
        .role-student { background: #20c997; }
        .role-guardian { background: #0dcaf0; }

        @media (max-width: 768px) {
            .modal-xl, .modal-lg-plus {
                max-width: 95%;
                margin: 10px auto;
            }

            #userManagementModal .minimal-toolbar {
                width: 100%;
                justify-content: stretch;
            }

            #userManagementModal .minimal-input-group {
                width: 100%;
            }

            #userManagementModal .minimal-card {
                padding: 1.2rem 1.3rem;
            }
        }
    </style>

</body>

</html>