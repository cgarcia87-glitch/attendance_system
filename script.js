/**
 * Attendance Management System - Master Client Script
 * Premium Gold & Black Theme
 */

// Configure Gold & Black Chart Defaults globally
if (typeof Chart !== 'undefined') {
    Chart.defaults.color = '#F5D76E';
    Chart.defaults.borderColor = 'rgba(212, 175, 55, 0.2)';
    if (Chart.defaults.plugins && Chart.defaults.plugins.legend) {
        Chart.defaults.plugins.legend.labels.color = '#FFFFFF';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // -------------------------------------------------------------
    // 1. PASSWORD VISIBILITY TOGGLE (Eye Icon)
    // -------------------------------------------------------------
    const toggleButtons = document.querySelectorAll('.password-toggle-btn');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const group = this.closest('.password-group');
            const input = group ? group.querySelector('input') : document.getElementById(this.dataset.target);
            const icon = this.querySelector('i');

            if (!input || !icon) return;

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                this.setAttribute('title', 'Hide password');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
                this.setAttribute('title', 'Show password');
            }
        });
    });

    // -------------------------------------------------------------
    // 2. MOBILE SIDEBAR TOGGLE
    // -------------------------------------------------------------
    const sidebar = document.getElementById('app-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        // Close sidebar on backdrop click outside
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }

    // -------------------------------------------------------------
    // 3. SWEETALERT2 FLASH MESSAGES
    // -------------------------------------------------------------
    const flashElement = document.getElementById('flash-message');
    if (flashElement && typeof Swal !== 'undefined') {
        const type = flashElement.dataset.type || 'info';
        const message = flashElement.dataset.message || '';

        Swal.fire({
            icon: type,
            title: message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true
        });
    }

    // -------------------------------------------------------------
    // 4. CONFIRMATION DIALOG FOR DELETIONS
    // -------------------------------------------------------------
    document.querySelectorAll('.btn-delete-confirm, [data-confirm]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const form = this.closest('form');
            const text = this.dataset.confirm || 'This action cannot be undone. Do you wish to continue?';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Are you sure?',
                    text: text,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#ef4444',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Yes, proceed',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (form) {
                            form.submit();
                        } else if (this.href) {
                            window.location.href = this.href;
                        }
                    }
                });
            } else {
                if (confirm(text)) {
                    if (form) form.submit();
                    else if (this.href) window.location.href = this.href;
                }
            }
        });
    });

    // -------------------------------------------------------------
    // 5. DATATABLES INITIALIZATION
    // -------------------------------------------------------------
    if (typeof jQuery !== 'undefined' && jQuery.fn.DataTable) {
        $('.datatable').each(function () {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    language: {
                        search: "_INPUT_",
                        searchPlaceholder: "Search records...",
                        paginate: {
                            previous: "<i class='bi bi-chevron-left'></i>",
                            next: "<i class='bi bi-chevron-right'></i>"
                        }
                    }
                });
            }
        });
    }

    // -------------------------------------------------------------
    // 6. ATTENDANCE STATUS BUTTON TOGGLES
    // -------------------------------------------------------------
    document.querySelectorAll('.attendance-toggle-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const studentId = this.dataset.student;
            const status = this.dataset.status;
            const container = this.closest('.attendance-toggle-group');

            // Update hidden input
            const hiddenInput = document.getElementById(`status_input_${studentId}`);
            if (hiddenInput) {
                hiddenInput.value = status;
            }

            // Update UI buttons inside this group
            container.querySelectorAll('.attendance-toggle-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Update row background highlight
            const row = document.getElementById(`student_row_${studentId}`);
            if (row) {
                row.classList.remove('table-success', 'table-danger', 'table-warning', 'table-info');
                if (status === 'PRESENT') row.classList.add('table-success-subtle');
                else if (status === 'ABSENT') row.classList.add('table-danger-subtle');
                else if (status === 'LATE') row.classList.add('table-warning-subtle');
                else if (status === 'EXCUSED') row.classList.add('table-info-subtle');
            }
        });
    });

    // Batch buttons: Mark All Present, Absent, Late
    const markAllPresentBtn = document.getElementById('mark-all-present');
    if (markAllPresentBtn) {
        markAllPresentBtn.addEventListener('click', function () {
            document.querySelectorAll('.attendance-toggle-btn.btn-present').forEach(btn => btn.click());
        });
    }

    const markAllAbsentBtn = document.getElementById('mark-all-absent');
    if (markAllAbsentBtn) {
        markAllAbsentBtn.addEventListener('click', function () {
            document.querySelectorAll('.attendance-toggle-btn.btn-absent').forEach(btn => btn.click());
        });
    }

    const markAllLateBtn = document.getElementById('mark-all-late');
    if (markAllLateBtn) {
        markAllLateBtn.addEventListener('click', function () {
            document.querySelectorAll('.attendance-toggle-btn.btn-late').forEach(btn => btn.click());
        });
    }
});

// -------------------------------------------------------------
// 7. QR CODE SCANNER HELPER (For Camera / Manual Scan)
// -------------------------------------------------------------
function submitQrScan(qrCode, sessionId, classId, baseUrl) {
    if (!qrCode || !sessionId || !classId) return;

    fetch(`${baseUrl}/teacher/take_attendance.php?action=scan_qr`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            qr_code: qrCode,
            session_id: sessionId,
            class_id: classId,
            status: 'PRESENT'
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Recorded!',
                text: data.message,
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false
            });

            // Update row if student is visible in table
            const studentId = data.student.id;
            const presentBtn = document.querySelector(`.attendance-toggle-btn.btn-present[data-student="${studentId}"]`);
            if (presentBtn) {
                presentBtn.click();
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Check-in Error',
                text: data.message
            });
        }
    })
    .catch(err => {
        console.error('QR scan error:', err);
    });
}
