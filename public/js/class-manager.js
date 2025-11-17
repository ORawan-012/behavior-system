// Class Manager (Tabbed) - Minimal, syntax-clean implementation
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    const modal = document.getElementById('classManagementModal');
    if (!modal) return;

    // Tabs
    const tabs = {
      list: document.getElementById('classroom-list-tab'),
      detail: document.getElementById('classroom-detail-tab'),
      form: document.getElementById('classroom-form-tab')
    };

    // List
    const grid = document.getElementById('classroomGrid');
    const pagination = document.querySelector('#classroomList nav ul');
    const searchInput = document.getElementById('classroomSearch');
    const levelFilter = document.getElementById('filterLevel');
  // Removed add-class button per requirement

    // Detail
    const dName = document.getElementById('detail-classroom-name');
    const dTeacher = document.getElementById('detail-teacher-name');
    const dStudentCount = document.getElementById('detail-student-count');
    const dAvgScore = document.getElementById('detail-avg-score');
    const dStudentsBody = document.getElementById('detail-students-list');
    const dStudentsPagination = document.getElementById('detail-student-pagination');
    const btnEditFromDetail = document.getElementById('btnEditClassFromDetail');
    const btnDeleteFromDetail = document.getElementById('btnDeleteClassFromDetail');

    // Form
    const form = document.getElementById('formClassroom');
    const fieldId = document.getElementById('classId');
    const fieldLevel = document.getElementById('classes_level');
    const fieldRoom = document.getElementById('classes_room_number');
    const fieldTeacher = document.getElementById('teacher_id');
    const formTitle = document.getElementById('formClassTitle');
    const btnBackToList = document.getElementById('btnBackToList');
    const btnCancelForm = document.getElementById('btnCancelClassForm');

    // Delete
    const deleteModalEl = document.getElementById('deleteClassModal');
    const deleteClassId = document.getElementById('deleteClassId');
    const deleteClassName = document.getElementById('deleteClassName');
    const btnConfirmDelete = document.getElementById('confirmDeleteClass');

    const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    const state = { page: 1, perPage: 12, search: '', level: '', currentClassId: null, studentSearch: '' };

    // API
    const api = {
      list: () => {
        const q = new URLSearchParams();
        q.set('page', String(state.page));
        q.set('perPage', String(state.perPage));
        if (state.search) q.set('search', state.search);
        if (state.level) q.set('level', state.level);
        return fetch('/api/classes?' + q.toString(), { headers: { 'Accept': 'application/json' } });
      },
      detail: (id) => fetch('/api/classes/' + id, { headers: { 'Accept': 'application/json' } }),
      students: (id, page) => {
        const q = new URLSearchParams();
        q.set('page', String(page||1));
        q.set('perPage', '10');
        if (state.studentSearch) q.set('search', state.studentSearch);
        return fetch('/api/classes/' + id + '/students?' + q.toString(), { headers: { 'Accept': 'application/json' } });
      },
      teachers: () => fetch('/api/classes/teachers/all', { headers: { 'Accept': 'application/json' } }),
      getStudent: (id) => fetch('/api/students/' + id, { headers: { 'Accept': 'application/json' } }),
      updateStudent: (id, fd) => { fd.append('_method','PUT'); return fetch('/api/students/' + id, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: fd }); },
  // Remove create endpoint usage (add-class disabled)
      update: (id, fd) => { fd.append('_method','PUT'); return fetch('/api/classes/' + id, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: fd }); },
      remove: (id) => fetch('/api/classes/' + id, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
    };

    // Utils
    function escapeHtml(t){ if(t==null) return ''; return String(t).replace(/[&<>"']/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#039;'}[m]; }); }
    function switchTo(panelId){
      var links = document.querySelectorAll('#classManagementTabs .nav-link');
      Array.prototype.forEach.call(links, function(b){ b.classList.remove('active'); });
      var panes = document.querySelectorAll('#classManagementTabContent .tab-pane');
      Array.prototype.forEach.call(panes, function(p){ p.classList.remove('show','active'); });
      var btn = document.querySelector('#classManagementTabs .nav-link[data-bs-target="#' + panelId + '"]');
      var panel = document.getElementById(panelId);
      if (btn) btn.classList.add('active');
      if (panel) panel.classList.add('show','active');
    }
    function enable(el){ if(el) el.removeAttribute('disabled'); }
    function disable(el){ if(el) el.setAttribute('disabled','disabled'); }

    // List render
    function renderGrid(paged){
      if(!grid) return;
      var items=(paged&&paged.data)||[];
      if(!items.length){ grid.innerHTML='<div class="col-12 text-center text-muted py-4">ไม่มีห้องเรียน</div>'; return; }
      grid.innerHTML = items.map(function(c){
        var id=c.classes_id||c.id;
        var name=escapeHtml(c.classes_level) + '/' + escapeHtml(c.classes_room_number);
        var teacher = (c.teacher && c.teacher.user) ? (escapeHtml(c.teacher.user.users_first_name) + ' ' + escapeHtml(c.teacher.user.users_last_name)) : 'ยังไม่ได้กำหนด';
        return '<div class="col-lg-3 col-md-4 col-sm-6">\
          <div class="card h-100 shadow-sm class-card" data-id="'+id+'">\
            <div class="card-body d-flex flex-column">\
              <div class="d-flex justify-content-between align-items-start mb-2">\
                <div>\
                  <h5 class="mb-1">'+name+'</h5>\
                  <small class="text-muted">ครู: '+teacher+'</small>\
                </div>\
                <button class="btn btn-outline-primary btn-sm view-class-btn" data-id="'+id+'"><i class="fas fa-eye"></i></button>\
              </div>\
            </div>\
          </div>\
        </div>';
      }).join('');
      attachViewHandlers();
      renderPagination(paged);
    }

    function renderPagination(paged){
      if(!pagination) return; pagination.innerHTML='';
      if(!paged||paged.last_page<=1) return;
      var cur=paged.current_page, total=paged.last_page;
      function add(label, page, disabled, active){
        var li=document.createElement('li'); li.className='page-item'+(disabled?' disabled':'')+(active?' active':'');
        var a=document.createElement('a'); a.className='page-link'; a.href='#'; a.textContent=label; a.dataset.page=String(page);
        if(!disabled) a.addEventListener('click', function(e){ e.preventDefault(); state.page=page; loadList(); });
        li.appendChild(a); pagination.appendChild(li);
      }
      add('ก่อนหน้า', Math.max(1,cur-1), cur===1, false);
      for(var i=1;i<=total;i++) add(String(i), i, false, i===cur);
      add('ถัดไป', Math.min(total,cur+1), cur===total, false);
    }

    function attachViewHandlers(){
      var els=document.querySelectorAll('#classroomGrid .view-class-btn, #classroomGrid .class-card');
      Array.prototype.forEach.call(els, function(el){
        el.addEventListener('click', function(e){
          e.preventDefault();
          var id = el.getAttribute('data-id');
          if(!id && el.closest){ var card = el.closest('.class-card'); if(card) id = card.getAttribute('data-id'); }
          if(!id) return; state.currentClassId=id;
          enable(tabs.detail); switchTo('classroom-detail-panel'); loadDetail(id);
        });
      });
    }

    // Detail
    function loadDetail(id){
      api.detail(id).then(function(r){ return r.json(); }).then(function(json){
        if(!json.success) throw new Error(json.message||'โหลดข้อมูลห้องเรียนล้มเหลว');
        var c=json.data;
        var name=escapeHtml(c.classes_level) + '/' + escapeHtml(c.classes_room_number);
        var teacher=(c.teacher && c.teacher.user)? (escapeHtml(c.teacher.user.users_first_name)+' '+escapeHtml(c.teacher.user.users_last_name)) : 'ยังไม่ได้กำหนด';
        if(dName) dName.textContent=name;
        if(dTeacher) dTeacher.textContent='ครูประจำชั้น: ' + teacher;
        loadStudents(id,1);
      }).catch(function(err){ alert(err.message); });
    }

    function loadStudents(id, page){
      api.students(id, page).then(function(r){ return r.json(); }).then(function(json){
        if(!json.success) throw new Error(json.message||'โหลดรายชื่อนักเรียนล้มเหลว');
        var paged=json.data; var items=paged.data||[];
        if(dStudentCount) dStudentCount.textContent=String(paged.total||items.length||0);
        var avg = items.length? Math.round(items.reduce(function(s,st){ return s+(st.students_current_score||0); },0)/items.length):0;
        if(dAvgScore) dAvgScore.textContent=String(avg);
        renderStudents(items);
        renderStudentsPager(paged);
      }).catch(function(err){ alert(err.message); });
    }

    function renderStudents(items){
      if(!dStudentsBody) return;
        // กรองตามบทบาท: ถ้าไม่ใช่ admin ให้แสดงเฉพาะ active หรือ suspended
        var role = (window.authRole || '').toLowerCase();
        var visible = (role === 'admin') ? items : items.filter(function(st){
          return ['active','suspended'].includes((st.students_status||'').toLowerCase());
        });
        if(!visible.length){ dStudentsBody.innerHTML='<tr><td colspan="6" class="text-center text-muted py-4">ไม่มีนักเรียน</td></tr>'; return; }
        var statusLabels = {
          'active': 'กำลังศึกษา',
          'suspended': 'พักการเรียน',
          'expelled': 'พ้นสภาพ/ลาออก',
          'transferred': 'ย้ายสถานศึกษา',
          'graduate': 'จบการศึกษา'
        };
        dStudentsBody.innerHTML = visible.map(function(st,idx){
          var code=escapeHtml(st.students_student_code||'-');
          var first=escapeHtml(st.user && st.user.users_first_name ? st.user.users_first_name : '');
          var last=escapeHtml(st.user && st.user.users_last_name ? st.user.users_last_name : '');
          var score=Number(st.students_current_score||0);
          var studentId = st.students_id || st.id;
          var status=(st.students_status||'').toLowerCase();
          var nameHtml = first + ' ' + last;
          // ถ้าเป็น admin และสถานะไม่ใช่ active หรือ suspended ให้ไฮไลท์เป็นสีแดงและโชว์ badge สถานะ
          if(role === 'admin' && !['active','suspended'].includes(status)){
            nameHtml = '<span class="text-danger fw-semibold">'+nameHtml+'</span> <span class="badge bg-danger ms-1">'+(statusLabels[status]||status)+'</span>';
          }
          return '<tr>\
            <td>'+ (idx+1) +'</td>\
            <td>'+ code +'</td>\
            <td class="text-center">'+ nameHtml +'</td>\
            <td>'+ score +'</td>\
            <td>\
              <div class="btn-group btn-group-sm">\
                <button class="btn btn-outline-primary view-student-btn" data-student-id="'+studentId+'" title="ดู"><i class="fas fa-eye"></i></button>\
                <button class="btn btn-outline-warning edit-student-btn" data-student-id="'+studentId+'" title="แก้ไข"><i class="fas fa-edit"></i></button>\
              </div>\
            </td>\
          </tr>';
        }).join('');
      attachStudentHandlers();
    }

    function renderStudentsPager(paged){
      if(!dStudentsPagination) return; dStudentsPagination.innerHTML='';
      if(!paged||paged.last_page<=1) return;
      var cur=paged.current_page, total=paged.last_page;
      function add(label, page, disabled, active){
        var li=document.createElement('li'); li.className='page-item'+(disabled?' disabled':'')+(active?' active':'');
        var a=document.createElement('a'); a.className='page-link'; a.href='#'; a.textContent=label;
        if(!disabled) a.addEventListener('click', function(e){ e.preventDefault(); loadStudents(state.currentClassId, page); });
        li.appendChild(a); dStudentsPagination.appendChild(li);
      }
      add('ก่อนหน้า', Math.max(1,cur-1), cur===1, false);
      for(var i=1;i<=total;i++) add(String(i), i, false, i===cur);
      add('ถัดไป', Math.min(total,cur+1), cur===total, false);
    }

    // Teachers dropdown
    function populateTeachers(){
      if(!fieldTeacher) return;
      api.teachers().then(function(r){ return r.json(); }).then(function(json){
        if(!json.success) return;
        var arr=json.data||[];
        fieldTeacher.innerHTML = '<option value="" selected disabled>เลือกครูประจำชั้น</option>' +
          arr.map(function(t){ return '<option value="'+t.teachers_id+'">'+escapeHtml(t.users_first_name||'')+' '+escapeHtml(t.users_last_name||'')+'</option>'; }).join('');
      }).catch(function(){});
    }

    function resetForm(){
      if(!form) return; form.reset();
      if(fieldId) fieldId.value='';
      if(formTitle) formTitle.innerHTML='<i class="fas fa-edit me-2 text-primary"></i>แก้ไขห้องเรียน';
      var invalid=form.querySelectorAll('.is-invalid'); Array.prototype.forEach.call(invalid, function(i){ i.classList.remove('is-invalid'); });
      // enable class level & room when creating
      if(fieldLevel) fieldLevel.removeAttribute('disabled');
      if(fieldRoom) fieldRoom.removeAttribute('disabled');
    }
    function fillFormForEdit(cls){
      if(fieldId) fieldId.value=cls.classes_id||cls.id||'';
      if(fieldLevel){ fieldLevel.value=cls.classes_level||''; fieldLevel.setAttribute('disabled','disabled'); }
      if(fieldRoom){ fieldRoom.value=cls.classes_room_number||''; fieldRoom.setAttribute('disabled','disabled'); }
      if(fieldTeacher) fieldTeacher.value=cls.teachers_id||cls.teacher_id||'';
      if(formTitle) formTitle.innerHTML='<i class="fas fa-edit me-2 text-primary"></i>แก้ไขครูประจำชั้น';
    }
    function saveForm(){
      if(!form) return; var fd=new FormData(form); var id=fieldId&&fieldId.value?fieldId.value:null;
      // Creation is disabled. If no id, block and return.
      if(!id){ alert('ไม่อนุญาตให้เพิ่มห้องเรียนใหม่'); return; }

      // ถ้ามีการเลือกครูประจำชั้น ให้ตั้ง flag homeroom สำหรับครูคนนั้น
      if(fieldTeacher && fieldTeacher.value){
        fd.append('set_homeroom_for_teacher', '1');
      }

      var req=api.update(id,fd);
      req.then(function(r){return r.json();}).then(function(json){
        if(!json.success){ if(json.errors){ Object.keys(json.errors).forEach(function(k){ var input=form.querySelector('[name="'+k+'"]'); if(input) input.classList.add('is-invalid'); }); } throw new Error(json.message||'บันทึกไม่สำเร็จ'); }
        disable(tabs.detail); switchTo('classroom-list-panel'); loadList();
      }).catch(function(err){ alert(err.message); });
    }

    function openDelete(id, name){ if(!deleteModalEl) return; if(deleteClassId) deleteClassId.value=id; if(deleteClassName) deleteClassName.textContent=name||''; bootstrap.Modal.getOrCreateInstance(deleteModalEl).show(); }
    function confirmDelete(){ var id=deleteClassId?deleteClassId.value:null; if(!id) return; api.remove(id).then(function(r){return r.json();}).then(function(json){ if(!json.success) throw new Error(json.message||'ลบไม่สำเร็จ'); var bs=bootstrap.Modal.getInstance(deleteModalEl); if(bs) bs.hide(); disable(tabs.detail); switchTo('classroom-list-panel'); loadList(); }).catch(function(err){ alert(err.message); }); }

    // Student handlers
    function attachStudentHandlers(){
      var viewBtns = document.querySelectorAll('.view-student-btn');
      var editBtns = document.querySelectorAll('.edit-student-btn');
      
      Array.prototype.forEach.call(viewBtns, function(btn){
        btn.addEventListener('click', function(e){
          e.preventDefault();
          var studentId = (e.currentTarget || btn).getAttribute('data-student-id');
          window.__lastStudentId = studentId;
          if(studentId) openStudentModal(studentId, false);
        });
      });
      
      Array.prototype.forEach.call(editBtns, function(btn){
        btn.addEventListener('click', function(e){
          e.preventDefault();
          var studentId = (e.currentTarget || btn).getAttribute('data-student-id');
          window.__lastStudentId = studentId;
          if(studentId) openStudentModal(studentId, true);
        });
      });
    }

    // Student modal functions
    function openStudentModal(studentId, editMode){
      api.getStudent(studentId).then(function(r){ return r.json(); }).then(function(json){
        if(!json.success) throw new Error(json.message||'โหลดข้อมูลนักเรียนล้มเหลว');
        var student = json.data || json.student;
        if(editMode){
          // Close any open modal/offcanvas before showing the sidebar
          try{ document.querySelectorAll('.modal.show').forEach(function(m){ var inst=bootstrap.Modal.getInstance(m); if(inst) inst.hide(); }); }catch(e){}
          try{ document.querySelectorAll('.offcanvas.show').forEach(function(o){ var off=bootstrap.Offcanvas.getInstance(o); if(off) off.hide(); }); }catch(e){}
          populateStudentEditModal(student);
          var oc = document.getElementById('studentEditSidebar');
          if(oc){ bootstrap.Offcanvas.getOrCreateInstance(oc).show(); }
        } else {
          // Use existing global loader for student detail modal to ensure consistent UI
          var detailModalEl = document.getElementById('studentDetailModal');
          if(detailModalEl){ detailModalEl.setAttribute('data-student-id', student.students_id || student.id || ''); }
          if(typeof window.loadStudentDetails === 'function'){
            window.loadStudentDetails(student.students_id || student.id);
          }
          // Close any open modal/offcanvas before showing the detail modal
          try{ document.querySelectorAll('.modal.show').forEach(function(m){ var inst=bootstrap.Modal.getInstance(m); if(inst) inst.hide(); }); }catch(e){}
          try{ document.querySelectorAll('.offcanvas.show').forEach(function(o){ var off=bootstrap.Offcanvas.getInstance(o); if(off) off.hide(); }); }catch(e){}
          bootstrap.Modal.getOrCreateInstance(document.getElementById('studentDetailModal')).show();
        }
      }).catch(function(err){ alert(err.message); });
    }

    function populateStudentViewModal(student){
      var user = student.user || {};
      var fullName = (user.users_first_name || '') + ' ' + (user.users_last_name || '');
      
      // Display mode elements
  var el;
  el=document.getElementById('display-student-name'); if(el) el.textContent = fullName.trim() || '-';
  el=document.getElementById('display-student-code'); if(el) el.textContent = student.students_student_code || '-';
  el=document.getElementById('display-first-name'); if(el) el.textContent = user.users_first_name || '-';
  el=document.getElementById('display-last-name'); if(el) el.textContent = user.users_last_name || '-';
  el=document.getElementById('display-email'); if(el) el.textContent = user.users_email || '-';
  el=document.getElementById('display-phone'); if(el) el.textContent = user.users_phone_number || '-';
  el=document.getElementById('display-score'); if(el) el.textContent = student.students_current_score || '100';
  el=document.getElementById('display-classroom'); if(el) el.textContent = (student.classroom ? student.classroom.classes_level + '/' + student.classroom.classes_room_number : '-');
  // Remember current student id on the modal for later actions (e.g., open edit)
  var detailModalEl = document.getElementById('studentDetailModal');
  if(detailModalEl){ detailModalEl.setAttribute('data-student-id', student.students_id || student.id || ''); }
  window.__lastStudentId = student.students_id || student.id || null;
    }

    function populateStudentEditModal(student){
      var user = student.user || {};
      var fullName = (user.users_first_name || '') + ' ' + (user.users_last_name || '');
      var score = student.students_current_score || 100;
      
      var el;
      el=document.getElementById('se-student-id'); if(el) el.value = student.students_id || student.id || '';
      el=document.getElementById('se-student-code'); if(el) el.value = student.students_student_code || '';
      el=document.getElementById('se-score'); if(el) el.value = score;
      el=document.getElementById('se-first-name'); if(el) el.value = user.users_first_name || '';
      el=document.getElementById('se-last-name'); if(el) el.value = user.users_last_name || '';
      el=document.getElementById('se-email'); if(el) el.value = user.users_email || '';
      el=document.getElementById('se-phone'); if(el) el.value = user.users_phone_number || '';
      
      // Populate header section
      el=document.getElementById('se-student-name-header'); if(el) el.textContent = fullName.trim() || 'นักเรียน';
      el=document.getElementById('se-header-student-code'); if(el) el.textContent = student.students_student_code || '-';
      el=document.getElementById('se-header-score'); if(el) el.textContent = score;
      
      // Update score indicator color
      el=document.getElementById('se-score-indicator'); 
      if(el) {
        el.className = 'fas fa-circle ms-1';
        el.style.fontSize = '0.5rem';
        if(score >= 80) el.classList.add('text-success');
        else if(score >= 60) el.classList.add('text-warning'); 
        else el.classList.add('text-danger');
      }
      
      // Handle status field (admin can edit, teacher sees readonly)
      el=document.getElementById('se-status'); 
      if(el) el.value = student.students_status || 'active';
      
      el=document.getElementById('se-status-readonly'); 
      if(el) {
        var statusText = {
          'active': 'ศึกษาอยู่',
          'suspended': 'พักการเรียน',
          'expelled': 'พ้นสภาพ/ลาออก',
          'transferred': 'ย้ายสถานศึกษา',
          'graduate': 'จบการศึกษา'
        };
        el.value = statusText[student.students_status] || 'ศึกษาอยู่';
      }
    }

    function saveStudentChanges(){
      var form = document.getElementById('studentEditForm');
      var fd = new FormData(form);
      var studentId = document.getElementById('se-student-id').value;
      var studentName = document.getElementById('se-student-name-header').textContent;
      var saveBtn = document.getElementById('btnSaveStudent');
      
      // Disable button to prevent double-click
      if(saveBtn) {
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>กำลังบันทึก...';
      }
      
      // Show SweetAlert confirmation
      Swal.fire({
        title: 'ยืนยันการบันทึกข้อมูล',
        html: `คุณต้องการบันทึกการเปลี่ยนแปลงข้อมูลของ<br><strong>${studentName}</strong> หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-save me-2"></i>บันทึก',
        cancelButtonText: '<i class="fas fa-times me-2"></i>ยกเลิก',
        showLoaderOnConfirm: true,
        preConfirm: () => {
          return api.updateStudent(studentId, fd)
            .then(function(r){ return r.json(); })
            .then(function(json){
              if(!json.success) throw new Error(json.message||'บันทึกข้อมูลไม่สำเร็จ');
              return json;
            })
            .catch(function(err){ 
              Swal.showValidationMessage(`เกิดข้อผิดพลาด: ${err.message}`);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
      }).then((result) => {
        // Re-enable button regardless of result
        if(saveBtn) {
          saveBtn.disabled = false;
          saveBtn.innerHTML = '<i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง';
        }
        
        if (result.isConfirmed) {
          // Close sidebar and reload student list
          var oc=document.getElementById('studentEditSidebar'); 
          if(oc){ var off=bootstrap.Offcanvas.getInstance(oc); if(off) off.hide(); }
          if(state.currentClassId) loadStudents(state.currentClassId, 1);
          
          // Show success message
          Swal.fire({
            title: 'บันทึกสำเร็จ!',
            text: `ข้อมูลของ ${studentName} ได้รับการอัปเดตเรียบร้อยแล้ว`,
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
          });
        }
      });
    }

    // Bind events
    function bind(){
      var navLinks=document.querySelectorAll('#classManagementTabs .nav-link');
      Array.prototype.forEach.call(navLinks, function(btn){
        btn.addEventListener('click', function(e){
          if(btn.hasAttribute('disabled')){ e.preventDefault(); return; }
          var target=btn.getAttribute('data-bs-target'); if(!target) return; e.preventDefault(); switchTo(target.replace('#',''));
        });
      });
  // Add-class disabled: no handler for btnShowAddClass
      if(searchInput){ searchInput.addEventListener('keypress', function(e){ if(e.key==='Enter'){ e.preventDefault(); state.search=searchInput.value.trim(); state.page=1; loadList(); } }); }
      if(levelFilter){ levelFilter.addEventListener('change', function(){ state.level=levelFilter.value; state.page=1; loadList(); }); }
      
      // Student search handlers
      var studentSearchInput = document.getElementById('studentSearchInDetail');
      if(studentSearchInput){
        studentSearchInput.addEventListener('keypress', function(e){
          if(e.key==='Enter'){
            e.preventDefault();
            state.studentSearch = studentSearchInput.value.trim();
            if(state.currentClassId) loadStudents(state.currentClassId, 1);
          }
        });
      }
      
      var btnStudentSearch = document.getElementById('btnStudentSearch');
      if(btnStudentSearch){
        btnStudentSearch.addEventListener('click', function(e){
          e.preventDefault();
          var searchInput = document.getElementById('studentSearchInDetail');
          if(searchInput){
            state.studentSearch = searchInput.value.trim();
            if(state.currentClassId) loadStudents(state.currentClassId, 1);
          }
        });
      }
      
      if(btnEditFromDetail){ btnEditFromDetail.addEventListener('click', function(){ if(!state.currentClassId) return; api.detail(state.currentClassId).then(function(r){return r.json();}).then(function(json){ if(!json.success) throw new Error(json.message||'โหลดข้อมูลไม่สำเร็จ'); populateTeachers(); fillFormForEdit(json.data); enable(tabs.form); switchTo('classroom-form-panel'); }).catch(function(err){ alert(err.message); }); }); }
      if(btnDeleteFromDetail){ btnDeleteFromDetail.addEventListener('click', function(){ if(!state.currentClassId) return; var name=dName?dName.textContent:''; openDelete(state.currentClassId, name); }); }
      if(btnBackToList){ btnBackToList.addEventListener('click', function(){ switchTo('classroom-list-panel'); }); }
      if(btnCancelForm){ btnCancelForm.addEventListener('click', function(){ switchTo('classroom-list-panel'); }); }
  if(form){ form.addEventListener('submit', function(e){ e.preventDefault(); saveForm(); }); }
      if(btnConfirmDelete){ btnConfirmDelete.addEventListener('click', confirmDelete); }

      // Student modal handlers
  var btnEditStudent = document.getElementById('btnEditStudent');
  var btnCancelEdit = document.getElementById('btnCancelEdit');
      var studentEditForm = document.getElementById('studentEditForm');
      if(btnEditStudent){
        btnEditStudent.addEventListener('click', function(){
          var dm = document.getElementById('studentDetailModal');
          var currentId = dm ? dm.getAttribute('data-student-id') : null;
          if(!currentId) currentId = window.__lastStudentId || null;
          if(currentId) openStudentModal(currentId, true);
        });
      }
  if(btnCancelEdit){ btnCancelEdit.addEventListener('click', function(){ var oc=document.getElementById('studentEditSidebar'); if(oc){ var off=bootstrap.Offcanvas.getInstance(oc); if(off) off.hide(); } }); }
      if(studentEditForm){ studentEditForm.addEventListener('submit', function(e){ e.preventDefault(); saveStudentChanges(); }); }

      modal.addEventListener('shown.bs.modal', function(){ disable(tabs.detail); disable(tabs.form); switchTo('classroom-list-panel'); populateTeachers(); loadList(); });
    }

    function loadList(){ if(grid) grid.innerHTML='<div class="col-12 text-center text-muted py-4">กำลังโหลด...</div>'; api.list().then(function(r){return r.json();}).then(function(json){ if(!json.success) throw new Error(json.message||'โหลดรายการล้มเหลว'); renderGrid(json.data); }).catch(function(err){ if(grid) grid.innerHTML='<div class="col-12 text-danger text-center py-4">'+escapeHtml(err.message)+'</div>'; }); }

    bind();
  });
})();