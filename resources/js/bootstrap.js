import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Alpine.js Collapse Plugin for Livewire
// Must be registered BEFORE Alpine starts (which Livewire does automatically)
import collapse from '@alpinejs/collapse';

// Hook into Alpine before Livewire starts it
document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(collapse);
});
