/**
 * AKRU — Main JavaScript Entry Point
 *
 * Stack: Alpine.js for interactivity, PWA for offline support.
 */

import './bootstrap';

// Alpine.js
import Alpine from 'alpinejs';
import persist from '@alpinejs/persist';

Alpine.plugin(persist);
window.Alpine = Alpine;
Alpine.start();

// PWA modules
import './pwa/indexeddb';
import './pwa/sync-queue';
import './pwa/offline-status';
