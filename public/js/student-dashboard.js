/**
 * Student Dashboard JavaScript
 * Handles charts and interactive features - simplified version without effects
 */

// Global chart instances
let behaviorChart = null;
let behaviorChartMobile = null;
let violationChart = null;

// Wait until DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Show loading animation
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loadingOverlay);
    
    // Initialize components
    setTimeout(function() {
        if (window.chartData) {
            initCharts(window.chartData);
        }
        
        if (window.violationData) {
            initViolationChart(window.violationData);
        }
        
        initializeEventListeners();
        handleMobileContent();
        
        // Remove loading overlay with fade effect
        loadingOverlay.style.opacity = '0';
        setTimeout(() => {
            loadingOverlay.remove();
        }, 500);
        
    }, 800);
});

/**
 * Initialize charts with provided data
 */
function initCharts(chartData) {
    // ใช้ข้อมูลกิจกรรมจาก window.recentActivities ถ้ามี เพื่อให้เสถียร
    function getDeductionEvents() {
        if (Array.isArray(window.recentActivities) && window.recentActivities.length) {
            return window.recentActivities.filter(ev => ev.title && /ถูกหักคะแนน/.test(ev.title));
        }
        // fallback DOM (กรณี script เรียกก่อนตัวแปรถูกเซ็ต)
        const items = document.querySelectorAll('.activity-item');
        const events = [];
        items.forEach(item => {
            const titleEl = item.querySelector('.activity-content p.mb-0.fw-medium');
            const dateEl = item.querySelector('.activity-content p.text-muted');
            if (!titleEl) return;
            const titleText = titleEl.textContent || '';
            if (/ถูกหักคะแนน/.test(titleText)) {
                let dateText = '';
                if (dateEl) {
                    dateText = (dateEl.textContent.split('-').pop() || '').trim();
                }
                events.push({ title: titleText.trim(), date: dateText });
            }
        });
        return events;
    }

    const deductionEvents = getDeductionEvents();

    // ฟังก์ชันเสริม: กรณีมีเหตุหักคะแนนเดียว ให้แสดง 2 จุด (เริ่มต้น 100 และหลังหักเป็นคะแนนปัจจุบัน)
    function applySingleDeductionTwoPoint(chartData, currentScore, deductionEvents) {
        if (!chartData || !chartData.datasets || !chartData.datasets[0]) return;
        if (deductionEvents.length !== 1 || currentScore == null) return;
        const startLabel = chartData.labels && chartData.labels.length ? chartData.labels[0] : 'เริ่มต้น';
        const eventLabel = deductionEvents[0].date || 'เหตุหักคะแนน';
        chartData.labels = [startLabel, eventLabel];
        chartData.datasets[0].data = [100, currentScore];
    }

    // ปรับข้อมูลกราฟให้แสดงการลดลงจาก 100 ไปยังคะแนนปัจจุบัน หรือแสดง 2 จุด (100 -> current) หากมีเหตุเดียว
    try {
        const currentScoreEl = document.getElementById('behavior-points');
        const currentScore = currentScoreEl ? parseInt(currentScoreEl.textContent.trim(), 10) : null;
        // กรณีเหตุหักคะแนนเดียว => สร้าง 2 จุด
        if (deductionEvents.length === 1 && currentScore !== null) {
            applySingleDeductionTwoPoint(chartData, currentScore, deductionEvents);
        } else {
            // เงื่อนไขเดิม: Interpolate หากข้อมูลคงที่
            if (chartData && chartData.datasets && chartData.datasets[0] && Array.isArray(chartData.datasets[0].data)) {
                const ds = chartData.datasets[0];
                const dataArr = ds.data.slice();
                const allSame = dataArr.length > 0 && dataArr.every(v => v === dataArr[0]);
                if (currentScore !== null && currentScore < 100 && allSame && dataArr.length > 1) {
                    const points = dataArr.length;
                    const totalDrop = 100 - currentScore;
                    const step = totalDrop / (points - 1);
                    const newData = [];
                    for (let i = 0; i < points - 1; i++) {
                        const value = Math.max(currentScore, Math.round((100 - step * i) * 100) / 100);
                        newData.push(value);
                    }
                    newData.push(currentScore);
                    ds.data = newData;
                } else if (dataArr.length > 0 && dataArr[0] !== 100) {
                    if (currentScore !== null && currentScore <= dataArr[dataArr.length - 1]) {
                        ds.data[0] = 100;
                    }
                }
            }
        }
        if (chartData && chartData.datasets && chartData.datasets[0] && Array.isArray(chartData.datasets[0].data)) {
            const ds = chartData.datasets[0];
            const dataArr = ds.data.slice();
            const allSame = dataArr.length > 0 && dataArr.every(v => v === dataArr[0]);
            // เงื่อนไข: คะแนนปัจจุบัน < 100 และข้อมูลที่ได้มาทั้งหมดเป็น 100 (หรือค่าเดียวกัน) และมีมากกว่า 1 จุด
            if (currentScore !== null && currentScore < 100 && allSame && dataArr.length > 1) {
                const points = dataArr.length;
                const totalDrop = 100 - currentScore;
                const step = totalDrop / (points - 1);
                const newData = [];
                for (let i = 0; i < points - 1; i++) {
                    const value = Math.max(currentScore, Math.round((100 - step * i) * 100) / 100);
                    newData.push(value);
                }
                // จุดสุดท้าย = คะแนนปัจจุบันจริง
                newData.push(currentScore);
                ds.data = newData;
            } else if (dataArr.length > 0 && dataArr[0] !== 100) {
                // หากจุดเริ่มต้นไม่ใช่ 100 แต่เราต้องการให้เริ่มที่ 100 (ตาม requirement) และข้อมูลยังไม่ลดลง
                if (currentScore !== null && currentScore <= dataArr[dataArr.length - 1]) {
                    ds.data[0] = 100;
                }
            }
        }
    } catch (e) {
        console.warn('Chart data adjustment failed:', e);
    }

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true, // เริ่มที่ 0
                min: 0,
                max: 100, // สูงสุด 100
                ticks: {
                    stepSize: 20
                }
            }
        },
        plugins: {
            legend: {
                display: chartData.datasets.length > 1, // แสดงเมื่อมีหลาย dataset
                position: 'top'
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            label += `${context.parsed.y} คะแนน`;
                        }
                        return label;
                    }
                }
            }
        },
        elements: {
            line: {
                tension: 0.3, // ทำให้เส้นโค้งเล็กน้อย
                borderWidth: 3,
            },
            point: {
                radius: 5,
                hoverRadius: 7
            }
        }
    };

    // สร้าง Gradient
    const createGradient = (ctx) => {
        if (!ctx || !ctx.canvas) return 'rgba(59, 130, 246, 0.1)';
        const chart = ctx.canvas.getContext('2d');
        if(!chart) return 'rgba(59, 130, 246, 0.1)';
        const gradient = chart.createLinearGradient(0, 0, 0, ctx.canvas.height);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');
        return gradient;
    };

    // Desktop Chart
    const desktopCtx = document.getElementById('behaviorChart');
    if (desktopCtx) {
        if (behaviorChart) {
            behaviorChart.destroy();
        }
        
        const desktopChartCtx = desktopCtx.getContext('2d');
        const gradient = createGradient(desktopChartCtx);

        // เพิ่มสีพื้นหลังให้ dataset
        const desktopData = JSON.parse(JSON.stringify(chartData)); // Deep copy
        if(desktopData.datasets[0]){
            desktopData.datasets[0].fill = true;
            desktopData.datasets[0].backgroundColor = gradient;
        }

        behaviorChart = new Chart(desktopChartCtx, {
            type: 'line',
            data: desktopData,
            options: { ...options, plugins: { ...options.plugins, legend: { display: false } } }
        });

        // บังคับปรับหลังสร้าง (กันกรณี DOM ไม่พร้อม) หากเหตุเดียว -> 2 จุด
        if (deductionEvents.length === 1) {
            try {
                const currentScoreEl = document.getElementById('behavior-points');
                const currentScore = currentScoreEl ? parseInt(currentScoreEl.textContent.trim(), 10) : null;
                if (currentScore !== null) {
                    applySingleDeductionTwoPoint(behaviorChart.data, currentScore, deductionEvents);
                    behaviorChart.update();
                }
            } catch (err) {
                console.warn('Single-point override failed (desktop):', err);
            }
        }
    }
    
    // Mobile Chart
    const mobileCtx = document.getElementById('behaviorChartMobile');
    if (mobileCtx) {
        if (behaviorChartMobile) {
            behaviorChartMobile.destroy();
        }

        const mobileChartCtx = mobileCtx.getContext('2d');
        const mobileGradient = createGradient(mobileChartCtx);

        const mobileData = JSON.parse(JSON.stringify(chartData)); // Deep copy
        if(mobileData.datasets[0]){
            mobileData.datasets[0].fill = true;
            mobileData.datasets[0].backgroundColor = mobileGradient;
        }
        
        behaviorChartMobile = new Chart(mobileChartCtx, {
            type: 'line',
            data: mobileData,
            options: { ...options, plugins: { ...options.plugins, legend: { display: false } } }
        });

        if (deductionEvents.length === 1) {
            try {
                const currentScoreEl = document.getElementById('behavior-points');
                const currentScore = currentScoreEl ? parseInt(currentScoreEl.textContent.trim(), 10) : null;
                if (currentScore !== null) {
                    applySingleDeductionTwoPoint(behaviorChartMobile.data, currentScore, deductionEvents);
                    behaviorChartMobile.update();
                }
            } catch (err) {
                console.warn('Single-point override failed (mobile):', err);
            }
        }
    }
}

/**
 * Initialize Violation Distribution Chart
 */
function initViolationChart(violationData) {
    const violationCtx = document.getElementById('violationChart');
    if (violationCtx) {
        // ทำลายกราฟเก่าก่อนสร้างใหม่
        if (violationChart) {
            violationChart.destroy();
        }
        
        violationChart = new Chart(violationCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: violationData.labels,
                datasets: [{
                    data: violationData.data,
                    backgroundColor: violationData.colors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                family: 'Prompt',
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed;
                                
                                // ถ้าเป็นข้อมูลว่าง
                                if (label === 'ไม่มีข้อมูล') {
                                    return 'ยังไม่มีข้อมูลพฤติกรรม';
                                }
                                
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} ครั้ง (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
}

/**
 * Handle mobile-specific content
 */
function handleMobileContent() {
    // Clone activity items for mobile view
    const activityItems = document.querySelectorAll('.activities-area .activity-item');
    const mobileActivitiesContainer = document.querySelector('.mobile-activities');

    if (mobileActivitiesContainer && activityItems.length > 0) {
        activityItems.forEach(item => {
            mobileActivitiesContainer.appendChild(item.cloneNode(true));
        });
    }
}

/**
 * Initialize all event listeners for interactive elements
 */
function initializeEventListeners() {
    // Bottom navbar active state (mobile)
    const mobileNavLinks = document.querySelectorAll('.bottom-navbar .nav-link');
    mobileNavLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Remove active class from all links
            mobileNavLinks.forEach(l => {
                l.classList.remove('text-primary-app');
                l.classList.add('text-muted');
            });
            
            // Add active class to clicked link
            this.classList.remove('text-muted');
            this.classList.add('text-primary-app');
        });
    });

    // Desktop navbar active state - แบบเรียบง่ายไม่มี effects
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            // Remove active class from all items
            navItems.forEach(navItem => {
                navItem.classList.remove('active');
            });
            
            // Add active class to this item
            this.classList.add('active');
        });
    });
    
    // Add responsive handling for window resizing
    window.addEventListener('resize', function() {
        // Adjust charts if needed
        const desktopChart = Chart.getChart('behaviorChart');
        const mobileChart = Chart.getChart('behaviorChartMobile');
        const violationChartInstance = Chart.getChart('violationChart');
        
        if (desktopChart) desktopChart.resize();
        if (mobileChart) mobileChart.resize();
        if (violationChartInstance) violationChartInstance.resize();
    });
}

/**
 * Toggle User menu dropdown
 */
function toggleUserMenu() {
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        userMenu.classList.toggle('show');
        
        // Close menu when clicking outside
        if (userMenu.classList.contains('show')) {
            document.addEventListener('click', closeUserMenuOnClickOutside);
        } else {
            document.removeEventListener('click', closeUserMenuOnClickOutside);
        }
    }
}

/**
 * Close user menu when clicking outside
 */
function closeUserMenuOnClickOutside(event) {
    const userMenu = document.getElementById('userMenu');
    const userProfile = document.querySelector('.user-profile');
    
    if (userMenu && !userMenu.contains(event.target) && !userProfile.contains(event.target)) {
        userMenu.classList.remove('show');
        document.removeEventListener('click', closeUserMenuOnClickOutside);
    }
}

// เพิ่มฟังก์ชันนี้ให้ window เพื่อให้เรียกใช้จาก onclick ได้
window.toggleUserMenu = toggleUserMenu;