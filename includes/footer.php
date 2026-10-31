<?php
/**
 * Shared Footer Component
 */
$isSubdir = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/tracker/') !== false;
$rootPath = $isSubdir ? '../' : './';
?>
    </main>

    <footer class="footer">
        <div class="app-container">
            <p><strong>Let's Move</strong> — Calorie Tracker & Fitness Management System.</p>
            <p style="margin-top: 6px; font-size: 0.8rem; color: var(--text-dim);">
                Engineered with Clean Architecture • Dual Database (SQLite / MySQL) • High Precision BMR & TDEE Algorithms
            </p>
        </div>
    </footer>

    <script>
        // Mobile Navigation Toggle
        const navToggleBtn = document.getElementById('navToggleBtn');
        const mainNavLinks = document.getElementById('mainNavLinks');
        if (navToggleBtn && mainNavLinks) {
            navToggleBtn.addEventListener('click', () => {
                mainNavLinks.classList.toggle('open');
            });
        }

        // Global Toast Notification Helper
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toastContainer');
            if (!container) return;
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `<span>${type === 'success' ? '✓' : '⚠️'}</span> <span>${message}</span>`;
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(10px)';
                toast.style.transition = 'all 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        };
    </script>
</body>
</html>
