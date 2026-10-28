<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Kelola Bidang Pekerjaan</h1>
        <p class="mt-1 text-sm text-slate-500">
            Admin dan super admin dapat menambah, mengubah, dan menghapus bidang pekerjaan.
        </p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Bidang Pekerjaan</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Bidang ini dipilih peminjam saat mengajukan peminjaman alat.
                </p>
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                type="button"
                @click="openCreate"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Bidang
            </button>
        </div>

        <div class="mt-5">
            <label class="relative block max-w-md">
                <span class="sr-only">Cari bidang pekerjaan</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama bidang pekerjaan..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-[640px] w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left font-semibold text-slate-500">
                            <th class="px-4 py-3">Nama Bidang</th>
                            <th class="px-4 py-3 text-center">Dipakai Peminjaman</th>
                            <th class="px-4 py-3">Dibuat</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-if="isLoading">
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                Memuat data bidang pekerjaan...
                            </td>
                        </tr>
                        <tr v-else-if="loadError">
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-rose-500">
                                {{ loadError }}
                            </td>
                        </tr>
                        <tr v-else v-for="bidang in rows" :key="bidang.id">
                            <td class="px-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ bidang.nama }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID #{{ bidang.id }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-700">
                                {{ bidang.jumlah_peminjaman }}
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ bidang.created_at ?? '-' }}</td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-blue-200 hover:text-blue-600"
                                        type="button"
                                        @click="openEdit(bidang)"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                        </svg>
                                    </button>
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-500 transition hover:bg-rose-50"
                                        type="button"
                                        @click="removeBidang(bidang)"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18" />
                                            <path d="M8 6V4h8v2" />
                                            <path d="m6 6 1 14h10l1-14" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!isLoading && !loadError && !rows.length">
                            <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                                Tidak ada data bidang pekerjaan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="pagination.lastPage > 1"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            <span>
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} | Total {{ pagination.total }} bidang
            </span>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:text-slate-300"
                    type="button"
                    :disabled="pagination.currentPage === 1"
                    @click="goToPage(pagination.currentPage - 1)"
                >
                    Sebelumnya
                </button>
                <button
                    v-for="page in pageNumbers"
                    :key="page"
                    class="h-9 min-w-[36px] rounded-lg border px-3 text-sm font-semibold transition"
                    :class="page === pagination.currentPage
                        ? 'border-blue-600 bg-blue-600 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                    type="button"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>
                <button
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:text-slate-300"
                    type="button"
                    :disabled="pagination.currentPage === pagination.lastPage"
                    @click="goToPage(pagination.currentPage + 1)"
                >
                    Selanjutnya
                </button>
            </div>
        </div>
    </section>

    <teleport to="body">
        <div
            v-if="formOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="closeForm"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ isEdit ? 'Edit Bidang Pekerjaan' : 'Tambah Bidang Pekerjaan' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ isEdit ? 'Perbarui bidang yang dipilih.' : 'Tambahkan bidang pekerjaan baru ke sistem.' }}
                        </p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeForm"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Nama Bidang *</span>
                        <input
                            v-model="form.nama"
                            type="text"
                            placeholder="Contoh: Listrik"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            @keyup.enter="submitForm"
                        />
                        <p v-if="errors.nama" class="text-xs text-rose-500">{{ errors.nama }}</p>
                    </label>
                </div>

                <p v-if="formError" class="mt-3 text-sm font-semibold text-rose-500">{{ formError }}</p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeForm"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="isSubmitting"
                        @click="submitForm"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';
import { invalidateBidangPekerjaanCache } from '../../lib/bidangPekerjaan';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Bidang Pekerjaan',
                subtitle: 'Kelola daftar bidang pekerjaan',
                activeMenu: 'bidang-pekerjaan',
            },
            () => page
        ),
});

const rows = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const search = ref('');
const formOpen = ref(false);
const isSubmitting = ref(false);
const formError = ref('');
const errors = reactive({ nama: '' });
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;
let filterTimeout = null;

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 10,
});

const form = reactive({
    id: null,
    nama: '',
});

const isEdit = computed(() => form.id !== null);

const pageNumbers = computed(() => {
    const total = pagination.lastPage;
    const current = pagination.currentPage;
    const delta = 2;
    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);
    const pages = [];

    for (let i = start; i <= end; i += 1) {
        pages.push(i);
    }

    return pages;
});

const loadRows = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/bidang-pekerjaans', {
            params: {
                search: search.value || undefined,
                page: pagination.currentPage,
                per_page: pagination.perPage,
            },
            __skipGlobalLoading: true,
        });

        rows.value = Array.isArray(response.data?.data) ? response.data.data : [];
        const meta = response.data?.meta ?? {};
        pagination.currentPage = meta.current_page ?? 1;
        pagination.lastPage = meta.last_page ?? 1;
        pagination.total = meta.total ?? rows.value.length;
    } catch (error) {
        rows.value = [];
        loadError.value = error?.response?.data?.message ?? 'Gagal memuat bidang pekerjaan.';
    } finally {
        isLoading.value = false;
    }
};

const goToPage = (page) => {
    const next = Math.min(Math.max(1, page), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }
    pagination.currentPage = next;
    loadRows();
};

const resetForm = () => {
    form.id = null;
    form.nama = '';
    errors.nama = '';
    formError.value = '';
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const openEdit = (bidang) => {
    resetForm();
    form.id = bidang.id;
    form.nama = bidang.nama;
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const submitForm = async () => {
    errors.nama = '';
    formError.value = '';

    if (!form.nama.trim()) {
        errors.nama = 'Nama bidang wajib diisi.';
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = { nama: form.nama.trim() };
        const response = form.id
            ? await axios.put('/api/bidang-pekerjaans/' + form.id, payload)
            : await axios.post('/api/bidang-pekerjaans', payload);

        // Daftar ini juga dipakai sebagai pilihan di form peminjaman.
        invalidateBidangPekerjaanCache();
        await loadRows();
        closeForm();
        showAlert('success', response.data?.message ?? 'Bidang pekerjaan berhasil disimpan.');
    } catch (error) {
        errors.nama = error?.response?.data?.errors?.nama?.[0] ?? '';
        formError.value = errors.nama
            ? ''
            : error?.response?.data?.message ?? 'Gagal menyimpan bidang pekerjaan.';
    } finally {
        isSubmitting.value = false;
    }
};

const removeBidang = async (bidang) => {
    if (!confirm(`Hapus bidang pekerjaan "${bidang.nama}"?`)) {
        return;
    }

    try {
        const response = await axios.delete('/api/bidang-pekerjaans/' + bidang.id);
        invalidateBidangPekerjaanCache();
        await loadRows();
        showAlert('success', response.data?.message ?? 'Bidang pekerjaan berhasil dihapus.');
    } catch (error) {
        showAlert('error', error?.response?.data?.message ?? 'Gagal menghapus bidang pekerjaan.');
    }
};

const showAlert = (type, message) => {
    alertType.value = type;
    alertTitle.value = type === 'success' ? 'Berhasil' : 'Gagal';
    alertMessage.value = message;
    if (alertTimeout) {
        clearTimeout(alertTimeout);
    }
    alertTimeout = setTimeout(() => {
        alertMessage.value = '';
        alertTitle.value = '';
    }, 3000);
};

const closeAlert = () => {
    if (alertTimeout) {
        clearTimeout(alertTimeout);
    }
    alertMessage.value = '';
    alertTitle.value = '';
};

watch(search, () => {
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }
    filterTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        loadRows();
    }, 300);
});

onMounted(() => {
    loadRows();
});
</script>
