/**
 * AKRU IndexedDB Wrapper
 *
 * Provides a clean API for offline data storage.
 * Blueprint §2.12: Draft offline disimpan di IndexedDB dengan
 * local_id, company_id, user_id, device_id, version, timestamp,
 * payload hash, dan idempotency key.
 */

const DB_NAME = 'akru_offline';
const DB_VERSION = 1;

const STORES = {
    DRAFTS: 'offline_drafts',
    SYNC_QUEUE: 'sync_queue',
    CACHE: 'data_cache',
};

class AkruDB {
    constructor() {
        this.db = null;
    }

    async open() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Offline drafts store
                if (!db.objectStoreNames.contains(STORES.DRAFTS)) {
                    const drafts = db.createObjectStore(STORES.DRAFTS, { keyPath: 'local_id' });
                    drafts.createIndex('company_id', 'company_id', { unique: false });
                    drafts.createIndex('user_id', 'user_id', { unique: false });
                    drafts.createIndex('type', 'type', { unique: false });
                    drafts.createIndex('status', 'status', { unique: false });
                    drafts.createIndex('created_at', 'created_at', { unique: false });
                }

                // Sync queue store
                if (!db.objectStoreNames.contains(STORES.SYNC_QUEUE)) {
                    const queue = db.createObjectStore(STORES.SYNC_QUEUE, { keyPath: 'id', autoIncrement: true });
                    queue.createIndex('status', 'status', { unique: false });
                    queue.createIndex('idempotency_key', 'idempotency_key', { unique: true });
                    queue.createIndex('created_at', 'created_at', { unique: false });
                }

                // Data cache store
                if (!db.objectStoreNames.contains(STORES.CACHE)) {
                    const cache = db.createObjectStore(STORES.CACHE, { keyPath: 'key' });
                    cache.createIndex('expires_at', 'expires_at', { unique: false });
                }
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                resolve(this.db);
            };

            request.onerror = (event) => {
                reject(event.target.error);
            };
        });
    }

    /**
     * Save an offline draft transaction.
     */
    async saveDraft(draft) {
        const tx = this.db.transaction(STORES.DRAFTS, 'readwrite');
        const store = tx.objectStore(STORES.DRAFTS);

        const record = {
            local_id: draft.local_id || crypto.randomUUID(),
            company_id: draft.company_id,
            user_id: draft.user_id,
            device_id: draft.device_id,
            type: draft.type,
            payload: draft.payload,
            payload_hash: await this.hashPayload(draft.payload),
            idempotency_key: draft.idempotency_key || crypto.randomUUID(),
            version: draft.version || 1,
            status: 'draft',
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
        };

        return new Promise((resolve, reject) => {
            const request = store.put(record);
            request.onsuccess = () => resolve(record);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Get all pending drafts for sync.
     */
    async getPendingDrafts() {
        const tx = this.db.transaction(STORES.DRAFTS, 'readonly');
        const store = tx.objectStore(STORES.DRAFTS);
        const index = store.index('status');

        return new Promise((resolve, reject) => {
            const request = index.getAll('draft');
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Generate SHA-256 hash of payload for integrity checking.
     */
    async hashPayload(payload) {
        const data = new TextEncoder().encode(JSON.stringify(payload));
        const hash = await crypto.subtle.digest('SHA-256', data);
        return Array.from(new Uint8Array(hash))
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
    }
}

// Global instance
window.akruDB = new AkruDB();

export default AkruDB;
export { STORES };
