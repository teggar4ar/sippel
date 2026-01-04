/**
 * Inactivity Timer for Auto-Logout
 *
 * Tracks user activity and automatically logs out after a period of inactivity.
 * Shows a warning modal before logout.
 */

export function initInactivityTimer(config = {}) {
    const defaults = {
        idleTimeout: 30 * 60 * 1000,      // 30 minutes idle before warning
        warningDuration: 2 * 60 * 1000,   // 2 minutes warning before logout
        logoutUrl: '/app/logout',
        checkInterval: 1000,               // Check every second
        events: ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'],
    };

    const settings = { ...defaults, ...config };

    let lastActivity = Date.now();
    let warningShown = false;
    let warningTimeout = null;
    let modalElement = null;
    let countdownInterval = null;

    // Create warning modal
    function createWarningModal() {
        const modal = document.createElement('div');
        modal.id = 'inactivity-warning-modal';
        modal.className = 'fixed inset-0 z-[9999] hidden';
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 relative">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                            <svg class="w-8 h-8 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-2">Sesi Akan Berakhir</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400 mb-4">
                            Anda akan keluar otomatis dalam <span id="countdown-timer" class="font-bold text-amber-600 dark:text-amber-400">2:00</span> karena tidak ada aktivitas.
                        </p>
                        <div class="flex gap-3">
                            <button id="logout-now-btn" class="flex-1 px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-xl transition-colors">
                                Keluar Sekarang
                            </button>
                            <button id="stay-logged-in-btn" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors">
                                Tetap Masuk
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);

        // Event listeners for modal buttons
        modal.querySelector('#logout-now-btn').addEventListener('click', performLogout);
        modal.querySelector('#stay-logged-in-btn').addEventListener('click', dismissWarning);

        return modal;
    }

    // Format time as MM:SS
    function formatTime(ms) {
        const totalSeconds = Math.max(0, Math.floor(ms / 1000));
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    // Show warning modal
    function showWarning() {
        if (warningShown) return;

        warningShown = true;
        if (!modalElement) {
            modalElement = createWarningModal();
        }
        modalElement.classList.remove('hidden');

        let remainingTime = settings.warningDuration;
        const countdownEl = modalElement.querySelector('#countdown-timer');

        // Update countdown every second
        countdownInterval = setInterval(() => {
            remainingTime -= 1000;
            if (countdownEl) {
                countdownEl.textContent = formatTime(remainingTime);
            }

            if (remainingTime <= 0) {
                clearInterval(countdownInterval);
                performLogout();
            }
        }, 1000);

        // Set timeout for auto-logout
        warningTimeout = setTimeout(performLogout, settings.warningDuration);
    }

    // Hide warning modal and reset
    function dismissWarning() {
        warningShown = false;
        if (modalElement) {
            modalElement.classList.add('hidden');
        }
        if (warningTimeout) {
            clearTimeout(warningTimeout);
            warningTimeout = null;
        }
        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }
        resetActivity();
    }

    // Perform logout
    function performLogout() {
        // Create a form and submit it for proper CSRF logout
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = settings.logoutUrl;

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || document.querySelector('input[name="_token"]')?.value;

        if (csrfToken) {
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrfToken;
            form.appendChild(csrfInput);
        }

        document.body.appendChild(form);
        form.submit();
    }

    // Reset activity timestamp
    function resetActivity() {
        lastActivity = Date.now();
    }

    // Handle user activity
    function handleActivity() {
        if (!warningShown) {
            resetActivity();
        }
    }

    // Check for inactivity
    function checkInactivity() {
        const idleTime = Date.now() - lastActivity;

        if (!warningShown && idleTime >= settings.idleTimeout) {
            showWarning();
        }
    }

    // Initialize
    function init() {
        // Attach activity listeners
        settings.events.forEach(event => {
            document.addEventListener(event, handleActivity, { passive: true });
        });

        // Start checking for inactivity
        setInterval(checkInactivity, settings.checkInterval);

        // Reset on page visibility change (user returns to tab)
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && !warningShown) {
                resetActivity();
            }
        });

        console.log('[InactivityTimer] Initialized with', settings.idleTimeout / 1000 / 60, 'min idle timeout');
    }

    init();

    // Return public API
    return {
        reset: resetActivity,
        logout: performLogout,
    };
}

// Auto-initialize if data attribute is present
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-inactivity-timer]');
    if (container) {
        const idleMinutes = parseInt(container.dataset.idleMinutes) || 30;
        const warningMinutes = parseInt(container.dataset.warningMinutes) || 2;
        const logoutUrl = container.dataset.logoutUrl || '/app/logout';

        window.inactivityTimer = initInactivityTimer({
            idleTimeout: idleMinutes * 60 * 1000,
            warningDuration: warningMinutes * 60 * 1000,
            logoutUrl: logoutUrl,
        });
    }
});
