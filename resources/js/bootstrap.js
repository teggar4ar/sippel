import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Alpine.js Collapse Plugin - must register before Livewire starts Alpine
import collapse from '@alpinejs/collapse';

// Define qrCountdown globally BEFORE Alpine starts
// This function returns an Alpine component object for QR code countdown timers
window.qrCountdown = function(expiresAt, remainingSeconds) {
    return {
        expiresAt: expiresAt,
        remainingSeconds: remainingSeconds,
        timer: null,

        init() {
            if (this.remainingSeconds <= 0) return;

            this.timer = setInterval(() => {
                const now = Math.floor(Date.now() / 1000);
                this.remainingSeconds = Math.max(0, this.expiresAt - now);

                if (this.remainingSeconds <= 0 && this.timer) {
                    clearInterval(this.timer);
                    this.timer = null;
                }
            }, 1000);
        },

        destroy() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },

        formatTime() {
            if (this.remainingSeconds <= 0) return 'Selesai';
            const m = Math.floor(this.remainingSeconds / 60);
            const s = this.remainingSeconds % 60;
            return m > 0 ? `${m}m ${s}s` : `${s}s`;
        },

        formatTimeColon() {
            if (this.remainingSeconds <= 0) return 'Selesai';
            const m = Math.floor(this.remainingSeconds / 60);
            const s = this.remainingSeconds % 60;
            return m > 0 ? `${m}:${s.toString().padStart(2, '0')}` : `${s}s`;
        }
    };
};

// Livewire 3 automatically starts Alpine, so we hook in before that
document.addEventListener('livewire:init', () => {
    // Register Alpine collapse plugin
    if (window.Livewire && window.Livewire.Alpine) {
        window.Livewire.Alpine.plugin(collapse);
    }
});
