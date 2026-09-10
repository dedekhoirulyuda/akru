/**
 * AKRU Sync Queue Manager
 *
 * Manages the offline→online synchronization queue.
 * Blueprint §4.6: Retry with idempotency key, no silent last-write-wins,
 * posting/closing/approval/tax are online-only.
 */

class SyncQueue {
    constructor() {
        this.isOnline = navigator.onLine;
        this.isSyncing = false;
        this.listeners = [];

        window.addEventListener('online', () => this.handleOnline());
        window.addEventListener('offline', () => this.handleOffline());
    }

    handleOnline() {
        this.isOnline = true;
        this.notify('online');
        this.processQueue();
    }

    handleOffline() {
        this.isOnline = false;
        this.notify('offline');
    }

    /**
     * Add a transaction to the sync queue.
     */
    async enqueue(transaction) {
        if (!window.akruDB?.db) {
            await window.akruDB.open();
        }

        const item = {
            ...transaction,
            idempotency_key: transaction.idempotency_key || crypto.randomUUID(),
            status: 'pending',
            retries: 0,
            max_retries: 5,
            created_at: new Date().toISOString(),
        };

        // If online, try immediate sync
        if (this.isOnline) {
            try {
                const result = await this.syncItem(item);
                return result;
            } catch (err) {
                // Fall through to queue
                console.warn('[AKRU Sync] Immediate sync failed, queuing:', err);
            }
        }

        // Save to IndexedDB queue
        await window.akruDB.saveDraft(item);
        this.notify('queued', item);
        return item;
    }

    /**
     * Process all pending items in the queue.
     */
    async processQueue() {
        if (this.isSyncing || !this.isOnline) return;
        this.isSyncing = true;
        this.notify('syncing');

        try {
            const pending = await window.akruDB.getPendingDrafts();

            for (const item of pending) {
                try {
                    await this.syncItem(item);
                } catch (err) {
                    console.error('[AKRU Sync] Failed:', item.local_id, err);
                    item.retries = (item.retries || 0) + 1;

                    if (item.retries >= (item.max_retries || 5)) {
                        item.status = 'failed';
                    }
                }
            }
        } finally {
            this.isSyncing = false;
            this.notify('idle');
        }
    }

    /**
     * Sync a single item to the server.
     */
    async syncItem(item) {
        const response = await fetch('/api/v1/sync', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Idempotency-Key': item.idempotency_key,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
            body: JSON.stringify(item),
        });

        if (!response.ok) {
            throw new Error(`Sync failed: ${response.status}`);
        }

        return response.json();
    }

    /**
     * Subscribe to sync events.
     */
    on(callback) {
        this.listeners.push(callback);
        return () => {
            this.listeners = this.listeners.filter(l => l !== callback);
        };
    }

    notify(event, data = null) {
        this.listeners.forEach(cb => cb(event, data));
    }
}

window.akruSync = new SyncQueue();

export default SyncQueue;
