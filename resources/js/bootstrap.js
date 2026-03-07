import axios from 'axios';
globalThis.axios = axios;

globalThis.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

//Alpine.js Collapse Plugin - must register before Livewire starts Alpine
import collapse from '@alpinejs/collapse';

// Livewire 3 automatically starts Alpine, so we hook in before that
document.addEventListener('livewire:init', () => {
    // This runs after Livewire initializes but before Alpine starts
    if (globalThis.Livewire?.Alpine) {
        globalThis.Livewire.Alpine.plugin(collapse);
    }
});
