import axios from 'axios';
import { computed, ref } from 'vue';

/**
 * Pilihan pekerjaan overhaul untuk field "Pekerjaan" pada form peminjaman.
 *
 * Daftarnya dipakai beberapa halaman dan hampir tidak berubah selama pengguna
 * membuka aplikasi, jadi hasilnya disimpan di memori dengan pola yang sama
 * seperti lib/areas.js: permintaan paralel dari beberapa komponen cukup
 * menghasilkan satu request.
 *
 * Label pilihan juga dibangun di sini supaya ketiga form menampilkan teks yang
 * sama persis.
 */

const STATUS_LABEL = {
    berlangsung: 'berlangsung',
    akan_datang: 'akan datang',
    selesai: 'selesai',
};

const LOAD_ERROR_MESSAGE = 'Daftar pekerjaan overhaul gagal dimuat. Muat ulang halaman untuk mencoba lagi.';

let cachedOptions = null;
let inFlightRequest = null;

export const loadPekerjaanOhOptions = async ({ force = false } = {}) => {
    if (!force && cachedOptions) {
        return cachedOptions;
    }

    if (!force && inFlightRequest) {
        return inFlightRequest;
    }

    // Kegagalan sengaja tidak ditelan di sini supaya form bisa memberi tahu
    // pengguna, dan percobaan berikutnya tetap menembak ulang server.
    inFlightRequest = axios
        .get('/api/pekerjaan-ohs/pilihan', { __skipGlobalLoading: true })
        .then((response) => {
            cachedOptions = Array.isArray(response.data?.data) ? response.data.data : [];
            return cachedOptions;
        })
        .finally(() => {
            inFlightRequest = null;
        });

    return inFlightRequest;
};

/** Dipanggil setelah pekerjaan overhaul dibuat/diubah/dihapus. */
export const invalidatePekerjaanOhCache = () => {
    cachedOptions = null;
    inFlightRequest = null;
};

/**
 * @param {Array}  options      Hasil dari endpoint pilihan.
 * @param {string} currentValue Nilai yang sedang terpilih di form.
 */
export const buildPekerjaanOhChoices = (options, currentValue = '') => {
    const choices = (Array.isArray(options) ? options : [])
        .filter((item) => String(item?.nama ?? '').trim() !== '')
        .map((item) => {
            const keterangan = [item.periode, STATUS_LABEL[item.status]].filter(Boolean).join(' - ');

            return {
                value: item.nama,
                label: keterangan ? `${item.nama} (${keterangan})` : item.nama,
                // Pencarian diarahkan ke nama lebih dulu. Tanpa ini, angka pada
                // periode ikut tercocokkan dan "unit 2" juga memunculkan
                // pekerjaan lain yang periodenya mengandung 2027.
                search: item.nama,
            };
        });

    // Peminjaman yang diulang, draft lama, dan data peminjaman yang sudah ada
    // bisa memuat pekerjaan yang tidak lagi ada di daftar. Nilainya tetap
    // ditampilkan agar tidak terhapus diam-diam saat form dibuka.
    const selected = String(currentValue ?? '').trim();
    if (selected !== '' && !choices.some((item) => item.value === selected)) {
        choices.unshift({
            value: selected,
            label: `${selected} (di luar daftar)`,
            search: selected,
        });
    }

    return choices;
};

/**
 * Dipakai komponen yang butuh daftar beserta status muat dan pesan errornya.
 *
 * @param {Function} currentValue Getter nilai pekerjaan yang sedang terpilih.
 */
/**
 * Form menyimpan nama pekerjaan (agar nilai lama yang diisi bebas tetap utuh),
 * sedangkan database menyimpan relasinya sebagai id. Fungsi ini menjembatani
 * keduanya saat form dikirim; nama yang tidak ada di daftar menghasilkan null.
 */
export const findPekerjaanOhId = (options, nama) => {
    const dicari = String(nama ?? '').trim().toLowerCase();
    if (dicari === '') {
        return null;
    }

    const cocok = (Array.isArray(options) ? options : []).find(
        (item) => String(item?.nama ?? '').trim().toLowerCase() === dicari
    );

    return cocok?.id ?? null;
};

export const usePekerjaanOhOptions = (currentValue = () => '') => {
    const options = ref([]);
    const isLoading = ref(false);
    const error = ref('');

    const load = async ({ force = false } = {}) => {
        isLoading.value = true;
        error.value = '';

        try {
            options.value = await loadPekerjaanOhOptions({ force });
        } catch (exception) {
            options.value = [];
            error.value = LOAD_ERROR_MESSAGE;
        } finally {
            isLoading.value = false;
        }
    };

    const choices = computed(() => buildPekerjaanOhChoices(options.value, currentValue()));

    return { options, isLoading, error, choices, load };
};
