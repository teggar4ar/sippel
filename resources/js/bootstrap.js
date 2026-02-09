import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Alpine.js Collapse Plugin for Livewire
import collapse from '@alpinejs/collapse';

// Livewire provides Alpine, so we register plugins before it starts
document.addEventListener('livewire:init', () => {
    // Alpine is available as window.Alpine from Livewire
    if (window.Alpine) {
        window.Alpine.plugin(collapse);
    }
});
