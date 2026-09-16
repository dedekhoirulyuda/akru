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

// Global AI Chat Store for Antigravity-Style Split View
Alpine.store('aiChat', {
    isOpen: localStorage.getItem('akru_ai_open') === 'true',
    width: parseInt(localStorage.getItem('akru_ai_width') || '480'),
    isResizing: false,

    toggle() {
        this.isOpen = !this.isOpen;
        localStorage.setItem('akru_ai_open', this.isOpen ? 'true' : 'false');
        if (this.isOpen) {
            window.dispatchEvent(new CustomEvent('akru-ai-focus-input'));
        }
    },
    open() {
        this.isOpen = true;
        localStorage.setItem('akru_ai_open', 'true');
        window.dispatchEvent(new CustomEvent('akru-ai-focus-input'));
    },
    close() {
        this.isOpen = false;
        localStorage.setItem('akru_ai_open', 'false');
    },
    setWidth(newWidth) {
        const minW = 340;
        const maxW = Math.max(minW, Math.min(window.innerWidth - 300, Math.floor(window.innerWidth * 0.7)));
        this.width = Math.max(minW, Math.min(newWidth, maxW));
        localStorage.setItem('akru_ai_width', this.width);
    }
});

// Theme Store (Light Mode & Night Mode)
const initialTheme = localStorage.getItem('akru_theme') || 'light';
if (initialTheme === 'dark') {
    document.documentElement.classList.add('dark');
} else {
    document.documentElement.classList.remove('dark');
}

Alpine.store('theme', {
    mode: initialTheme,
    toggle() {
        this.mode = this.mode === 'dark' ? 'light' : 'dark';
        localStorage.setItem('akru_theme', this.mode);
        if (this.mode === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    },
    isDark() {
        return this.mode === 'dark';
    }
});

Alpine.start();

// PWA modules
import './pwa/indexeddb';
import './pwa/sync-queue';
import './pwa/offline-status';
