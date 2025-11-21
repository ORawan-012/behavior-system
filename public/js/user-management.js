// User Management Script for Dashboard
class UserManagement {
    constructor() {
        this.currentPage = 1;
        this.currentFilters = {};
        this.currentUserId = null;
        this.users = [];
        this.classrooms = [];
        this.init();
    }

    // Normalize various date formats to 'YYYY-MM-DD' for <input type="date">
    formatDateForInput(value) {
        if (!value) return '';
        try {
            // If already in YYYY-MM-DD or starts with it (e.g. YYYY-MM-DDTHH:MM:SS)
            const s = String(value).trim();
            const isoLike = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
            if (isoLike) return `${isoLike[1]}-${isoLike[2]}-${isoLike[3]}`;

            // dd/mm/yyyy
            const dmy = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            if (dmy) {
                const d = dmy[1].padStart(2,'0');
                const m = dmy[2].padStart(2,'0');
                const y = dmy[3];
                return `${y}-${m}-${d}`;
            }

            // Excel serial number (days since 1899-12-30)
            if (/^\d{3,6}$/.test(s)) {
                const serial = parseInt(s, 10);
                const base = new Date(Date.UTC(1899, 11, 30));
                base.setUTCDate(base.getUTCDate() + serial);
                const y = base.getUTCFullYear();
                const m = String(base.getUTCMonth() + 1).padStart(2, '0');
                const d = String(base.getUTCDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }

            // Fallback: Date parse
            const dt = new Date(s);
            if (!isNaN(dt.getTime())) {
                const y = dt.getFullYear();
                const m = String(dt.getMonth() + 1).padStart(2, '0');
                const d = String(dt.getDate()).padStart(2, '0');
                return `${y}-${m}-${d}`;
            }
        } catch (_) { /* ignore */ }
        return '';
    }

    init() {
        // Initialize event listeners
        this.bindEvents();
        // Load classrooms for filters
        this.loadClassrooms();
    }

    bindEvents() {
        // Search functionality
        const searchBtn = document.getElementById('userSearchBtn');
        const searchInput = document.getElementById('userSearchInput');
        
        if (searchBtn) {
            searchBtn.addEventListener('click', () => this.performSearch());
        }
        
        if (searchInput) {
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performSearch();
                }
            });
        }

        // Filter events
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        const classroomFilter = document.getElementById('classroomFilter');

        if (roleFilter) roleFilter.addEventListener('change', () => this.applyFilters());
        if (statusFilter) statusFilter.addEventListener('change', () => this.applyFilters());
        if (classroomFilter) classroomFilter.addEventListener('change', () => this.applyFilters());

        // Form submission
        const editForm = document.getElementById('userEditForm');
        if (editForm) {
            editForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveUser();
            });
        }
    }

    async loadClassrooms() {
        try {
            // Load all classrooms (no pagination, includes empty rooms)
            const response = await fetch('/api/classes/all');
            const data = await response.json();
            
            if (data.success) {
                const payload = data.data;
                this.classrooms = Array.isArray(payload) ? payload : (payload && Array.isArray(payload.data) ? payload.data : []);
                this.populateClassroomFilter();
                this.populateClassroomSelect();
            }
        } catch (error) {
            console.error('Failed to load classrooms:', error);
        }
        return true;
    }

    populateClassroomFilter() {
        const select = document.getElementById('classroomFilter');
        if (!select) return;

        // Clear existing options except first one
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

    (this.classrooms || []).forEach(classroom => {
            const option = document.createElement('option');
            option.value = classroom.classes_id;
            option.textContent = `${classroom.classes_level}/${classroom.classes_room_number}`;
            select.appendChild(option);
        });
    }

    populateClassroomSelect() {
        const select = document.getElementById('editStudentClassroom');
        const teacherSelect = document.getElementById('editTeacherAssignedClass');
        if (!select && !teacherSelect) return;

        if (select) {
            while (select.children.length > 1) select.removeChild(select.lastChild);
        }
    if (teacherSelect) {
            while (teacherSelect.children.length > 1) teacherSelect.removeChild(teacherSelect.lastChild);
        }

    (this.classrooms || []).forEach(classroom => {
            if (select) {
                const option = document.createElement('option');
                option.value = classroom.classes_id;
        option.textContent = `${classroom.classes_level}/${classroom.classes_room_number}`;
                select.appendChild(option);
            }
            if (teacherSelect) {
                const option2 = document.createElement('option');
                option2.value = classroom.classes_id;
        option2.textContent = `${classroom.classes_level}/${classroom.classes_room_number}`;
                teacherSelect.appendChild(option2);
            }
        });
    }

    async loadUsers(page = 1) {
        const loadingDiv = document.getElementById('usersLoading');
        const tableBody = document.getElementById('usersTableBody');
        
        if (loadingDiv) loadingDiv.style.display = 'block';
        if (tableBody) tableBody.innerHTML = '';

        try {
            const params = new URLSearchParams({
                page: page,
                ...this.currentFilters
            });

            const response = await fetch(`/api/users?${params}`);
            const data = await response.json();

            if (loadingDiv) loadingDiv.style.display = 'none';

            if (data.success) {
                this.users = data.data.data;
                this.renderUsers();
                this.renderPagination(data.data);
                if (data.stats) {
                    this.updateStats(data.stats);
                } else {
                    console.warn('[UserManagement] No stats object returned from API.');
                }
            } else {
                throw new Error(data.message || 'Failed to load users');
            }
        } catch (error) {
            if (loadingDiv) loadingDiv.style.display = 'none';
            console.error('Failed to load users:', error);
            this.showError('ไม่สามารถโหลดข้อมูลผู้ใช้ได้');
        }
    }

    renderUsers() {
        const tableBody = document.getElementById('usersTableBody');
        if (!tableBody) return;

        if (this.users.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <p>ไม่พบข้อมูลผู้ใช้</p>
                    </td>
                </tr>
            `;
            return;
        }

        const roleNames = {
            'admin': 'ผู้ดูแลระบบ',
            'teacher': 'ครู',
            'student': 'นักเรียน',
            'guardian': 'ผู้ปกครอง'
        };

        const roleColors = {
            'admin': 'danger',
            'teacher': 'primary',
            'student': 'info',
            'guardian': 'warning'
        };

        tableBody.innerHTML = this.users.map(user => {
            const userName = (user.users_name_prefix || '') + (user.users_first_name || '') + ' ' + (user.users_last_name || '');
            const avatarUrl = user.users_profile_image 
                ? `/storage/${user.users_profile_image}`
                : `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=95A4D8&color=fff`;

            let codeOrId = '';
            let classOrDept = '-';

            if (user.users_role === 'student' && user.student) {
                codeOrId = user.student.students_student_code || '';
                if (user.student.classroom) {
                    classOrDept = `${user.student.classroom.classes_level}/${user.student.classroom.classes_room_number}`;
                }
            } else if (user.users_role === 'teacher' && user.teacher) {
                codeOrId = user.teacher.teachers_employee_code || '';
                classOrDept = user.teacher.teachers_department || '-';
            }

            return `
                <tr>
                    <td>
                        <div>
                            ${codeOrId ? `<strong>${codeOrId}</strong><br>` : ''}
                            ${user.users_email ? `<small class="text-muted">${user.users_email}</small>` : ''}
                        </div>
                    </td>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="${avatarUrl}" class="rounded-circle me-2" width="32" height="32" alt="${userName}">
                            <div>
                                <span>${userName}</span>
                                ${user.users_email ? `<br><small class="text-muted">${user.users_email}</small>` : ''}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-${roleColors[user.users_role] || 'secondary'}">
                            ${roleNames[user.users_role] || user.users_role}
                        </span>
                    </td>
                    <td>${classOrDept}</td>
                    <td>
                        <div class="form-check form-switch">
                            <input class="form-check-input status-toggle" type="checkbox" 
                                   data-user-id="${user.users_id}"
                                   ${(user.users_status === 'active') ? 'checked' : ''}
                                   ${user.users_id === window.authUserId ? 'disabled' : ''}>
                            <label class="form-check-label">
                                <span class="badge bg-${(user.users_status === 'active') ? 'success' : 'secondary'}">
                                    ${(user.users_status === 'active') ? 'ใช้งาน' : 'ปิดใช้งาน'}
                                </span>
                            </label>
                        </div>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="userManager.viewUser(${user.users_id})">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning" onclick="userManager.editUser(${user.users_id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            ${user.users_id !== window.authUserId ? `
                                <button class="btn btn-sm btn-outline-danger" onclick="userManager.confirmDeleteUser(${user.users_id}, '${userName}')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            ` : ''}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Bind status toggle events
        this.bindStatusToggles();
    }

    bindStatusToggles() {
        document.querySelectorAll('.status-toggle').forEach(toggle => {
            toggle.addEventListener('change', async (e) => {
                const userId = e.target.dataset.userId;
                const isChecked = e.target.checked;
                
                try {
                    const response = await fetch(`/api/users/${userId}/toggle-status`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        const badge = e.target.nextElementSibling.querySelector('.badge');
                        const isActive = !!data.is_active || data.status === 'active';
                        badge.className = `badge bg-${isActive ? 'success' : 'secondary'}`;
                        badge.textContent = isActive ? 'ใช้งาน' : 'ปิดใช้งาน';
                        
                        this.showSuccess(data.message);
                    } else {
                        e.target.checked = !isChecked;
                        this.showError(data.message);
                    }
                } catch (error) {
                    e.target.checked = !isChecked;
                    this.showError('ไม่สามารถเปลี่ยนสถานะได้');
                }
            });
        });
    }

    renderPagination(paginationData) {
        const pagination = document.getElementById('usersPagination');
        if (!pagination) return;

        if (paginationData.last_page <= 1) {
            pagination.innerHTML = '';
            return;
        }

        let html = '<ul class="pagination mb-0">';
        
        // Previous button
        html += `
            <li class="page-item ${paginationData.current_page === 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="userManager.loadUsers(${paginationData.current_page - 1})" ${paginationData.current_page === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>
            </li>
        `;

        // Page numbers
        const start = Math.max(1, paginationData.current_page - 2);
        const end = Math.min(paginationData.last_page, paginationData.current_page + 2);

        for (let i = start; i <= end; i++) {
            html += `
                <li class="page-item ${i === paginationData.current_page ? 'active' : ''}">
                    <button class="page-link" onclick="userManager.loadUsers(${i})">${i}</button>
                </li>
            `;
        }

        // Next button
        html += `
            <li class="page-item ${paginationData.current_page === paginationData.last_page ? 'disabled' : ''}">
                <button class="page-link" onclick="userManager.loadUsers(${paginationData.current_page + 1})" ${paginationData.current_page === paginationData.last_page ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </li>
        `;

        html += '</ul>';
        pagination.innerHTML = html;
    }

    updateStats(stats) {
        // Debug log (สามารถลบได้ภายหลัง)
        // Enhanced stats update with more detailed information
        if (document.getElementById('totalUsersCount')) {
            document.getElementById('totalUsersCount').textContent = stats?.total || 0;
        }
        if (document.getElementById('studentsUserCount')) {
            document.getElementById('studentsUserCount').textContent = stats?.students || 0;
        }
        if (document.getElementById('teachersUserCount')) {
            document.getElementById('teachersUserCount').textContent = stats?.teachers || 0;
        }
        if (document.getElementById('guardiansUserCount')) {
            document.getElementById('guardiansUserCount').textContent = stats?.guardians || 0;
        }
        
        // New enhanced stats
        if (document.getElementById('activeUsersCount')) {
            document.getElementById('activeUsersCount').textContent = `${stats?.active || 0} คนใช้งาน`;
        }
        if (document.getElementById('avgStudentScore')) {
            const avgScore = stats?.avgStudentScore ? parseFloat(stats.avgStudentScore).toFixed(1) : '-';
            document.getElementById('avgStudentScore').textContent = `คะแนนเฉลี่ย: ${avgScore}`;
        }
        if (document.getElementById('homeroomTeacherCount')) {
            const value = (stats && (stats.homeroomTeachers !== undefined && stats.homeroomTeachers !== null)) ? stats.homeroomTeachers : 0;
            document.getElementById('homeroomTeacherCount').textContent = `${value} ครูประจำชั้น`;
        }
        if (document.getElementById('linkedStudentsCount')) {
            document.getElementById('linkedStudentsCount').textContent = `${stats?.linkedStudents || 0} นักเรียนที่เชื่อมโยง`;
        }
        if (document.getElementById('userCountBadge')) {
            document.getElementById('userCountBadge').textContent = stats?.total || 0;
        }
    }

    performSearch() {
        const searchInput = document.getElementById('userSearchInput');
        if (searchInput) {
            this.currentFilters.search = searchInput.value.trim();
            this.currentPage = 1;
            this.loadUsers(1);
        }
    }

    applyFilters() {
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        const classroomFilter = document.getElementById('classroomFilter');

        this.currentFilters = {};

        if (roleFilter && roleFilter.value) {
            this.currentFilters.role = roleFilter.value;
        }

        if (statusFilter && statusFilter.value !== '') {
            this.currentFilters.status = statusFilter.value;
        }

        if (classroomFilter && classroomFilter.value) {
            this.currentFilters.classroom = classroomFilter.value;
        }

        const searchInput = document.getElementById('userSearchInput');
        if (searchInput && searchInput.value.trim()) {
            this.currentFilters.search = searchInput.value.trim();
        }

        this.currentPage = 1;
        this.loadUsers(1);
    }

    clearFilters() {
        document.getElementById('roleFilter').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('classroomFilter').value = '';
        document.getElementById('userSearchInput').value = '';
        
        this.currentFilters = {};
        this.currentPage = 1;
        this.loadUsers(1);
    }

    async viewUser(userId) {
        this.currentUserId = userId;
        await this.loadUserDetails(userId);
        this.showSlider('view');
    }

    async editUser(userId) {
        this.currentUserId = userId;
        await this.loadUserDetails(userId);
        this.showSlider('edit');
    }

    async loadUserDetails(userId) {
    const loadingDiv = document.getElementById('userDetailLoading');
    const errorDiv = document.getElementById('userDetailError');
    const viewDiv = document.getElementById('userDetailView');
    const editDiv = document.getElementById('userDetailEdit');

    const wasViewVisible = viewDiv.style.display !== 'none';
    const wasEditVisible = editDiv.style.display !== 'none';

        // Reset states
        loadingDiv.style.display = 'block';
        errorDiv.style.display = 'none';
        viewDiv.style.display = 'none';
        editDiv.style.display = 'none';

        try {
            const response = await fetch(`/api/users/${userId}`);
            const data = await response.json();

            loadingDiv.style.display = 'none';

            if (data.success) {
                this.currentUser = data.user;
                await this.populateUserDetails();
                if (wasEditVisible) {
                    this.switchToEditMode();
                } else if (wasViewVisible) {
                    this.switchToViewMode();
                }
            } else {
                throw new Error(data.message || 'Failed to load user details');
            }
        } catch (error) {
            loadingDiv.style.display = 'none';
            errorDiv.style.display = 'block';
            document.getElementById('userDetailErrorMessage').textContent = error.message || 'เกิดข้อผิดพลาดในการโหลดข้อมูล';
        }
    }

    async populateUserDetails() {
        if (!this.currentUser) return;

        const user = this.currentUser;
        const userName = `${user.users_first_name || ''} ${user.users_last_name || ''}`.trim();
        const avatarUrl = user.users_profile_image 
            ? `/storage/${user.users_profile_image}`
            : `https://ui-avatars.com/api/?name=${encodeURIComponent(userName)}&background=667eea&color=fff`;

        // Enhanced view mode population
        const userAvatarElements = document.querySelectorAll('#userAvatar');
        userAvatarElements.forEach(el => el.src = avatarUrl);
        
        const userFullNameElements = document.querySelectorAll('#userFullName, #userFullNameDisplay');
        userFullNameElements.forEach(el => el.textContent = userName);

        // Enhanced email and phone display
        const emailElements = document.querySelectorAll('#userEmail, #userEmailDisplay');
        emailElements.forEach(el => el.textContent = user.users_email || '-');
        
        const phoneElements = document.querySelectorAll('#userPhone, #userPhoneDisplay');
        phoneElements.forEach(el => el.textContent = user.users_phone_number || '-');

        // Enhanced birthdate display
        if (document.getElementById('userBirthdate')) {
            const birthdate = user.users_birthdate ? new Date(user.users_birthdate).toLocaleDateString('th-TH') : '-';
            document.getElementById('userBirthdate').textContent = birthdate;
        }

        // Enhanced join date display
        if (document.getElementById('userJoinDate')) {
            const joinDate = user.users_created_at ? new Date(user.users_created_at).toLocaleDateString('th-TH') : '-';
            document.getElementById('userJoinDate').textContent = joinDate;
        }

        const roleNames = {
            'admin': '👨‍💼 ผู้ดูแลระบบ',
            'teacher': '👩‍🏫 ครู',
            'student': '🎓 นักเรียน',
            'guardian': '👪 ผู้ปกครอง'
        };

        const roleColors = {
            'admin': 'role-admin',
            'teacher': 'role-teacher',
            'student': 'role-student',
            'guardian': 'role-guardian'
        };

        const statusNames = {
            'active': '🟢 ใช้งาน',
            'inactive': '🔴 ปิดใช้งาน',
            'suspended': '⏸️ ถูกพัก'
        };

        const statusColors = {
            'active': 'status-active',
            'inactive': 'status-inactive',
            'suspended': 'status-suspended'
        };

        // Enhanced role and status badges
        const roleBadgeElements = document.querySelectorAll('#userRoleBadge');
        roleBadgeElements.forEach(el => {
            el.className = `badge ${roleColors[user.users_role] || 'bg-secondary'} text-white`;
            el.textContent = roleNames[user.users_role] || user.users_role;
        });

        const statusBadgeElements = document.querySelectorAll('#userStatus, #userStatusBadge');
        statusBadgeElements.forEach(el => {
            el.innerHTML = `<span class="badge ${statusColors[user.users_status] || 'bg-secondary'} text-white">${statusNames[user.users_status] || user.users_status}</span>`;
        });

        // Enhanced status icon
        if (document.getElementById('userStatusIcon')) {
            const statusIcon = document.getElementById('userStatusIcon');
            statusIcon.className = `badge rounded-circle ${statusColors[user.users_status] || 'bg-secondary'}`;
        }

        // Hide all role-specific details first
        document.getElementById('studentDetails').style.display = 'none';
        document.getElementById('teacherDetails').style.display = 'none';
        document.getElementById('guardianDetails').style.display = 'none';

        // Enhanced role-specific details display
        if (user.users_role === 'student' && user.student) {
            document.getElementById('studentDetails').style.display = 'block';
            document.getElementById('studentCode').textContent = user.student.students_student_code || '-';
            
            if (user.student.classroom) {
                document.getElementById('studentClassroom').textContent = 
                    `${user.student.classroom.classes_level}/${user.student.classroom.classes_room_number}`;
            } else {
                document.getElementById('studentClassroom').textContent = '-';
            }
            
            // Enhanced student information
            const score = user.student.students_current_score || 100;
            document.getElementById('studentScore').textContent = `${score}`;
            
            const genderNames = { 'male': 'ชาย', 'female': 'หญิง', 'other': 'อื่นๆ' };
            document.getElementById('studentGender').textContent = genderNames[user.student.students_gender] || '-';
            
            const statusNames = { 'active': '🟢 กำลังศึกษา', 'suspended': '⏸️ พักการศึกษา', 'expelled': '🔴 ออกจากการศึกษา', 'graduate': '🎓 จบการศึกษา', 'transferred': '🏫 ย้ายสถานศึกษา' };
            document.getElementById('studentStatus').textContent = statusNames[user.student.students_status] || '-';
            
            document.getElementById('studentAcademicYear').textContent = user.student.students_academic_year || '-';
            
        } else if (user.users_role === 'teacher' && user.teacher) {
            document.getElementById('teacherDetails').style.display = 'block';
            document.getElementById('teacherEmployeeId').textContent = user.teacher.teachers_employee_code || '-';
            document.getElementById('teacherPosition').textContent = user.teacher.teachers_position || '-';
            document.getElementById('teacherDepartment').textContent = user.teacher.teachers_department || '-';
            document.getElementById('teacherMajor').textContent = user.teacher.teachers_major || '-';
            
            // Assigned class information
            if (user.teacher.assigned_class && user.teacher.assigned_class.classes_level) {
                document.getElementById('teacherAssignedClass').textContent = 
                    `${user.teacher.assigned_class.classes_level}/${user.teacher.assigned_class.classes_room_number}`;
            } else {
                document.getElementById('teacherAssignedClass').textContent = '-';
            }
            
            // Homeroom teacher status
            const isHomeroom = user.teacher.teachers_is_homeroom_teacher;
            document.getElementById('teacherHomeroomStatus').innerHTML = 
                `<span class="badge bg-${isHomeroom ? 'success' : 'secondary'}">${isHomeroom ? '⭐ ครูประจำชั้น' : 'ครูทั่วไป'}</span>`;
                
        } else if (user.users_role === 'guardian' && user.guardian) {
            document.getElementById('guardianDetails').style.display = 'block';
            // Guardian birthdate (from users_birthdate)
            const gBirth = user.users_birthdate ? new Date(user.users_birthdate).toLocaleDateString('th-TH') : '-';
            const gBirthEl = document.getElementById('guardianBirthdate');
            if (gBirthEl) gBirthEl.textContent = gBirth;
            document.getElementById('guardianRelationship').textContent = user.guardian.guardians_relationship_to_student || '-';
            document.getElementById('guardianPhone').textContent = user.guardian.guardians_phone || '-';
            document.getElementById('guardianEmail').textContent = user.guardian.guardians_email || '-';
            document.getElementById('guardianLineId').textContent = user.guardian.guardians_line_id || '-';
            
            const contactMethods = { 'phone': '📞 โทรศัพท์', 'email': '📧 อีเมล', 'line': '💬 LINE' };
            document.getElementById('guardianPreferredContact').textContent = 
                contactMethods[user.guardian.guardians_preferred_contact_method] || '-';
            
            // Display linked students
            if (user.guardian.students && user.guardian.students.length > 0) {
                document.getElementById('guardianStudentsCount').innerHTML = 
                    `<span class="badge bg-success">${user.guardian.students.length} คน</span>`;
                document.getElementById('guardianLinkedStudentsDisplay').style.display = 'block';
                
                const studentsList = document.getElementById('guardianStudentsList');
                studentsList.innerHTML = user.guardian.students.map(student => `
                    <div class="col-md-6">
                        <div class="card border-0 bg-light">
                            <div class="card-body py-2">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-graduation-cap text-success me-2"></i>
                                    <div>
                                        <div class="fw-semibold">${student.user.users_first_name} ${student.user.users_last_name}</div>
                                        <small class="text-muted">${student.students_student_code}</small>
                                        ${student.classroom ? `<br><small class="text-muted">${student.classroom.classes_level}/${student.classroom.classes_room_number}</small>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            } else {
                document.getElementById('guardianStudentsCount').innerHTML = '<span class="badge bg-secondary">0 คน</span>';
                document.getElementById('guardianLinkedStudentsDisplay').style.display = 'none';
            }
        }

        // Populate edit mode
        document.getElementById('editUserId').value = user.users_id;
        document.getElementById('editUserFirstName').value = user.users_first_name || '';
        document.getElementById('editUserLastName').value = user.users_last_name || '';
        // Username field no longer supported (container may not be .mb-3 anymore)
        const usernameEl = document.getElementById('editUserUsernameField');
        if (usernameEl) {
            const wrap = usernameEl.closest('.mb-3') || usernameEl.closest('[class*="col-"]') || usernameEl.parentElement;
            if (wrap) wrap.style.display = 'none';
            else usernameEl.style.display = 'none';
        }
        document.getElementById('editUserEmailField').value = user.users_email || '';
    if (document.getElementById('editUserPhone')) document.getElementById('editUserPhone').value = user.users_phone_number || '';
    if (document.getElementById('editUserBirthdate')) document.getElementById('editUserBirthdate').value = this.formatDateForInput(user.users_birthdate);
        document.getElementById('editUserActive').checked = (user.users_status === 'active');

    // Hide all role-specific edit fields first
        document.getElementById('editStudentFields').style.display = 'none';
        document.getElementById('editTeacherFields').style.display = 'none';
    document.getElementById('editGuardianFields').style.display = 'none';

        // Show role-specific edit fields
        if (user.users_role === 'student' && user.student) {
            document.getElementById('editStudentFields').style.display = 'block';
            document.getElementById('editStudentCode').value = user.student.students_student_code || '';
            // Ensure classrooms are loaded and options exist before setting value
            if (!this.classrooms || this.classrooms.length === 0) {
                await this.loadClassrooms();
            } else {
                // repopulate select if empty
                const s = document.getElementById('editStudentClassroom');
                if (s && s.options.length <= 1) this.populateClassroomSelect();
            }
            const classSelect = document.getElementById('editStudentClassroom');
            if (classSelect) {
                const desired = user.student.class_id ? String(user.student.class_id) : '';
                // If options not yet populated, repopulate and then set value
                if (classSelect.options.length <= 1 && this.classrooms && this.classrooms.length) {
                    this.populateClassroomSelect();
                }
                classSelect.value = desired;
            }
            if (document.getElementById('editStudentGender')) document.getElementById('editStudentGender').value = user.student.students_gender || '';
            if (document.getElementById('editStudentStatus')) document.getElementById('editStudentStatus').value = user.student.students_status || 'active';
            if (document.getElementById('editStudentScore')) document.getElementById('editStudentScore').value = user.student.students_current_score ?? '';
        } else if (user.users_role === 'teacher' && user.teacher) {
            document.getElementById('editTeacherFields').style.display = 'block';
            document.getElementById('editTeacherEmployeeId').value = user.teacher.teachers_employee_code || '';
            document.getElementById('editTeacherPosition').value = user.teacher.teachers_position || '';
            document.getElementById('editTeacherDepartment').value = user.teacher.teachers_department || '';
            if (document.getElementById('editTeacherMajor')) document.getElementById('editTeacherMajor').value = user.teacher.teachers_major || '';
            // Ensure classrooms for teacher select
            if (!this.classrooms || this.classrooms.length === 0) {
                await this.loadClassrooms();
            } else {
                const ts = document.getElementById('editTeacherAssignedClass');
                if (ts && ts.options.length <= 1) this.populateClassroomSelect();
            }
            if (document.getElementById('editTeacherAssignedClass')) document.getElementById('editTeacherAssignedClass').value = user.teacher.assigned_class_id ? String(user.teacher.assigned_class_id) : '';
            if (document.getElementById('editTeacherIsHomeroom')) document.getElementById('editTeacherIsHomeroom').checked = !!user.teacher.teachers_is_homeroom_teacher;
        } else if (user.users_role === 'guardian') {
            document.getElementById('editGuardianFields').style.display = 'block';
            if (document.getElementById('editGuardianRelationship')) document.getElementById('editGuardianRelationship').value = (user.guardian?.guardians_relationship_to_student) || '';
            if (document.getElementById('editGuardianEmail')) document.getElementById('editGuardianEmail').value = (user.guardian?.guardians_email) || '';
            if (document.getElementById('editGuardianPhone')) document.getElementById('editGuardianPhone').value = (user.guardian?.guardians_phone) || '';
            if (document.getElementById('editGuardianLineId')) document.getElementById('editGuardianLineId').value = (user.guardian?.guardians_line_id) || '';
            if (document.getElementById('editGuardianPreferredContact')) document.getElementById('editGuardianPreferredContact').value = (user.guardian?.guardians_preferred_contact_method) || '';
            this.initGuardianStudentPicker();
        }

        // Handle delete button visibility
        const deleteBtn = document.getElementById('deleteUserBtn');
        if (deleteBtn) {
            deleteBtn.style.display = user.users_id === window.authUserId ? 'none' : 'block';
        }
    }

    async initGuardianStudentPicker() {
        const userId = this.currentUser.users_id;
        const tokens = document.getElementById('guardianLinkedStudents');
        const input = document.getElementById('guardianStudentSearch');
        const dd = document.getElementById('guardianStudentDropdown');
        if (!tokens || !input || !dd) return;

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        const renderLinked = async () => {
            const r = await fetch(`/api/users/${userId}/guardian/students`);
            const j = await r.json();
            tokens.innerHTML = '';
            if (!j.success) return;
            j.data.forEach(s => {
                const el = document.createElement('span');
                el.className = 'badge bg-light text-dark border d-inline-flex align-items-center';
                el.style.gap = '6px'; el.style.padding = '8px 10px';
                el.innerHTML = `<i class="fas fa-user-graduate text-primary"></i>${s.code || '-'} · ${s.name || '-'} · ${s.class || '-'} <button class="btn btn-sm btn-link text-danger p-0 ms-1" title="ลบ"><i class="fas fa-times"></i></button>`;
                el.querySelector('button').addEventListener('click', async () => {
                    await fetch(`/api/users/${userId}/guardian/students/${s.id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrf } });
                    renderLinked();
                });
                tokens.appendChild(el);
            });
        };

        const search = async (q) => {
            if (!q) { dd.style.display = 'none'; dd.innerHTML=''; return; }
            const r = await fetch(`/api/users/${userId}/guardian/students/search?q=${encodeURIComponent(q)}`);
            const j = await r.json();
            dd.innerHTML='';
            if (!j.success || j.data.length===0){ dd.style.display='none'; return; }
            j.data.forEach(s => {
                const a = document.createElement('a');
                a.href = 'javascript:void(0)';
                a.className = 'list-group-item list-group-item-action';
                a.innerHTML = `<div class="d-flex justify-content-between"><div><strong>${s.code || '-'}</strong> · ${s.name || '-'}</div><small class="text-muted">${s.class || '-'}</small></div>`;
                a.addEventListener('click', async () => {
                    await fetch(`/api/users/${userId}/guardian/students`, {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ student_id: s.id })
                    });
                    input.value=''; dd.style.display='none'; renderLinked();
                });
                dd.appendChild(a);
            });
            dd.style.display='block';
        };

        let timer = null;
        input.oninput = (e) => { clearTimeout(timer); timer = setTimeout(() => search(e.target.value.trim()), 200); };
        document.addEventListener('click', (e)=>{ if(!dd.contains(e.target) && e.target!==input) dd.style.display='none'; });

        renderLinked();
    }

    showSlider(mode = 'view') {
        const modalEl = document.getElementById('userDetailSlider');
        const title = document.getElementById('userDetailSliderLabel');
        
        if (mode === 'view') {
            title.textContent = 'ข้อมูลผู้ใช้';
            this.switchToViewMode();
        } else {
            title.textContent = 'แก้ไขข้อมูลผู้ใช้';
            this.switchToEditMode();
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }

    switchToViewMode() {
        document.getElementById('userDetailView').style.display = 'block';
        document.getElementById('userDetailEdit').style.display = 'none';
        document.getElementById('userDetailSliderLabel').textContent = 'ข้อมูลผู้ใช้';
    }

    switchToEditMode() {
        document.getElementById('userDetailView').style.display = 'none';
        document.getElementById('userDetailEdit').style.display = 'block';
        document.getElementById('userDetailSliderLabel').textContent = 'แก้ไขข้อมูลผู้ใช้';
    }

    async saveUser() {
        const form = document.getElementById('userEditForm');
        const formData = new FormData(form);
        const userId = document.getElementById('editUserId').value;

        // Convert FormData to object
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        // Clean up empty strings for nullable fields to avoid validation errors
        const nullableFields = [
            'students_academic_year', 
            'students_gender', 
            'students_current_score',
            'teachers_employee_code',
            'teachers_position',
            'teachers_department',
            'teachers_major',
            'assigned_class_id'
        ];

        nullableFields.forEach(field => {
            if (data[field] === '') {
                data[field] = null;
            }
        });

        // Handle checkbox
        data.users_active = document.getElementById('editUserActive').checked ? 1 : 0;
        // Normalize booleans
        // จัดการสถานะครูประจำชั้น: ถ้าเลือก assigned_class_id และติ๊ก homeroom -> ตั้งค่า 1, ถ้าไม่ได้ติ๊ก -> 0
        if (data.hasOwnProperty('teachers_is_homeroom_teacher')) {
            const homeroomChecked = document.getElementById('editTeacherIsHomeroom')?.checked;
            const assignedClassVal = document.getElementById('editTeacherAssignedClass')?.value;
            data.teachers_is_homeroom_teacher = (homeroomChecked && assignedClassVal) ? 1 : 0;
        }

        // ถ้าติ๊กเป็นครูประจำชั้น แต่ไม่ได้เลือกชั้น ให้แจ้งและหยุด
        if (document.getElementById('editTeacherIsHomeroom')?.checked && !document.getElementById('editTeacherAssignedClass')?.value) {
            this.showError('กรุณาเลือกชั้นเรียนสำหรับครูประจำชั้น');
            return;
        }
        // Align field names with backend
        if (data.classes_id) {
            data.class_id = data.classes_id;
            delete data.classes_id;
        }
        if (data.teachers_employee_id) {
            data.teachers_employee_code = data.teachers_employee_id;
            delete data.teachers_employee_id;
        }

        try {
            const response = await fetch(`/api/users/${userId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccess(result.message);
                
                // Reload users list in background
                this.loadUsers(this.currentPage);

                // Reload user details to reflect changes without closing modal
                await this.loadUserDetails(userId);
            } else {
                console.error('Save user error:', result);
                if (result.errors) {
                    let errorMessages = '';
                    Object.keys(result.errors).forEach(key => {
                        errorMessages += result.errors[key].join('\n') + '\n';
                    });
                    this.showError(errorMessages);
                } else {
                    this.showError(result.message);
                }
            }
        } catch (error) {
            this.showError('ไม่สามารถบันทึกข้อมูลได้');
        }
    }

    confirmDeleteUser(userId, userName) {
        if (userId === window.authUserId) {
            this.showError('ไม่สามารถลบบัญชีของตัวเองได้');
            return;
        }

        Swal.fire({
            title: 'ยืนยันการลบ',
            text: `คุณต้องการลบผู้ใช้ "${userName}" หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบ',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                this.deleteUser(userId);
            }
        });
    }

    async deleteUser(userId) {
        try {
            const response = await fetch(`/api/users/${userId}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showSuccess(data.message);
                
                // Close modal if open
                const modal = bootstrap.Modal.getInstance(document.getElementById('userDetailSlider'));
                if (modal) modal.hide();
                
                this.loadUsers(this.currentPage);
            } else {
                this.showError(data.message);
            }
        } catch (error) {
            this.showError('ไม่สามารถลบผู้ใช้ได้');
        }
    }

    // Enhanced user management functions
    async toggleUserStatus() {
        if (!this.currentUser) return;
        
        const user = this.currentUser;
    const currentStatus = user.users_status;
    const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        const result = await Swal.fire({
            title: 'เปลี่ยนสถานะผู้ใช้?',
            text: `ต้องการเปลี่ยนสถานะจาก "${currentStatus === 'active' ? 'ใช้งาน' : 'ปิดใช้งาน'}" เป็น "${newStatus === 'active' ? 'ใช้งาน' : 'ปิดใช้งาน'}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`/api/users/${user.users_id}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    const successMessage = data.message || 'เปลี่ยนสถานะเรียบร้อยแล้ว';
                    this.showSuccess(successMessage);
                    this.loadUserDetails(user.users_id);
                    this.loadUsers(this.currentPage);
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                this.showError('ไม่สามารถเปลี่ยนสถานะได้');
            }
        }
    }

    async resetUserPassword() {
        if (!this.currentUser) return;
        
        const user = this.currentUser;
        const result = await Swal.fire({
            title: 'รีเซ็ตรหัสผ่าน?',
            text: `ต้องการรีเซ็ตรหัสผ่านของ "${user.users_first_name} ${user.users_last_name}"?\nรหัสผ่านใหม่จะถูกตั้งใหม่`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'รีเซ็ต',
            cancelButtonText: 'ยกเลิก'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch(`/api/users/${user.users_id}/reset-password`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                const data = await response.json();
                if (data.success) {
                    const newPassword = data.new_password || '123456789';

                    Swal.fire({
                        icon: 'success',
                        title: 'รีเซ็ตรหัสผ่านสำเร็จ',
                        html: `
                            <p class="mb-2">แจ้งรหัสผ่านใหม่ให้นักเรียนด้วยตนเอง</p>
                            <div class="py-3 px-4 bg-light rounded border fw-bold fs-4" style="letter-spacing: 2px;">${newPassword}</div>
                            <small class="text-muted d-block mt-3">ระบบได้คัดลอกรหัสผ่านใหม่นี้ไว้ในคลิปบอร์ด (หากอุปกรณ์รองรับ)</small>
                        `,
                        confirmButtonText: 'รับทราบ'
                    });

                    if (navigator.clipboard?.writeText) {
                        navigator.clipboard.writeText(newPassword).catch(() => {});
                    }
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                this.showError('ไม่สามารถรีเซ็ตรหัสผ่านได้');
            }
        }
    }

    // export functionality removed per requirements

    // Enhanced filter functions
    showUserFilter() {
        const filterBar = document.getElementById('userFilterBar');
        const toggleBtn = document.getElementById('filterToggleBtn');
        
        if (filterBar.style.display === 'none' || !filterBar.style.display) {
            filterBar.style.display = 'block';
            toggleBtn.classList.add('active');
        } else {
            filterBar.style.display = 'none';
            toggleBtn.classList.remove('active');
        }
    }

    clearUserFilters() {
        // Clear all filter inputs
        document.getElementById('roleFilter').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('classroomFilter').value = '';
        if (document.getElementById('dateFromFilter')) document.getElementById('dateFromFilter').value = '';
        if (document.getElementById('dateToFilter')) document.getElementById('dateToFilter').value = '';
        
        // Clear search
        document.getElementById('userSearchInput').value = '';
        
        // Reset filters and reload
        this.currentFilters = {};
        this.currentPage = 1;
        this.loadUsers(1);
    }

    applyUserFilters() {
        const roleFilter = document.getElementById('roleFilter');
        const statusFilter = document.getElementById('statusFilter');
        const classroomFilter = document.getElementById('classroomFilter');
        const dateFromFilter = document.getElementById('dateFromFilter');
        const dateToFilter = document.getElementById('dateToFilter');

        this.currentFilters = {
            ...this.currentFilters,
            role: roleFilter?.value || '',
            status: statusFilter?.value || '',
            classroom: classroomFilter?.value || '',
            date_from: dateFromFilter?.value || '',
            date_to: dateToFilter?.value || ''
        };

        this.currentPage = 1;
        this.loadUsers(1);
    }

    showError(message) {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: message
        });
    }

    showSuccess(message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: 'สำเร็จ',
            text: message,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
    }
}

// Global functions for user management
let userManager;

// Global functions accessible from HTML
function showUserManagement() {
    if (!userManager) {
        userManager = new UserManagement();
    }
    userManager.loadUsers();
}

function showUserFilter() {
    if (userManager) {
        userManager.showUserFilter();
    }
}

function applyUserFilters() {
    if (userManager) {
        userManager.applyUserFilters();
    }
}

function clearUserFilters() {
    if (userManager) {
        userManager.clearUserFilters();
    }
}

// exportUserData removed

function switchToEditMode() {
    if (userManager) {
        userManager.switchToEditMode();
    }
}

function switchToViewMode() {
    if (userManager) {
        userManager.switchToViewMode();
    }
}

function toggleUserStatus() {
    if (userManager) {
        userManager.toggleUserStatus();
    }
}

function resetUserPassword() {
    if (userManager) {
        userManager.resetUserPassword();
    }
}

function confirmDeleteUser() {
    if (userManager) {
        userManager.confirmDeleteUser();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    userManager = new UserManagement();
});
window.showUserManagement = function() {
    if (!window.userManager) {
        window.userManager = new UserManagement();
    }
    window.userManager.loadUsers(1);
};

window.hideUserManagement = function() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('userManagementModal'))
                 || bootstrap.Modal.getOrCreateInstance(document.getElementById('userManagementModal'));
    modal.hide();
};

window.showUserFilter = function() {
    const filterBar = document.getElementById('userFilterBar');
    if (filterBar) {
        filterBar.style.display = filterBar.style.display === 'none' ? 'block' : 'none';
    }
};

window.applyUserFilters = function() {
    if (window.userManager) {
        window.userManager.applyFilters();
    }
};

window.clearUserFilters = function() {
    if (window.userManager) {
        window.userManager.clearFilters();
    }
};

window.switchToEditMode = function() {
    if (window.userManager) {
        window.userManager.switchToEditMode();
    }
};

window.switchToViewMode = function() {
    if (window.userManager) {
        window.userManager.switchToViewMode();
    }
};

window.confirmDeleteUser = function() {
    if (window.userManager && window.userManager.currentUser) {
        window.userManager.confirmDeleteUser(
            window.userManager.currentUser.users_id,
            (window.userManager.currentUser.users_name_prefix || '') + 
            (window.userManager.currentUser.users_first_name || '') + ' ' + 
            (window.userManager.currentUser.users_last_name || '')
        );
    }
};

// Store auth user ID for comparisons
// This will be set from the blade template
