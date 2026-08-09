(() => {
    'use strict';

    const DB_NAME = 'vk-finance';
    const DB_VERSION = 1;
    const STORE_NAME = 'app_data';

    function openDatabase() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open(DB_NAME, DB_VERSION);

            request.onerror = () => {
                reject(request.error);
            };

            request.onupgradeneeded = () => {
                const db = request.result;

                if (!db.objectStoreNames.contains(STORE_NAME)) {
                    db.createObjectStore(STORE_NAME);
                }
            };

            request.onsuccess = () => {
                resolve(request.result);
            };
        });
    }

    async function set(key, value) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readwrite'
            );

            transaction.objectStore(STORE_NAME).put(value, key);

            transaction.oncomplete = () => {
                db.close();
                resolve();
            };

            transaction.onerror = () => {
                db.close();
                reject(transaction.error);
            };
        });
    }

    async function get(key) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readonly'
            );

            const request =
                transaction.objectStore(STORE_NAME).get(key);

            request.onsuccess = () => {
                resolve(request.result ?? null);
            };

            request.onerror = () => {
                reject(request.error);
            };

            transaction.oncomplete = () => {
                db.close();
            };
        });
    }

    async function remove(key) {
        const db = await openDatabase();

        return new Promise((resolve, reject) => {
            const transaction = db.transaction(
                STORE_NAME,
                'readwrite'
            );

            transaction.objectStore(STORE_NAME).delete(key);

            transaction.oncomplete = () => {
                db.close();
                resolve();
            };

            transaction.onerror = () => {
                db.close();
                reject(transaction.error);
            };
        });
    }

    window.VKStorage = {
        set,
        get,
        remove
    };
})();