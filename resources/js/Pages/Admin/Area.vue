<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Kelola Area</h1>
        <p class="mt-1 text-sm text-slate-500">Super admin dapat menambah, mengubah, dan menghapus seluruh area.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Area</h2>
                <p class="mt-1 text-sm text-slate-500">Slug area dibuat otomatis dari nama area.</p>
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                type="button"
                @click="openCreate"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Area
            </button>
        </div>

        <div class="mt-5">
            <label class="relative block max-w-md">
                <span class="sr-only">Cari area</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama atau slug area..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-[760px] w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left font-semibold text-slate-500">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Slug</th>
                            <th class="px-4 py-3 text-center">Pengguna</th>
                            <th class="px-4 py-3">Dibuat</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-if="isLoading">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                Memuat data area...
                            </td>
                        </tr>
                        <tr v-else-if="loadError">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-rose-500">
                                {{ loadError }}
                            </td>
                        </tr>
                        <tr v-else v-for="area in areas" :key="area.id">
                            <td class="px-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ area.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID #{{ area.id }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ area.slug }}</td>
                            <td class="px-4 py-4 text-center font-semibold text-slate-700">{{ area.users_count }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ area.created_at ?? '-' }}</td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-blue-200 hover:text-blue-600"
                                        type="button"
                                        @click="openEdit(area)"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                        </svg>
                                    </button>
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-500 transition hover:bg-rose-50"
                                        type="button"
                                        @click="removeArea(area)"
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
                        <tr v-if="!isLoading && !loadError && !areas.length">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">
                                Tidak ada data area.
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
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} | Total {{ pagination.total }} area
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
                            {{ isEdit ? 'Edit Area' : 'Tambah Area' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ isEdit ? 'Perbarui nama area yang dipilih.' : 'Tambahkan area baru ke sistem.' }}
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
                        <span>Nama Area *</span>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Contoh: KS TUBUN"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        <p v-if="errors.name" class="text-xs text-rose-500">{{ errors.name }}</p>
                    </label>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Slug Otomatis</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ previewSlug || 'area' }}</p>
                    </div>
                </div>

                <p v-if="errors.general || formError" class="mt-3 text-sm font-semibold text-rose-500">
                    {{ errors.general || formError }}
                </p>

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

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Kelola Area',
                subtitle: 'Kelola seluruh area operasional',
                activeMenu: 'area',
            },
            () => page
        ),
});

const areas = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const search = ref('');
const formOpen = ref(false);
const isSubmitting = ref(false);
const formError = ref('');
const errors = ref({});
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
    name: '',
});

const isEdit = computed(() => form.id !== null);
const previewSlug = computed(() =>
    String(form.name ?? '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
);

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

const normalizeErrors = (payload) => {
    if (!payload) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(payload).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])
    );
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

const resetForm = () => {
    form.id = null;
    form.name = '';
    formError.value = '';
    errors.value = {};
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const openEdit = (area) => {
    form.id = area.id;
    form.name = area.name ?? '';
    formError.value = '';
    errors.value = {};
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
    resetForm();
};

const buildParams = () => {
    const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage,
    };

    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }

    return params;
};

const loadAreas = async () => {
    isLoading.value = true;
    loadError.value = '';

    try {
        const response = await axios.get('/api/admin/areas', { params: buildParams() });
        const payload = response.data ?? {};
        areas.value = Array.isArray(payload.data) ? payload.data : [];
        pagination.currentPage = Number(payload.meta?.current_page ?? 1) || 1;
        pagination.lastPage = Number(payload.meta?.last_page ?? 1) || 1;
        pagination.perPage = Number(payload.meta?.per_page ?? pagination.perPage) || 10;
        pagination.total = Number(payload.meta?.total ?? areas.value.length) || 0;
    } catch (error) {
        areas.value = [];
        loadError.value = error.response?.data?.message ?? 'Gagal memuat data area.';
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
    loadAreas();
};

const submitForm = async () => {
    isSubmitting.value = true;
    formError.value = '';
    errors.value = {};

    try {
        const payload = {
            name: form.name,
        };

        if (isEdit.value) {
            await axios.put(`/api/admin/areas/${form.id}`, payload);
        } else {
            await axios.post('/api/admin/areas', payload);
        }

        await loadAreas();
        showAlert('success', `Area berhasil ${isEdit.value ? 'diperbarui' : 'ditambahkan'}.`);
        closeForm();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = normalizeErrors(error.response?.data?.errors);
            formError.value = error.response?.data?.message ?? 'Validasi gagal.';
        } else if (error.response?.status === 403) {
            formError.value = 'Anda tidak memiliki akses untuk mengelola area.';
        } else {
            formError.value = error.response?.data?.message ?? 'Gagal menyimpan data area.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

const removeArea = async (area) => {
    if (!confirm(`Hapus area "${area.name}"?`)) {
        return;
    }

    try {
        await axios.delete(`/api/admin/areas/${area.id}`);

        if (areas.value.length === 1 && pagination.currentPage > 1) {
            pagination.currentPage -= 1;
        }

        await loadAreas();
        showAlert('success', 'Area berhasil dihapus.');
    } catch (error) {
        showAlert('error', error.response?.data?.message ?? 'Gagal menghapus area.');
    }
};

watch(search, () => {
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }

    filterTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        loadAreas();
    }, 300);
});

onMounted(() => {
    loadAreas();
});
</script>
