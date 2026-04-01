<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Riwayat Peminjaman - {{ areaName }}</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar riwayat peminjaman yang tersedia</p>
    </div>

    <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Total</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ totalCount }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Menunggu Review</p>
            <p class="mt-2 text-2xl font-semibold text-blue-600">{{ reviewCount }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Disiapkan</p>
            <p class="mt-2 text-2xl font-semibold text-amber-500">{{ processCount }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Terkirim</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ deliveredCount }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start gap-3">
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
                    <path d="M3 4h18l-7 8v6l-4 2v-8L3 4z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Peminjaman</h2>
                <p class="mt-1 text-sm text-slate-500">Tampilkan riwayat dalam format tabel sederhana</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="text"
                    placeholder="Cari keperluan atau ID..."
                />
            </div>
            <div class="w-full lg:w-56">
                <select
                    v-model="statusFilter"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    <option value="Semua">Semua Status</option>
                    <option value="Menunggu Review">Menunggu Review</option>
                    <option value="Dipesan">Dipesan</option>
                    <option value="Disiapkan">Disiapkan</option>
                    <option value="Terkirim">Terkirim</option>
                    <option value="Ditolak">Ditolak</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat data peminjaman...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Belum ada peminjaman.
            </p>
            <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-4 py-3">Dibuat</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Keperluan</th>
                                <th class="px-4 py-3">Periode</th>
                                <th class="px-4 py-3 text-center">Item</th>
                                <th class="px-4 py-3">Foto</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="item in filteredItems"
                                :key="item.id"
                                class="align-top transition hover:bg-slate-50"
                            >
                                <td class="px-4 py-4 text-slate-600">
                                    {{ item.createdAt }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="statusClass(item.status)"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ item.title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID #{{ item.id }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    <p>{{ item.borrowDate }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Kembali: {{ item.returnDate }}</p>
                                </td>
                                <td class="px-4 py-4 text-center font-semibold text-slate-700">
                                    {{ item.itemCount }}
                                </td>
                                <td class="px-4 py-4">
                                    <div
                                        v-if="item.photoPreview.length"
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <div
                                            v-for="photo in item.photoPreview"
                                            :key="photo.id ?? photo.url ?? photo.originalName"
                                            class="h-10 w-10 overflow-hidden rounded-lg border border-slate-200 bg-white"
                                        >
                                            <img
                                                :src="photoSrc(photo)"
                                                :alt="photo.originalName || item.title"
                                                class="h-full w-full object-cover"
                                                loading="lazy"
                                            />
                                        </div>
                                        <span v-if="item.photoExtraCount" class="text-xs text-slate-500">
                                            +{{ item.photoExtraCount }}
                                        </span>
                                    </div>
                                    <span v-else class="text-slate-400">-</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            v-if="item.suratJalanUrl"
                                            class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300"
                                            type="button"
                                            @click="openSuratJalan(item)"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                <path d="M14 2v6h6" />
                                                <path d="M16 13H8" />
                                                <path d="M16 17H8" />
                                                <path d="M10 9H8" />
                                            </svg>
                                            Surat Jalan
                                        </button>
                                        <button
                                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                            type="button"
                                            @click="openDetail(item)"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            Detail
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div
            v-if="pagination.lastPage > 1"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            <span>
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} - Total {{ pagination.total }} peminjaman
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

    <PeminjamanDetailModal
        :open="!!selectedItem"
        :item="selectedItem"
        @close="closeDetail"
    />
    <SuratJalanModal
        :open="!!suratJalanItem"
        :url="suratJalanItem?.suratJalanUrl"
        :path="suratJalanItem?.suratJalanPath"
        :title="suratJalanItem?.title"
        :pengirim-name="suratJalanItem?.pengirimNama"
        :peminjaman-id="suratJalanItem?.id"
        :peminjaman-status="suratJalanItem?.status"
        @close="suratJalanItem = null"
        @accepted="handleSuratJalanAccepted"
    />
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PeminjamanDetailModal from '../../Components/PeminjamanDetailModal.vue';
import SuratJalanModal from '../../Components/SuratJalanModal.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Riwayat Peminjaman',
                subtitle: 'Pantau riwayat dan status peminjaman',
                activeMenu: 'riwayat',
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
const roleKey = computed(() =>
    (page.props.auth?.user?.role?.key ?? cachedUser.value?.role?.key ?? '').toLowerCase()
);
const isMgrTool = computed(() => roleKey.value === 'mgr_tool');
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const setAreaSwitching = inject('setAreaSwitching', null);
const activeAreaName = inject('activeAreaName', ref('Area tidak diketahui'));
const areaName = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaName.value
        : page.props.auth?.user?.area?.name ?? 'Area tidak diketahui'
);

const items = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 8,
});

const search = ref('');
const statusFilter = ref('Semua');
const selectedItem = ref(null);
const suratJalanItem = ref(null);

const filteredItems = computed(() => items.value);

const totalCount = computed(() => (pagination.total ? pagination.total : items.value.length));
const reviewCount = computed(() => items.value.filter((item) => item.status === 'Menunggu Review').length);
const processCount = computed(() => items.value.filter((item) => item.status === 'Disiapkan').length);
const deliveredCount = computed(() => items.value.filter((item) => item.status === 'Terkirim').length);

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

const openDetail = (item) => {
    selectedItem.value = item;
};

const openSuratJalan = (item) => {
    suratJalanItem.value = item;
};

const closeDetail = () => {
    selectedItem.value = null;
};

const handleSuratJalanAccepted = async () => {
    await loadHistory();
    suratJalanItem.value = null;
};

const normalizeHistory = (item) => {
    const tools = Array.isArray(item?.tools)
        ? item.tools.map((tool) => ({
              alat_id: tool?.alat_id ?? null,
              name: tool?.name ?? '-',
              code: tool?.code ?? '-',
              qty: Number.isFinite(tool?.qty) ? tool.qty : 0,
              approvedQty: Number.isFinite(tool?.approved_qty) ? tool.approved_qty : null,
              reviewStatus: tool?.review_status ?? 'Menunggu Review',
              rejectionReason: tool?.rejection_reason ?? '',
              photos: Array.isArray(tool?.photos)
                  ? tool.photos.map((photo) => ({
                        id: photo?.id ?? null,
                        url: photo?.url ?? photo?.path ?? '',
                        path: photo?.path ?? '',
                        originalName: photo?.original_name ?? '',
                    }))
                  : [],
              reports: Array.isArray(tool?.reports)
                  ? tool.reports.map((report) => ({
                        id: report?.id ?? null,
                        alatId: report?.alat_id ?? tool?.alat_id ?? null,
                        alatName: report?.alat_name ?? tool?.name ?? '-',
                        alatCode: report?.alat_code ?? tool?.code ?? '-',
                        kategori: report?.kategori ?? '-',
                        status: report?.status ?? 'Dilaporkan',
                        jumlah: Number.isFinite(report?.jumlah) ? report.jumlah : 0,
                        deskripsi: report?.deskripsi ?? '',
                        createdAt: report?.created_at ?? '-',
                        url: report?.url ?? report?.path ?? '',
                        path: report?.path ?? '',
                        originalName: report?.original_name ?? '',
                    }))
                  : [],
          }))
        : [];

    const allPhotos = tools
        .flatMap((tool) => (Array.isArray(tool?.photos) ? tool.photos : []))
        .filter((photo) => photo?.url || photo?.path);
    const photoPreview = allPhotos.slice(0, 3);

    return {
        id: item?.id ?? '',
        title: item?.title ?? '-',
        createdAt: item?.created_at ?? '-',
        borrowDate: item?.borrow_date ?? '-',
        returnDate: item?.return_date ?? '-',
        itemCount: Number.isFinite(item?.item_count) ? item.item_count : 0,
        status: item?.status ?? 'Menunggu Review',
        pengirimNama: item?.pengirim_nama ?? '',
        suratJalanUrl: item?.surat_jalan_url ?? '',
        suratJalanPath: item?.surat_jalan_path ?? '',
        tools,
        reports: Array.isArray(item?.reports)
            ? item.reports.map((report) => ({
                  id: report?.id ?? null,
                  alatId: report?.alat_id ?? null,
                  alatName: report?.alat_name ?? '-',
                  alatCode: report?.alat_code ?? '-',
                  kategori: report?.kategori ?? '-',
                  status: report?.status ?? 'Dilaporkan',
                  jumlah: Number.isFinite(report?.jumlah) ? report.jumlah : 0,
                  deskripsi: report?.deskripsi ?? '',
                  createdAt: report?.created_at ?? '-',
                  url: report?.url ?? report?.path ?? '',
                  path: report?.path ?? '',
                  originalName: report?.original_name ?? '',
              }))
            : [],
        photoPreview,
        photoExtraCount: Math.max(allPhotos.length - photoPreview.length, 0),
    };
};

const photoSrc = (photo) => {
    if (!photo) {
        return '';
    }
    if (photo.url) {
        return photo.url;
    }
    if (!photo.path) {
        return '';
    }
    return photo.path.startsWith('/storage/') ? photo.path : `/storage/${photo.path}`;
};

const statusClass = (status) => {
    switch (status) {
        case 'Menunggu Review':
            return 'bg-blue-50 text-blue-600';
        case 'Dipesan':
            return 'bg-slate-100 text-slate-700';
        case 'Disiapkan':
            return 'bg-amber-50 text-amber-600';
        case 'Terkirim':
            return 'bg-emerald-50 text-emerald-600';
        case 'Dikembalikan':
            return 'bg-indigo-50 text-indigo-600';
        case 'Ditolak':
            return 'bg-rose-50 text-rose-600';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

let filterTimeout = null;

const buildFilterParams = () => {
    const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage,
    };
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (statusFilter.value && statusFilter.value !== 'Semua') {
        params.status = statusFilter.value;
    }
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }
    return params;
};

const goToPage = (page) => {
    const next = Math.min(Math.max(1, page), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }
    pagination.currentPage = next;
    loadHistory();
};

const loadHistory = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/peminjaman', {
            params: buildFilterParams(),
        });
        const payload = response.data;
        if (Array.isArray(payload)) {
            items.value = payload.map((item) => normalizeHistory(item));
            pagination.total = items.value.length;
            pagination.lastPage = 1;
            pagination.currentPage = 1;
            return;
        }
        const data = Array.isArray(payload?.data) ? payload.data : [];
        const meta = payload?.meta ?? payload ?? {};
        items.value = data.map((item) => normalizeHistory(item));
        pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
        pagination.lastPage = Number(meta.last_page ?? pagination.lastPage) || 1;
        pagination.perPage = Number(meta.per_page ?? pagination.perPage) || pagination.perPage;
        pagination.total = Number(meta.total ?? pagination.total) || items.value.length;
    } catch (error) {
        items.value = [];
        loadError.value = 'Gagal memuat data peminjaman.';
    } finally {
        isLoading.value = false;
    }
};

onMounted(() => {
    cachedUser.value = loadCachedUser();
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(true);
    }
    loadHistory().finally(() => {
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
        try {
            await loadHistory();
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);

watch(
    [() => search.value, () => statusFilter.value],
    () => {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }
        filterTimeout = setTimeout(() => {
            pagination.currentPage = 1;
            loadHistory();
        }, 300);
    },
);
</script>

