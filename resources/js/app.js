// resources/js/app.js
import '../css/app.css'; // penting: Vite akan bundle ini

import Alpine from 'alpinejs'
import collapse from '@alpinejs/collapse'

Alpine.plugin(collapse)
window.Alpine = Alpine
Alpine.start()

// setup global axios/jQuery CSRF jika perlu (opsional)
