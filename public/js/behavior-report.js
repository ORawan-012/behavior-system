/**
 * JavaScript สำหรับจัดการการบันทึกพฤติกรรม
 */

// ตัวแปรสำหรับเก็บข้อมูล
const behaviorReport = {
    selectedStudent: null,
    violations: [],
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
};

// เริ่มต้นระบบเมื่อ DOM โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function () {
    initializeBehaviorReport();
});

/**
 * เริ่มต้นระบบบันทึกพฤติกรรม
 */
function initializeBehaviorReport() {
    setupEventListeners();
    loadViolationTypes();
    loadClassroomOptions();
    setDefaultDateTime();
    loadRecentReports();
}

/**
 * ตั้งค่า Event Listeners
 */
function setupEventListeners() {
    const studentSearch = document.getElementById('behaviorStudentSearch');
    const classFilter = document.getElementById('classFilter');
    const violationType = document.getElementById('violationType');
    const saveBtn = document.getElementById('saveViolationBtn');

    // ค้นหานักเรียน
    if (studentSearch) {
        let searchTimeout;

        // เมื่อพิมพ์ในช่องค้นหา
        studentSearch.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            const searchValue = this.value.trim();

            // ค้นหาทันทีถ้ามีข้อความ 1 ตัวอักษรขึ้นไป
            if (searchValue.length >= 1) {
                searchTimeout = setTimeout(() => {
                    searchStudents(searchValue);
                }, 300);
            } else {
                hideStudentResults();
            }
        });

        // เมื่อกด Enter
        studentSearch.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const searchValue = this.value.trim();
                if (searchValue.length >= 1) {
                    searchStudents(searchValue);
                }
            }
        });

        // Focus ช่องค้นหาเมื่อเปิด modal
        const modal = document.getElementById('newViolationModal');
        if (modal) {
            modal.addEventListener('shown.bs.modal', function () {
                studentSearch.focus();
            });
        }
    }


    // กรองตามห้อง
    if (classFilter) {
        classFilter.addEventListener('change', function () {
            const searchInput = document.getElementById('behaviorStudentSearch');
            const selectedClassId = this.value;

            // ถ้าเลือกห้อง ให้แสดงนักเรียนในห้องนั้นทันที
            if (selectedClassId) {
                // ค้นหาด้วยคำว่า "" (ค้นหาทั้งหมด) แต่กรองตามห้อง
                searchStudents('');
            } else if (searchInput && searchInput.value.length >= 2) {
                // ถ้าไม่เลือกห้อง แต่มีการพิมพ์ค้นหา ให้ค้นหาตามปกติ
                searchStudents(searchInput.value);
            } else {
                // ถ้าไม่เลือกห้อง และไม่มีการพิมพ์ค้นหา ให้ซ่อนผลลัพธ์
                hideStudentResults();
            }
        });
    }

    // เปลี่ยนประเภทพฤติกรรม
    if (violationType) {
        violationType.addEventListener('change', function () {
            updatePointsDeducted(this.value);
        });
    }

    // บันทึกข้อมูล (ใช้เฉพาะไฟล์นี้เท่านั้น - ไม่ให้ teacher-dashboard.js ทำงานร่วม)
    if (saveBtn && !saveBtn.hasAttribute('data-listener-attached')) {
        // ลบ event listener อื่น ๆ ที่อาจมี (จาก teacher-dashboard.js)
        const newBtn = saveBtn.cloneNode(true);
        saveBtn.parentNode.replaceChild(newBtn, saveBtn);

        newBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            saveBehaviorReport();
        });
        newBtn.setAttribute('data-listener-attached', 'behavior-report');
    }

    // รีเซ็ตฟอร์มเมื่อปิด modal
    const modal = document.getElementById('newViolationModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function () {
            resetForm();
        });
    }
}

/**
 * ซ่อนผลการค้นหานักเรียน
 */
function hideStudentResults() {
    const resultsContainer = document.getElementById('studentResults');
    if (resultsContainer) {
        resultsContainer.style.display = 'none';
    }
}

/**
 * ค้นหานักเรียน
 */
function searchStudents(searchTerm) {
    const classFilter = document.getElementById('classFilter');
    const classId = classFilter ? classFilter.value : '';
    const resultsContainer = document.getElementById('studentResults');

    if (!resultsContainer) return;

    // แปลง searchTerm ให้เป็น string เสมอ
    const searchQuery = (searchTerm || '').trim();

    // ถ้าไม่มีคำค้นหาและไม่มีการเลือกห้อง ให้ซ่อนผลลัพธ์
    if (searchQuery.length < 1 && !classId) {
        resultsContainer.style.display = 'none';
        return;
    }

    // แสดง loading
    resultsContainer.innerHTML = '<div class="list-group-item"><i class="fas fa-spinner fa-spin me-2"></i>กำลังค้นหา...</div>';
    resultsContainer.style.display = 'block';

    // เพิ่ม CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    fetch(`/api/behavior-reports/students/search?term=${encodeURIComponent(searchQuery)}&class_id=${classId}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data) {
                displayStudentResults(data.data);
            } else {
                resultsContainer.innerHTML = '<div class="list-group-item text-muted"><i class="fas fa-info-circle me-2"></i>ไม่พบนักเรียนที่ตรงกับคำค้นหา</div>';
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            resultsContainer.innerHTML = '<div class="list-group-item text-danger"><i class="fas fa-exclamation-circle me-2"></i>เกิดข้อผิดพลาดในการค้นหา กรุณาลองใหม่อีกครั้ง</div>';
        });
}

/**
 * แสดงผลการค้นหานักเรียน
 */
function displayStudentResults(students) {
    const resultsContainer = document.getElementById('studentResults');

    if (!resultsContainer) return;

    if (students.length === 0) {
        resultsContainer.innerHTML = '<div class="list-group-item text-muted">ไม่พบนักเรียนที่ตรงกับคำค้นหา</div>';
        return;
    }

    resultsContainer.innerHTML = '';

    students.forEach(student => {
        const item = document.createElement('div');
        item.className = 'list-group-item list-group-item-action';
        item.style.cursor = 'pointer';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">${student.name}</h6>
                    <small class="text-muted">รหัส: ${student.student_id} | ห้อง: ${student.class}</small>
                </div>
                <div class="text-end">
                    <span class="badge bg-primary">${student.current_score} คะแนน</span>
                </div>
            </div>
        `;

        item.addEventListener('click', function () {
            selectStudent(student);
        });

        resultsContainer.appendChild(item);
    });
}

/**
 * เลือกนักเรียน
 */
function selectStudent(student) {
    behaviorReport.selectedStudent = student;

    // ซ่อนผลการค้นหา
    hideStudentResults();

    // แสดงข้อมูลนักเรียนที่เลือก
    const selectedInfo = document.getElementById('selectedStudentInfo');
    const infoDisplay = document.getElementById('studentInfoDisplay');

    if (selectedInfo && infoDisplay) {
        infoDisplay.innerHTML = `
            <strong>${student.name}</strong> 
            (รหัส: ${student.student_id}) 
            ห้อง: ${student.class} 
            คะแนนปัจจุบัน: <span class="badge bg-primary">${student.current_score}</span>
        `;

        selectedInfo.style.display = 'block';
    }

    // ตั้งค่า hidden input
    const selectedStudentId = document.getElementById('selectedStudentId');
    if (selectedStudentId) {
        selectedStudentId.value = student.id;
    }

    // เคลียร์ช่องค้นหา
    const searchInput = document.getElementById('behaviorStudentSearch');
    if (searchInput) {
        searchInput.value = student.name;
    }
}

/**
 * โหลดตัวเลือกห้องเรียนสำหรับกรอง
 */
function loadClassroomOptions() {
    const classFilter = document.getElementById('classFilter');

    if (!classFilter) return;

    // ดึงข้อมูลห้องเรียนจาก API (เฉพาะห้องที่มีนักเรียน)
    fetch('/api/classes/all', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const classrooms = Array.isArray(data.data) ? data.data :
                    (data.data && Array.isArray(data.data.data) ? data.data.data : []);

                // เคลียร์ตัวเลือกเดิม (เว้น option แรก "ทุกห้อง")
                while (classFilter.children.length > 1) {
                    classFilter.removeChild(classFilter.lastChild);
                }

                // เพิ่มตัวเลือกห้องเรียน
                classrooms.forEach(classroom => {
                    const option = document.createElement('option');
                    option.value = classroom.classes_id;
                    // แสดงในรูปแบบ "ม.1/1" หรือ "ป.6/2"
                    option.textContent = `${classroom.classes_level}/${classroom.classes_room_number}`;
                    classFilter.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error loading classrooms:', error.message);
            // ไม่แสดง error ให้ผู้ใช้เห็น เพราะไม่ critical
        });
}

/**
 * โหลดประเภทพฤติกรรม
 */
function loadViolationTypes() {
    fetch('/api/violations/all')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // ตรวจสอบโครงสร้างข้อมูลที่ได้รับ
            let violationsArray = [];

            if (data.success && Array.isArray(data.data)) {
                violationsArray = data.data;
            } else if (Array.isArray(data)) {
                violationsArray = data;
            } else if (data.violations && Array.isArray(data.violations)) {
                violationsArray = data.violations;
            } else {
                throw new Error('ข้อมูลประเภทพฤติกรรมไม่ถูกต้อง');
            }

            // กรองซ้ำ (กันกรณี API หรือสคริปต์อื่นเพิ่มมาซ้ำ) โดยใช้ key = name(lower) + id
            const uniqueMap = new Map();
            violationsArray.forEach(v => {
                if (!v) return;
                const nameKey = (v.violations_name || '').trim().toLowerCase();
                // ถ้าชื่อเคยมีแล้ว ข้าม (เก็บตัวแรกไว้)
                if (!uniqueMap.has(nameKey)) {
                    uniqueMap.set(nameKey, v);
                }
            });
            const uniqueViolations = Array.from(uniqueMap.values());
            behaviorReport.violations = uniqueViolations;
            updateViolationSelect(uniqueViolations);
            // รอ microtask เผื่อมีสคริปต์อื่น inject เสร็จแล้วจึง dedup อีกครั้ง
            queueMicrotask(() => deduplicateViolationSelect());
        })
        .catch(error => {
            console.error('Error loading violations:', error.message);
            showError('ไม่สามารถโหลดประเภทพฤติกรรมได้');
        });
}

/**
 * อัปเดต select ประเภทพฤติกรรม
 */
function updateViolationSelect(violations) {
    const select = document.getElementById('violationType');

    if (!select) return;
    // ถ้ามี optgroup หรือ option จากสคริปต์อื่น ให้เคลียร์ทั้งหมด (ยกเว้น placeholder ตัวแรกถ้ามี)
    const placeholderText = select.options.length ? select.options[0].textContent : 'เลือกประเภทพฤติกรรม';
    select.innerHTML = `<option value="">${placeholderText}</option>`;

    if (!Array.isArray(violations)) {
        console.error('violations ต้องเป็น array');
        return;
    }

    const frag = document.createDocumentFragment();
    const addedNames = new Set();
    violations.forEach(violation => {
        if (!violation) return;
        const name = (violation.violations_name || '').trim();
        const nameKey = name.toLowerCase();
        if (addedNames.has(nameKey)) return; // กันซ้ำอีกชั้น
        addedNames.add(nameKey);
        const option = document.createElement('option');
        option.value = violation.violations_id;
        option.textContent = name;
        option.dataset.points = violation.violations_points_deducted;
        frag.appendChild(option);
    });
    select.appendChild(frag);
}

/**
 * ลบ option ที่ซ้ำกันใน select (กันกรณีสคริปต์อื่นเคยเพิ่มไว้ก่อนหน้า)
 */
function deduplicateViolationSelect() {
    const select = document.getElementById('violationType');
    if (!select) return;
    const seenName = new Set();
    const seenValue = new Set();
    for (let i = 1; i < select.options.length; i++) {
        const opt = select.options[i];
        const nameKey = opt.textContent.trim().toLowerCase();
        const valueKey = opt.value;
        if (seenName.has(nameKey) || (valueKey && seenValue.has(valueKey))) {
            select.remove(i);
            i--;
        } else {
            seenName.add(nameKey);
            if (valueKey) seenValue.add(valueKey);
        }
    }
}

// เพิ่มการ deduplicate เมื่อโฟกัสหรือคลิก select (กันกรณี script อื่นเพิ่มหลังโหลด)
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('violationType');
    if (!select) return;
    ['focus', 'click'].forEach(evt => {
        select.addEventListener(evt, () => {
            // ใช้ requestAnimationFrame เพื่อให้ option ใหม่ (ถ้ามี) ถูกเพิ่มก่อน
            requestAnimationFrame(() => deduplicateViolationSelect());
        });
    });
});

/**
 * อัปเดตคะแนนที่หัก
 */
function updatePointsDeducted(violationId) {
    const pointsInput = document.getElementById('pointsDeducted');
    const select = document.getElementById('violationType');

    if (!pointsInput || !select) return;

    const selectedOption = select.options[select.selectedIndex];

    if (selectedOption && selectedOption.dataset.points) {
        pointsInput.value = selectedOption.dataset.points;
    } else {
        pointsInput.value = 0;
    }
}

/**
 * ตั้งค่าวันที่และเวลาเริ่มต้น
 */
function setDefaultDateTime() {
    const now = new Date();
    const dateInput = document.getElementById('violationDate');
    const timeInput = document.getElementById('violationTime');

    if (dateInput) {
        dateInput.value = now.toISOString().split('T')[0];
        dateInput.max = now.toISOString().split('T')[0];
    }

    if (timeInput) {
        timeInput.value = now.toTimeString().slice(0, 5);
    }
}

// Global variable เพื่อป้องกันการบันทึกซ้ำ
let isSubmitting = false;

/**
 * บันทึกรายงานพฤติกรรม
 */
function saveBehaviorReport() {
    if (!validateForm()) {
        return;
    }

    // ป้องกันการกดซ้ำขณะกำลังประมวลผล
    if (isSubmitting) {
        return;
    }

    const saveBtn = document.getElementById('saveViolationBtn');
    if (!saveBtn) return;

    // ป้องกันการกดซ้ำขณะกำลังประมวลผล
    if (saveBtn.disabled) {
        return;
    }

    isSubmitting = true;
    const originalText = saveBtn.innerHTML;

    // แสดง loading
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> กำลังบันทึก...';

    // เตรียมข้อมูล
    const formData = new FormData();
    formData.append('student_id', document.getElementById('selectedStudentId')?.value || '');
    formData.append('violation_id', document.getElementById('violationType')?.value || '');
    formData.append('violation_date', document.getElementById('violationDate')?.value || '');
    formData.append('violation_time', document.getElementById('violationTime')?.value || '');
    formData.append('description', document.getElementById('violationDescription')?.value || '');

    // รวมวันที่และเวลา
    const date = document.getElementById('violationDate')?.value;
    const time = document.getElementById('violationTime')?.value;
    if (date && time) {
        formData.append('violation_datetime', `${date} ${time}`);
    }

    // แนบไฟล์หลักฐาน
    const evidenceFile = document.getElementById('evidenceFile')?.files[0];
    if (evidenceFile) {
        formData.append('evidence', evidenceFile);
    }

    // ส่งข้อมูล
    fetch('/api/behavior-reports', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': behaviorReport.csrfToken,
            'Accept': 'application/json'
        },
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccess('บันทึกพฤติกรรมเรียบร้อยแล้ว');
                resetForm();
                loadRecentReports();
                // ปิด modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('newViolationModal'));
                if (modal) {
                    modal.hide();
                }
            } else {
                showError(data.message || 'เกิดข้อผิดพลาดในการบันทึก');
            }
        })
        .catch(error => {
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
        })
        .finally(() => {
            // คืนสถานะปุ่ม
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
            isSubmitting = false; // รีเซ็ต flag
        });
}

/**
 * ตรวจสอบความถูกต้องของฟอร์ม
 */
function validateForm() {
    let isValid = true;
    const errors = [];

    // ตรวจสอบการเลือกนักเรียน
    if (!document.getElementById('selectedStudentId')?.value) {
        errors.push('กรุณาเลือกนักเรียน');
        isValid = false;
    }

    // ตรวจสอบประเภทพฤติกรรม
    if (!document.getElementById('violationType')?.value) {
        errors.push('กรุณาเลือกประเภทพฤติกรรม');
        isValid = false;
    }

    // ตรวจสอบวันที่
    if (!document.getElementById('violationDate')?.value) {
        errors.push('กรุณาระบุวันที่เกิดเหตุการณ์');
        isValid = false;
    }

    // ตรวจสอบเวลา
    if (!document.getElementById('violationTime')?.value) {
        errors.push('กรุณาระบุเวลาที่เกิดเหตุการณ์');
        isValid = false;
    }

    if (!isValid) {
        showError(errors.join('<br>'));
    }

    return isValid;
}

/**
 * รีเซ็ตฟอร์ม
 */
function resetForm() {
    const form = document.getElementById('violationForm');
    if (form) {
        form.reset();
    }

    const selectedStudentId = document.getElementById('selectedStudentId');
    if (selectedStudentId) {
        selectedStudentId.value = '';
    }

    const pointsDeducted = document.getElementById('pointsDeducted');
    if (pointsDeducted) {
        pointsDeducted.value = '0';
    }

    const selectedStudentInfo = document.getElementById('selectedStudentInfo');
    if (selectedStudentInfo) {
        selectedStudentInfo.style.display = 'none';
    }

    hideStudentResults();
    behaviorReport.selectedStudent = null;
    setDefaultDateTime();
}

/**
 * โหลดรายงานล่าสุด
 */
function loadRecentReports() {
    const tableBody = document.getElementById('recentViolationsTable');

    if (!tableBody) return;

    // แสดง loading
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-4">
                <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
                <p class="text-muted">กำลังโหลดข้อมูล...</p>
            </td>
        </tr>
    `;

    fetch('/api/behavior-reports/recent?limit=10')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayRecentReports(data.data);
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-4 text-danger">
                            เกิดข้อผิดพลาดในการโหลดข้อมูล
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-danger">
                        เกิดข้อผิดพลาดในการเชื่อมต่อ
                    </td>
                </tr>
            `;
        });
}

/**
 * แสดงรายงานล่าสุด
 */
function displayRecentReports(reports) {
    const tableBody = document.getElementById('recentViolationsTable');

    if (!tableBody) return;

    if (reports.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4">
                    <div class="text-muted">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <p>ไม่มีข้อมูลการบันทึกพฤติกรรม</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tableBody.innerHTML = '';

    reports.forEach(report => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${report.student_name}</td>
            <td>
                <span class="badge bg-danger">${report.violation_name}</span>
            </td>
            <td>${report.points_deducted} คะแนน</td>
            <td>${report.created_at}</td>
            <td>${report.teacher_name}</td>
            <td>
                <button class="btn btn-sm btn-primary-app view-violation-btn" data-id="${report.id}" onclick="showViolationDetail(${report.id})">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-sm btn-outline-primary edit-violation-btn" data-id="${report.id}" onclick="openEditViolationSidebar(${report.id})" title="แก้ไขรายงาน">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
}

/**
 * แสดงรายละเอียดการกระทำผิด
 */
function showViolationDetail(reportId) {
    // แสดง modal
    const modal = new bootstrap.Modal(document.getElementById('violationDetailModal'));
    modal.show();

    // แสดง loading state
    showViolationDetailLoading();

    // ดึงข้อมูล
    fetch(`/api/behavior-reports/${reportId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayViolationDetail(data.data);
            } else {
                showViolationDetailError(data.message || 'ไม่พบข้อมูลรายงาน');
            }
        })
        .catch(error => {
            showViolationDetailError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        });
}

/**
 * แสดง loading state ของ modal
 */
function showViolationDetailLoading() {
    document.getElementById('violationDetailLoading').style.display = 'block';
    document.getElementById('violationDetailData').style.display = 'none';
    document.getElementById('violationDetailError').style.display = 'none';
    document.getElementById('deleteReportBtn').style.display = 'none';
    document.getElementById('editReportBtn').style.display = 'none';
}

/**
 * แสดง error state ของ modal
 */
function showViolationDetailError(message) {
    document.getElementById('violationDetailLoading').style.display = 'none';
    document.getElementById('violationDetailData').style.display = 'none';
    document.getElementById('violationDetailError').style.display = 'block';
    document.getElementById('violationDetailError').innerHTML = `
        <i class="fas fa-exclamation-circle fa-2x mb-3"></i>
        <p>${message}</p>
    `;
    document.getElementById('deleteReportBtn').style.display = 'none';
    document.getElementById('editReportBtn').style.display = 'none';
}

/**
 * แสดงรายละเอียดการกระทำผิด
 */
function displayViolationDetail(data) {
    // ซ่อน loading และ error
    document.getElementById('violationDetailLoading').style.display = 'none';
    document.getElementById('violationDetailError').style.display = 'none';

    // แสดงข้อมูล
    document.getElementById('violationDetailData').style.display = 'block';
    document.getElementById('deleteReportBtn').style.display = 'inline-block';
    document.getElementById('editReportBtn').style.display = 'inline-block';

    // กำหนดสีของ badge ตามประเภท
    let badgeClass = 'bg-danger';
    switch (data.violation.category) {
        case 'light':
            badgeClass = 'bg-warning';
            break;
        case 'medium':
            badgeClass = 'bg-orange';
            break;
        case 'severe':
            badgeClass = 'bg-danger';
            break;
    }

    // แสดงข้อมูลนักเรียน
    document.getElementById('studentInfo').innerHTML = `
        <img src="${data.student.avatar_url}" class="rounded-circle me-3" width="50" height="50" alt="รูปประจำตัว">
        <div>
            <h5 class="mb-1">${data.student.name}</h5>
            <p class="mb-0 text-muted">รหัสนักเรียน: ${data.student.student_code} | ชั้น ${data.student.class}</p>
        </div>
    `;

    // แสดงรายละเอียดการกระทำผิด
    const evidenceHtml = data.report.evidence_url
        ? `<div class="mb-3">
               <label class="text-muted d-block">รูปภาพหลักฐาน</label>
               <img src="${data.report.evidence_url}" class="img-fluid rounded" alt="รูปภาพหลักฐาน" style="max-height: 300px;">
           </div>`
        : `<div class="mb-3">
               <label class="text-muted d-block">รูปภาพหลักฐาน</label>
               <p class="text-muted">ไม่มีรูปภาพหลักฐาน</p>
           </div>`;

    document.getElementById('violationInfo').innerHTML = `
        <div class="mb-3">
            <label class="text-muted d-block">ประเภทการกระทำผิด</label>
            <span class="badge ${badgeClass}">${data.violation.name}</span>
        </div>
        <div class="mb-3">
            <label class="text-muted d-block">วันและเวลา</label>
            <p class="mb-0">${data.report.report_date_thai}</p>
        </div>
        <div class="mb-3">
            <label class="text-muted d-block">คะแนนที่หัก</label>
            <p class="mb-0">${data.violation.points_deducted} คะแนน</p>
        </div>
        <div class="mb-3">
            <label class="text-muted d-block">บันทึกโดย</label>
            <p class="mb-0">${data.teacher.name}</p>
        </div>
        <div class="mb-3">
            <label class="text-muted d-block">รายละเอียด</label>
            <p class="mb-0">${data.report.description || 'ไม่มีรายละเอียดเพิ่มเติม'}</p>
        </div>
        ${evidenceHtml}
    `;

    // ตั้งค่าปุ่มลบและแก้ไข
    document.getElementById('deleteReportBtn').onclick = function () {
        deleteViolationReport(data.id);
    };

    document.getElementById('editReportBtn').onclick = function () {
        editViolationReport(data.id);
    };
}

/**
 * ลบรายงานพฤติกรรม
 */
function deleteViolationReport(reportId) {
    if (confirm('คุณต้องการลบรายงานพฤติกรรมนี้หรือไม่?\n\nข้อมูลที่ลบแล้วจะไม่สามารถกู้คืนได้')) {
        // แสดง loading state
        const deleteBtn = document.getElementById('deleteReportBtn');
        const originalText = deleteBtn.innerHTML;
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังลบ...';

        fetch(`/api/behavior-reports/${reportId}/delete`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': behaviorReport.csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
            .then(response => {
                // ตรวจสอบสิทธิ์การเข้าถึง
                if (response.status === 403) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'คุณไม่มีสิทธิ์ลบรายงานนี้');
                    });
                }
                if (!response.ok) {
                    throw new Error('เกิดข้อผิดพลาดในการลบรายงาน');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // แสดงข้อความสำเร็จด้วย SweetAlert ถ้ามี
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: data.message || 'ลบรายงานพฤติกรรมเรียบร้อยแล้ว',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        showSuccess(data.message || 'ลบรายงานพฤติกรรมเรียบร้อยแล้ว');
                    }

                    // ปิด modal และรีเฟรชรายการ
                    const modal = bootstrap.Modal.getInstance(document.getElementById('violationDetailModal'));
                    if (modal) {
                        modal.hide();
                    }

                    // รีเฟรชรายการ
                    loadRecentReports();
                } else {
                    throw new Error(data.message || 'เกิดข้อผิดพลาดในการลบรายงาน');
                }
            })
            .catch(error => {
                // แสดงข้อความ error ด้วย SweetAlert ถ้ามี
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'ไม่สามารถลบได้',
                        text: error.message,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    showError(error.message);
                }
            })
            .finally(() => {
                // คืนสถานะปุ่ม
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalText;
            });
    }
}

/**
 * แก้ไขรายงานพฤติกรรม
 */
function editViolationReport(reportId) {
    // ปิด modal รายละเอียด
    const detailModal = bootstrap.Modal.getInstance(document.getElementById('violationDetailModal'));
    if (detailModal) {
        detailModal.hide();
    }

    // เปิด sidebar และโหลดข้อมูล
    openEditViolationSidebar(reportId);
}

/**
 * ฟังก์ชันสำหรับโหลดข้อมูลนักเรียนเพื่อแสดงใน Student Detail Modal
 */
function loadStudentDetails(studentId) {
    if (!studentId) {
        showStudentDetailError('ไม่พบรหัสนักเรียน');
        return;
    }

    // ตั้งค่า data-student-id ให้กับ modal (เพิ่มบรรทัดนี้)
    document.getElementById('studentDetailModal').setAttribute('data-student-id', studentId);

    showStudentDetailLoading();

    fetch(`/api/students/${studentId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        credentials: 'same-origin'
    })
        .then(response => {
            if (!response.ok) {
                if (response.status === 404) {
                    throw new Error('ไม่พบข้อมูลนักเรียนที่ระบุ');
                } else if (response.status === 403) {
                    throw new Error('คุณไม่มีสิทธิ์เข้าถึงข้อมูลนี้');
                } else if (response.status === 500) {
                    throw new Error('เกิดข้อผิดพลาดภายในเซิร์ฟเวอร์');
                } else {
                    throw new Error(`เกิดข้อผิดพลาด (HTTP ${response.status})`);
                }
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('เซิร์ฟเวอร์ส่งข้อมูลกลับมาในรูปแบบที่ไม่ถูกต้อง');
            }

            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.message || 'ไม่สามารถดึงข้อมูลนักเรียนได้');
            }

            if (!data.student) {
                throw new Error('ข้อมูลนักเรียนไม่สมบูรณ์');
            }

            populateStudentDetailModal(data.student);
        })
        .catch(error => {
            showStudentDetailError(error.message);
        });
}

/**
 * แสดงสถานะ Loading
 */
function showStudentDetailLoading() {
    const modal = document.getElementById('studentDetailModal');
    const modalBody = modal.querySelector('.modal-body');

    modalBody.innerHTML = `
        <div class="text-center py-5" id="student-detail-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">กำลังโหลด...</span>
            </div>
            <p class="mt-2 text-muted">กำลังโหลดข้อมูลนักเรียน...</p>
        </div>
    `;
}

/**
 * แสดงข้อผิดพลาด
 */
function showStudentDetailError(message) {
    const modal = document.getElementById('studentDetailModal');
    const modalBody = modal.querySelector('.modal-body');

    modalBody.innerHTML = `
        <div class="text-center py-5 text-danger">
            <i class="fas fa-exclamation-circle fa-3x mb-3"></i>
            <h5>เกิดข้อผิดพลาด</h5>
            <p>${message}</p>
            <button class="btn btn-secondary mt-3" onclick="closeStudentDetailModal()">ปิด</button>
        </div>
    `;
}

/**
 * เติมข้อมูลนักเรียนลงในโมดัล
 */
function populateStudentDetailModal(student) {
    const modal = document.getElementById('studentDetailModal');
    const modalBody = modal.querySelector('.modal-body');

    const fullName = `${student.user.users_name_prefix}${student.user.users_first_name} ${student.user.users_last_name}`;

    const avatarUrl = student.user.users_profile_image
        ? `/storage/${student.user.users_profile_image}`
        : `https://ui-avatars.com/api/?name=${encodeURIComponent(student.user.users_first_name)}&background=95A4D8&color=fff`;

    const classroomText = student.classroom
        ? `${student.classroom.classes_level}/${student.classroom.classes_room_number}`
        : 'ไม่มีห้องเรียน';

    const guardianName = student.guardian && student.guardian.user
        ? `${student.guardian.user.users_name_prefix}${student.guardian.user.users_first_name} ${student.guardian.user.users_last_name}`
        : 'ไม่มีข้อมูล';

    const guardianPhone = student.guardian?.guardians_phone || '-';

    const birthDate = student.user.users_birthdate
        ? new Date(student.user.users_birthdate).toLocaleDateString('th-TH', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        })
        : '-';

    const score = student.students_current_score || 100;
    let progressClass = 'bg-success';
    let emojiSrc = 'https://cdn.jsdelivr.net/npm/emoji-datasource-apple@7.0.2/img/apple/64/1f60a.png'; // 😊

    if (score <= 50) {
        progressClass = 'bg-danger';
        emojiSrc = 'https://cdn.jsdelivr.net/npm/emoji-datasource-apple@7.0.2/img/apple/64/1f622.png'; // 😢
    } else if (score <= 75) {
        progressClass = 'bg-warning';
        emojiSrc = 'https://cdn.jsdelivr.net/npm/emoji-datasource-apple@7.0.2/img/apple/64/1f610.png'; // 😐
    }

    // สร้างตารางประวัติการกระทำผิด
    let violationsTableRows = '';
    if (student.behavior_reports && student.behavior_reports.length > 0) {
        violationsTableRows = student.behavior_reports.map(report => {
            let badgeClass = 'bg-info';
            if (report.violation.violations_category === 'severe') {
                badgeClass = 'bg-danger';
            } else if (report.violation.violations_category === 'medium') {
                badgeClass = 'bg-warning text-dark';
            }

            const reportDate = new Date(report.reports_report_date).toLocaleDateString('th-TH', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });

            return `
                <tr>
                    <td>${reportDate}</td>
                    <td><span class="badge ${badgeClass}">${report.violation.violations_name}</span></td>
                    <td>${report.violation.violations_points_deducted}</td>
                    <td>${report.teacher.user.users_first_name}</td>
                </tr>
            `;
        }).join('');
    } else {
        violationsTableRows = `
            <tr>
                <td colspan="4" class="text-center text-muted py-3">ไม่พบประวัติการกระทำผิด</td>
            </tr>
        `;
    }

    modalBody.innerHTML = `
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="text-center">
                    <img src="${avatarUrl}" class="rounded-circle" width="100" height="100">
                    <h5 class="mt-3 mb-1">${fullName}</h5>
                    <span class="badge bg-primary-app">${classroomText}</span>
                    <hr>
                    <div class="d-grid gap-2 mt-3">
                        <button class="btn btn-primary-app" onclick="openBehaviorRecordModal(${student.students_id}, '${fullName}', '${classroomText}')">
                            บันทึกพฤติกรรม
                        </button>
                        <button class="btn btn-outline-secondary" onclick="printStudentReport(event)" data-student-id="${student.students_id}">พิมพ์รายงาน</button>
                        <button class="btn btn-warning" onclick="slideToPasswordReset(${student.students_id}, '${fullName}')" id="resetPasswordBtn-${student.students_id}">
                            <i class="fas fa-key me-1"></i> รีเซ็ตรหัสผ่าน
                        </button>
                        ${guardianPhone !== '-' ?
            `<button class="btn ${score < 40 ? 'btn-danger' : 'btn-outline-warning'}" id="notifyParentBtn"
                                    onclick="openParentNotificationModal(${student.students_id}, '${fullName}', '${classroomText}', ${score}, '${guardianPhone}')">
                                <i class="fas fa-bell me-1"></i> แจ้งเตือนผู้ปกครอง
                                ${score < 40 ? '<span class="badge bg-white text-danger ms-1">!</span>' : ''}
                            </button>`
            : ''
        }
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">รหัสนักเรียน</label>
                        <p>${student.students_student_code || '-'}</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">ชั้นเรียน</label>
                        <p>${classroomText}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">เลขประจำตัวประชาชน</label>
                        <p>${student.id_number || '-'}</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">วันเกิด</label>
                        <p>${birthDate}</p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">ชื่อผู้ปกครอง</label>
                        <p>${guardianName}</p>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">เบอร์โทรผู้ปกครอง</label>
                        <p>${guardianPhone}</p>
                    </div>
                </div>
                
                <h6 class="mt-4">สถิติคะแนนความประพฤติ</h6>
                <div style="position: relative; margin-bottom: 25px; margin-top: 30px;">
                    <div style="position: absolute; left: calc(${score}% - 18px); top: -10px; z-index: 1000; 
                                background-color: white; width: 40px; height: 40px; 
                                border-radius: 50%; box-shadow: 0 3px 10px rgba(0,0,0,0.4); 
                                display: flex; align-items: center; justify-content: center; 
                                border: 3px solid white;">
                        <img src="${emojiSrc}" style="height: 30px; width: 30px;" alt="สถานะ">
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar ${progressClass}" role="progressbar" style="width: ${score}%">${score}/100</div>
                    </div>
                </div>
                
                <h6 class="mt-4">ประวัติการกระทำผิดล่าสุด</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless">
                        <thead class="table-light">
                            <tr>
                                <th>วันที่</th>
                                <th>ประเภท</th>
                                <th>คะแนนที่หัก</th>
                                <th>บันทึกโดย</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${violationsTableRows}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
}

/**
 * เปิดโมดัลบันทึกพฤติกรรม
 */
function openBehaviorRecordModal(studentId, studentName, classroom) {
    const studentDetailModal = bootstrap.Modal.getInstance(document.getElementById('studentDetailModal'));
    if (studentDetailModal) {
        studentDetailModal.hide();
    }

    setTimeout(() => {
        const violationModal = new bootstrap.Modal(document.getElementById('newViolationModal'));

        document.getElementById('selectedStudentId').value = studentId;
        document.getElementById('behaviorStudentSearch').value = studentName;

        const selectedStudentInfo = document.getElementById('selectedStudentInfo');
        const studentInfoDisplay = document.getElementById('studentInfoDisplay');
        studentInfoDisplay.innerHTML = `
            <strong>${studentName}</strong> (รหัสนักเรียน: ${studentId}) 
            ชั้น ${classroom}
        `;
        selectedStudentInfo.style.display = 'block';

        violationModal.show();
    }, 500);
}

/**
 * ปิดโมดัลข้อมูลนักเรียน
 */
function closeStudentDetailModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('studentDetailModal'));
    if (modal) {
        modal.hide();
    }
}

/**
 * แสดงข้อความสำเร็จ
 */
function showSuccess(message) {
    showToast('success', message);
}

/**
 * แสดงข้อความข้อผิดพลาด
 */
function showError(message) {
    showToast('error', message);
}

/**
 * แสดง Toast Notification
 */
function showToast(type, message) {
    // สร้าง Toast Element ถ้ายังไม่มี
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }

    const toastId = 'toast-' + Date.now();
    const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    const icon = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
    const title = type === 'success' ? 'สำเร็จ' : 'ข้อผิดพลาด';

    // แปลง \n เป็น <br> สำหรับการแสดงผล
    const formattedMessage = message.replace(/\n/g, '<br>');

    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgClass} text-white">
                <i class="${icon} me-2"></i>
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${formattedMessage}
            </div>
        </div>
    `;

    toastContainer.insertAdjacentHTML('beforeend', toastHtml);

    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: type === 'success' ? 3000 : 5000
    });

    toast.show();

    // ลบ Toast เมื่อซ่อน
    toastElement.addEventListener('hidden.bs.toast', function () {
        this.remove();
    });
}

/**
 * ฟังก์ชันสำหรับพิมพ์รายงานนักเรียน
 */
function printStudentReport(event) {
    const button = event.currentTarget;
    let studentId = button.getAttribute('data-student-id');

    // ถ้าไม่เจอในปุ่ม ให้ไปหาใน modal
    if (!studentId) {
        const modal = document.getElementById('studentDetailModal');
        studentId = modal ? modal.getAttribute('data-student-id') : null;
    }

    if (!studentId) {
        alert('ไม่พบรหัสนักเรียน กรุณาลองใหม่อีกครั้ง');
        return;
    }

    // แสดง loading
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> กำลังสร้างรายงาน...';

    // เรียก API เพื่อสร้าง PDF
    // เพิ่ม credentials: 'include' เพื่อให้แน่ใจว่า cookies (เช่น session cookie) ถูกส่งไปด้วย
    fetch(`/api/students/${studentId}/report`, {
        method: 'GET',
        headers: {
            'Accept': 'application/pdf, application/json', // Client ยอมรับ PDF หรือ JSON (สำหรับ error)
            'X-Requested-With': 'XMLHttpRequest', // ระบุว่าเป็น AJAX request
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '' // ส่ง CSRF Token
        },
        credentials: 'include' // เพิ่มบรรทัดนี้เพื่อให้ browser ส่ง cookies ไปกับ request
    })
        .then(async response => { // เพิ่ม async เพื่อให้ใช้ await ภายในได้
            if (!response.ok) {
                let errorMessage = `เกิดข้อผิดพลาด (${response.status})`;

                // พยายามอ่าน error message จาก JSON response ถ้ามี
                if (response.headers.get('content-type')?.includes('application/json')) {
                    try {
                        const errorData = await response.json();
                        if (errorData && errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (e) {
                    }
                } else {
                    // ถ้าไม่ใช่ JSON อาจอ่านเป็น text
                    try {
                        const errorText = await response.text();
                        console.error('Server error text:', errorText);
                        // อาจจะแสดง errorText บางส่วนถ้ามีประโยชน์
                    } catch (e) {
                        console.error('Could not read text error response:', e);
                    }
                }
                // ตรวจสอบสถานะเพื่อแสดงข้อความที่เหมาะสม
                if (response.status === 401) { // Unauthorized
                    errorMessage = 'คุณไม่มีสิทธิ์เข้าถึง กรุณาเข้าสู่ระบบใหม่อีกครั้ง (401)';
                } else if (response.status === 403) { // Forbidden
                    errorMessage = 'คุณไม่มีสิทธิ์ในการดำเนินการนี้ (403)';
                } else if (response.status === 404) { // Not Found
                    errorMessage = 'ไม่พบข้อมูลหรือ Endpoint ที่ร้องขอ (404)';
                }
                throw new Error(errorMessage); // โยน Error พร้อม message ที่ได้
            }

            return response.blob();
        })
        .then(blob => {
            if (blob.type !== 'application/pdf') {
                console.warn('Received blob is not PDF. Type:', blob.type);
                throw new Error('Server ไม่ได้ส่งไฟล์ PDF กลับมาอย่างถูกต้อง');
            }

            // สร้าง URL สำหรับดาวน์โหลด
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `รายงานนักเรียน-${studentId}.pdf`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(error => { // แก้ไขการจัดการ error ให้แสดง message ที่ชัดเจนขึ้น
            console.error('Error generating report:', error);
            alert(`ไม่สามารถสร้างรายงานได้: ${error.message}`);
        })
        .finally(() => {
            // คืนค่าปุ่ม
            button.disabled = false;
            button.innerHTML = originalText;
        });
}

// Event Listeners สำหรับ Student Detail Modal
document.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-bs-target="#studentDetailModal"]')) {
            const button = e.target.closest('[data-bs-target="#studentDetailModal"]');
            const studentId = button.getAttribute('data-student-id');

            if (studentId) {
                setTimeout(() => {
                    loadStudentDetails(studentId);
                }, 100);
            }
        }
    });

    const studentDetailModal = document.getElementById('studentDetailModal');
    if (studentDetailModal) {
        studentDetailModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (button && button.hasAttribute('data-student-id')) {
                const studentId = button.getAttribute('data-student-id');

                setTimeout(() => {
                    loadStudentDetails(studentId);
                }, 150);
            }
        });

        studentDetailModal.addEventListener('shown.bs.modal', function () {
            this.removeAttribute('aria-hidden');
        });

        studentDetailModal.addEventListener('hidden.bs.modal', function () {
            this.setAttribute('aria-hidden', 'true');
        });
    }
});

/**
 * ตรวจสอบสิทธิ์ครูประจำชั้น
 */
function checkTeacherPermission(studentId) {
    return fetch(`/api/teacher/check-permission/${studentId}`, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
    })
        .then(response => response.json())
        .then(data => {
            // หากไม่มีสิทธิ์ แสดง error message
            if (!data.hasPermission) {
                showError(data.message || 'คุณไม่มีสิทธิ์รีเซ็ตรหัสผ่านของนักเรียนคนนี้');
                return false;
            }

            return data.success && data.hasPermission;
        })
        .catch(error => {
            console.error('Error checking teacher permission:', error);
            return false;
        });
}

/**
 * เลื่อนไปยังหน้า Password Reset
 */
function slideToPasswordReset(studentId, studentName) {
    // ตรวจสอบสิทธิ์ครูประจำชั้นก่อน
    checkTeacherPermission(studentId)
        .then(hasPermission => {
            if (!hasPermission) {
                return; // checkTeacherPermission จะจัดการ error message เอง
            }

            // ตรวจสอบว่ามี Modal อยู่แล้วหรือไม่
            const existingModal = document.getElementById('studentDetailModal');
            if (!existingModal) {
                showError('ไม่พบ Modal นักเรียน กรุณาเปิด Modal ใหม่');
                return;
            }

            // แปลง Modal ให้รองรับระบบ slide
            transformModalToSlideSystem(existingModal, studentId, studentName);
        })
        .catch(error => {
            console.error('Error checking teacher permission:', error);
            showError('เกิดข้อผิดพลาดในการตรวจสอบสิทธิ์ กรุณาลองใหม่อีกครั้ง');
        });
}

/**
 * แปลง Modal ปัจจุบันให้รองรับระบบ slide
 */
function transformModalToSlideSystem(modal, studentId, studentName) {
    const modalContent = modal.querySelector('.modal-content');
    const currentBody = modal.querySelector('.modal-body');
    const currentHeader = modal.querySelector('.modal-header');

    // เก็บข้อมูลเดิมไว้
    const originalBodyContent = currentBody.innerHTML;
    const originalHeaderContent = currentHeader.innerHTML;

    // สร้างโครงสร้างใหม่สำหรับ slide system
    const slideHtml = `
        <div class="modal-slide-container">
            <div class="modal-slide-content slide-to-main" id="slideContent">
                <!-- Panel 1: ข้อมูลนักเรียน (เดิม) -->
                <div class="modal-slide-panel" id="mainPanel">
                    <div class="modal-header">
                        ${originalHeaderContent}
                    </div>
                    <div class="modal-body">
                        ${originalBodyContent}
                    </div>
                </div>
                
                <!-- Panel 2: รีเซ็ตรหัสผ่าน -->
                <div class="modal-slide-panel" id="passwordPanel">
                    <div class="modal-header-slide bg-warning text-white">
                        <button type="button" class="modal-back-btn" onclick="slideToMain()">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <h5 class="modal-header-title">
                            <i class="fas fa-key me-2"></i>รีเซ็ตรหัสผ่าน - ${studentName}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="password-reset-panel">
                        <form id="resetPasswordForm">
                            <div class="form-group-slide">
                                <label class="form-label fw-bold">รหัสผ่านใหม่</label>
                                <div class="input-group">
                                    <input type="password" class="form-control-slide" id="new_password" name="new_password" 
                                           minlength="8" required placeholder="กรอกรหัสผ่านใหม่ (อย่างน้อย 8 ตัวอักษร)">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength-indicator" id="passwordStrength"></div>
                            </div>

                            <div class="form-group-slide">
                                <label class="form-label fw-bold">ยืนยันรหัสผ่านใหม่</label>
                                <div class="input-group">
                                    <input type="password" class="form-control-slide" id="new_password_confirmation" 
                                           name="new_password_confirmation" minlength="8" required placeholder="ยืนยันรหัสผ่านใหม่">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('new_password_confirmation')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="text-danger small mt-1" id="passwordMatchError" style="display: none;">
                                    รหัสผ่านไม่ตรงกัน
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle me-2"></i>คำเตือนสำคัญ:</h6>
                                <ul class="mb-0 small">
                                    <li>นักเรียนจะสามารถเข้าสู่ระบบได้ทันทีด้วยรหัสผ่านใหม่</li>
                                    <li>ระบบจะแจ้งเตือนผู้ปกครองโดยอัตโนมัติ</li>
                                    <li>การดำเนินการนี้จะถูกบันทึกในระบบตรวจสอบ</li>
                                </ul>
                            </div>
                        </form>
                        
                        <div class="slide-action-buttons">
                            <button type="button" class="btn-slide btn-slide-secondary" onclick="slideToMain()">
                                <i class="fas fa-arrow-left me-2"></i>ย้อนกลับ
                            </button>
                            <button type="button" class="btn-slide btn-slide-warning" onclick="resetStudentPassword(${studentId})" id="confirmResetBtn">
                                <i class="fas fa-key me-2"></i>ยืนยันการรีเซ็ต
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    // แทนที่เนื้อหา Modal
    modalContent.innerHTML = slideHtml;

    // เพิ่ม event listeners สำหรับ password validation
    setupPasswordValidation();

    // Slide ไปหน้า password reset
    setTimeout(() => {
        slideToPasswordScreen();
    }, 100);
}

/**
 * เลื่อนไปหน้า Password Reset
 */
function slideToPasswordScreen() {
    const slideContent = document.getElementById('slideContent');
    if (slideContent) {
        slideContent.classList.remove('slide-to-main');
        slideContent.classList.add('slide-to-password');
    }
}

/**
 * เลื่อนกลับไปหน้าหลัก
 */
function slideToMain() {
    const slideContent = document.getElementById('slideContent');
    if (slideContent) {
        slideContent.classList.remove('slide-to-password');
        slideContent.classList.add('slide-to-main');
    }
}

/**
 * ตั้งค่า Password Validation
 */
function setupPasswordValidation() {
    const passwordInput = document.getElementById('new_password');
    const confirmInput = document.getElementById('new_password_confirmation');
    const strengthIndicator = document.getElementById('passwordStrength');
    const matchError = document.getElementById('passwordMatchError');
    const confirmBtn = document.getElementById('confirmResetBtn');

    if (!passwordInput || !confirmInput) return;

    // ตรวจสอบความแข็งแรงของรหัสผ่าน
    passwordInput.addEventListener('input', function () {
        const password = this.value;
        const strength = calculatePasswordStrength(password);

        if (password.length === 0) {
            strengthIndicator.style.width = '0%';
            strengthIndicator.className = 'password-strength-indicator';
        } else if (strength < 30) {
            strengthIndicator.style.width = '33%';
            strengthIndicator.className = 'password-strength-indicator password-strength-weak';
        } else if (strength < 60) {
            strengthIndicator.style.width = '66%';
            strengthIndicator.className = 'password-strength-indicator password-strength-medium';
        } else {
            strengthIndicator.style.width = '100%';
            strengthIndicator.className = 'password-strength-indicator password-strength-strong';
        }

        validatePasswordMatch();
    });

    // ตรวจสอบรหัสผ่านตรงกันหรือไม่
    confirmInput.addEventListener('input', validatePasswordMatch);

    function validatePasswordMatch() {
        const password = passwordInput.value;
        const confirm = confirmInput.value;

        if (confirm.length > 0 && password !== confirm) {
            matchError.style.display = 'block';
            confirmInput.style.borderColor = '#dc3545';
            confirmBtn.disabled = true;
        } else {
            matchError.style.display = 'none';
            confirmInput.style.borderColor = '#ced4da';
            confirmBtn.disabled = password.length < 8 || password !== confirm;
        }
    }
}

/**
 * คำนวณความแข็งแรงของรหัสผ่าน
 */
function calculatePasswordStrength(password) {
    let strength = 0;

    // ความยาว
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 25;

    // มีตัวเลข
    if (/\d/.test(password)) strength += 15;

    // มีตัวอักษรพิมพ์เล็ก
    if (/[a-z]/.test(password)) strength += 15;

    // มีตัวอักษรพิมพ์ใหญ่
    if (/[A-Z]/.test(password)) strength += 10;

    // มีอักขระพิเศษ
    if (/[^A-Za-z0-9]/.test(password)) strength += 10;

    return Math.min(strength, 100);
}

/**
 * แสดง/ซ่อน รหัสผ่าน
 */
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

/**
 * รีเซ็ตรหัสผ่านนักเรียน
 */
function resetStudentPassword(studentId) {
    const password = document.getElementById('new_password').value;
    const confirmation = document.getElementById('new_password_confirmation').value;
    const btn = document.getElementById('confirmResetBtn');

    // ตรวจสอบรหัสผ่าน
    if (password !== confirmation) {
        showError('รหัสผ่านใหม่และการยืนยันไม่ตรงกัน');
        return;
    }

    if (password.length < 8) {
        showError('รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร');
        return;
    }

    // แสดง loading state
    btn.disabled = true;
    btn.classList.add('btn-loading');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังรีเซ็ตรหัสผ่าน...';

    // ส่งข้อมูลไปยัง API
    fetch(`/api/teacher/student/${studentId}/reset-password`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: JSON.stringify({
            new_password: password,
            new_password_confirmation: confirmation
        })
    })
        .then(async response => {
            const data = await response.json().catch(() => ({}));
            // ถ้า validation ไม่ผ่าน ให้แสดงรายละเอียดจากเซิร์ฟเวอร์
            if (!response.ok) {
                if (response.status === 422 && data && data.errors) {
                    const messages = Object.values(data.errors).flat().join('\n');
                    throw new Error(messages || data.message || 'ข้อมูลไม่ถูกต้อง');
                }
                throw new Error(data.message || `เกิดข้อผิดพลาด (HTTP ${response.status})`);
            }
            return data;
        })
        .then(data => {
            if (data.success) {
                showSuccess(data.message || 'รีเซ็ตรหัสผ่านสำเร็จแล้ว');

                // รอ 2 วินาที แล้วปิด Modal
                setTimeout(() => {
                    const modal = document.getElementById('studentDetailModal');
                    if (modal) {
                        const bsModal = bootstrap.Modal.getInstance(modal);
                        if (bsModal) {
                            bsModal.hide();
                        }
                    }
                }, 2000);
            } else {
                showError(data.message || 'เกิดข้อผิดพลาดในการรีเซ็ตรหัสผ่าน');
            }
        })
        .catch(error => {
            console.error('Error resetting password:', error);
            showError('เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง');
        })
        .finally(() => {
            // คืนสถานะปุ่ม
            btn.disabled = false;
            btn.classList.remove('btn-loading');
            btn.innerHTML = originalText;
        });
}

/**
 * เปิด Sidebar แก้ไขรายงานพฤติกรรม
 */
function openEditViolationSidebar(reportId) {
    const sidebar = document.getElementById('editViolationSidebar');
    sidebar.classList.add('show');

    // รีเซ็ตสถานะ
    showEditViolationLoading();
    hideEditViolationError();
    hideEditViolationForm();
    hideEditViolationActions();

    // โหลดข้อมูลรายงาน
    loadViolationReportForEdit(reportId);

    // เพิ่ม event listener สำหรับปิด sidebar เมื่อคลิกพื้นหลัง
    sidebar.addEventListener('click', function (e) {
        if (e.target === sidebar) {
            closeEditViolationSidebar();
        }
    });
}

/**
 * ปิด Sidebar แก้ไขรายงานพฤติกรรม
 */
function closeEditViolationSidebar() {
    const sidebar = document.getElementById('editViolationSidebar');
    sidebar.classList.remove('show');

    // รีเซ็ตฟอร์ม
    const form = document.getElementById('violationEditForm');
    if (form) {
        form.reset();
    }
}

/**
 * แสดงสถานะ Loading ของ Sidebar
 */
function showEditViolationLoading() {
    document.getElementById('editViolationLoading').style.display = 'block';
}

/**
 * ซ่อนสถานะ Loading ของ Sidebar
 */
function hideEditViolationLoading() {
    document.getElementById('editViolationLoading').style.display = 'none';
}

/**
 * แสดงข้อผิดพลาดใน Sidebar
 */
function showEditViolationError(message) {
    document.getElementById('editViolationErrorMessage').textContent = message;
    document.getElementById('editViolationError').style.display = 'block';
}

/**
 * ซ่อนข้อผิดพลาดใน Sidebar
 */
function hideEditViolationError() {
    document.getElementById('editViolationError').style.display = 'none';
}

/**
 * แสดงฟอร์มแก้ไขใน Sidebar
 */
function showEditViolationForm() {
    document.getElementById('editViolationForm').style.display = 'block';
}

/**
 * ซ่อนฟอร์มแก้ไขใน Sidebar
 */
function hideEditViolationForm() {
    document.getElementById('editViolationForm').style.display = 'none';
}

/**
 * แสดงปุ่มแอคชั่นใน Sidebar
 */
function showEditViolationActions() {
    document.getElementById('editViolationActions').style.display = 'block';
}

/**
 * ซ่อนปุ่มแอคชั่นใน Sidebar
 */
function hideEditViolationActions() {
    document.getElementById('editViolationActions').style.display = 'none';
}

/**
 * โหลดข้อมูลรายงานพฤติกรรมสำหรับแก้ไข
 */
function loadViolationReportForEdit(reportId) {
    fetch(`/api/behavior-reports/${reportId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('ไม่สามารถโหลดข้อมูลได้');
            }
            return response.json();
        })
        .then(response => {
            if (response.success && response.data) {
                populateEditForm(response.data);
            } else {
                throw new Error(response.message || 'ไม่สามารถโหลดข้อมูลได้');
            }
        })
        .catch(error => {
            hideEditViolationLoading();
            showEditViolationError(error.message);
        });
}

/**
 * เติมข้อมูลในฟอร์มแก้ไข
 */
function populateEditForm(data) {
    hideEditViolationLoading();

    // เติมข้อมูลพื้นฐาน
    document.getElementById('editReportId').value = data.id;

    // เก็บ context สำหรับคำนวณคะแนนตอนยืนยันบันทึก
    window.behaviorEditContext = {
        oldViolationPoints: (data.violation && typeof data.violation.points_deducted !== 'undefined') ? Number(data.violation.points_deducted) : 0,
        studentCurrentScore: (data.student && typeof data.student.current_score !== 'undefined') ? Number(data.student.current_score) : 100,
        originalViolationId: null,
        originalDate: null,
        originalTime: null,
        originalDescription: ''
    };

    // ข้อมูลนักเรียน
    const studentInfo = `
        <div class="d-flex align-items-center">
            <img src="${data.student.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(data.student.name) + '&background=95A4D8&color=fff'}" class="rounded-circle me-2" width="30" height="30" alt="รูปประจำตัว">
            <div>
                <strong>${data.student.name || 'ไม่ระบุชื่อ'}</strong><br>
                <small class="text-muted">รหัส: ${data.student.student_code || 'ไม่ระบุ'} | ชั้น: ${data.student.class || 'ไม่ระบุ'}</small>
            </div>
        </div>
    `;
    document.getElementById('editStudentInfoDisplay').innerHTML = studentInfo;

    // โหลดประเภทพฤติกรรมและเลือกค่าปัจจุบัน - ต้องใช้ violation id ที่ถูกต้อง
    // ก่อนอื่นต้องหา violation id จากข้อมูลเดิม
    findViolationIdAndLoadTypes(data);

    // เติมข้อมูลวันที่และเวลา
    if (data.report && data.report.report_datetime) {
        try {
            // แปลงรูปแบบวันที่จาก API มาเป็น input format
            const reportDate = new Date(data.report.report_datetime);
            if (!isNaN(reportDate.getTime())) {
                document.getElementById('editViolationDate').value = reportDate.toISOString().split('T')[0];
                document.getElementById('editViolationTime').value = reportDate.toTimeString().split(' ')[0].substring(0, 5);
                // เก็บค่าเดิมไว้สำหรับเทียบ
                window.behaviorEditContext.originalDate = document.getElementById('editViolationDate').value;
                window.behaviorEditContext.originalTime = document.getElementById('editViolationTime').value;
            }
        } catch (e) {
            console.error('Error parsing date:', e);
            // ใช้วันที่ปัจจุบันเป็นค่าเริ่มต้น
            const now = new Date();
            document.getElementById('editViolationDate').value = now.toISOString().split('T')[0];
            document.getElementById('editViolationTime').value = now.toTimeString().split(' ')[0].substring(0, 5);
            window.behaviorEditContext.originalDate = document.getElementById('editViolationDate').value;
            window.behaviorEditContext.originalTime = document.getElementById('editViolationTime').value;
        }
    }

    // เติมรายละเอียด
    document.getElementById('editViolationDescription').value = data.report?.description || '';
    window.behaviorEditContext.originalDescription = document.getElementById('editViolationDescription').value || '';

    // หลักฐาน
    if (data.report?.evidence_url) {
        document.getElementById('currentEvidenceImage').src = data.report.evidence_url;
        document.getElementById('currentEvidenceSection').style.display = 'block';
    } else {
        document.getElementById('currentEvidenceSection').style.display = 'none';
    }

    showEditViolationForm();
    showEditViolationActions();

    // เพิ่ม event listener สำหรับปุ่มบันทึก
    document.getElementById('saveEditViolationBtn').onclick = function () {
        saveViolationEdit();
    };

    // ปุ่มลบจากใน Sidebar แก้ไข
    const deleteBtn = document.getElementById('deleteEditViolationBtn');
    if (deleteBtn) {
        deleteBtn.onclick = function () {
            const reportId = document.getElementById('editReportId').value;
            if (!reportId) return;

            if (typeof Swal !== 'undefined') {
                const vt = document.getElementById('editViolationType');
                const vtText = vt && vt.options[vt.selectedIndex] ? vt.options[vt.selectedIndex].text : '-';
                const points = Number(document.getElementById('editPointsDeducted')?.textContent || 0);
                const studentName = (data && data.student && data.student.name) ? data.student.name : '-';

                Swal.fire({
                    title: 'ยืนยันการลบรายงานนี้?',
                    html: `
                        <div class='text-start small'>
                            <div class='mb-1'>นักเรียน: <strong>${studentName}</strong></div>
                            <div class='mb-1'>ประเภทพฤติกรรม: <strong>${vtText}</strong></div>
                            <div class='mb-1'>คะแนนที่เคยหัก: <strong>${points}</strong> คะแนน</div>
                            <div class='text-muted'>ระบบจะทำการคืนคะแนนที่หักและลบรายงานนี้ออก</div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'ลบรายงาน',
                    cancelButtonText: 'ยกเลิก',
                    confirmButtonColor: '#d33'
                }).then(result => {
                    if (!result.isConfirmed) return;

                    // แสดงกำลังลบ
                    deleteBtn.disabled = true;
                    const original = deleteBtn.innerHTML;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> กำลังลบ...';

                    fetch(`/api/behavior-reports/${reportId}/delete`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': behaviorReport.csrfToken,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    })
                        .then(r => {
                            if (!r.ok) throw new Error('ลบไม่สำเร็จ');
                            return r.json();
                        })
                        .then(resp => {
                            if (resp.success === false) throw new Error(resp.message || 'ลบไม่สำเร็จ');

                            // Toast แจ้งลบสำเร็จ
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    icon: 'success',
                                    title: resp.message || 'ลบรายงานสำเร็จ',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    timerProgressBar: true
                                });
                            }

                            // รีเฟรชตารางและปิด Sidebar
                            loadRecentReports();
                            closeEditViolationSidebar();
                        })
                        .catch(err => {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({ icon: 'error', title: 'ลบไม่สำเร็จ', text: err.message || 'เกิดข้อผิดพลาด' });
                            }
                        })
                        .finally(() => {
                            deleteBtn.disabled = false;
                            deleteBtn.innerHTML = original;
                        });
                });
            }
        };
    }
}

/**
 * ค้นหา violation ID และโหลดประเภทพฤติกรรม
 */
function findViolationIdAndLoadTypes(data) {
    fetch('/api/violations/all')
        .then(response => response.json())
        .then(violationsData => {
            if (violationsData.success) {
                const select = document.getElementById('editViolationType');
                select.innerHTML = '<option value="">เลือกประเภทพฤติกรรม</option>';

                let selectedViolationId = null;

                violationsData.data.forEach(violation => {
                    const option = document.createElement('option');
                    option.value = violation.violations_id;
                    option.textContent = violation.violations_name;
                    option.dataset.points = violation.violations_points_deducted;

                    // ค้นหา violation ที่ตรงกับชื่อในข้อมูล
                    if (violation.violations_name === data.violation?.name) {
                        option.selected = true;
                        selectedViolationId = violation.violations_id;
                        document.getElementById('editPointsDeducted').textContent = violation.violations_points_deducted;
                    }

                    select.appendChild(option);
                });

                // เก็บ violation id เดิมไว้ใน context เพื่อใช้ตรวจสอบความเปลี่ยนแปลง
                if (window.behaviorEditContext) {
                    window.behaviorEditContext.originalViolationId = selectedViolationId;
                }

                // เพิ่ม event listener สำหรับการเปลี่ยนประเภทพฤติกรรม
                select.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    const points = selectedOption.dataset.points || 0;
                    document.getElementById('editPointsDeducted').textContent = points;
                });
            }
        })
        .catch(error => {
            console.error('Error loading violation types:', error);
        });
}

/**
 * โหลดประเภทพฤติกรรมสำหรับฟอร์มแก้ไข
 */
function loadViolationTypesForEdit(selectedViolationId) {
    fetch('/api/violations/all')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('editViolationType');
                select.innerHTML = '<option value="">เลือกประเภทพฤติกรรม</option>';

                data.data.forEach(violation => {
                    const option = document.createElement('option');
                    option.value = violation.violations_id;
                    option.textContent = violation.violations_name;
                    option.dataset.points = violation.violations_points_deducted;

                    if (violation.violations_id == selectedViolationId) {
                        option.selected = true;
                        document.getElementById('editPointsDeducted').textContent = violation.violations_points_deducted;
                    }

                    select.appendChild(option);
                });

                // เพิ่ม event listener สำหรับการเปลี่ยนประเภทพฤติกรรม
                select.addEventListener('change', function () {
                    const selectedOption = this.options[this.selectedIndex];
                    const points = selectedOption.dataset.points || 0;
                    document.getElementById('editPointsDeducted').textContent = points;
                });
            }
        })
        .catch(error => {
            console.error('Error loading violation types:', error);
        });
}

/**
 * บันทึกการแก้ไขรายงานพฤติกรรม
 */
function saveViolationEdit() {
    const form = document.getElementById('violationEditForm');
    const formData = new FormData(form);
    const reportId = document.getElementById('editReportId').value;

    // ซ่อนข้อความก่อนหน้า
    document.getElementById('editViolationSuccess').style.display = 'none';
    document.getElementById('editViolationFormError').style.display = 'none';

    // ปุ่มบันทึก loading
    const saveBtn = document.getElementById('saveEditViolationBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> กำลังบันทึก...';

    // รวมวันที่และเวลา
    const date = document.getElementById('editViolationDate').value;
    const time = document.getElementById('editViolationTime').value;
    if (date && time) {
        const datetime = `${date} ${time}:00`;
        formData.append('report_datetime', datetime);
    }

    // เตรียมข้อมูลคำนวณคะแนนเพื่อยืนยัน
    const select = document.getElementById('editViolationType');
    const selectedOption = select.options[select.selectedIndex];
    const newPoints = Number(selectedOption?.dataset?.points || 0);
    const oldPoints = Number((window.behaviorEditContext && window.behaviorEditContext.oldViolationPoints) || 0);
    const currentScore = Number((window.behaviorEditContext && window.behaviorEditContext.studentCurrentScore) || 100);
    const diff = newPoints - oldPoints; // คะแนนที่จะเปลี่ยนแปลงจากเดิม
    const calculatedNewScore = Math.max(0, currentScore - diff); // ไม่ให้ต่ำกว่า 0 (สอดคล้องกับ backend)

    // แนบไฟล์หลักฐาน (ถ้ามี)
    const evidenceFile = document.getElementById('editEvidenceFile').files[0];
    if (evidenceFile) {
        formData.append('evidence', evidenceFile);
    }

    // แสดง SweetAlert เพื่อยืนยันก่อนบันทึก
    if (typeof Swal !== 'undefined') {
        const changeText = diff === 0
            ? '<span class="text-muted">ไม่มีการเปลี่ยนคะแนนที่หัก</span>'
            : (diff > 0
                ? `<span class="text-danger">เพิ่มการหัก ${diff} คะแนน</span>`
                : `<span class="text-success">ลดการหัก ${Math.abs(diff)} คะแนน</span>`);

        Swal.fire({
            title: 'ยืนยันการบันทึกการแก้ไข?',
            html: `
                <div class='text-start small'>
                    <div class='mb-2'>ประเภทพฤติกรรมใหม่: <strong>${selectedOption?.text || '-'}</strong></div>
                    <div class='mb-2'>คะแนนที่หัก (เดิม ➜ ใหม่): <strong>${oldPoints}</strong> ➜ <strong>${newPoints}</strong></div>
                    <div class='mb-2'>ผลกระทบต่อคะแนนนักเรียน: ${changeText}</div>
                    <div class='p-2 mt-2 border rounded bg-light'>คะแนนนักเรียน: <strong>${currentScore}</strong> ➜ <strong>${calculatedNewScore}</strong></div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก'
        }).then(result => {
            if (!result.isConfirmed) {
                // ผู้ใช้ยกเลิก
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
                return;
            }

            // ตรวจสอบว่าไม่มีการเปลี่ยนแปลงข้อมูลหรือไม่ (ประเภท, วันที่, เวลา, รายละเอียด, ไฟล์หลักฐานใหม่)
            try {
                const ctx = window.behaviorEditContext || {};
                const newViolationId = (document.getElementById('editViolationType').value || '').toString();
                const newDate = (document.getElementById('editViolationDate').value || '').toString();
                const newTime = (document.getElementById('editViolationTime').value || '').toString();
                const newDesc = (document.getElementById('editViolationDescription').value || '').trim();
                const hasNewEvidence = (document.getElementById('editEvidenceFile').files || []).length > 0;
                const unchanged = (
                    (ctx.originalViolationId ? newViolationId === String(ctx.originalViolationId) : true) &&
                    (ctx.originalDate ? newDate === ctx.originalDate : true) &&
                    (ctx.originalTime ? newTime === ctx.originalTime : true) &&
                    (ctx.originalDescription !== undefined ? newDesc === (ctx.originalDescription || '').trim() : true) &&
                    !hasNewEvidence
                );

                if (unchanged) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'info',
                            title: 'บันทึกค่าเดิม',
                            text: 'ไม่ได้มีการเปลี่ยนแปลงข้อมูล',
                            showConfirmButton: false,
                            timer: 2000,
                            timerProgressBar: true
                        });
                    }
                    // คืนสถานะปุ่มและหยุดการส่งข้อมูล
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                    return;
                }
            } catch (e) {
                // ถ้าตรวจสอบไม่สำเร็จ ให้ผ่านไปยังการบันทึกตามปกติ
                console.warn('Unable to verify unchanged state:', e);
            }

            // ส่งข้อมูล (ใช้ POST route ที่รองรับ FormData)
            fetch(`/api/behavior-reports/${reportId}/update`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': behaviorReport.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
                .then(response => {
                    // ตรวจสอบสิทธิ์การเข้าถึง
                    if (response.status === 403) {
                        return response.json().then(data => {
                            throw new Error(data.message || 'คุณไม่มีสิทธิ์แก้ไขรายงานนี้');
                        });
                    }
                    if (!response.ok) {
                        throw new Error('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // แสดงข้อความสำเร็จ
                        document.getElementById('editViolationSuccessMessage').textContent = data.message || 'บันทึกการแก้ไขเรียบร้อยแล้ว';
                        document.getElementById('editViolationSuccess').style.display = 'block';

                        // รีเฟรชข้อมูลในตาราง
                        loadRecentReports();

                        // ปิด sidebar หลัง 2 วินาที
                        setTimeout(() => {
                            closeEditViolationSidebar();
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
                    }
                })
                .catch(error => {
                    // แสดงข้อความ error ในรูปแบบ alert ที่เด่นชัด
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'ไม่สามารถแก้ไขได้',
                            text: error.message,
                            confirmButtonText: 'ตกลง',
                            confirmButtonColor: '#d33'
                        });
                    }
                    document.getElementById('editViolationFormErrorMessage').textContent = error.message;
                    document.getElementById('editViolationFormError').style.display = 'block';
                })
                .finally(() => {
                    // คืนสถานะปุ่ม
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                });
        });
        return; // รอผลจาก SweetAlert
    }

    // กรณีไม่พบ SweetAlert ให้ส่งตรง (fallback)
    fetch(`/api/behavior-reports/${reportId}/update`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': behaviorReport.csrfToken,
            'Accept': 'application/json'
        },
        body: formData
    })
        .then(response => {
            // ตรวจสอบสิทธิ์การเข้าถึง
            if (response.status === 403) {
                return response.json().then(data => {
                    throw new Error(data.message || 'คุณไม่มีสิทธิ์แก้ไขรายงานนี้');
                });
            }
            if (!response.ok) {
                throw new Error('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                document.getElementById('editViolationSuccessMessage').textContent = data.message || 'บันทึกการแก้ไขเรียบร้อยแล้ว';
                document.getElementById('editViolationSuccess').style.display = 'block';
                loadRecentReports();
                setTimeout(() => { closeEditViolationSidebar(); }, 2000);
            } else {
                throw new Error(data.message || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล');
            }
        })
        .catch(error => {
            // แสดงข้อความ error ในรูปแบบ alert ที่เด่นชัด
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถแก้ไขได้',
                    text: error.message,
                    confirmButtonText: 'ตกลง',
                    confirmButtonColor: '#d33'
                });
            }
            document.getElementById('editViolationFormErrorMessage').textContent = error.message;
            document.getElementById('editViolationFormError').style.display = 'block';
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        });
}

// Laravel Log Viewer Functions
document.addEventListener('DOMContentLoaded', function () {
    // เพิ่ม event listener สำหรับปุ่ม Log
    const btnViewLog = document.getElementById('btnViewLog');
    const refreshLogBtn = document.getElementById('refreshLogBtn');

    if (btnViewLog) {
        btnViewLog.addEventListener('click', function (e) {
            e.preventDefault();
            showLaravelLog();
        });
    }

    if (refreshLogBtn) {
        refreshLogBtn.addEventListener('click', function () {
            loadLaravelLog();
        });
    }
});

/**
 * แสดง Laravel Log Modal และโหลดข้อมูล
 */
function showLaravelLog() {
    const modal = new bootstrap.Modal(document.getElementById('laravelLogModal'));
    modal.show();
    loadLaravelLog();
}

/**
 * โหลดไฟล์ Laravel Log
 */
function loadLaravelLog() {
    const logContainer = document.getElementById('logContainer');
    const logInfo = document.getElementById('logInfo');
    const refreshBtn = document.getElementById('refreshLogBtn');

    // แสดง loading ที่สวยงาม
    logContainer.innerHTML = `
        <div class="text-center" style="color: #7d8590; margin-top: 100px;">
            <div style="font-size: 24px; margin-bottom: 12px; animation: pulse 2s infinite;">⚡</div>
            <div style="font-size: 14px;">กำลังโหลดข้อมูล...</div>
        </div>
    `;

    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> โหลด...';

    fetch('/api/dashboard/laravel-log', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': behaviorReport.csrfToken
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // ประมวลผลและแสดง log ที่สวยงาม
                const formattedLog = formatLogContent(data.content);
                logContainer.innerHTML = formattedLog || `
                <div class="text-center" style="color: #7d8590; margin-top: 100px;">
                    <div style="font-size: 24px; margin-bottom: 12px;">📝</div>
                    <div style="font-size: 14px;">ไฟล์ log ว่างเปล่า</div>
                </div>
            `;

                // อัปเดตข้อมูลไฟล์
                const fileSize = formatFileSize(data.file_size);
                logInfo.textContent = `${data.lines_shown} บรรทัด • ${fileSize}`;

                // เลื่อนไปด้านล่างสุด
                setTimeout(() => {
                    logContainer.scrollTop = logContainer.scrollHeight;
                }, 100);
            } else {
                logContainer.innerHTML = `
                <div class="text-center" style="color: #f85149; margin-top: 100px;">
                    <div style="font-size: 24px; margin-bottom: 12px;">⚠️</div>
                    <div style="font-size: 14px;">${data.message || 'ไม่สามารถโหลดไฟล์ log ได้'}</div>
                </div>
            `;
                logInfo.textContent = 'เกิดข้อผิดพลาด';
            }
        })
        .catch(error => {
            console.error('Error loading log:', error);
            logContainer.innerHTML = `
            <div class="text-center" style="color: #f85149; margin-top: 100px;">
                <div style="font-size: 24px; margin-bottom: 12px;">🔌</div>
                <div style="font-size: 14px;">เกิดข้อผิดพลาดในการเชื่อมต่อ</div>
            </div>
        `;
            logInfo.textContent = 'เกิดข้อผิดพลาด';
        })
        .finally(() => {
            refreshBtn.disabled = false;
            refreshBtn.innerHTML = '<i class="fas fa-sync-alt me-1"></i> รีเฟรช';
        });
}

/**
 * Format log content ให้สวยงามและอ่านง่าย
 */
function formatLogContent(content) {
    if (!content) return '';

    // แยกบรรทัดและจัดรูปแบบ
    const lines = content.split('\n');
    let formattedHtml = '';

    lines.forEach(line => {
        if (!line.trim()) {
            formattedHtml += '<br>';
            return;
        }

        let className = '';
        let icon = '';

        // กำหนดสีและไอคอนตาม log level
        if (line.includes('[ERROR]') || line.includes('ERROR:')) {
            className = 'log-error';
            icon = '🔴 ';
        } else if (line.includes('[WARNING]') || line.includes('WARNING:')) {
            className = 'log-warning';
            icon = '🟡 ';
        } else if (line.includes('[INFO]') || line.includes('INFO:')) {
            className = 'log-info';
            icon = '🔵 ';
        } else if (line.includes('[DEBUG]') || line.includes('DEBUG:')) {
            className = 'log-debug';
            icon = '⚪ ';
        } else {
            className = 'log-default';
        }

        // Escape HTML และเพิ่ม styling
        const escapedLine = escapeHtml(line);
        formattedHtml += `<div class="${className}" style="margin-bottom: 4px; word-wrap: break-word;">${icon}${escapedLine}</div>`;
    });

    return `
        <style>
            .log-error { color: #ff6b6b; }
            .log-warning { color: #ffd93d; }
            .log-info { color: #74c0fc; }
            .log-debug { color: #b2bec3; }
            .log-default { color: #e6edf3; }
            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }
        </style>
        ${formattedHtml}
    `;
}

/**
 * Escape HTML characters เพื่อป้องกัน XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Format ขนาดไฟล์
 */
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}