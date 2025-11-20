// Set current date
document.addEventListener('DOMContentLoaded', function () {
    // Set current date in Thai format
    const today = new Date();
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    const thaiDate = today.toLocaleDateString('th-TH', options);
    const currentDateElement = document.querySelector('.current-date');
    if (currentDateElement) {
        currentDateElement.textContent = thaiDate;
    }

    // ตรวจสอบว่า DOM พร้อมแล้ว
    setTimeout(() => {
        // เช็คว่าองค์ประกอบสำคัญโหลดแล้วหรือยัง
        const overviewSection = document.getElementById('overview');
        const statCards = document.querySelectorAll('#overview .stat-card');


        if (statCards.length >= 4) {
            // โหลดข้อมูลสถิติประจำเดือน
            loadMonthlyStats();
        } else {
            console.warn('Stat cards not found, retrying in 1 second...');
            setTimeout(() => {
                loadMonthlyStats();
            }, 1000);
        }

        // Initialize charts
        loadViolationTrendChart();
        loadViolationTypesChart();

    }, 100); // รอ 100ms ให้ DOM โหลดเสร็จ

    // Mobile navigation active state
    const navLinks = document.querySelectorAll('.bottom-navbar .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function () {
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active', 'text-primary-app'));
            // Add active class to clicked link
            this.classList.add('active', 'text-primary-app');
        });
    });

    // Sidebar navigation active state
    const menuItems = document.querySelectorAll('.sidebar-menu .menu-item');
    menuItems.forEach(item => {
        if (!item.hasAttribute('data-bs-toggle')) {
            item.addEventListener('click', function () {
                // Remove active class from all items
                menuItems.forEach(i => i.classList.remove('active'));
                // Add active class to clicked item
                this.classList.add('active');
            });
        }
    });

    // (Removed simulated student search in modal to reduce unused code)

    // Date restriction for violation date (max 3 days in the past)
    const dateInput = document.querySelector('#violationDate');
    if (dateInput) {
        const today = new Date();
        const threeDaysAgo = new Date();
        threeDaysAgo.setDate(today.getDate() - 3);

        dateInput.valueAsDate = today;
        dateInput.min = threeDaysAgo.toISOString().split('T')[0];
        dateInput.max = today.toISOString().split('T')[0];
    }

    // Initialize popovers and tooltips if using them
    const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]');
    if (popoverTriggerList.length > 0) {
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    }

    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0) {
        const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // สำหรับแสดงฟอร์มเพิ่มประเภทพฤติกรรมใหม่
    const violationTypesList = document.getElementById('violationTypesList');
    const violationTypeForm = document.getElementById('violationTypeForm');
    const btnCloseViolationForm = document.getElementById('btnCloseViolationForm');
    const btnCancelViolationType = document.getElementById('btnCancelViolationType');
    const formViolationTitle = document.getElementById('formViolationTitle');
    const studentSearch = document.getElementById('studentSearch');
    const btnSearchStudent = document.getElementById('btnSearchStudent');
    const classFilter = document.getElementById('classFilter');

    // ปุ่มปิดฟอร์ม
    if (btnCloseViolationForm) {
        btnCloseViolationForm.addEventListener('click', function () {
            if (violationTypeForm) violationTypeForm.classList.add('d-none');
            if (violationTypesList) violationTypesList.classList.remove('d-none');
        });
    }

    // ปุ่มยกเลิกในฟอร์ม
    if (btnCancelViolationType) {
        btnCancelViolationType.addEventListener('click', function () {
            if (violationTypeForm) violationTypeForm.classList.add('d-none');
            if (violationTypesList) violationTypesList.classList.remove('d-none');
        });
    }

    // จัดการปุ่มแก้ไข
    const editButtons = document.querySelectorAll('.edit-violation-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            const violationId = this.getAttribute('data-id');
            editViolationType(violationId);
        });
    });

    // จัดการปุ่มลบ
    const deleteButtons = document.querySelectorAll('.delete-violation-btn');
    const deleteViolationModal = document.getElementById('deleteViolationModal');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            const violationId = this.getAttribute('data-id');
            const deleteViolationId = document.getElementById('deleteViolationId');
            if (deleteViolationId) {
                deleteViolationId.value = violationId;
            }
            if (deleteViolationModal && bootstrap.Modal) {
                const modal = new bootstrap.Modal(deleteViolationModal);
                modal.show();
            }
        });
    });

    // ปุ่มยืนยันการลบ
    const confirmDeleteBtn = document.getElementById('confirmDeleteViolation');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function () {
            const deleteViolationId = document.getElementById('deleteViolationId');
            if (deleteViolationId) {
                const violationId = deleteViolationId.value;
                deleteViolationType(violationId);
            }
        });
    }

    // การบันทึกฟอร์ม
    const formViolationType = document.getElementById('formViolationType');
    if (formViolationType) {
        formViolationType.addEventListener('submit', function (e) {
            e.preventDefault();
            saveViolationType();
        });
    }

    // ค้นหาประเภทพฤติกรรม
    const violationTypeSearch = document.getElementById('violationTypeSearch');
    if (violationTypeSearch) {
        violationTypeSearch.addEventListener('keyup', function (e) {
            const searchTerm = this.value.trim();
            filterViolationTypes(searchTerm);
        });
    }

    // แก้ไขการใช้ jQuery ด้วย Vanilla JavaScript
    // ยกเลิกการเรียก loadViolationTypes() ซ้ำเมื่อเปิด modal รายการประเภทพฤติกรรม
    // เพราะ behavior-report.js จัดการโหลดและอัปเดต select แล้ว
    // หากต้องการรีเฟรชเฉพาะตารางใน modal ควรมีฟังก์ชันเฉพาะไม่กระทบ select
    // const violationTypesModal = document.getElementById('violationTypesModal');
    // if (violationTypesModal) {
    //     violationTypesModal.addEventListener('shown.bs.modal', function() {
    //         loadViolationTypes(); // removed to avoid duplicate options
    //     });
    // }

    // ปิดการโหลดประเภทพฤติกรรมซ้ำใน modal (ให้ behavior-report.js จัดการเท่านั้น)
    // const newViolationModal = document.getElementById('newViolationModal');
    // if (newViolationModal) {
    //     newViolationModal.addEventListener('show.bs.modal', function() {
    //         updateViolationSelects();
    //     });
    // }

    if (studentSearch && btnSearchStudent) {
        btnSearchStudent.addEventListener('click', function () {
            searchStudents(studentSearch.value);
        });

        studentSearch.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchStudents(this.value);
            }
        });
    }

    if (classFilter) {
        classFilter.addEventListener('change', function () {
            const searchInput = document.getElementById('studentSearch');
            searchStudents(searchInput ? searchInput.value : '');
        });
    }

    // Profile image preview
    const profileInput = document.getElementById('profile_image');
    const profilePreview = document.getElementById('profile-preview');

    if (profileInput && profilePreview) {
        profileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    profilePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // โหลดข้อมูลสถิติประจำเดือน
    loadMonthlyStats();
});

// ฟังก์ชันโหลดสถิติประจำเดือน
function loadMonthlyStats() {
    fetch('/api/dashboard/stats', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateStatsDisplay(result.data);
            } else {
                console.error('Error loading monthly stats:', result.message);
            }
        })
        .catch(error => {
            console.error('Error fetching monthly stats:', error);
        });
}

// ฟังก์ชันอัปเดตการแสดงข้อมูลสถิติ
function updateStatsDisplay(stats) {
    try {
        // อัปเดตจำนวนพฤติกรรม
        const violationValueEl = document.querySelector('#overview .stat-card:nth-child(2) .stat-value');
        if (violationValueEl) {
            violationValueEl.textContent = stats.violation_count || 0;
        }

        // อัปเดตแนวโน้มจำนวนพฤติกรรม
        const violationTrendEl = document.querySelector('#overview .stat-card:nth-child(2) .stat-change');
        if (violationTrendEl) {
            violationTrendEl.className = 'stat-change mb-0 ' +
                (stats.violation_trend > 0 ? 'increase' : (stats.violation_trend < 0 ? 'decrease' : 'no-change'));
            violationTrendEl.innerHTML = `
                <i class="fas fa-${stats.violation_trend > 0 ? 'arrow-up' : (stats.violation_trend < 0 ? 'arrow-down' : 'equals')} me-1"></i>
                ${Math.abs(stats.violation_trend)}% จากเดือนก่อน
            `;
        }

        // อัปเดตจำนวนนักเรียนที่ถูกบันทึก
        const studentsValueEl = document.querySelector('#overview .stat-card:nth-child(3) .stat-value');
        if (studentsValueEl) {
            studentsValueEl.textContent = stats.students_count || 0;
        }

        // อัปเดตแนวโน้มจำนวนนักเรียน
        const studentsTrendEl = document.querySelector('#overview .stat-card:nth-child(3) .stat-change');
        if (studentsTrendEl) {
            studentsTrendEl.className = 'stat-change mb-0 ' +
                (stats.students_trend > 0 ? 'increase' : (stats.students_trend < 0 ? 'decrease' : 'no-change'));
            studentsTrendEl.innerHTML = `
                <i class="fas fa-${stats.students_trend > 0 ? 'arrow-up' : (stats.students_trend < 0 ? 'arrow-down' : 'equals')} me-1"></i>
                ${Math.abs(stats.students_trend)}% จากเดือนก่อน
            `;
        }

        // อัปเดตจำนวนพฤติกรรมรุนแรง
        const severeValueEl = document.querySelector('#overview .stat-card:nth-child(4) .stat-value');
        if (severeValueEl) {
            severeValueEl.textContent = stats.severe_count || 0;
        }

        // อัปเดตแนวโน้มพฤติกรรมรุนแรง
        const severeTrendEl = document.querySelector('#overview .stat-card:nth-child(4) .stat-change');
        if (severeTrendEl) {
            severeTrendEl.className = 'stat-change mb-0 ' +
                (stats.severe_trend > 0 ? 'increase' : (stats.severe_trend < 0 ? 'decrease' : 'no-change'));
            severeTrendEl.innerHTML = `
                <i class="fas fa-${stats.severe_trend > 0 ? 'arrow-up' : (stats.severe_trend < 0 ? 'arrow-down' : 'equals')} me-1"></i>
                ${Math.abs(stats.severe_trend)}% จากเดือนก่อน
            `;
        }

        // อัปเดตคะแนนเฉลี่ย
        const scoreValueEl = document.querySelector('#overview .stat-card:nth-child(5) .stat-value');
        if (scoreValueEl) {
            scoreValueEl.textContent = stats.avg_score.toFixed(1);
        }

        // อัปเดตแนวโน้มคะแนนเฉลี่ย
        const scoreTrendEl = document.querySelector('#overview .stat-card:nth-child(5) .stat-change');
        if (scoreTrendEl) {
            scoreTrendEl.className = 'stat-change mb-0 ' +
                (stats.score_trend > 0 ? 'increase' : (stats.score_trend < 0 ? 'decrease' : 'no-change'));
            scoreTrendEl.innerHTML = `
                <i class="fas fa-${stats.score_trend > 0 ? 'arrow-up' : (stats.score_trend < 0 ? 'arrow-down' : 'equals')} me-1"></i>
                ${Math.abs(stats.score_trend)} คะแนนจากเดือนก่อน
            `;
        }


    } catch (error) {
        console.error('Error updating stats display:', error);
    }
}

// ปรับปรุงฟังก์ชันโหลดกราฟแนวโน้ม
function loadViolationTrendChart() {
    const ctx = document.getElementById('violationTrend');
    if (!ctx) return;

    // แสดง Loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'chart-loading';
    loadingDiv.innerHTML = `
        <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
        <span class="ms-2">กำลังโหลดข้อมูล...</span>
    `;
    ctx.parentNode.insertBefore(loadingDiv, ctx);

    fetch('/api/dashboard/trends', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(result => {
            // ลบ Loading indicator
            if (loadingDiv) loadingDiv.remove();

            if (result.success) {
                initViolationTrendChart(result.data);
            } else {
                console.error('Error loading trend data:', result.message);
                initViolationTrendChart(null); // ใช้ข้อมูลเริ่มต้น
            }
        })
        .catch(error => {
            // ลบ Loading indicator
            if (loadingDiv) loadingDiv.remove();

            console.error('Error fetching trend data:', error);
            initViolationTrendChart(null); // ใช้ข้อมูลเริ่มต้น
        });
}

// ปรับปรุงฟังก์ชันโหลดกราฟประเภทพฤติกรรม
function loadViolationTypesChart() {
    const ctx = document.getElementById('violationTypes');
    if (!ctx) return;

    // แสดง Loading indicator
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'chart-loading';
    loadingDiv.innerHTML = `
        <div class="spinner-border spinner-border-sm text-primary" role="status">
            <span class="visually-hidden">กำลังโหลด...</span>
        </div>
        <span class="ms-2">กำลังโหลดข้อมูล...</span>
    `;
    ctx.parentNode.insertBefore(loadingDiv, ctx);

    fetch('/api/dashboard/violations', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => response.json())
        .then(result => {
            // ลบ Loading indicator
            if (loadingDiv) loadingDiv.remove();

            if (result.success) {
                initViolationTypesChart(result.data);
            } else {
                console.error('Error loading violation types data:', result.message);
                initViolationTypesChart(null); // ใช้ข้อมูลเริ่มต้น
            }
        })
        .catch(error => {
            // ลบ Loading indicator
            if (loadingDiv) loadingDiv.remove();

            console.error('Error fetching violation types data:', error);
            initViolationTypesChart(null); // ใช้ข้อมูลเริ่มต้น
        });
}

// ปรับปรุงฟังก์ชันสร้างกราฟแนวโน้ม
function initViolationTrendChart(chartData) {
    const ctx = document.getElementById('violationTrend');
    if (!ctx) return;

    // ลบกราฟเดิมถ้ามี
    if (window.violationTrendChart instanceof Chart) {
        window.violationTrendChart.destroy();
    }

    // ถ้าไม่มีข้อมูล ใช้ข้อมูลเริ่มต้น
    if (!chartData) {
        chartData = {
            labels: ['1', '5', '10', '15', '20', '25', '30'],
            datasets: [{
                label: 'พฤติกรรมที่ถูกบันทึก',
                data: [0, 0, 0, 0, 0, 0, 0],
                borderColor: 'rgb(16, 32, 173)',
                backgroundColor: 'rgba(16, 32, 173, 0.1)',
                tension: 0.4,
                fill: true
            }]
        };
    }

    // สร้างกราฟใหม่
    window.violationTrendChart = new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'จำนวนการบันทึก'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'วันที่'
                    }
                }
            }
        }
    });
}

// ปรับปรุงฟังก์ชันสร้างกราฟประเภทพฤติกรรม
function initViolationTypesChart(chartData) {
    const ctx = document.getElementById('violationTypes');
    if (!ctx) return;

    // ลบกราฟเดิมถ้ามี
    if (window.violationTypesChart instanceof Chart) {
        window.violationTypesChart.destroy();
    }

    // ถ้าไม่มีข้อมูล ใช้ข้อมูลเริ่มต้น
    if (!chartData) {
        chartData = {
            labels: [
                'ไม่มีข้อมูล'
            ],
            datasets: [{
                data: [1],
                backgroundColor: [
                    '#6c757d'
                ],
                borderWidth: 0
            }]
        };
    }

    // สร้างกราฟใหม่
    window.violationTypesChart = new Chart(ctx, {
        type: 'doughnut',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12,
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                }
            },
            cutout: '70%'
        }
    });
}

// ฟังก์ชันค้นหานักเรียน
function searchStudents() {
    const searchInput = document.querySelector('#students .form-control');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchTerm = this.value;
            const url = new URL(window.location.href);

            if (searchTerm) {
                url.searchParams.set('search', searchTerm);
            } else {
                url.searchParams.delete('search');
            }

            // โหลดหน้าใหม่พร้อมพารามิเตอร์ค้นหา
            window.location.href = url.toString();
        });
    }
}

// เรียกใช้เมื่อหน้าโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function () {
    searchStudents();
});

// (Removed showStudentSearchResults / hideStudentSearchResults simulation functions)

// ฟังก์ชันกรองข้อมูลกราฟ
function filterChartData(filterType) {
    // ใส่โค้ดสำหรับการกรองข้อมูลกราฟที่นี่
}

// เพิ่มตัวแปรสำหรับจัดการ violation
const violationManager = {
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    currentPage: 1,
    searchTerm: ''
};

// เพิ่มฟังก์ชันสำหรับ loading states
function showLoading(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        const loadingHTML = `
            <div class="text-center py-4" id="loading-${containerId}">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">กำลังโหลด...</span>
                </div>
                <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', loadingHTML);
    }
}

function hideLoading(containerId) {
    const loadingElement = document.getElementById(`loading-${containerId}`);
    if (loadingElement) {
        loadingElement.remove();
    }
}

// เพิ่มฟังก์ชันสำหรับแสดงข้อความ
function showSuccess(message) {
    // ใช้ SweetAlert2 หรือ Bootstrap Toast หรือ alert ธรรมดา
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: message,
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#1020AD'
        });
    } else {
        // ใช้ Bootstrap Toast หรือ alert ธรรมดา
        showToast(message, 'success');
    }
}

function showError(message) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด!',
            text: message,
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#dc3545'
        });
    } else {
        showToast(message, 'error');
    }
}

// ฟังก์ชัน Toast สำรอง (ถ้าไม่มี SweetAlert2)
function showToast(message, type = 'info') {
    // สร้าง toast element
    const toastContainer = document.getElementById('toast-container') || createToastContainer();
    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : type === 'error' ? 'bg-danger' : 'bg-info';

    const toastHTML = `
        <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHTML);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 5000
    });
    toast.show();

    // ลบ toast หลังจากซ่อน
    toastElement.addEventListener('hidden.bs.toast', function () {
        this.remove();
    });
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// เพิ่มฟังก์ชันสำหรับ violation management
function fetchViolations(page = 1, search = '') {
    const loadingContainer = document.querySelector('#violationTypesList .table tbody');
    if (loadingContainer) {
        loadingContainer.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">กำลังโหลด...</span>
                    </div>
                    <p class="mt-2 text-muted">กำลังโหลดข้อมูล...</p>
                </td>
            </tr>
        `;
    }

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    params.append('page', page);

    fetch(`/api/violations?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': violationManager.csrfToken
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // ตรวจสอบโครงสร้างข้อมูลที่ได้รับ
            if (data && data.data) {
                // ถ้าข้อมูลมาในรูปแบบ Laravel pagination
                if (data.data.data && Array.isArray(data.data.data)) {
                    renderViolationsList(data.data.data);
                    renderPagination(data.data);
                }
                // ถ้าข้อมูลมาในรูปแบบ array ธรรมดา
                else if (Array.isArray(data.data)) {
                    renderViolationsList(data.data);
                    // ไม่มี pagination สำหรับข้อมูลแบบ array ธรรมดา
                    const paginationContainer = document.querySelector('#violationTypesList .pagination');
                    if (paginationContainer) paginationContainer.innerHTML = '';
                }
                else {
                    // ถ้าโครงสร้างข้อมูลไม่ตรงกับที่คาดหวัง
                    renderViolationsList([]);
                }

                violationManager.currentPage = page;
                violationManager.searchTerm = search;
            } else {
                showError(data?.message || 'เกิดข้อผิดพลาดในการดึงข้อมูล');
                renderViolationsList([]);
            }
        })
        .catch(error => {
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
            if (loadingContainer) {
                loadingContainer.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4 text-danger">
                        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
                        <p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p>
                        <button class="btn btn-sm btn-outline-primary" onclick="fetchViolations(${page}, '${search}')">
                            <i class="fas fa-redo me-1"></i> ลองใหม่
                        </button>
                    </td>
                </tr>
            `;
            }
        });
}

// แก้ไขฟังก์ชัน renderPagination ให้ปลอดภัยยิ่งขึ้น
function renderPagination(data) {
    const paginationContainer = document.querySelector('#violationTypesList .pagination');

    // ตรวจสอบว่ามี container และข้อมูล pagination
    if (!paginationContainer) {
        console.warn('Pagination container not found');
        return;
    }

    // ตรวจสอบว่าข้อมูลมี pagination properties หรือไม่
    if (!data || typeof data !== 'object' || !data.last_page || data.last_page <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    const currentPage = data.current_page || 1;
    const lastPage = data.last_page || 1;
    const paginationHTML = [];

    // Previous button
    if (currentPage > 1) {
        paginationHTML.push(`
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage - 1}">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `);
    }

    // Page numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(lastPage, currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
        paginationHTML.push(`
            <li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }

    // Next button
    if (currentPage < lastPage) {
        paginationHTML.push(`
            <li class="page-item">
                <a class="page-link" href="#" data-page="${currentPage + 1}">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `);
    }

    paginationContainer.innerHTML = paginationHTML.join('');

    // Add click events (ลบ event listener เก่าก่อน)
    const newPaginationContainer = paginationContainer.cloneNode(true);
    paginationContainer.parentNode.replaceChild(newPaginationContainer, paginationContainer);

    newPaginationContainer.addEventListener('click', function (e) {
        e.preventDefault();
        const pageLink = e.target.closest('[data-page]');
        if (pageLink) {
            const page = parseInt(pageLink.dataset.page);
            if (page && page > 0 && page <= lastPage) {
                fetchViolations(page, violationManager.searchTerm);
            }
        }
    });
}

// เพิ่มฟังก์ชัน getCategoryBadge ก่อนฟังก์ชัน renderViolationsList
function getCategoryBadge(category) {
    const badges = {
        'light': '<span class="badge bg-success">เบา</span>',
        'medium': '<span class="badge bg-warning text-dark">ปานกลาง</span>',
        'severe': '<span class="badge bg-danger">หนัก</span>'
    };
    return badges[category] || '<span class="badge bg-secondary">ไม่ระบุ</span>';
}

// แก้ไขฟังก์ชัน renderViolationsList ให้ปลอดภัยยิ่งขึ้น
function renderViolationsList(violations) {
    const tbody = document.querySelector('#violationTypesList .table tbody');
    if (!tbody) {
        console.error('Table tbody not found');
        return;
    }

    // ตรวจสอบว่า violations เป็น array หรือไม่
    if (!Array.isArray(violations)) {
        console.error('Violations data is not an array:', violations);
        violations = [];
    }

    if (violations.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <p>ไม่พบข้อมูลประเภทพฤติกรรม</p>
                    ${violationManager.searchTerm ? `<p class="small">คำค้นหา: "${violationManager.searchTerm}"</p>` : ''}
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = violations.map(violation => {
        // ตรวจสอบความถูกต้องของข้อมูล violation
        const id = violation.violations_id || violation.id || '';
        const name = violation.violations_name || violation.name || 'ไม่ระบุ';
        const category = violation.violations_category || violation.category || '';
        const points = violation.violations_points_deducted || violation.points_deducted || 0;
        const description = violation.violations_description || violation.description || '';

        const categoryBadge = getCategoryBadge(category);

        return `
            <tr>
                <td>${escapeHtml(name)}</td>
                <td>${categoryBadge}</td>
                <td>${points} คะแนน</td>
                <td>${escapeHtml(description) || '-'}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary edit-violation-btn me-1" 
                            data-id="${id}" title="แก้ไข">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger delete-violation-btn" 
                            data-id="${id}" title="ลบ">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    // เพิ่ม event listeners ใหม่
    attachEditButtonListeners();
    attachDeleteButtonListeners();
}

// เพิ่มฟังก์ชัน escapeHtml เพื่อป้องกัน XSS
function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function (m) { return map[m]; });
}


// เพิ่มฟังก์ชันสำหรับการ attach event listeners ของปุ่มแก้ไขและลบ
function attachEditButtonListeners() {
    const editButtons = document.querySelectorAll('.edit-violation-btn');
    editButtons.forEach(button => {
        // ลบ event listener เดิม (ถ้ามี)
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);

        // เพิ่ม event listener ใหม่
        newButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const violationId = this.getAttribute('data-id');
            if (violationId && !this.disabled) {
                this.disabled = true;
                editViolationType(violationId);
                setTimeout(() => {
                    this.disabled = false;
                }, 1000);
            }
        });
    });
}

function attachDeleteButtonListeners() {
    const deleteButtons = document.querySelectorAll('.delete-violation-btn');
    deleteButtons.forEach(button => {
        // ลบ event listener เดิม (ถ้ามี)
        const newButton = button.cloneNode(true);
        button.parentNode.replaceChild(newButton, button);

        // เพิ่ม event listener ใหม่
        newButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const violationId = this.getAttribute('data-id');
            const deleteViolationId = document.getElementById('deleteViolationId');
            if (deleteViolationId) {
                deleteViolationId.value = violationId;
            }
            const deleteViolationModal = document.getElementById('deleteViolationModal');
            if (deleteViolationModal && bootstrap.Modal) {
                const modal = new bootstrap.Modal(deleteViolationModal);
                modal.show();
            }
        });
    });
}
