import { onBeforeUnmount, ref, watch } from 'vue';

/**
 * Penyimpanan draft form di localStorage.
 *
 * Tujuannya satu: isian yang belum dikirim tidak hilang kalau sesi berakhir,
 * tab tertutup, atau browser crash. Draft disimpan per pengguna (dan per baris
 * untuk form yang terikat pada satu data) supaya di komputer bersama draft
 * seseorang tidak muncul di akun orang lain.
 *
 * Draft hanya menyimpan nilai yang bisa di-JSON. File atau foto tidak ikut,
 * jadi form yang mewajibkan lampiran tetap meminta pengguna memilih ulang
 * filenya.
 */

const STORAGE_PREFIX = 'draft_v1';
const SAVE_DEBOUNCE_MS = 600;
const DEFAULT_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;

const storageKeyFor = (name, scope) =>
    `${STORAGE_PREFIX}:${name}:${scope === null || scope === undefined || scope === '' ? 'global' : scope}`;

const toPlain = (value) => {
    try {
        return JSON.parse(JSON.stringify(value ?? null));
    } catch (error) {
        return null;
    }
};

/** Draft yang seluruh isinya kosong tidak perlu disimpan. */
const hasContent = (value) => {
    if (value === null || value === undefined || value === '') {
        return false;
    }
    if (Array.isArray(value)) {
        return value.some((entry) => hasContent(entry));
    }
    if (typeof value === 'object') {
        return Object.values(value).some((entry) => hasContent(entry));
    }
    if (typeof value === 'number') {
        return Number.isFinite(value) && value !== 0;
    }
    if (typeof value === 'string') {
        return value.trim() !== '';
    }
    return true;
};

export const readDraft = (name, scope = null, maxAgeMs = DEFAULT_MAX_AGE_MS) => {
    if (typeof window === 'undefined') {
        return null;
    }

    let stored = null;
    try {
        stored = window.localStorage.getItem(storageKeyFor(name, scope));
    } catch (error) {
        // Mode privat atau penyimpanan diblokir: anggap tidak ada draft.
        return null;
    }

    if (!stored) {
        return null;
    }

    try {
        const parsed = JSON.parse(stored);
        const savedAt = Number(parsed?.savedAt ?? 0);

        if (!parsed || typeof parsed.data !== 'object' || parsed.data === null) {
            return null;
        }

        if (maxAgeMs > 0 && savedAt > 0 && Date.now() - savedAt > maxAgeMs) {
            clearDraft(name, scope);
            return null;
        }

        return { data: parsed.data, savedAt: savedAt > 0 ? new Date(savedAt) : null };
    } catch (error) {
        clearDraft(name, scope);
        return null;
    }
};

export const writeDraft = (name, scope, data) => {
    if (typeof window === 'undefined') {
        return false;
    }

    try {
        window.localStorage.setItem(
            storageKeyFor(name, scope),
            JSON.stringify({ savedAt: Date.now(), data })
        );
        return true;
    } catch (error) {
        // Kuota penuh atau penyimpanan diblokir. Draft bersifat bantuan, jadi
        // kegagalan menyimpan tidak boleh mengganggu pengisian form.
        return false;
    }
};

export const clearDraft = (name, scope = null) => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.removeItem(storageKeyFor(name, scope));
    } catch (error) {
        // Diabaikan: tidak ada yang bisa dilakukan dan tidak perlu diketahui pengguna.
    }
};

/**
 * @param {object}   options
 * @param {string}   options.name     Nama draft, dipakai sebagai bagian kunci penyimpanan.
 * @param {Function} options.source   Getter yang mengembalikan objek isian form.
 * @param {Function} [options.scope]  Getter pembeda draft, misalnya id pengguna atau id baris.
 * @param {Function} [options.isActive] Getter: true saat form sedang dibuka/relevan.
 */
export const useFormDraft = ({
    name,
    source,
    scope = () => null,
    isActive = () => true,
    maxAgeMs = DEFAULT_MAX_AGE_MS,
}) => {
    const savedAt = ref(null);
    let timerId = null;
    let pending = null;
    let pristineJson = null;

    const cancelPending = () => {
        if (timerId !== null) {
            window.clearTimeout(timerId);
            timerId = null;
        }
        pending = null;
    };

    const flush = () => {
        if (timerId !== null) {
            window.clearTimeout(timerId);
            timerId = null;
        }
        if (pending === null) {
            return;
        }

        const { scopeValue, data } = pending;
        pending = null;

        if (writeDraft(name, scopeValue, data)) {
            savedAt.value = new Date();
        }
    };

    watch(
        source,
        (value) => {
            if (!isActive()) {
                return;
            }

            const data = toPlain(value);
            if (data === null || !hasContent(data)) {
                return;
            }

            // Form yang baru dibuka dan belum disentuh tidak dianggap draft,
            // supaya tidak muncul tawaran memulihkan isian yang kosong.
            const currentJson = JSON.stringify(data);
            if (pristineJson !== null && currentJson === pristineJson) {
                return;
            }

            pending = { scopeValue: scope(), data };
            if (timerId === null) {
                timerId = window.setTimeout(flush, SAVE_DEBOUNCE_MS);
            }
        },
        { deep: true }
    );

    /** Dipanggil setelah form dibuka atau direset: jadi acuan "belum disentuh". */
    const markPristine = () => {
        const data = toPlain(source());
        pristineJson = data === null ? null : JSON.stringify(data);
    };

    const restore = (scopeValue = scope()) => readDraft(name, scopeValue, maxAgeMs);

    const clear = (scopeValue = scope()) => {
        cancelPending();
        clearDraft(name, scopeValue);
        savedAt.value = null;
    };

    // Tab ditutup sebelum jeda penyimpanan selesai.
    const handlePageHide = () => flush();
    if (typeof window !== 'undefined') {
        window.addEventListener('pagehide', handlePageHide);
    }

    onBeforeUnmount(() => {
        flush();
        if (typeof window !== 'undefined') {
            window.removeEventListener('pagehide', handlePageHide);
        }
    });

    return { restore, clear, flush, markPristine, savedAt };
};
