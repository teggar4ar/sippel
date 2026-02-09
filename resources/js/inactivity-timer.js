/**
 * Unified Inactivity Timer for Auto-Logout
 *
 * Shared module for admin, teacher, and student interfaces.
 * Tracks user activity and automatically logs out after a period of inactivity.
 * Shows an accessible warning modal with countdown before logout.
 *
 * Features:
 * - Configurable idle timeout and warning duration
 * - Accessible modal with ARIA attributes and focus trap
 * - Keyboard support (Escape to stay logged in)
 * - Dark mode support via Tailwind classes
 * - Mobile responsive (stacked buttons on small screens)
 * - bfcache handling (session validation on page restore)
 * - Proper cleanup on modal dismiss
 */

/**
 * Initialize the inactivity timer
 * @param {Object} config - Configuration options
 * @param {number} [config.idleTimeout=1800000] - Milliseconds before warning (default: 30 min)
 * @param {number} [config.warningDuration=120000] - Milliseconds of warning countdown (default: 2 min)
 * @param {string} [config.logoutUrl='/app/logout'] - POST logout endpoint
 * @param {string} [config.sessionCheckUrl] - Optional GET endpoint to validate session
 * @param {string[]} [config.events] - Activity events to track
 */
export function initInactivityTimer(config = {}) {
    const defaults = {
        idleTimeout: 30 * 60 * 1000,
        warningDuration: 2 * 60 * 1000,
        logoutUrl: '/app/logout',
        sessionCheckUrl: null,
        checkInterval: 1000,
        events: ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'],
    };

    const settings = { ...defaults, ...config };

    let lastActivity = Date.now();
    let warningShown = false;
    let warningTimeout = null;
    let modalElement = null;
    let countdownInterval = null;
    let previousActiveElement = null;
    let keydownHandler = null;

    /**
     * Create the warning modal with accessibility features
     */
    function createWarningModal() {
        const modal = document.createElement('div');
        modal.id = 'inactivity-warning-modal';
        modal.className = 'fixed inset-0 z-[99999] hidden';
        // Apply inline styles as fallback for environments without Tailwind (e.g., Filament admin)
        modal.style.cssText = 'position:fixed;inset:0;z-index:99999;display:none;';
        modal.setAttribute('role', 'alertdialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'inactivity-warning-title');
        modal.setAttribute('aria-describedby', 'inactivity-warning-desc');

        // Use inline styles as fallbacks for environments without Tailwind (e.g., Filament admin)
        modal.innerHTML = `
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" aria-hidden="true"
                 style="position:fixed;inset:0;background:rgba(0,0,0,0.6);backdrop-filter:blur(4px);"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4"
                 style="position:fixed;inset:0;display:flex;align-items:center;justify-content:center;padding:16px;">
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl max-w-sm w-full p-6 relative"
                     style="background:white;border-radius:16px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);max-width:384px;width:100%;padding:24px;position:relative;">
                    <div class="text-center" style="text-align:center;">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 flex items-center justify-center"
                             style="width:64px;height:64px;margin:0 auto 16px;border-radius:50%;background:#fef3c7;display:flex;align-items:center;justify-content:center;">
                            <svg class="w-8 h-8 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"
                                 style="width:32px;height:32px;color:#d97706;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                        </div>
                        <h3 id="inactivity-warning-title" class="text-lg font-semibold text-slate-900 mb-2"
                            style="font-size:18px;font-weight:600;color:#111827;margin:0 0 8px 0;">
                            Sesi Akan Berakhir
                        </h3>
                        <p id="inactivity-warning-desc" class="text-sm text-slate-600 mb-6"
                           style="font-size:14px;color:#4b5563;margin:0 0 24px 0;">
                            Anda akan keluar otomatis dalam
                            <span id="countdown-timer" class="font-bold text-amber-600"
                                  style="font-weight:700;color:#d97706;">2:00</span>
                            karena tidak ada aktivitas.
                        </p>
                        <div class="flex flex-col-reverse sm:flex-row gap-3"
                             style="display:flex;flex-direction:row;gap:12px;">
                            <button id="logout-now-btn"
                                    class="flex-1 px-4 py-3 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2"
                                    style="flex:1;padding:12px 16px;font-size:14px;font-weight:500;color:#374151;background:#f3f4f6;border:none;border-radius:12px;cursor:pointer;">
                                Keluar Sekarang
                            </button>
                            <button id="stay-logged-in-btn"
                                    class="flex-1 px-4 py-3 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-xl transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    style="flex:1;padding:12px 16px;font-size:14px;font-weight:500;color:white;background:#2563eb;border:none;border-radius:12px;cursor:pointer;">
                                Tetap Masuk
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Event listeners for modal buttons
        const logoutBtn = modal.querySelector('#logout-now-btn');
        const stayBtn = modal.querySelector('#stay-logged-in-btn');

        logoutBtn.addEventListener('click', performLogout);
        stayBtn.addEventListener('click', dismissWarning);

        return modal;
    }

    /**
     * Format milliseconds as MM:SS
     */
    function formatTime(ms) {
        const totalSeconds = Math.max(0, Math.floor(ms / 1000));
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    /**
     * Trap focus within the modal
     */
    function trapFocus(e) {
        if (!modalElement || !warningShown) return;

        const focusableElements = modalElement.querySelectorAll(
            'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
        );

        if (focusableElements.length === 0) return;

        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];

        if (e.key === 'Tab') {
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }

    /**
     * Handle keyboard events for modal
     */
    function handleKeydown(e) {
        if (!warningShown) return;

        if (e.key === 'Escape') {
            e.preventDefault();
            dismissWarning();
        } else if (e.key === 'Tab') {
            trapFocus(e);
        }
    }

    /**
     * Show warning modal with countdown
     */
    function showWarning() {
        if (warningShown) return;

        warningShown = true;
        previousActiveElement = document.activeElement;

        if (!modalElement) {
            modalElement = createWarningModal();
        }

        modalElement.style.display = 'block';

        // Set up keyboard handler
        keydownHandler = handleKeydown;
        document.addEventListener('keydown', keydownHandler);

        // Focus the "Stay logged in" button
        const stayBtn = modalElement.querySelector('#stay-logged-in-btn');
        if (stayBtn) {
            setTimeout(() => stayBtn.focus(), 50);
        }

        let remainingTime = settings.warningDuration;
        const countdownEl = modalElement.querySelector('#countdown-timer');

        // Initial display
        if (countdownEl) {
            countdownEl.textContent = formatTime(remainingTime);
        }

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

    /**
     * Hide warning modal and reset timers
     */
    function dismissWarning() {
        warningShown = false;

        if (modalElement) {
            modalElement.style.display = 'none';
        }

        if (warningTimeout) {
            clearTimeout(warningTimeout);
            warningTimeout = null;
        }

        if (countdownInterval) {
            clearInterval(countdownInterval);
            countdownInterval = null;
        }

        // Remove keyboard handler
        if (keydownHandler) {
            document.removeEventListener('keydown', keydownHandler);
            keydownHandler = null;
        }

        // Restore focus to previous element
        if (previousActiveElement && previousActiveElement.focus) {
            previousActiveElement.focus();
        }
        previousActiveElement = null;

        resetActivity();
    }

    /**
     * Perform logout via form submission
     */
    function performLogout() {
        // Clear all timers
        if (warningTimeout) clearTimeout(warningTimeout);
        if (countdownInterval) clearInterval(countdownInterval);

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

    /**
     * Reset activity timestamp
     */
    function resetActivity() {
        lastActivity = Date.now();
    }

    /**
     * Handle user activity events
     */
    function handleActivity() {
        if (!warningShown) {
            resetActivity();
        }
    }

    /**
     * Check for inactivity
     */
    function checkInactivity() {
        const idleTime = Date.now() - lastActivity;

        if (!warningShown && idleTime >= settings.idleTimeout) {
            showWarning();
        }
    }

    /**
     * Check session validity via API (for bfcache handling)
     */
    async function checkSession() {
        if (!settings.sessionCheckUrl || warningShown) return;

        try {
            const response = await fetch(settings.sessionCheckUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                window.location.replace(settings.logoutUrl.replace('/logout', '/login'));
                return;
            }

            const data = await response.json();
            if (!data.authorized) {
                window.location.replace(settings.logoutUrl.replace('/logout', '/login'));
            }
        } catch (e) {
            console.warn('[InactivityTimer] Session check failed:', e);
        }
    }

    /**
     * Initialize the timer
     */
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

        // Handle bfcache restoration
        window.addEventListener('pageshow', (event) => {
            if (event.persisted && !warningShown) {
                checkSession();
                resetActivity();
            }
        });
    }

    init();

    // Return public API
    return {
        reset: resetActivity,
        logout: performLogout,
        showWarning: showWarning,
        dismissWarning: dismissWarning,
    };
}

/**
 * Auto-initialize if data attribute is present on body or container
 */
document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-inactivity-timer]');
    if (container) {
        const idleMinutes = parseInt(container.dataset.idleMinutes) || 30;
        const warningMinutes = parseInt(container.dataset.warningMinutes) || 2;
        const logoutUrl = container.dataset.logoutUrl || '/app/logout';
        const sessionCheckUrl = container.dataset.sessionCheckUrl || null;

        window.inactivityTimer = initInactivityTimer({
            idleTimeout: idleMinutes * 60 * 1000,
            warningDuration: warningMinutes * 60 * 1000,
            logoutUrl: logoutUrl,
            sessionCheckUrl: sessionCheckUrl,
        });
    }
});
