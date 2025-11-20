<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://ui-avatars.com https://placehold.co; font-src 'self' data: https: https://fonts.gstatic.com;">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="robots" content="noindex, nofollow">
    <title>ระบบติดตามพฤติกรรมวินัยนักเรียน</title>
    
    <!-- Suppress Image Load Errors -->
    <style>
        img {
            background-color: #f8f9fa;
        }
        
        .hero-image {
            background: linear-gradient(135deg, #163AD7, #4573AA);
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .hero-image::before {
            content: '🏫';
            font-size: 4rem;
            color: white;
            display: block;
        }
        
        .hero-image[src]:not([src=""]) {
            background: none;
        }
        
        .hero-image[src]:not([src=""])::before {
            display: none;
        }
    </style>
    <!-- Bootstrap 5.3.6 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Google Fonts - Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AOS - Animate On Scroll -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- Swiper CSS for carousels -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <!-- Custom Styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="bg-light">
    <!-- เพิ่ม div สำหรับ particles -->
    <div id="particles-js" class="particles-container"></div>
    
    <div class="app-container bg-white">
        <!-- App Header/Nav Bar -->
        <nav class="navbar navbar-expand-lg bg-primary-app">
            <div class="container">
                <a class="navbar-brand text-white d-flex align-items-center" href="#">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="rounded-circle me-2" style="width: 35px; height: 35px;"> 
                    <span>ระบบติดตามพฤติกรรม</span>
                </a>
                <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="/login">
                                <i class="fas fa-sign-in-alt me-1"></i> เข้าสู่ระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="parallax-bg">
            <div class="container py-5 hero-section-wrapper">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7 text-center text-lg-start order-2 order-lg-1">
                        <h1 class="fw-bold text-primary-app display-4" data-aos="fade-up">
                            ระบบติดตาม<span class="text-accent-app">พฤติกรรมวินัย</span>นักเรียน
                        </h1>
                        <h4 class="text-primary-app mb-3" data-aos="fade-up" data-aos-delay="50">โรงเรียนนวมินทราชูทิศ มัชฌิม</h4>
                        <p class="lead my-4" data-aos="fade-up" data-aos-delay="100">
                            ติดตาม บันทึก และส่งเสริม<span id="typed-text"></span>
                        </p>
                        <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center justify-content-lg-start" data-aos="fade-up" data-aos-delay="200">
                            <a href="/login" class="btn btn-primary-app py-2 px-4 rounded-pill fw-medium btn-hover-effect">
                                <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-5 order-1 order-lg-2 text-center position-relative" data-aos="zoom-in">
                        <div class="floating-element position-absolute" style="top: -20px; right: 10%; z-index: 2">
                            <div class="bg-secondary-app rounded-circle p-2 shadow-sm">
                                <i class="fas fa-graduation-cap fa-2x text-primary-app"></i>
                            </div>
                        </div>
                        <div class="floating-element position-absolute" style="bottom: 10px; left: 10%; z-index: 2">
                            <div class="bg-accent-app rounded-circle p-2 shadow-sm">
                                <i class="fas fa-award fa-2x text-white"></i>
                            </div>
                        </div>
                        <img src="{{ asset('images/banner.png') }}?v={{ time() }}" 
                             alt="นวมินทราชูทิศ มัชฌิม" 
                             class="img-fluid rounded-4 shadow-lg hero-image"
                             onerror="this.style.display='none';"
                             style="max-height: 400px; object-fit: cover; width: 100%;"
                             loading="lazy"
                             crossorigin="anonymous">
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section - แสดงข้อมูลจริงจากฐานข้อมูล -->
        <div class="grid-section">
            <div class="container py-5 my-3">
                <div class="row g-4 stats-container">
                    <div class="col-6 col-md-3" data-aos="fade-up">
                        <div class="text-center p-3">
                            <div class="display-4 fw-bold text-primary-app mb-2">
                                <span class="counter" data-target="{{ $stats['total_students'] ?? 0 }}">0</span>
                            </div>
                            <p class="mb-0 text-muted">จำนวนนักเรียน</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                        <div class="text-center p-3">
                            <div class="display-4 fw-bold text-accent-app mb-2">
                                <span class="counter" data-target="{{ $stats['total_teachers'] ?? 0 }}">0</span>
                            </div>
                            <p class="mb-0 text-muted">คุณครู</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                        <div class="text-center p-3">
                            <div class="display-4 fw-bold text-secondary-app mb-2">
                                <span class="counter" data-target="{{ $stats['total_classes'] ?? 0 }}">0</span>
                            </div>
                            <p class="mb-0 text-muted">ห้องเรียน</p>
                        </div>
                    </div>
                    <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="300">
                        <div class="text-center p-3">
                            <div class="display-4 fw-bold text-primary-app mb-2">
                                <span class="counter" data-target="{{ $stats['total_behavior_reports'] ?? 0 }}">0</span>
                            </div>
                            <p class="mb-0 text-muted">บันทึกพฤติกรรม</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Features Section -->
        <div class="container py-5">
            <h2 class="text-center mb-5 text-primary-app fw-bold" data-aos="fade-up">ฟีเจอร์ของระบบ</h2>
            <div class="row g-4">
                <div class="col-md-4" data-aos="fade-up">
                    <div class="card h-100 app-card feature-card">
                        <div class="card-body text-center p-4">
                            <div class="bg-primary-app text-white rounded-circle p-3 d-inline-flex mb-3 icon-animation" style="width: 70px; height: 70px;">
                                <i class="fas fa-clipboard-list fa-2x m-auto"></i>
                            </div>
                            <h4 class="card-title fw-semibold">บันทึกพฤติกรรม</h4>
                            <p class="card-text text-muted">บันทึกและติดตามพฤติกรรมนักเรียนได้อย่างรวดเร็วและมีประสิทธิภาพ</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="card h-100 app-card feature-card">
                        <div class="card-body text-center p-4">
                            <div class="bg-accent-app text-white rounded-circle p-3 d-inline-flex mb-3 icon-animation" style="width: 70px; height: 70px;">
                                <i class="fas fa-chart-line fa-2x m-auto"></i>
                            </div>
                            <h4 class="card-title fw-semibold">รายงานสรุป</h4>
                            <p class="card-text text-muted">ดูรายงานสรุปพฤติกรรมในรูปแบบกราฟที่เข้าใจง่าย</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="card h-100 app-card feature-card">
                        <div class="card-body text-center p-4">
                            <div class="bg-secondary-app text-primary-app rounded-circle p-3 d-inline-flex mb-3 icon-animation" style="width: 70px; height: 70px;">
                                <i class="fas fa-bell fa-2x m-auto"></i>
                            </div>
                            <h4 class="card-title fw-semibold">การแจ้งเตือน</h4>
                            <p class="card-text text-muted">รับการแจ้งเตือนทันทีเมื่อมีการบันทึกพฤติกรรม</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Benefits Section -->
        <div class="container py-5">
            <div class="row align-items-center g-4">
                <div class="col-lg-6" data-aos="fade-right">
                    <h2 class="fw-bold text-primary-app mb-4">ระบบติดตามพฤติกรรมวินัยนักเรียน</h2>
                    <p class="lead">เครื่องมือสำหรับครูและบุคลากรโรงเรียนนวมินทราชูทิศ มัชฌิม</p>
                    
                    <div class="d-flex align-items-start mt-4">
                        <div class="bg-primary-app text-white rounded-circle p-2 me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-check m-auto d-flex justify-content-center align-items-center h-100"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold">บันทึกข้อมูลได้อย่างรวดเร็ว</h5>
                            <p class="text-muted">ครูสามารถบันทึกพฤติกรรมนักเรียนได้ทันทีผ่านระบบออนไลน์</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mt-3">
                        <div class="bg-primary-app text-white rounded-circle p-2 me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-check m-auto d-flex justify-content-center align-items-center h-100"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold">ประมวลผลอัตโนมัติ</h5>
                            <p class="text-muted">คำนวณคะแนนความประพฤติและจัดระดับพฤติกรรมอัตโนมัติ</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mt-3">
                        <div class="bg-primary-app text-white rounded-circle p-2 me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                            <i class="fas fa-check m-auto d-flex justify-content-center align-items-center h-100"></i>
                        </div>
                        <div>
                            <h5 class="fw-semibold">รายงานครบวงจร</h5>
                            <p class="text-muted">ออกรายงานสรุปพฤติกรรมทั้งรายบุคคล รายห้อง และรายระดับชั้น</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6 position-relative" data-aos="fade-left">
                    <img src="{{ asset('images/banner.png') }}" alt="ระบบติดตามพฤติกรรม" class="img-fluid rounded-4 shadow-lg">
                    
                    <!-- Floating elements for decoration -->
                    <div class="position-absolute" style="top: -25px; right: 30px; z-index: 2">
                        <div class="bg-white rounded-4 shadow-sm p-3 floating-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                <span>{{ $stats['reports_this_month'] ?? 0 }} รายงานใหม่</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="position-absolute" style="bottom: -15px; left: 20px; z-index: 2">
                        <div class="bg-white rounded-4 shadow-sm p-3 floating-card">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users text-success me-2"></i>
                                <span>{{ $stats['total_students'] ?? 0 }} นักเรียน</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- School Information Section -->
        <div class="container py-5 my-3">
            <h2 class="text-center mb-5 text-primary-app fw-bold" data-aos="fade-up">โรงเรียนนวมินทราชูทิศ มัชฌิม</h2>
            
            <div class="swiper info-swiper" data-aos="fade-up">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="card app-card p-4 h-100">
                            <div class="text-center mb-3">
                                <div class="bg-primary-app text-white rounded-circle p-3 d-inline-flex mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-school fa-2x m-auto"></i>
                                </div>
                                <h4 class="fw-semibold">ประวัติโรงเรียน</h4>
                            </div>
                            <p class="mb-0">
                                โรงเรียนนวมินทราชูทิศ มัชฌิม เป็นโรงเรียนมัธยมศึกษาประจำจังหวัด เน้นการจัดการศึกษาที่มีคุณภาพควบคู่คุณธรรม ผลิตนักเรียนที่มีความรู้ความสามารถและมีจิตสำนึกที่ดีต่อสังคม
                            </p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="card app-card p-4 h-100">
                            <div class="text-center mb-3">
                                <div class="bg-secondary-app text-primary-app rounded-circle p-3 d-inline-flex mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-medal fa-2x m-auto"></i>
                                </div>
                                <h4 class="fw-semibold">ปรัชญาโรงเรียน</h4>
                            </div>
                            <p class="mb-0">
                                "รักษ์ศักดิ์ศรี มีคุณธรรม นำวิชาการ สืบสานงานพระราชดำริ"<br>
                                มุ่งเน้นการพัฒนาผู้เรียนให้มีความรู้คู่คุณธรรม และดำเนินชีวิตตามหลักปรัชญาเศรษฐกิจพอเพียง
                            </p>
                        </div>
                    </div>
                    <div class="swiper-slide">
                        <div class="card app-card p-4 h-100">
                            <div class="text-center mb-3">
                                <div class="bg-accent-app text-white rounded-circle p-3 d-inline-flex mb-3" style="width: 80px; height: 80px;">
                                    <i class="fas fa-bullhorn fa-2x m-auto"></i>
                                </div>
                                <h4 class="fw-semibold">คติพจน์</h4>
                            </div>
                            <p class="mb-0">
                                "เรียนดี ประพฤติดี มีวินัย ใฝ่คุณธรรม"<br>
                                นักเรียนทุกคนพึงตระหนักและปฏิบัติตามกฎระเบียบวินัยของโรงเรียนอย่างเคร่งครัด เพื่อพัฒนาตนเองให้เป็นคนดี มีคุณภาพของสังคม
                            </p>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="container py-5" data-aos="fade-up">
            <div class="card bg-primary-app text-white p-4 p-md-5 rounded-4 cta-card">
                <div class="card-body text-center">
                    <h2 class="fw-bold mb-4">เริ่มใช้งานระบบตอนนี้</h2>
                    <p class="lead mb-4">ระบบที่จะช่วยให้การติดตามพฤติกรรมนักเรียนเป็นเรื่องง่าย</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="/login" class="btn btn-light text-primary-app py-2 px-4 rounded-pill fw-medium btn-hover-effect">
                            <i class="fas fa-sign-in-alt me-2"></i> เข้าสู่ระบบ
                        </a>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#moreInfoModal" class="btn btn-secondary-app py-2 px-4 rounded-pill fw-medium btn-hover-effect">
                            <i class="fas fa-info-circle me-2"></i> ข้อมูลเพิ่มเติม
                        </a>
                    </div>
                </div>
                <div class="cta-shape-1"></div>
                <div class="cta-shape-2"></div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- AOS - Animate On Scroll -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    
    <!-- Typed.js for text animations -->
    <script src="https://cdn.jsdelivr.net/npm/typed.js@2.0.12"></script>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <!-- GSAP for advanced animations -->
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.12.2/dist/gsap.min.js"></script>
    
    <!-- เพิ่ม Particles.js -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            mirror: false
        });
        
        // เพิ่มการเรียกใช้ Particles.js
        if (window.innerWidth > 992) {
            particlesJS("particles-js", {
              particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: "#95A4D8" },
                shape: { type: "circle" },
                opacity: { value: 0.5, random: true },
                size: { value: 3, random: true },
                line_linked: { enable: true, distance: 150, color: "#1020AD", opacity: 0.2, width: 1 },
                move: { enable: true, speed: 1, direction: "none", random: true, straight: false, out_mode: "out" }
              },
              interactivity: {
                detect_on: "canvas",
                events: { onhover: { enable: true, mode: "grab" }, onclick: { enable: true, mode: "push" } },
                modes: { grab: { distance: 140, line_linked: { opacity: 0.3 } }, push: { particles_nb: 3 } }
              },
              retina_detect: true
            });
        }
        
        // Typing animation
        let typed = new Typed('#typed-text', {
            strings: [
                'พฤติกรรมนักเรียน', 
                'วินัยในโรงเรียน', 
                'คุณธรรมจริยธรรม', 
                'การเป็นแบบอย่างที่ดี'
            ],
            typeSpeed: 50,
            backSpeed: 30,
            backDelay: 2000,
            loop: true
        });
        
        // Initialize School Info Swiper
        const infoSwiper = new Swiper('.info-swiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 6000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                768: {
                    slidesPerView: 2,
                },
                1200: {
                    slidesPerView: 3,
                    spaceBetween: 20
                }
            }
        });
        
        // Floating animation
        gsap.to('.floating-element', {
            y: '-15px',
            duration: 2,
            ease: 'power1.inOut',
            repeat: -1,
            yoyo: true
        });
        
        // Counter animation
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // 2 seconds
            const step = target / (duration / 16); // 16ms per frame
            
            let count = 0;
            const updateCounter = () => {
                count += step;
                if (count < target) {
                    counter.innerText = Math.ceil(count).toString();
                    requestAnimationFrame(updateCounter);
                } else {
                    counter.innerText = target.toString();
                }
            };
            
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    updateCounter();
                    observer.disconnect();
                }
            });
            
            observer.observe(counter);
        });
    </script>
    
    <!-- Image Error Handling -->
    <script>
        // Global error handler for images
        document.addEventListener('DOMContentLoaded', function() {
            // Clear any cached Facebook URLs
            if ('caches' in window) {
                caches.keys().then(function(cacheNames) {
                    cacheNames.forEach(function(cacheName) {
                        caches.open(cacheName).then(function(cache) {
                            cache.keys().then(function(requests) {
                                requests.forEach(function(request) {
                                    if (request.url.includes('facebook') || request.url.includes('fbcdn')) {
                                        cache.delete(request);
                                    }
                                });
                            });
                        });
                    });
                });
            }
            
            // Handle all image errors
            document.querySelectorAll('img').forEach(function(img) {
                img.addEventListener('error', function() {
                    // Hide broken images silently
                    this.style.display = 'none';
                });
            });
            
            // Override console.error to filter out specific errors
            const originalConsoleError = console.error;
            console.error = function(...args) {
                const message = args.join(' ');
                // Don't log Facebook/image related errors
                if (message.includes('fbcdn.net') || 
                    message.includes('scontent') || 
                    message.includes('403') ||
                    message.includes('Forbidden') ||
                    message.includes('facebook')) {
                    return; // Suppress these errors
                }
                originalConsoleError.apply(console, args);
            };
            
            // Override fetch to prevent Facebook requests
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                const url = args[0];
                if (typeof url === 'string' && (url.includes('facebook') || url.includes('fbcdn'))) {
                    return Promise.reject(new Error('Blocked Facebook request'));
                }
                return originalFetch.apply(this, args);
            };
        });
        
        // Global network error handler
        window.addEventListener('error', function(e) {
            if (e.target && e.target.tagName === 'IMG') {
                const src = e.target.src;
                if (src.includes('facebook') || src.includes('fbcdn')) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        }, true);
        
        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', function(e) {
            if (e.reason && e.reason.message && 
                (e.reason.message.includes('facebook') || e.reason.message.includes('fbcdn'))) {
                e.preventDefault();
                // Suppress Facebook network errors silently
            }
        });
    </script>

    <!-- More Info Modal -->
    <div class="modal fade" id="moreInfoModal" tabindex="-1" aria-labelledby="moreInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border-radius: 16px;">
                <div class="modal-header bg-primary-app text-white" style="border-radius: 16px 16px 0 0;">
                    <div class="d-flex align-items-center">
                        <div class="bg-white bg-opacity-20 rounded-circle p-2 me-3">
                            <i class="fas fa-info-circle fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="moreInfoModalLabel">ข้อมูลเพิ่มเติม</h5>
                            <small class="opacity-75">เกี่ยวกับระบบติดตามพฤติกรรมวินัยนักเรียน</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- ข้อมูลระบบ -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary-app mb-3">
                            <i class="fas fa-laptop-code me-2"></i>เกี่ยวกับระบบ
                        </h6>
                        <p class="text-muted">
                            ระบบติดตามพฤติกรรมวินัยนักเรียนถูกพัฒนาขึ้นเพื่อช่วยให้ครูและบุคลากรทางการศึกษาสามารถบันทึกและติดตามพฤติกรรมของนักเรียนได้อย่างมีประสิทธิภาพ 
                            โดยมีระบบคำนวณคะแนนความประพฤติอัตโนมัติและสามารถออกรายงานสรุปได้หลากหลายรูปแบบ
                        </p>
                    </div>

                    <!-- ฟีเจอร์หลัก -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary-app mb-3">
                            <i class="fas fa-star me-2"></i>ฟีเจอร์หลักของระบบ
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="bg-primary-app text-white rounded-circle p-2 me-3 flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="fas fa-clipboard-list small d-flex align-items-center justify-content-center h-100"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">บันทึกพฤติกรรม</h6>
                                        <small class="text-muted">บันทึกพฤติกรรมของนักเรียนได้อย่างรวดเร็ว</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="bg-accent-app text-white rounded-circle p-2 me-3 flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="fas fa-chart-bar small d-flex align-items-center justify-content-center h-100"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">รายงานและสถิติ</h6>
                                        <small class="text-muted">ดูรายงานสรุปและกราฟวิเคราะห์</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="bg-warning text-white rounded-circle p-2 me-3 flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="fas fa-bell small d-flex align-items-center justify-content-center h-100"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">แจ้งเตือนอัตโนมัติ</h6>
                                        <small class="text-muted">แจ้งเตือนผู้ปกครองเมื่อมีพฤติกรรม</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex">
                                    <div class="bg-success text-white rounded-circle p-2 me-3 flex-shrink-0" style="width: 36px; height: 36px;">
                                        <i class="fas fa-file-export small d-flex align-items-center justify-content-center h-100"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-1">ส่งออกรายงาน</h6>
                                        <small class="text-muted">ออกรายงานเป็นไฟล์ PDF และ Excel</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- กลุ่มผู้ใช้งาน -->
                    <div class="mb-4">
                        <h6 class="fw-bold text-primary-app mb-3">
                            <i class="fas fa-users me-2"></i>กลุ่มผู้ใช้งาน
                        </h6>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="card border h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="text-primary-app mb-2">
                                            <i class="fas fa-user-shield fa-2x"></i>
                                        </div>
                                        <h6 class="small mb-0">ผู้ดูแลระบบ</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="text-info mb-2">
                                            <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                        </div>
                                        <h6 class="small mb-0">ครู</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="text-warning mb-2">
                                            <i class="fas fa-graduation-cap fa-2x"></i>
                                        </div>
                                        <h6 class="small mb-0">นักเรียน</h6>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="card border h-100">
                                    <div class="card-body text-center p-3">
                                        <div class="text-success mb-2">
                                            <i class="fas fa-user-friends fa-2x"></i>
                                        </div>
                                        <h6 class="small mb-0">ผู้ปกครอง</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อมูลติดต่อ -->
                    <div class="mb-3">
                        <h6 class="fw-bold text-primary-app mb-3">
                            <i class="fas fa-phone me-2"></i>ติดต่อเรา
                        </h6>
                        <div class="card bg-light border-0">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-school text-primary-app me-2 mt-1"></i>
                                            <div>
                                                <small class="text-muted d-block">โรงเรียน</small>
                                                <span class="fw-medium">โรงเรียนนวมินทราชูทิศ มัชฌิม</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start">
                                            <i class="fas fa-map-marker-alt text-danger me-2 mt-1"></i>
                                            <div>
                                                <small class="text-muted d-block">ที่ตั้ง</small>
                                                <span class="fw-medium">จังหวัดนครสวรรค์</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light" style="border-radius: 0 0 16px 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <a href="/login" class="btn btn-primary-app">
                        <i class="fas fa-sign-in-alt me-2"></i>เข้าสู่ระบบ
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>