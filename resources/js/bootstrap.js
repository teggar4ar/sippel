import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

//Alpine.js Collapse Plugin - must register before Livewire starts Alpine
import collapse from '@alpinejs/collapse';

// Livewire 3 automatically starts Alpine, so we hook in before that
document.addEventListener('livewire:init', () => {
    // This runs after Livewire initializes but before Alpine starts
    if (window.Livewire && window.Livewire.Alpine) {
        window.Livewire.Alpine.plugin(collapse);
    }
});
