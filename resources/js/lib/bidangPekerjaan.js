import axios from 'axios';
import { computed, ref } from 'vue';

/**
 * Pilihan bidang pekerjaan untuk form peminjaman.
 *
 * Isinya pendek dan jarang berubah, jadi hasilnya disimpan di memori dengan
 * pola yang sama seperti lib/areas.js dan lib/pekerjaanOh.js.
 */

const LOAD_ERROR_MESSAGE = 'Daftar bidang pekerjaan gagal dimuat. Muat ulang halaman untuk mencoba lagi.';

let cachedOptions = null;
let inFlightRequest = null;

export const loadBidangPekerjaanOptions = async ({ force = false } = {}) => {
    if (!force && cachedOptions) {
        return cachedOptions;
    }

    if (!force && inFlightRequest) {
        return inFlightRequest;
    }

    inFlightRequest = axios
        .get('/api/bidang-pekerjaans/pilihan', { __skipGlobalLoading: true })
        .then((response) => {
            cachedOptions = Array.isArray(response.data?.data) ? response.data.data : [];
            return cachedOptions;
        })
        .finally(() => {
            inFlightRequest = null;
        });

    return inFlightRequest;
};

/** Dipanggil setelah bidang dibuat/diubah/dihapus. */
export const invalidateBidangPekerjaanCache = () => {
    cachedOptions = null;
    inFlightRequest = null;
};

/**
 * Nilai pilihan sengaja berupa teks berisi id, karena SearchableSelect bekerja
 * dengan nilai string. Pemanggil mengubahnya kembali ke angka saat mengirim.
 */
export const buildBidangPekerjaanChoices = (options) =>
    (Array.isArray(options) ? options : [])
        .filter((item) => String(item?.nama ?? '').trim() !== '')
        .map((item) => ({
            value: String(item.id),
            label: item.nama,
            search: item.nama,
        }));

export const useBidangPekerjaanOptions = () => {
    const options = ref([]);
    const isLoading = ref(false);
    const error = ref('');

    const load = async ({ force = false } = {}) => {
        isLoading.value = true;
        error.value = '';

        try {
            options.value = await loadBidangPekerjaanOptions({ force });
        } catch (exception) {
            options.value = [];
            error.value = LOAD_ERROR_MESSAGE;
        } finally {
            isLoading.value = false;
        }
    };

    const choices = computed(() => buildBidangPekerjaanChoices(options.value));

    return { options, isLoading, error, choices, load };
};
