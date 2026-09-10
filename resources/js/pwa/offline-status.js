/**
 * AKRU Offline Status UI
 *
 * Blueprint §2.12: Menampilkan online/offline, pending, failed, conflict.
 * Status terlihat di global shell dan tiap record.
 */

class OfflineStatus {
    constructor() {
        this.banner = document.getElementById('offline-banner');
        this.indicator = document.getElementById('sync-indicator');

        window.addEventListener('online', () => this.update(true));
        window.addEventListener('offline', () => this.update(false));

        // Subscribe to sync events
        if (window.akruSync) {
            window.akruSync.on((event) => this.handleSyncEvent(event));
        }

        // Initial state
        this.update(navigator.onLine);
    }

    update(isOnline) {
        if (this.banner) {
            this.banner.classList.toggle('hidden', isOnline);
        }

        if (this.indicator) {
            const dot = this.indicator.querySelector('div');
            if (dot) {
                dot.className = `w-2 h-2 rounded-full ${isOnline ? 'bg-green-400' : 'bg-amber-400 animate-pulse'}`;
                this.indicator.title = isOnline ? 'Online' : 'Offline — draft lokal tersimpan';
            }
        }
    }

    handleSyncEvent(event) {
        if (!this.indicator) return;
        const dot = this.indicator.querySelector('div');
        if (!dot) return;

        switch (event) {
            case 'syncing':
                dot.className = 'w-2 h-2 rounded-full bg-blue-400 animate-pulse';
                this.indicator.title = 'Sedang sinkronisasi...';
                break;
            case 'idle':
                dot.className = 'w-2 h-2 rounded-full bg-green-400';
                this.indicator.title = 'Tersinkronisasi';
                break;
            case 'offline':
                dot.className = 'w-2 h-2 rounded-full bg-amber-400 animate-pulse';
                this.indicator.title = 'Offline';
                break;
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.akruOfflineStatus = new OfflineStatus();
});

export default OfflineStatus;
