import axios from 'axios';

/**
 * Daftar area hampir tidak pernah berubah namun sebelumnya diambil ulang oleh
 * AppLayout dan tiap halaman pada setiap navigasi. Modul ini menyimpan hasilnya
 * di memori dan membagikan promise yang sama sehingga permintaan paralel dari
 * beberapa komponen hanya menghasilkan satu request.
 */
let cachedAreas = null;
let inFlightRequest = null;

export const loadAreas = async ({ force = false } = {}) => {
    if (!force && cachedAreas) {
        return cachedAreas;
    }

    if (!force && inFlightRequest) {
        return inFlightRequest;
    }

    inFlightRequest = axios
        .get('/api/areas', { __skipGlobalLoading: true })
        .then((response) => {
            cachedAreas = Array.isArray(response.data) ? response.data : [];
            return cachedAreas;
        })
        .catch(() => [])
        .finally(() => {
            inFlightRequest = null;
        });

    return inFlightRequest;
};

/** Dipanggil setelah area dibuat/diubah/dihapus agar cache tidak basi. */
export const invalidateAreasCache = () => {
    cachedAreas = null;
    inFlightRequest = null;
};
