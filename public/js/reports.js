const THAI_MONTHS = {
    1: 'มกราคม',
    2: 'กุมภาพันธ์',
    3: 'มีนาคม',
    4: 'เมษายน',
    5: 'พฤษภาคม',
    6: 'มิถุนายน',
    7: 'กรกฎาคม',
    8: 'สิงหาคม',
    9: 'กันยายน',
    10: 'ตุลาคม',
    11: 'พฤศจิกายน',
    12: 'ธันวาคม'
};

const REPORT_SELECT_PAIRS = [
    { monthId: 'report_month', yearId: 'report_year' },
    { monthId: 'risk_report_month', yearId: 'risk_report_year' },
    { monthId: 'all_data_report_month', yearId: 'all_data_report_year' }
];

const REPORT_CLASS_SELECTS = [
    'report_class_id',
    'risk_report_class_id',
    'all_data_report_class_id'
];

const REPORT_LEVEL_SELECTS = [
    'report_level'
];

const REPORT_ACADEMIC_YEAR_SELECTS = [
    'report_academic_year'
];

const reportFilterState = {
    monthsByYear: {},
    years: [],
    classes: [],
    levels: [],
    academicYears: [],
    readyPromise: null
};

/**
 * ดึงข้อมูลเดือนที่มีรายงานในปีการศึกษาปัจจุบัน
 */
async function fetchAvailableMonths() {
    const response = await fetch('/api/reports/available-months', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!response.ok) {
        throw new Error('ไม่สามารถดึงเดือนที่มีรายงานได้');
    }
    const payload = await response.json();
    if (!payload.success) {
        throw new Error(payload.message || 'ไม่สามารถดึงเดือนที่มีรายงานได้');
    }
    const months = payload.data?.months || [];
    const monthsByYear = {};
    const yearsSet = new Set();

    months.forEach(({ year, month }) => {
        yearsSet.add(year);
        if (!monthsByYear[year]) {
            monthsByYear[year] = [];
        }
        monthsByYear[year].push(month);
    });

    // เรียงข้อมูลล่าสุดก่อน
    const years = Array.from(yearsSet).sort((a, b) => b - a);
    Object.keys(monthsByYear).forEach((year) => {
        monthsByYear[year] = monthsByYear[year].sort((a, b) => b - a);
    });

    const levels = payload.data?.levels || [];
    const academicYears = payload.data?.academic_years || [];
    return { monthsByYear, years, levels, academicYears };
}

/**
 * ดึงข้อมูลชั้นเรียนจากฐานข้อมูล
 */
async function fetchClasses() {
    const response = await fetch('/api/classes/all', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!response.ok) {
        throw new Error('ไม่สามารถดึงข้อมูลชั้นเรียนได้');
    }
    const payload = await response.json();
    if (!payload.success) {
        throw new Error(payload.message || 'ไม่สามารถดึงข้อมูลชั้นเรียนได้');
    }
    return payload.data || [];
}

function setLevelOptions(selectEl, levels) {
    if (!selectEl) return;
    selectEl.innerHTML = '';
    const allOpt = document.createElement('option');
    allOpt.value = '';
    allOpt.textContent = 'ทุกระดับชั้น';
    selectEl.appendChild(allOpt);

    levels.forEach((level) => {
        const option = document.createElement('option');
        option.value = level;
        option.textContent = level;
        selectEl.appendChild(option);
    });
}

function setAcademicYearOptions(selectEl, academicYears) {
    if (!selectEl) return;
    selectEl.innerHTML = '';
    const allOpt = document.createElement('option');
    allOpt.value = '';
    allOpt.textContent = 'ทุกปีการศึกษา';
    selectEl.appendChild(allOpt);

    academicYears.forEach(({ be }) => {
        const option = document.createElement('option');
        option.value = be; // ส่งค่าพ.ศ. ให้ backend แปลงเอง
        option.textContent = be;
        selectEl.appendChild(option);
    });
}

function setYearOptions(yearSelect, years) {
    if (!yearSelect) return;
    yearSelect.innerHTML = '';
    years.forEach((year) => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year + 543; // แสดง พ.ศ. แต่เก็บ ค.ศ.
        yearSelect.appendChild(option);
    });
}

function setMonthOptions(monthSelect, months, selectedYear) {
    if (!monthSelect) return;
    monthSelect.innerHTML = '';
    months.forEach((month) => {
        const option = document.createElement('option');
        option.value = month;
        option.textContent = THAI_MONTHS[month] || month;
        option.dataset.year = selectedYear;
        monthSelect.appendChild(option);
    });
}

function setClassOptions(selectEl, classes) {
    if (!selectEl) return;
    selectEl.innerHTML = '';
    const allOpt = document.createElement('option');
    allOpt.value = '';
    allOpt.textContent = 'ทุกชั้นเรียน';
    selectEl.appendChild(allOpt);

    classes.forEach((cls) => {
        const option = document.createElement('option');
        option.value = cls.classes_id;
        option.textContent = `${cls.classes_level} / ห้อง ${cls.classes_room_number}`;
        selectEl.appendChild(option);
    });
}

function applyDefaultSelection(yearEl, monthEl, state) {
    if (!yearEl || !monthEl) return;
    const defaultYear = state.years[0];
    yearEl.value = defaultYear ?? '';
    const months = state.monthsByYear[defaultYear] || [];
    setMonthOptions(monthEl, months, defaultYear);
    if (months.length > 0) {
        monthEl.value = months[0];
    }
}

function wireYearChange(yearEl, monthEl, state) {
    if (!yearEl || !monthEl) return;
    yearEl.addEventListener('change', () => {
        const year = Number(yearEl.value);
        const months = state.monthsByYear[year] || [];
        setMonthOptions(monthEl, months, year);
        if (months.length > 0) {
            monthEl.value = months[0];
        } else {
            monthEl.value = '';
        }
    });
}

function populateReportFilters(state) {
    REPORT_SELECT_PAIRS.forEach(({ yearId, monthId }) => {
        const yearEl = document.getElementById(yearId);
        const monthEl = document.getElementById(monthId);
        setYearOptions(yearEl, state.years);
        applyDefaultSelection(yearEl, monthEl, state);
        wireYearChange(yearEl, monthEl, state);
    });

    REPORT_CLASS_SELECTS.forEach((id) => {
        const select = document.getElementById(id);
        setClassOptions(select, state.classes);
    });

    REPORT_LEVEL_SELECTS.forEach((id) => {
        const select = document.getElementById(id);
        setLevelOptions(select, state.levels);
    });

    REPORT_ACADEMIC_YEAR_SELECTS.forEach((id) => {
        const select = document.getElementById(id);
        setAcademicYearOptions(select, state.academicYears);
    });
}

async function ensureReportFiltersReady() {
    if (reportFilterState.readyPromise) {
        return reportFilterState.readyPromise;
    }

    reportFilterState.readyPromise = (async () => {
        const [monthsState, classes] = await Promise.all([
            fetchAvailableMonths(),
            fetchClasses()
        ]);

        reportFilterState.monthsByYear = monthsState.monthsByYear;
        reportFilterState.years = monthsState.years;
        reportFilterState.classes = classes;
        reportFilterState.levels = monthsState.levels;
        reportFilterState.academicYears = monthsState.academicYears;

        if (!reportFilterState.years.length) {
            alert('ยังไม่มีข้อมูลพฤติกรรมในปีการศึกษานี้');
            return;
        }

        populateReportFilters(reportFilterState);
    })();

    return reportFilterState.readyPromise;
}

/**
 * สร้างรายงานพฤติกรรมประจำเดือน
 */
async function generateMonthlyReport() {
    await ensureReportFiltersReady();
    const modal = new bootstrap.Modal(document.getElementById('monthlyReportModal'));
    modal.show();
}

/**
 * สร้างรายงานสรุปนักเรียนที่มีความเสี่ยง
 */
async function generateRiskStudentsReport() {
    await ensureReportFiltersReady();
    const modal = new bootstrap.Modal(document.getElementById('riskStudentsReportModal'));
    modal.show();
}

/**
 * สร้างรายงานข้อมูลพฤติกรรมทั้งหมด
 */
async function generateAllBehaviorDataReport() {
    await ensureReportFiltersReady();
    const modal = new bootstrap.Modal(document.getElementById('allBehaviorDataReportModal'));
    modal.show();
}

/**
 * ดาวน์โหลดรายงานพฤติกรรมประจำเดือน
 */
function downloadMonthlyReport() {
    const month = document.getElementById('report_month').value;
    const year = document.getElementById('report_year').value;
    const classId = document.getElementById('report_class_id').value;
    const level = document.getElementById('report_level')?.value || '';
    const academicYear = document.getElementById('report_academic_year')?.value || '';
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (!month || !year) {
        alert('กรุณาเลือกเดือนและปีที่ต้องการสร้างรายงาน');
        return;
    }
    
    // สร้าง URL พร้อมพารามิเตอร์
    let url = `/reports/monthly?month=${month}&year=${year}`;
    if (classId) {
        url += `&class_id=${classId}`;
    }
    if (level) {
        url += `&level=${encodeURIComponent(level)}`;
    }
    if (academicYear) {
        url += `&academic_year=${academicYear}`;
    }
    
    // เปิด URL ใหม่เพื่อดาวน์โหลด (หรือเปิดในแท็บใหม่)
    window.open(url, '_blank');
    
    // ปิด Modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('monthlyReportModal'));
    modal.hide();
}

/**
 * ดาวน์โหลดรายงานสรุปนักเรียนที่มีความเสี่ยง
 */
function downloadRiskStudentsReport() {
    const month = document.getElementById('risk_report_month').value;
    const year = document.getElementById('risk_report_year').value;
    const classId = document.getElementById('risk_report_class_id').value;
    const riskLevel = document.getElementById('risk_report_level').value;
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (!month || !year) {
        alert('กรุณาเลือกเดือนและปีที่ต้องการสร้างรายงาน');
        return;
    }
    
    // สร้าง URL พร้อมพารามิเตอร์
    let url = `/reports/risk-students?month=${month}&year=${year}`;
    if (classId) {
        url += `&class_id=${classId}`;
    }
    if (riskLevel && riskLevel !== 'all') {
        url += `&risk_level=${riskLevel}`;
    }
    
    // เปิด URL ใหม่เพื่อดาวน์โหลด (หรือเปิดในแท็บใหม่)
    window.open(url, '_blank');
    
    // ปิด Modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('riskStudentsReportModal'));
    modal.hide();
}

/**
 * ดาวน์โหลดรายงานข้อมูลพฤติกรรมทั้งหมด
 */
function downloadAllBehaviorDataReport() {
    const month = document.getElementById('all_data_report_month').value;
    const year = document.getElementById('all_data_report_year').value;
    const classId = document.getElementById('all_data_report_class_id').value;
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (!month || !year) {
        alert('กรุณาเลือกเดือนและปีที่ต้องการสร้างรายงาน');
        return;
    }
    
    // สร้าง URL พร้อมพารามิเตอร์
    let url = `/reports/all-behavior-data?month=${month}&year=${year}`;
    if (classId) {
        url += `&class_id=${classId}`;
    }
    
    // เปิด URL ใหม่เพื่อดาวน์โหลด (หรือเปิดในแท็บใหม่)
    window.open(url, '_blank');
    
    // ปิด Modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('allBehaviorDataReportModal'));
    modal.hide();
}

// เตรียม filter เมื่อ Document โหลดเสร็จ
document.addEventListener('DOMContentLoaded', function() {
    ensureReportFiltersReady().catch((err) => {
        console.error(err);
    });
});