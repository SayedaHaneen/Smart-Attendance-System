/* assets/js/theme.js - Modern Theme Engine & UX Utilities */

(function () {
    // Theme Engine Initialization
    const savedTheme = localStorage.getItem('app-theme') || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    document.documentElement.setAttribute('data-theme', savedTheme);

    function updateThemeToggleIcons(theme) {
        document.querySelectorAll('.btn-theme-toggle i').forEach(icon => {
            if (theme === 'dark') {
                icon.className = 'fas fa-sun';
            } else {
                icon.className = 'fas fa-moon';
            }
        });
    }

    window.toggleAppTheme = function () {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('app-theme', newTheme);
        updateThemeToggleIcons(newTheme);
    };

    window.toggleAdminSidebar = function () {
        if (window.innerWidth < 992) {
            document.body.classList.toggle('sidebar-open');
        } else {
            document.body.classList.toggle('sidebar-collapsed');
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateThemeToggleIcons(currentTheme);

        // Toast Container Setup
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            document.body.appendChild(container);
        }

        // Real-Time Table Live Search Filter
        document.querySelectorAll('[data-table-search]').forEach(searchInput => {
            const targetTableId = searchInput.getAttribute('data-table-search');
            const table = document.getElementById(targetTableId);
            if (!table) return;

            searchInput.addEventListener('input', (e) => {
                const term = e.target.value.toLowerCase().trim();
                const rows = table.querySelectorAll('tbody tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(term)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    });

    // Toast Notification System
    window.showToast = function (message, type = 'info', title = '') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `custom-toast ${type}`;

        let iconClass = 'fa-info-circle';
        if (type === 'success') iconClass = 'fa-check-circle';
        if (type === 'error') iconClass = 'fa-exclamation-circle';
        if (type === 'warning') iconClass = 'fa-exclamation-triangle';

        toast.innerHTML = `
            <i class="fas ${iconClass} fa-lg" style="color: var(--${type === 'info' ? 'primary' : type})"></i>
            <div style="flex:1">
                ${title ? `<div style="font-weight:700; font-size:0.875rem; margin-bottom:2px;">${title}</div>` : ''}
                <div style="font-size:0.85rem;">${message}</div>
            </div>
            <button onclick="this.parentElement.remove()" style="background:none; border:none; color:var(--text-muted); cursor:pointer;">&times;</button>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, 4000);
    };

    // Web Audio Sound Chime Generator for Attendance Mark Success
    window.playSuccessChime = function () {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const ctx = new AudioContext();
            
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            
            osc.type = 'sine';
            osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
            osc.frequency.setValueAtTime(880, ctx.currentTime + 0.1); // A5
            
            gain.gain.setValueAtTime(0.1, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
            
            osc.connect(gain);
            gain.connect(ctx.destination);
            
            osc.start();
            osc.stop(ctx.currentTime + 0.4);
        } catch (e) {
            console.log('Audio chime not supported or allowed', e);
        }
    };
})();
