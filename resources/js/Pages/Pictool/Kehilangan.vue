<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Laporan Kehilangan</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau laporan kehilangan alat per area</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M3 7h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                        <path d="M3 7l4-4h10l4 4" />
                        <path d="m8 12 8 8" />
                        <path d="m16 12-8 8" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Data Kehilangan</h2>
                    <p class="mt-1 text-sm text-slate-500">Area aktif: {{ areaName }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300"
                    type="button"
                    @click="exportCsv"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12" />
                        <path d="m7 10 5 5 5-5" />
                        <path d="M5 21h14" />
                    </svg>
                    Export CSV
                </button>
                <button
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                    type="button"
                    @click="openCreate"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Tambah Laporan
                </button>
            </div>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1.2fr,0.8fr,0.6fr]">
            <label class="relative">
                <span class="sr-only">Cari laporan</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari alat, pelapor, atau deskripsi..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
            <select
                v-model="statusFilter"
                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
                <option v-for="status in statusOptions" :key="status" :value="status">
                    Status - {{ status }}
                </option>
            </select>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-widest text-slate-500">
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Alat</th>
                            <th class="px-4 py-3">Pelapor</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-if="isLoading">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                Memuat data kehilangan...
                            </td>
                        </tr>
                        <tr v-else-if="loadError">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-rose-500">
                                {{ loadError }}
                            </td>
                        </tr>
                        <tr v-else v-for="item in reports" :key="item.id">
                            <td class="px-4 py-4 text-slate-600">
                                <p class="font-semibold text-slate-900">{{ item.createdAt }}</p>
                                <p class="mt-1 text-xs text-slate-400">#{{ item.id }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ item.alatNama }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ item.alatKode }} - {{ item.areaName }}</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ item.userName }}
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ item.jumlah }} unit
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="rounded-full px-3 py-1 text-[11px] font-semibold"
                                    :class="statusBadge(item.status)"
                                >
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <p class="line-clamp-2 max-w-[240px]">
                                    {{ item.deskripsi }}
                                </p>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        class="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:border-amber-300"
                                        type="button"
                                        @click="openStatusModal(item)"
                                    >
                                        Persetujuan
                                    </button>
                                    <button
                                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                        type="button"
                                        @click="openDetail(item)"
                                    >
                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!isLoading && !loadError && !reports.length">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada laporan kehilangan.
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
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} - Total {{ pagination.total }} laporan
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
            class="fixed inset-0 z-50 bg-slate-900/50"
        >
            <div class="flex min-h-full items-center justify-center overflow-y-auto p-4" @click.self="closeForm">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Tambah Laporan Kehilangan</h3>
                        <p class="mt-1 text-sm text-slate-500">Isi detail kehilangan alat yang ditemukan.</p>
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

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>Alat *</span>
                        <select
                            v-model="form.alat_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Pilih alat</option>
                            <option v-for="tool in tools" :key="tool.id" :value="tool.id">
                                {{ tool.nama }} ({{ tool.kode }})
                            </option>
                        </select>
                        <p v-if="formErrors.alat_id" class="text-xs font-semibold text-rose-500">{{ formErrors.alat_id }}</p>
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Jumlah Hilang *</span>
                        <input
                            v-model.number="form.jumlah"
                            type="number"
                            min="1"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        <p v-if="formErrors.jumlah" class="text-xs font-semibold text-rose-500">{{ formErrors.jumlah }}</p>
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Status</span>
                        <input
                            type="text"
                            value="Dilaporkan"
                            disabled
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700"
                        />
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>Deskripsi Kehilangan *</span>
                        <textarea
                            v-model="form.deskripsi"
                            rows="3"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Jelaskan kehilangan yang terjadi"
                        ></textarea>
                        <p v-if="formErrors.deskripsi" class="text-xs font-semibold text-rose-500">{{ formErrors.deskripsi }}</p>
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>Foto Kehilangan *</span>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700"
                            @change="handleFileChange"
                        />
                        <p v-if="formErrors.foto" class="text-xs font-semibold text-rose-500">{{ formErrors.foto }}</p>
                    </label>
                </div>

                <div v-if="previewUrl" class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                    <img :src="previewUrl" alt="Preview" class="h-56 w-full object-cover" />
                </div>

                <p v-if="formError" class="mt-3 text-sm font-semibold text-rose-500">
                    {{ formError }}
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
                        {{ isSubmitting ? 'Mengirim...' : 'Kirim Laporan' }}
                    </button>
                </div>
            </div>
            </div>
        </div>
    </teleport>

    <teleport to="body">
        <div
            v-if="detailItem"
            class="fixed inset-0 z-50 bg-slate-900/50"
        >
            <div class="flex min-h-full items-center justify-center overflow-y-auto p-4" @click.self="detailItem = null">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Detail Kehilangan</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ detailItem?.alatNama }} - {{ detailItem?.alatKode }}</p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="detailItem = null"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="space-y-3 text-sm">
                        <p class="text-slate-500">Pelapor</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.userName }}</p>
                        <p class="text-slate-500">Area</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.areaName }}</p>
                        <p class="text-slate-500">Tanggal</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.createdAt }}</p>
                        <p class="text-slate-500">Jumlah</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.jumlah }} unit</p>
                        <p class="text-slate-500">Status</p>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                            :class="statusBadge(detailItem?.status)"
                        >
                            {{ detailItem?.status }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Deskripsi</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ detailItem?.deskripsi }}</p>
                        <div v-if="detailItem?.url" class="mt-4 overflow-hidden rounded-2xl border border-slate-200">
                            <img :src="detailItem.url" alt="Foto kehilangan" class="h-56 w-full object-cover" />
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </teleport>

    <LaporanStatusModal
        :open="!!selectedStatusItem"
        :report="selectedStatusItem"
        :model-value="statusForm.status"
        report-label="kehilangan"
        :error="statusError"
        :is-submitting="isStatusSubmitting"
        @close="closeStatusModal"
        @submit="submitStatus"
        @update:model-value="statusForm.status = $event"
    />
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import LaporanStatusModal from '../../Components/LaporanStatusModal.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Laporan Kehilangan',
                subtitle: 'Pantau laporan kehilangan alat per area',
                activeMenu: 'laporan-kehilangan',
            },
            () => page
        ),
});

const page = usePage();
const loadCachedUser = () => {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        const cached = window.localStorage.getItem('auth_user');
        return cached ? JSON.parse(cached) : null;
    } catch (err) {
        return null;
    }
};

const cachedUser = ref(loadCachedUser());
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const setAreaSwitching = inject('setAreaSwitching', null);
const activeAreaName = inject('activeAreaName', ref('Area tidak diketahui'));
const refreshSidebarNotificationCounts = inject('refreshSidebarNotificationCounts', async () => {});
const areaName = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaName.value
        : page.props.auth?.user?.area?.name ?? 'Area tidak diketahui'
);

const reports = ref([]);
const tools = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const search = ref('');
const selectedTool = ref('');
const statusFilter = ref('Semua');

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 10,
});

const formOpen = ref(false);
const formError = ref('');
const formErrors = reactive({
    alat_id: '',
    jumlah: '',
    deskripsi: '',
    foto: '',
});
const isSubmitting = ref(false);
const fileInput = ref(null);
const previewUrl = ref('');
const detailItem = ref(null);
const selectedStatusItem = ref(null);
const isStatusSubmitting = ref(false);
const statusError = ref('');
const statusForm = reactive({
    status: '',
});

const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const form = reactive({
    alat_id: '',
    jumlah: 1,
    deskripsi: '',
    foto: null,
});

const statusOptions = computed(() => {
    const base = ['Dilaporkan', 'Disetujui', 'Ditolak', 'Selesai'];
    const dynamic = new Set(base);
    reports.value.forEach((item) => {
        if (item.status) {
            dynamic.add(item.status);
        }
    });
    return ['Semua', ...Array.from(dynamic)];
});

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

const statusBadge = (status) => {
    switch ((status || '').toLowerCase()) {
        case 'dilaporkan':
            return 'bg-amber-100 text-amber-700';
        case 'disetujui':
            return 'bg-blue-100 text-blue-700';
        case 'ditolak':
            return 'bg-rose-100 text-rose-700';
        case 'selesai':
            return 'bg-emerald-100 text-emerald-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const normalizeReport = (item) => ({
    id: item?.id ?? '',
    alatId: item?.alat_id ?? '',
    alatNama: item?.alat_nama ?? '-',
    alatKode: item?.alat_kode ?? '-',
    areaName: item?.area_name ?? '-',
    userName: item?.user_name ?? '-',
    jumlah: Number.isFinite(item?.jumlah) ? item.jumlah : 0,
    status: item?.status ?? 'Dilaporkan',
    deskripsi: item?.deskripsi ?? '-',
    url: item?.url ?? '',
    createdAt: item?.created_at ?? '-',
});

const buildParams = () => {
    const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage,
    };
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (selectedTool.value) {
        params.alat_id = selectedTool.value;
    }
    if (statusFilter.value && statusFilter.value !== 'Semua') {
        params.status = statusFilter.value;
    }
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }
    return params;
};

const buildExportParams = () => {
    const params = {};
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (selectedTool.value) {
        params.alat_id = selectedTool.value;
    }
    if (statusFilter.value && statusFilter.value !== 'Semua') {
        params.status = statusFilter.value;
    }
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }
    return params;
};

const exportCsv = () => {
    axios.get('/api/laporan-kehilangan/export', {
        params: buildExportParams(),
        responseType: 'blob',
    })
        .then((response) => {
            const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            const disposition = response.headers['content-disposition'] ?? '';
            const match = disposition.match(/filename=\"?([^\";]+)\"?/i);

            link.href = url;
            link.download = match?.[1] ?? 'laporan-kehilangan.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(() => {
            showAlert('error', 'Gagal mengunduh file CSV.');
        });
};

const loadReports = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/laporan-kehilangan', {
            params: buildParams(),
        });
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        reports.value = data.map((item) => normalizeReport(item));
        if (Array.isArray(payload)) {
            pagination.currentPage = 1;
            pagination.lastPage = 1;
            pagination.total = reports.value.length;
        } else {
            const meta = payload?.meta ?? {};
            pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
            pagination.lastPage = Number(meta.last_page ?? 1);
            pagination.perPage = Number(meta.per_page ?? pagination.perPage);
            pagination.total = Number(meta.total ?? reports.value.length);
        }
    } catch (error) {
        reports.value = [];
        loadError.value = 'Gagal memuat laporan kehilangan.';
        showAlert('error', loadError.value);
    } finally {
        isLoading.value = false;
    }
};

const loadTools = async () => {
    try {
        const params = {};
        if (isAreaSwitcherRole.value && activeAreaId.value) {
            params.area_id = activeAreaId.value;
        }
        const response = await axios.get('/api/alats', { params });
        const data = Array.isArray(response.data) ? response.data : [];
        tools.value = data.map((tool) => ({
            id: tool.id,
            nama: tool.nama ?? '-',
            kode: tool.kode ?? '-',
        }));
    } catch (error) {
        tools.value = [];
    }
};

const goToPage = (pageNumber) => {
    const next = Math.min(Math.max(1, pageNumber), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }
    pagination.currentPage = next;
    loadReports();
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

const resetFormErrors = () => {
    formError.value = '';
    formErrors.alat_id = '';
    formErrors.jumlah = '';
    formErrors.deskripsi = '';
    formErrors.foto = '';
};

const resetForm = () => {
    form.alat_id = '';
    form.jumlah = 1;
    form.deskripsi = '';
    form.foto = null;
    resetFormErrors();
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = '';
    }
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const handleFileChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    form.foto = file;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = '';
    }
    if (file) {
        previewUrl.value = URL.createObjectURL(file);
    }
};

const submitForm = async () => {
    resetFormErrors();
    if (!form.alat_id || !form.deskripsi || !form.foto || form.jumlah < 1) {
        formError.value = 'Lengkapi semua field wajib.';
        return;
    }
    isSubmitting.value = true;
    try {
        const payload = new FormData();
        payload.append('alat_id', form.alat_id);
        payload.append('deskripsi', form.deskripsi);
        payload.append('jumlah', form.jumlah);
        payload.append('foto', form.foto);

        await axios.post('/api/laporan-kehilangan', payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            params: isAreaSwitcherRole.value && activeAreaId.value ? { area_id: activeAreaId.value } : {},
        });

        await loadReports();
        await refreshSidebarNotificationCounts();
        closeForm();
        showAlert('success', 'Laporan kehilangan berhasil dikirim.');
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response?.data?.errors ?? {};
            formErrors.alat_id = errors.alat_id?.[0] ?? '';
            formErrors.jumlah = errors.jumlah?.[0] ?? '';
            formErrors.deskripsi = errors.deskripsi?.[0] ?? '';
            formErrors.foto = errors.foto?.[0] ?? '';
            formError.value = error.response?.data?.message ?? 'Periksa kembali isian.';
        } else {
            formError.value = error.response?.data?.message ?? 'Gagal mengirim laporan.';
        }
        showAlert('error', formError.value);
    } finally {
        isSubmitting.value = false;
    }
};

const openDetail = (item) => {
    detailItem.value = item;
};

const openStatusModal = (item) => {
    selectedStatusItem.value = item;
    statusForm.status = item?.status && item.status !== 'Dilaporkan' ? item.status : '';
    statusError.value = '';
};

const closeStatusModal = () => {
    selectedStatusItem.value = null;
    statusForm.status = '';
    statusError.value = '';
};

const submitStatus = async () => {
    if (!selectedStatusItem.value?.id || !statusForm.status) {
        statusError.value = 'Pilih status terlebih dahulu.';
        return;
    }

    isStatusSubmitting.value = true;
    statusError.value = '';
    try {
        await axios.put(`/api/laporan-kehilangan/${selectedStatusItem.value.id}`, {
            status: statusForm.status,
            ...(isAreaSwitcherRole.value && activeAreaId.value ? { area_id: activeAreaId.value } : {}),
        });
        await loadReports();
        await refreshSidebarNotificationCounts();
        closeStatusModal();
        showAlert('success', 'Status laporan kehilangan berhasil diperbarui.');
    } catch (error) {
        statusError.value =
            error?.response?.data?.errors?.status?.[0] ??
            error?.response?.data?.message ??
            'Gagal memperbarui status laporan.';
        showAlert('error', statusError.value);
    } finally {
        isStatusSubmitting.value = false;
    }
};

let filterTimeout = null;
watch([search, selectedTool, statusFilter], () => {
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }
    filterTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        loadReports();
    }, 300);
});

onMounted(() => {
    cachedUser.value = loadCachedUser();
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(true);
    }
    Promise.all([loadTools(), loadReports()]).finally(() => {
        if (isAreaSwitcherRole.value) {
            setAreaSwitching?.(false);
        }
    });
});

watch(
    () => activeAreaId.value,
    async (next, prev) => {
        if (!isAreaSwitcherRole.value) {
            return;
        }
        const shouldShow = prev !== undefined && prev !== null && next !== prev;
        if (shouldShow) {
            setAreaSwitching?.(true);
        }
        pagination.currentPage = 1;
        selectedTool.value = '';
        try {
            await Promise.all([loadTools(), loadReports()]);
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);
</script>
