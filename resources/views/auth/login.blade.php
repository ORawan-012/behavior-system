<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - ระบบติดตามพฤติกรรมวินัยนักเรียน</title>
    <!-- Bootstrap 5.3.6 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        /* Color Variables */
        :root {
            --primary-app: #1020AD;
            --primary-light: rgba(16, 32, 173, 0.1);
            --primary-dark: #0a1570;
            --secondary-app: #F6E200;
            --secondary-light: #fffbd3;
            --accent-app: #95A4D8;
            --accent-light: rgba(149, 164, 216, 0.2);
            --accent-dark: #7180c0;
            --light-gray: #f8f9fa;
            --border-radius-lg: 1rem;
            --box-shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --box-shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
            --box-shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            --transition-base: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            --transition-bounce: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        /* Base Layout */
        body {
            font-family: 'Prompt', sans-serif;
            background-color: var(--light-gray);
            overflow-x: hidden;
        }
        
        /* Particles Container */
        .particles-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
        
        /* App Container */
        .app-container {
            position: relative;
            box-shadow: var(--box-shadow-lg);
            overflow: hidden;
        }
        
        @media (min-width: 992px) {
            .app-container {
                margin: 2rem auto;
                max-width: 1000px;
                border-radius: var(--border-radius-lg);
                min-height: calc(100vh - 4rem);
                height: auto;
                display: grid;
                grid-template-rows: auto 1fr;
                overflow-y: auto;
            }
        }
        
        /* Form Container */
        .app-form-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 1.5rem;
            position: relative;
        }
        
        @media (min-width: 992px) {
            .app-form-container {
                padding: 2rem;
                max-width: 800px;
                padding-bottom: 4rem;
            }
            
            /* Left decoration for desktop */
            .app-form-container::before {
                content: '';
                position: absolute;
                left: -30px;
                top: 20%;
                height: 60%;
                width: 6px;
                background: linear-gradient(to bottom, var(--primary-app), var(--accent-app));
                border-radius: 3px;
                opacity: 0.7;
            }
        }
        
        /* Header/Nav Bar */
        .navbar {
            padding: 1rem;
            background: linear-gradient(135deg, var(--primary-app), var(--primary-dark)) !important;
        }
        
        @media (min-width: 992px) {
            .navbar {
                border-radius: var(--border-radius-lg) var(--border-radius-lg) 0 0;
                padding: 0.75rem 2rem;
            }
            
            .navbar-brand {
                font-size: 1.25rem;
                font-weight: 600;
                letter-spacing: 0.5px;
            }
            
            .navbar-brand img {
                transform: scale(1.2);
                transition: var(--transition-base);
            }
            
            .navbar-brand:hover img {
                transform: scale(1.3) rotate(5deg);
            }
        }
        
        /* Form Title */
        .form-title {
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }
        
        .form-title h2 {
            color: var(--primary-app);
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }
        
        .form-title h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-app), var(--accent-app));
            border-radius: 2px;
        }
        
        .form-title p {
            margin-top: 1.5rem;
            color: #6c757d;
        }
        
        @media (min-width: 992px) {
            .form-title h2 {
                font-size: 2.2rem;
            }
            
            .form-title p {
                font-size: 1.1rem;
                max-width: 80%;
                margin-left: auto;
                margin-right: auto;
            }
        }
        
        /* Role Cards */
        .role-cards-container {
            margin: 2rem 0;
        }
        
        .role-card {
            cursor: pointer;
            transition: var(--transition-bounce);
            border: 2px solid transparent;
            height: 100%;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--box-shadow-sm);
        }
        
        .role-card .card-body {
            padding: 2rem 1.5rem;
            position: relative;
            z-index: 1;
            background-color: white;
            transition: var(--transition-base);
        }
        
        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, var(--primary-light), var(--accent-light));
            opacity: 0;
            transition: var(--transition-base);
            z-index: 0;
        }
        
        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--box-shadow-md);
        }
        
        .role-card:hover::before {
            opacity: 1;
        }
        
        .role-card.selected {
            border-color: var(--primary-app);
            box-shadow: 0 0 0 4px var(--primary-light);
        }
        
        .role-card.selected .role-icon {
            transform: scale(1.15);
        }
        
        /* Role Image */
        .role-image {
            max-width: 80%;
            height: auto;
            transition: var(--transition-bounce);
        }

        .role-icon {
            width: 90px;
            height: 90px;
            display: flex;
            justify-content: center;
            align-items: center;
            border-radius: 50%;
            margin: 0 auto 20px;
            transition: var(--transition-bounce);
            box-shadow: var(--box-shadow-sm);
            background-color: white;
            overflow: hidden;
            padding: 0.5rem;
        }

        @media (min-width: 992px) {
            .role-icon {
                width: 120px;
                height: 120px;
            }
        }

        .role-card:hover .role-image {
            transform: scale(1.15);
        }

        .role-card.selected .role-image {
            transform: scale(1.15);
        }
        
        .role-card h5 {
            font-weight: 600;
            margin-top: 1rem;
            transition: var(--transition-base);
        }
        
        .role-card:hover h5 {
            color: var(--primary-app);
        }
        
        @media (min-width: 992px) {
            .role-card {
                border-radius: 20px;
            }
            
            .role-icon {
                width: 100px;
                height: 100px;
            }
            
            .role-card .card-body {
                padding: 2.5rem;
            }
            
            .role-card h5 {
                font-size: 1.5rem;
            }
        }
        
        /* Login Forms */
        .login-form {
            display: none;
            animation: fadeInUp 0.5s ease forwards;
        }
        
        .login-form.active {
            display: block;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Form Controls */
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1.5px solid #ced4da;
            padding: 1rem 0.75rem;
            transition: var(--transition-base);
        }
        
        .form-control:focus {
            border-color: var(--primary-app);
            box-shadow: 0 0 0 0.25rem var(--primary-light);
        }
        
        .form-floating > label {
            padding: 1rem 0.75rem;
        }
        
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: var(--primary-app);
            font-weight: 500;
        }
        
        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition-bounce);
        }
        
        .btn-lg {
            padding: 1rem 2rem;
        }
        
        .btn-accent-app {
            background: linear-gradient(135deg, var(--accent-app), var(--accent-dark));
            border: none;
            color: white;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            padding: 12px 20px;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .btn-accent-app:hover, .btn-accent-app:focus {
            background: linear-gradient(135deg, var(--accent-dark), var(--primary-app));
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
            color: white;
        }

        .btn-accent-app:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-spinner {
            margin-left: 8px;
        }

        .btn.is-loading .btn-text {
            visibility: hidden;
            opacity: 0;
        }

        .btn.is-loading .btn-spinner {
            visibility: visible;
            opacity: 1;
        }
        
        .btn-link {
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition-base);
        }
        
        .btn-link:hover {
            color: var(--primary-app) !important;
            transform: translateY(-2px);
        }
        
        /* Login button spinner */
        .btn-spinner {
            display: none;
            width: 1.5rem;
            height: 1.5rem;
        }
        
        .btn.is-loading .btn-text {
            display: none;
        }
        
        .btn.is-loading .btn-spinner {
            display: inline-block;
        }
        
        /* Password field with toggle */
        .password-field {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 15px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
        }
        
        .password-toggle:hover {
            color: var(--primary-app);
        }
        
        /* Toast Notifications */
        .toast {
            border-radius: 10px;
            box-shadow: var(--box-shadow-md);
        }
        
        .toast-header {
            border-radius: 10px 10px 0 0;
        }
        
        /* Loading indicator */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition-base);
        }
        
        .loading-overlay.show {
            opacity: 1;
            visibility: visible;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--accent-light);
            border-top-color: var(--primary-app);
            border-radius: 50%;
            animation: spinner 1s linear infinite;
        }
        
        @keyframes spinner {
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Additional animations */
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }

        /* Tab role selection */
        .role-nav {
            border-radius: 50px;
            background-color: var(--light-gray);
            border: none;
            overflow: hidden;
            display: flex;
            margin-bottom: 2rem;
        }
        
        .role-nav .nav-link {
            color: #6c757d;
            border: none;
            border-radius: 0;
            padding: 0.75rem 1.25rem;
            transition: var(--transition-base);
            font-weight: 500;
            text-align: center;
        }
        
        .role-nav .nav-link.active {
            background-color: var(--primary-app);
            color: white;
            font-weight: 600;
        }

        .role-nav .nav-link:not(.active):hover {
            color: var(--primary-app);
            background-color: rgba(149, 164, 216, 0.1);
        }
        
        /* Remember me checkbox */
        .form-check-input:checked {
            background-color: var(--primary-app);
            border-color: var(--primary-app);
        }
        
        .form-check-input:focus {
            border-color: var(--accent-app);
            box-shadow: 0 0 0 0.25rem var(--primary-light);
        }

        /* Back Button */
        .back-to-login {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition-bounce);
            box-shadow: var(--box-shadow-sm);
        }

        .back-to-login:hover {
            transform: translateX(-3px);
            box-shadow: var(--box-shadow-md);
        }

        @media (min-width: 992px) {
            .back-to-login {
                top: 20px;
                left: 20px;
            }
        }
    </style>
</head>
<body class="bg-light">
    <div id="particles-js" class="particles-container"></div>
    
    <div class="app-container bg-white min-vh-100 d-flex flex-column">
        <!-- เพิ่มปุ่มย้อนกลับไปหน้าแรก -->
        <a href="/" class="back-to-login btn btn-sm rounded-circle bg-white shadow-sm text-primary-app">
            <i class="fas fa-arrow-left"></i>
        </a>
        
        <!-- Header/Nav Bar (Simplified) -->
        <nav class="navbar bg-primary-app shadow-sm">
            <div class="container">
                <a class="navbar-brand text-white d-flex align-items-center mx-auto" href="#">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="rounded-circle me-2" width="40" height="40"> 
                    <span>ระบบติดตามพฤติกรรม</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container py-4 flex-grow-1 d-flex flex-column">
            <div class="app-form-container">
                <!-- แสดงข้อความ Error หลัก -->
                @if ($errors->any() && !$errors->has('email') && !$errors->has('password') && !$errors->has('parent_phone') && !$errors->has('student_code'))
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Form Title -->
                <div class="form-title">
                    <h2 class="text-primary-app fw-bold">เข้าสู่ระบบ</h2>
                    <p class="text-muted">เข้าสู่ระบบติดตามพฤติกรรมวินัยนักเรียน</p>
                </div>
                
                <!-- Role Selection Section -->
                <div class="role-selection mb-4">
                    <h5 class="text-center mb-4">เลือกประเภทผู้ใช้งาน</h5>
                    
                    <div class="row g-3 role-cards-container">
                        <!-- Teacher Role -->
                        <div class="col-md-4">
                            <div class="card role-card" data-role="teacher" data-target="teacherLoginForm">
                                <div class="card-body text-center p-4">
                                    <div class="role-icon bg-white">
                                        <img src="{{ asset('images/teacher.png') }}" alt="ครู" class="img-fluid role-image">
                                    </div>
                                    <h5 class="card-title mb-0">ครู</h5>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Student Role -->
                        <div class="col-md-4">
                            <div class="card role-card" data-role="student" data-target="studentLoginForm">
                                <div class="card-body text-center p-4">
                                    <div class="role-icon bg-white">
                                        <img src="{{ asset('images/student.png') }}" alt="นักเรียน" class="img-fluid role-image">
                                    </div>
                                    <h5 class="card-title mb-0">นักเรียน</h5>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Parent Role -->
                        <div class="col-md-4">
                            <div class="card role-card" data-role="parent" data-target="parentLoginForm">
                                <div class="card-body text-center p-4">
                                    <div class="role-icon bg-white">
                                        <img src="{{ asset('images/parental.png') }}" alt="ผู้ปกครอง" class="img-fluid role-image">
                                    </div>
                                    <h5 class="card-title mb-0">ผู้ปกครอง</h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="invalid-feedback text-center mb-3" id="roleError">
                        กรุณาเลือกประเภทผู้ใช้งาน
                    </div>
                </div>
                
                <!-- Login Forms Container -->
                <div class="login-forms-container mt-4">
                    <!-- Teacher Login Form -->
                    <form id="teacherLoginForm" class="login-form needs-validation" method="POST" action="{{ route('login') }}" novalidate>
                        @csrf
                        <input type="hidden" name="role" value="teacher">
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="teacherEmail" name="email" placeholder="อีเมลครู" required>
                                <label for="teacherEmail">อีเมลครู</label>
                                <div class="invalid-feedback">
                                    กรุณากรอกอีเมลให้ถูกต้อง
                                </div>
                            </div>
                            @error('email')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3 password-field">
                            <div class="form-floating">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="teacherPassword" name="password" placeholder="รหัสผ่าน" required>
                                <label for="teacherPassword">รหัสผ่าน</label>
                                <button type="button" class="password-toggle" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">
                                    กรุณากรอกรหัสผ่าน
                                </div>
                            </div>
                            @error('password')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="teacherRemember" name="remember">
                                <label class="form-check-label" for="teacherRemember">จดจำฉัน</label>
                            </div>
                            <a href="#" class="btn-link text-muted" onclick="showForgotPasswordModal('teacher'); return false;">ลืมรหัสผ่าน?</a>
                        </div>
                        <button type="submit" class="btn btn-accent-app btn-lg w-100 d-flex align-items-center justify-content-center">
                            <span class="btn-text">เข้าสู่ระบบ</span>
                            <span class="spinner-border spinner-border-sm btn-spinner d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>

                    <!-- Student Login Form -->
                    <form id="studentLoginForm" class="login-form needs-validation" method="POST" action="{{ route('login') }}" novalidate>
                        @csrf
                        <input type="hidden" name="role" value="student">
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="studentEmail" name="email" placeholder="อีเมลนักเรียน" required>
                                <label for="studentEmail">อีเมลนักเรียน</label>
                                <div class="invalid-feedback">
                                    กรุณากรอกอีเมลให้ถูกต้อง (@school.ac.th)
                                </div>
                            </div>
                            @error('email')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3 password-field">
                            <div class="form-floating">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="studentPassword" name="password" placeholder="รหัสผ่าน" required>
                                <label for="studentPassword">รหัสผ่าน</label>
                                <button type="button" class="password-toggle" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <div class="invalid-feedback">
                                    กรุณากรอกรหัสผ่าน
                                </div>
                            </div>
                            @error('password')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="studentRemember" name="remember">
                                <label class="form-check-label" for="studentRemember">จดจำฉัน</label>
                            </div>
                            <a href="#" class="btn-link text-muted" onclick="showForgotPasswordModal('student'); return false;">ลืมรหัสผ่าน?</a>
                        </div>
                        <button type="submit" class="btn btn-accent-app btn-lg w-100 d-flex align-items-center justify-content-center">
                            <span class="btn-text">เข้าสู่ระบบ</span>
                            <span class="spinner-border spinner-border-sm btn-spinner d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>

                    <!-- Parent Login Form -->
                    <form id="parentLoginForm" class="login-form needs-validation" method="POST" action="{{ route('login') }}" novalidate>
                        @csrf
                        <input type="hidden" name="role" value="guardian">
                        <div class="mb-3">
                            <div class="form-floating">
                                <input type="tel" class="form-control @error('parent_phone') is-invalid @enderror" id="parentPhone" name="parent_phone" placeholder="เบอร์โทรศัพท์ผู้ปกครอง" required>
                                <label for="parentPhone">เบอร์โทรศัพท์ผู้ปกครอง</label>
                                <div class="invalid-feedback">
                                    กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง
                                </div>
                            </div>
                            @error('parent_phone')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <small>
                                <strong>หมายเหตุ:</strong> ผู้ปกครองสามารถเข้าสู่ระบบได้ด้วยเบอร์โทรศัพท์เพียงอย่างเดียว
                                <br>ระบบจะตรวจสอบและเชื่อมโยงกับข้อมูลนักเรียนโดยอัตโนมัติ
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="parentRemember" name="remember">
                                <label class="form-check-label" for="parentRemember">จดจำฉัน</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-accent-app btn-lg w-100 d-flex align-items-center justify-content-center">
                            <span class="btn-text">เข้าสู่ระบบ</span>
                            <span class="spinner-border spinner-border-sm btn-spinner d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast for notifications -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong class="me-auto">แจ้งเตือน</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                กรุณาตรวจสอบข้อมูลที่กรอก
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    
    <script>
        // ฟังก์ชันแสดง SweetAlert สำหรับลืมรหัสผ่าน
        function showForgotPasswordModal(role) {
            let title = 'ลืมรหัสผ่าน?';
            let message = '';
            let icon = 'info';
            
            // กำหนดข้อความตาม role
            if (role === 'teacher') {
                title = 'ลืมรหัสผ่าน - ครู';
                message = '<p class="mb-0">กรุณาติดต่อ <strong>ผู้ดูแลระบบ</strong> หรือ <strong>เจ้าหน้าที่ IT</strong> เพื่อขอรับรหัสผ่านใหม่</p>';
            } else if (role === 'student') {
                title = 'ลืมรหัสผ่าน - นักเรียน';
                message = '<p class="mb-0">กรุณาติดต่อ <strong>ครูประจำชั้น</strong> เพื่อขอรับรหัสผ่านใหม่</p>';
            }
            
            Swal.fire({
                icon: icon,
                title: title,
                html: message,
                confirmButtonText: 'รับทราบ',
                confirmButtonColor: '#95A4D8',
                customClass: {
                    popup: 'rounded-3',
                    confirmButton: 'px-4 py-2'
                }
            });
        }
        
        // คำสั่ง JavaScript สำหรับ login.blade.php
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize particles background for desktop only
            if (window.innerWidth > 992) {
                particlesJS('particles-js', {
                    // กำหนดค่า particles ตามต้องการ
                });
            }
            
            // Elements selection
            const roleCards = document.querySelectorAll('.role-card');
            const loginForms = document.querySelectorAll('.login-form');
            const errorToast = new bootstrap.Toast(document.getElementById('errorToast'));
            
            // Password toggle buttons
            const passwordToggles = document.querySelectorAll('.password-toggle');
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const passwordField = this.closest('.password-field').querySelector('input');
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.querySelector('i').classList.toggle('fa-eye');
                    this.querySelector('i').classList.toggle('fa-eye-slash');
                });
            });
            
            // Role card selection
            roleCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Reset selected state
                    roleCards.forEach(c => c.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Hide all forms first
                    loginForms.forEach(form => {
                        form.classList.remove('active');
                    });
                    
                    // Show selected form
                    const targetFormId = this.dataset.target;
                    document.getElementById(targetFormId).classList.add('active');
                });
            });
            
            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                        errorToast.show();
                    } else {
                        // ถ้าฟอร์มถูกต้อง แสดง loading
                        const submitBtn = form.querySelector('button[type="submit"]');
                        submitBtn.querySelector('.btn-spinner').classList.remove('d-none');
                        submitBtn.disabled = true;
                        
                        // ปล่อยให้ฟอร์มถูกส่งตามปกติ
                    }
                    form.classList.add('was-validated');
                });
            });

            // ถ้ามีการส่งฟอร์มแล้วเกิดข้อผิดพลาด ให้แสดงฟอร์มที่มีปัญหา
            // (ทำงานเมื่อมีการรีเฟรชหน้าหลังส่งฟอร์มผิดพลาด)
            if (document.querySelector('.is-invalid')) {
                // ดูว่ามีฟอร์มไหนที่มีข้อผิดพลาด
                let formWithError = null;
                
                if (document.querySelector('#teacherLoginForm .is-invalid')) {
                    formWithError = 'teacherLoginForm';
                } else if (document.querySelector('#studentLoginForm .is-invalid')) {
                    formWithError = 'studentLoginForm';
                } else if (document.querySelector('#parentLoginForm .is-invalid')) {
                    formWithError = 'parentLoginForm';
                }
                
                if (formWithError) {
                    // ซ่อนทุกฟอร์มก่อน
                    loginForms.forEach(form => form.classList.remove('active'));
                    
                    // แสดงเฉพาะฟอร์มที่มีปัญหา
                    document.getElementById(formWithError).classList.add('active');
                    
                    // หา role card ที่ตรงกับฟอร์มและเลือกมัน
                    roleCards.forEach(card => {
                        card.classList.remove('selected');
                        if (card.dataset.target === formWithError) {
                            card.classList.add('selected');
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>