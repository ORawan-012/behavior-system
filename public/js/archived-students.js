/**
 * JavaScript สำหรับจัดการประวัติการเก็บข้อมูลนักเรียน
 * ใช้งานกับ Sidebar: archivedStudentsSidebar, studentHistorySidebar
 */

// ใช้ URL จาก server ถ้ามี เพื่อหลีกเลี่ยงการ hard-code path
const ARCHIVED_STUDENTS_API_URL = (typeof window !== 'undefined' && window.ARCHIVED_STUDENTS_API_URL)
    ? window.ARCHIVED_STUDENTS_API_URL
    : '/api/teacher/archived-students';
const STUDENT_HISTORY_API_BASE = (typeof window !== 'undefined' && window.STUDENT_HISTORY_API_BASE)
    ? window.STUDENT_HISTORY_API_BASE
    : '/api/teacher/student-history';

// ตัวแปรสำหรับเก็บข้อมูลและสถานะ
let archivedStudentsData = {
    students: [],
    currentPage: 1,
    totalPages: 1,
    perPage: 10,
    isLoading: false,
    filters: {
        status: '',
        level: '',
        room: '',
        score: '',
        search: ''
    }
};

/**
 * เปิด Sidebar สำหรับนักเรียนที่เก็บประวัติ
 */
function openArchivedStudentsSidebar() {
    const sidebar = document.getElementById('archivedStudentsSidebar');
    if (sidebar) {
        sidebar.classList.add('show');
        // โหลดข้อมูลเริ่มต้น
        loadArchivedStudents();
    }
    // ป้องกันการ scroll ของ body
    if (document && document.body) document.body.style.overflow = 'hidden';
}

/**
 * ปิด Sidebar สำหรับนักเรียนที่เก็บประวัติ
 */
function closeArchivedStudentsSidebar() {
    const sidebar = document.getElementById('archivedStudentsSidebar');
    if (sidebar) sidebar.classList.remove('show');
    // คืนค่าการ scroll ของ body
    if (document && document.body) document.body.style.overflow = '';
}

/**
 * เปิด Sidebar สำหรับประวัติของนักเรียน
 */
function openStudentHistorySidebar(studentId, studentName) {
    const sidebar = document.getElementById('studentHistorySidebar');
    if (sidebar) sidebar.classList.add('show');
    // โหลดข้อมูลประวัติ
    loadStudentHistory(studentId, studentName);
}

/**
 * ปิด Sidebar สำหรับประวัติของนักเรียน
 */
function closeStudentHistorySidebar() {
    const sidebar = document.getElementById('studentHistorySidebar');
    if (sidebar) sidebar.classList.remove('show');
}

/**
 * กลับไปยัง Sidebar รายการนักเรียน
 */
function backToArchivedStudents() {
    closeStudentHistorySidebar();
    // ไม่ต้องเปิด archived students sidebar ใหม่ เพราะยังเปิดอยู่
}

/**
 * ฟังก์ชันค้นหานักเรียนตามตัวกรอง
 */
function _getEl(id){ return document.getElementById(id); }
function _getVal(id){ const el = _getEl(id); return el ? (el.value || '').trim() : ''; }

function searchArchivedStudents() {
    // อัปเดตตัวกรองจากฟอร์มอย่างปลอดภัย (ใช้ prefix archived เพื่อเลี่ยงชนกับ admin user management)
    archivedStudentsData.filters = {
        status: _getVal('archivedStatusFilter'),
        level: _getVal('archivedLevelFilter'),
        room: _getVal('archivedRoomFilter'),
        score: _getVal('archivedScoreFilter'),
        search: _getVal('archivedSearchInput')
    };
    archivedStudentsData.currentPage = 1;
    loadArchivedStudents();
}

/**
 * ฟังก์ชันล้างตัวกรอง
 */
function clearFilters() {
    const ids = ['archivedStatusFilter','archivedLevelFilter','archivedRoomFilter','archivedScoreFilter','archivedSearchInput'];
    ids.forEach(id => { const el = _getEl(id); if (el) el.value = ''; });
    archivedStudentsData.filters = { status: '', level: '', room: '', score: '', search: '' };
    archivedStudentsData.currentPage = 1;
    loadArchivedStudents();
}

/**
 * ฟังก์ชันโหลดข้อมูลนักเรียนที่เก็บประวัติ
 */
function loadArchivedStudents(page = 1) {
    if (archivedStudentsData.isLoading) return;

    archivedStudentsData.isLoading = true;
    archivedStudentsData.currentPage = page;

    // แสดง loading state
    showArchivedDataLoading();

    // สร้าง query parameters
    const params = new URLSearchParams({
        page: page,
        per_page: archivedStudentsData.perPage,
        ...archivedStudentsData.filters
    });

    // ลบ parameter ที่เป็นค่าว่าง
    for (let [key, value] of [...params.entries()]) {
        if (!value) {
            params.delete(key);
        }
    }

    fetch(`${ARCHIVED_STUDENTS_API_URL}?${params}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                archivedStudentsData.students = data.data || [];
                archivedStudentsData.totalPages = data.pagination?.last_page || 1;
                renderArchivedStudentsCards();
                renderPagination();
            } else {
                showArchivedDataError(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล');
            }
        })
        .catch(error => {
            console.error('Error loading archived students:', error);
            showArchivedDataError('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        })
        .finally(() => {
            archivedStudentsData.isLoading = false;
            hideArchivedDataLoading();
        });
}

/**
 * แสดง loading state
 */
function showArchivedDataLoading() {
    document.getElementById('archivedDataLoading').style.display = 'block';
    document.getElementById('archivedDataContainer').style.display = 'none';
}

/**
 * ซ่อน loading state
 */
function hideArchivedDataLoading() {
    document.getElementById('archivedDataLoading').style.display = 'none';
    document.getElementById('archivedDataContainer').style.display = 'block';
}

/**
 * แสดงข้อความผิดพลาด
 */
function showArchivedDataError(message) {
    const container = document.getElementById('archivedStudentsList');
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <p>${message}</p>
                <button class="btn btn-primary btn-sm" onclick="loadArchivedStudents()">
                    <i class="fas fa-redo me-1"></i>ลองใหม่
                </button>
            </div>
        </div>
    `;
}

/**
 * แสดงข้อมูลนักเรียนแบบ Card สำหรับ Sidebar
 */
function renderArchivedStudentsCards() {
    const container = document.getElementById('archivedStudentsList');
    
    if (!archivedStudentsData.students || archivedStudentsData.students.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-search fa-2x mb-3"></i>
                <p>ไม่พบข้อมูลตามเงื่อนไขที่ค้นหา</p>
            </div>
        `;
        return;
    }

    container.innerHTML = archivedStudentsData.students.map(student => {
        const scoreClass = getScoreClass(student.final_score);
        const statusBadgeClass = getStatusBadgeClass(student.status);
        
        // ปรับให้ตรงกับโครงสร้างข้อมูลจาก API (ใช้ field names ที่แท้จริง)
        const studentId = student.students_id || student.id;
        const studentName = student.full_name || student.name;
        const studentCodeView = student.students_student_code || student.student_code || '-';
        const classInfo = `${student.class_level || '-'}/${student.class_room || '-'}`;
        const statusLabel = student.status_label || student.status;
        const finalScore = student.final_score || 100;
        const avatarUrl = student.avatar_url || `https://ui-avatars.com/api/?name=${encodeURIComponent(studentName || 'Student')}&background=95A4D8&color=fff`;
        
        // ตรวจสอบและป้องกัน undefined
        const safeName = studentName || 'ไม่ระบุชื่อ';
        const safeNameForJs = safeName.replace(/'/g, "\\'");
        
        return `
            <div class="student-card" onclick="viewStudentHistory(${studentId}, '${safeNameForJs}')">
                <div class="student-card-header">
                    <img src="${avatarUrl}" class="student-card-avatar" alt="${safeName}" onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(safeName)}&background=95A4D8&color=fff'">
                    <div class="student-card-info">
                        <h6 class="student-card-name">${safeName}</h6>
                        <div class="student-card-meta">
                            <span class="badge bg-primary">${studentCodeView}</span>
                            <span class="badge bg-info">${classInfo}</span>
                            <span class="badge ${statusBadgeClass}">${statusLabel}</span>
                        </div>
                    </div>
                </div>
                <div class="student-card-stats">
                    <span class="text-muted">คะแนนสุดท้าย:</span>
                    <span class="badge ${scoreClass}">${finalScore}/100</span>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * ได้รับ CSS class สำหรับคะแนน
 */
function getScoreClass(score) {
    if (score >= 90) return 'bg-success';
    if (score >= 75) return 'bg-warning';
    if (score >= 50) return 'bg-warning text-dark';
    return 'bg-danger';
}

/**
 * ได้รับ CSS class สำหรับสถานะ
 */
function getStatusBadgeClass(status) {
    switch (status) {
        case 'graduate':
        case 'graduated': 
        case 'จบการศึกษา': 
            return 'bg-success';
        case 'transferred': 
        case 'ย้ายโรงเรียน': 
            return 'bg-info';
        case 'suspended': 
        case 'พักการเรียน': 
            return 'bg-warning';
        case 'expelled': 
        case 'ถูกไล่ออก': 
            return 'bg-danger';
        default: 
            return 'bg-secondary';
    }
}

/**
 * แสดง pagination แบบปรับปรุงสำหรับ Sidebar
 */
function renderPagination() {
    const paginationContainer = document.getElementById('archivedPagination');
    
    if (archivedStudentsData.totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let paginationHTML = '<nav><ul class="pagination pagination-sm justify-content-center">';
    
    // Previous button
    const prevDisabled = archivedStudentsData.currentPage === 1 ? 'disabled' : '';
    paginationHTML += `
        <li class="page-item ${prevDisabled}">
            <a class="page-link" href="#" onclick="loadArchivedStudents(${archivedStudentsData.currentPage - 1})">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;

    // Page numbers (แสดงเฉพาะบางหน้าเพื่อประหยัดพื้นที่)
    const startPage = Math.max(1, archivedStudentsData.currentPage - 1);
    const endPage = Math.min(archivedStudentsData.totalPages, archivedStudentsData.currentPage + 1);

    if (startPage > 1) {
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadArchivedStudents(1)">1</a>
            </li>
        `;
        if (startPage > 2) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        const activeClass = i === archivedStudentsData.currentPage ? 'active' : '';
        paginationHTML += `
            <li class="page-item ${activeClass}">
                <a class="page-link" href="#" onclick="loadArchivedStudents(${i})">${i}</a>
            </li>
        `;
    }

    if (endPage < archivedStudentsData.totalPages) {
        if (endPage < archivedStudentsData.totalPages - 1) {
            paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
        paginationHTML += `
            <li class="page-item">
                <a class="page-link" href="#" onclick="loadArchivedStudents(${archivedStudentsData.totalPages})">${archivedStudentsData.totalPages}</a>
            </li>
        `;
    }

    // Next button
    const nextDisabled = archivedStudentsData.currentPage === archivedStudentsData.totalPages ? 'disabled' : '';
    paginationHTML += `
        <li class="page-item ${nextDisabled}">
            <a class="page-link" href="#" onclick="loadArchivedStudents(${archivedStudentsData.currentPage + 1})">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;

    paginationHTML += '</ul></nav>';
    paginationContainer.innerHTML = paginationHTML;
}

/**
 * ดูประวัติการบันทึกพฤติกรรมของนักเรียน
 */
function viewStudentHistory(studentId, studentName) {
    // ตรวจสอบพารามิเตอร์
    if (!studentId || studentId === 'undefined') {
        console.error('Invalid studentId in viewStudentHistory:', studentId);
        alert('ไม่พบรหัสนักเรียน กรุณาลองใหม่อีกครั้ง');
        return;
    }

    // เปิด sidebar ประวัติ
    openStudentHistorySidebar(studentId, studentName);
}

/**
 * โหลดข้อมูลประวัติการบันทึกพฤติกรรมของนักเรียน
 */
function loadStudentHistory(studentId, studentName) {
    // ตรวจสอบ studentId
    if (!studentId || studentId === 'undefined') {
        console.error('Invalid studentId:', studentId);
        showHistoryError('ไม่พบรหัสนักเรียน');
        return;
    }

    // แสดง loading state
    showHistoryLoading();

    fetch(`${STUDENT_HISTORY_API_BASE}/${studentId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                renderStudentHistory(data.data, studentName);
            } else {
                showHistoryError(data.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูลประวัติ');
            }
        })
        .catch(error => {
            console.error('Error loading student history:', error);
            showHistoryError('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
        })
        .finally(() => {
            hideHistoryLoading();
        });
}

/**
 * แสดง loading state สำหรับประวัติ
 */
function showHistoryLoading() {
    document.getElementById('historyLoading').style.display = 'block';
    document.getElementById('historyContainer').style.display = 'none';
}

/**
 * ซ่อน loading state สำหรับประวัติ
 */
function hideHistoryLoading() {
    document.getElementById('historyLoading').style.display = 'none';
    document.getElementById('historyContainer').style.display = 'block';
}

/**
 * แสดงข้อความผิดพลาดสำหรับประวัติ
 */
function showHistoryError(message) {
    const container = document.getElementById('behaviorHistoryList');
    container.innerHTML = `
        <div class="text-center py-4">
            <div class="text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <p>${message}</p>
                <button class="btn btn-primary btn-sm" onclick="loadStudentHistory()">
                    <i class="fas fa-redo me-1"></i>ลองใหม่
                </button>
            </div>
        </div>
    `;
}

/**
 * แสดงข้อมูลประวัติการบันทึกพฤติกรรม
 */
function renderStudentHistory(data, studentName) {
    const student = data.student;
    const statistics = data.statistics;
    const history = data.history || [];

    // ตรวจสอบข้อมูลนักเรียน
    if (!student) {
        showHistoryError('ไม่พบข้อมูลนักเรียน');
        return;
    }

    // อัปเดตข้อมูลนักเรียน
    const avatarElement = document.getElementById('studentAvatar');
    if (avatarElement) {
        const safeName = student.name || studentName || 'Student';
        avatarElement.src = student.avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(safeName)}&background=95A4D8&color=fff`;
        avatarElement.onerror = function() {
            this.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(safeName)}&background=95A4D8&color=fff`;
        };
    }
    
    const nameElement = document.getElementById('studentName');
    if (nameElement) nameElement.textContent = student.name || studentName || 'ไม่ระบุชื่อ';
    
    // อัปเดตรหัสนักเรียน (ข้อความเฉพาะ ไม่มี HTML)
    const codeElement = document.getElementById('studentCodeView');
    if (codeElement) {
        codeElement.textContent = student.code || '-';
    }
    
    // อัปเดตชั้นเรียน (ข้อความเฉพาะ ไม่มี HTML)
    const classElement = document.getElementById('studentClassView');
    if (classElement) {
        classElement.textContent = student.class || '-';
    }
    
    // อัปเดตสถานะ
    const statusBadge = document.getElementById('studentStatus');
    if (statusBadge) {
        const statusClass = getStatusBadgeClass(student.status);
        statusBadge.textContent = student.status || '-';
        statusBadge.className = `badge ${statusClass}`;
    }
    
    // อัปเดตคะแนน
    const scoreBadge = document.getElementById('studentScore');
    if (scoreBadge) {
        const scoreClass = getScoreClass(student.final_score || 0);
        scoreBadge.textContent = `${student.final_score || 0}/100`;
        scoreBadge.className = `badge ${scoreClass}`;
    }

    // แสดงสถิติ
    if (statistics) {
        const totalViolationsElement = document.getElementById('totalViolations');
        if (totalViolationsElement) totalViolationsElement.textContent = statistics.total_violations || 0;
        
        const totalScoreDeductedElement = document.getElementById('totalScoreDeducted');
        if (totalScoreDeductedElement) totalScoreDeductedElement.textContent = statistics.total_score_deducted || 0;
        
        const averageScoreElement = document.getElementById('averageScore');
        if (averageScoreElement) averageScoreElement.textContent = statistics.average_score_per_year || 0;
    }

    // แสดงรายการประวัติ
    renderBehaviorHistory(history);
}

/**
 * แสดงรายการประวัติการบันทึกพฤติกรรม
 */
function renderBehaviorHistory(history) {
    const container = document.getElementById('behaviorHistoryList');
    
    if (!history || history.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4 text-muted">
                <i class="fas fa-history fa-2x mb-3"></i>
                <p>ไม่มีประวัติการบันทึกพฤติกรรม</p>
            </div>
        `;
        return;
    }

    container.innerHTML = history.map(record => {
        // ตัดสินประเภทจากคะแนนที่หัก (ถ้าเป็นบวกหรือศูนย์ = positive, ถ้าเป็นลบ = negative)
        const typeClass = (record.points_deducted || 0) <= 0 ? 'positive' : 'negative';
        
        return `
            <div class="history-item ${typeClass}">
                <div class="history-header">
                    <div class="history-date">${record.date || '-'}</div>
                    <div class="history-points">
                        ${(record.points_deducted || 0) > 0 ? '-' : '+'}${Math.abs(record.points_deducted || 0)} คะแนน
                    </div>
                </div>
                <div class="history-violation">${record.violation_name || '-'}</div>
                ${record.description ? `<div class="history-description">${record.description}</div>` : ''}
                <div class="history-footer">
                    <div class="history-teacher">บันทึกโดย: ${record.teacher_name || '-'}</div>
                    <div class="history-year">ปีการศึกษา ${record.academic_year || '-'}</div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * ส่งออกข้อมูลเป็นไฟล์ CSV
 */
function exportArchivedData() {
    if (!archivedStudentsData.students || archivedStudentsData.students.length === 0) {
        alert('ไม่มีข้อมูลให้ส่งออก');
        return;
    }

    // สร้างหัวข้อ CSV
    const headers = ['รหัสนักเรียน', 'ชื่อ-นามสกุล', 'ชั้น', 'คะแนนสุดท้าย', 'สถานะ'];
    
    // สร้างข้อมูล CSV
    const csvContent = [
        headers.join(','),
        ...archivedStudentsData.students.map(student => [
            `"${student.students_student_code || student.student_code || '-'}"`,
            `"${student.full_name || student.name || '-'}"`,
            `"${student.class_level || '-'}/${student.class_room || '-'}"`,
            student.final_score || 0,
            `"${student.status_label || student.status || '-'}"`
        ].join(','))
    ].join('\n');

    // ดาวน์โหลดไฟล์
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = `archived_students_${new Date().toISOString().split('T')[0]}.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// เพิ่ม Event Listeners เมื่อหน้าโหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    // ปิด sidebar เมื่อคลิกที่ overlay (ตรวจสอบ element ก่อน)
    const archivedSidebar = document.getElementById('archivedStudentsSidebar');
    if (archivedSidebar) {
        archivedSidebar.addEventListener('click', function(e) {
            if (e.target === this) closeArchivedStudentsSidebar();
        });
    }
    const historySidebar = document.getElementById('studentHistorySidebar');
    if (historySidebar) {
        historySidebar.addEventListener('click', function(e) {
            if (e.target === this) closeStudentHistorySidebar();
        });
    }
    
    // ป้องกันการปิด sidebar เมื่อคลิกใน content
    document.querySelectorAll('.sidebar-content').forEach(content => {
        content.addEventListener('click', function(e) { e.stopPropagation(); });
    });
    
    // ปิด sidebar เมื่อกด ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const archivedEl = document.getElementById('archivedStudentsSidebar');
            const historyEl = document.getElementById('studentHistorySidebar');
            if (historyEl && historyEl.classList.contains('show')) {
                closeStudentHistorySidebar();
            } else if (archivedEl && archivedEl.classList.contains('show')) {
                closeArchivedStudentsSidebar();
            }
        }
    });
    
    // เพิ่ม event listener สำหรับ search input
    const searchInput = document.getElementById('archivedSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchArchivedStudents();
        });
    }
});
