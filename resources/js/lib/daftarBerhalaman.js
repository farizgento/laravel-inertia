/**
 * Pembaca respons daftar peminjaman.
 *
 * Endpoint daftar sekarang mengirim { data, meta } agar datanya dibatasi per
 * halaman. Bentuk array lama tetap diterima supaya endpoint yang belum diubah
 * ikut bekerja tanpa penyesuaian terpisah.
 */
export const bacaDaftar = (response) => {
    const isi = response?.data;

    if (Array.isArray(isi)) {
        return { rows: isi, meta: null, hasMore: false };
    }

    const rows = Array.isArray(isi?.data) ? isi.data : [];
    const meta = isi?.meta ?? null;

    return { rows, meta, hasMore: Boolean(meta?.has_more) };
};
