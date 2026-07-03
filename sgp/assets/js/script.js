// ===================================
// University Medical System - JS
// ===================================

document.addEventListener('DOMContentLoaded', function () {

    // Sidebar toggle for mobile
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('open');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('open');
        });
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function () { alert.remove(); }, 500);
        }, 5000);
    });

    // Confirm dialogs
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    // Set min date for leave date input
    const leaveDateInput = document.getElementById('leave_date');
    if (leaveDateInput) {
        const today = new Date().toISOString().split('T')[0];
        leaveDateInput.setAttribute('min', today);
    }

    // OTP auto-focus next input
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach(function (input, idx) {
        input.addEventListener('input', function () {
            if (this.value.length === 1 && idx < otpInputs.length - 1) {
                otpInputs[idx + 1].focus();
            }
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !this.value && idx > 0) {
                otpInputs[idx - 1].focus();
            }
        });
    });

    // Current date display in topbar
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        const now = new Date();
        const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
        dateEl.textContent = now.toLocaleDateString('en-IN', opts);
    }

    // Table search filter
    const tableSearch = document.getElementById('tableSearch');
    if (tableSearch) {
        tableSearch.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(function (row) {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    }

    // Status filter
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(function (row) {
                const statusCell = row.querySelector('.badge');
                if (!val || (statusCell && statusCell.textContent.toLowerCase().includes(val))) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Highlight active nav item
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-item').forEach(function (item) {
        if (currentPath.endsWith(item.getAttribute('href'))) {
            item.classList.add('active');
        }
    });
});
