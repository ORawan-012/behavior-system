/**
 * Student Dashboard JavaScript
 * Handles charts and interactive features - simplified version without effects
 */

// Global chart instances
let behaviorChart = null;
let behaviorChartMobile = null;
let violationChart = null;

// Wait until DOM is fully loaded
document.addEventListener('DOMContentLoaded', function () {
    // Show loading animation
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="loading-spinner"></div>';
    document.body.appendChild(loadingOverlay);

    // Initialize components
    setTimeout(function () {
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
    // ฟังก์ชันช่วยดึงรายการหักคะแนนจาก recent activities
    function getDeductionEvents() {
        if (Array.isArray(window.recentActivities) && window.recentActivities.length) {
            return window.recentActivities.filter(ev => ev.title && /ถูกหักคะแนน/.test(ev.title));
        }
        return [];
    }

    const deductionEvents = getDeductionEvents();

    // ⚠️ ไม่ต้องปรับแต่งข้อมูลเลย - ใช้ข้อมูลจาก backend ตรงๆ
    // Backend (StudentController.getBehaviorChartData) จะส่งข้อมูลที่ถูกต้องมาให้แล้ว

    // ตรวจสอบข้อมูลจริงเพื่อปรับ scale ให้เหมาะสม
    const dataPoints = chartData && chartData.datasets && chartData.datasets[0] ? chartData.datasets[0].data : [];
    const values = dataPoints.filter(v => v != null && !isNaN(v));
    const minScore = values.length > 0 ? Math.min(...values) : 0;
    const maxScore = values.length > 0 ? Math.max(...values) : 100;

    // คำนวณ scale ที่เหมาะสม
    const scoreRange = maxScore - minScore;
    const yMin = scoreRange > 50 ? 0 : Math.max(0, minScore - 10);
    const yMax = scoreRange > 50 ? 100 : Math.min(100, maxScore + 10);

    const options = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'nearest',
            intersect: false
        },
        scales: {
            y: {
                beginAtZero: false,
                min: yMin,
                max: yMax,
                ticks: {
                    stepSize: 10,
                    callback: function (value) {
                        return value + ' คะแนน';
                    },
                    font: {
                        family: 'Prompt',
                        size: 11
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)'
                }
            },
            x: {
                ticks: {
                    font: {
                        family: 'Prompt',
                        size: 11
                    },
                    maxRotation: 45,
                    minRotation: 0
                },
                grid: {
                    display: false
                }
            }
        },
        plugins: {
            legend: {
                display: chartData.datasets.length > 1,
                position: 'top',
                labels: {
                    font: {
                        family: 'Prompt',
                        size: 12
                    }
                }
            },
            tooltip: {
                enabled: true,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: {
                    family: 'Prompt',
                    size: 13
                },
                bodyFont: {
                    family: 'Prompt',
                    size: 12
                },
                padding: 12,
                displayColors: false,
                callbacks: {
                    title: function (context) {
                        return context[0].label || '';
                    },
                    label: function (context) {
                        if (context.parsed.y !== null) {
                            return `คะแนน: ${context.parsed.y}`;
                        }
                        return '';
                    }
                }
            }
        },
        elements: {
            line: {
                tension: 0.4, // เส้นโค้งนุ่มนวล
                borderWidth: 3,
            },
            point: {
                radius: 4,
                hoverRadius: 6,
                hitRadius: 10,
                borderWidth: 2
            }
        }
    };

    // สร้าง Gradient
    const createGradient = (ctx) => {
        if (!ctx || !ctx.canvas) return 'rgba(59, 130, 246, 0.1)';
        const chart = ctx.canvas.getContext('2d');
        if (!chart) return 'rgba(59, 130, 246, 0.1)';
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
        if (desktopData.datasets[0]) {
            desktopData.datasets[0].fill = true;
            desktopData.datasets[0].backgroundColor = gradient;
        }

        behaviorChart = new Chart(desktopChartCtx, {
            type: 'line',
            data: desktopData,
            options: { ...options, plugins: { ...options.plugins, legend: { display: false } } }
        });
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
        if (mobileData.datasets[0]) {
            mobileData.datasets[0].fill = true;
            mobileData.datasets[0].backgroundColor = mobileGradient;
        }

        behaviorChartMobile = new Chart(mobileChartCtx, {
            type: 'line',
            data: mobileData,
            options: { ...options, plugins: { ...options.plugins, legend: { display: false } } }
        });
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
                            label: function (context) {
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
        link.addEventListener('click', function () {
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
        item.addEventListener('click', function () {
            // Remove active class from all items
            navItems.forEach(navItem => {
                navItem.classList.remove('active');
            });

            // Add active class to this item
            this.classList.add('active');
        });
    });

    // Add responsive handling for window resizing
    window.addEventListener('resize', function () {
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