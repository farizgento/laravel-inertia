<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Mutasi alat</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar pengiriman yang sudah diproses</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
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
                    <path d="M3 7h11v10H3z" />
                    <path d="M14 10h4l3 3v4h-7z" />
                    <circle cx="7.5" cy="19" r="1.5" />
                    <circle cx="17.5" cy="19" r="1.5" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Mutasi alat</h2>
                <p class="mt-1 text-sm text-slate-500">Filter berdasarkan status pengiriman</p>
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
        </div>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-2">
            <div class="grid gap-2 md:grid-cols-3">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === tab.key
                        ? 'bg-white text-blue-700 shadow-sm'
                        : 'text-slate-600 hover:bg-white/70'"
                    type="button"
                    @click="activeTab = tab.key"
                >
                    <span class="text-slate-400">
                        <svg v-if="tab.key === 'dikirim'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h11v10H3z" />
                            <path d="M14 10h4l3 3v4h-7z" />
                            <circle cx="7.5" cy="19" r="1.5" />
                            <circle cx="17.5" cy="19" r="1.5" />
                        </svg>
                        <svg v-else-if="tab.key === 'diterima'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h11v10H3z" />
                            <path d="M14 10h4l3 3v4h-7z" />
                            <circle cx="7.5" cy="19" r="1.5" />
                            <circle cx="17.5" cy="19" r="1.5" />
                            <path d="M7 12l2 2 4-4" />
                        </svg>
                        <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h13l3 5v6H3z" />
                            <circle cx="7.5" cy="18" r="1.5" />
                            <circle cx="17.5" cy="18" r="1.5" />
                            <path d="M9 12h6" />
                        </svg>
                    </span>
                    <span>{{ tab.label }}</span>
                    <span
                        class="flex h-5 min-w-[20px] items-center justify-center rounded-full px-1 text-xs font-semibold"
                        :class="activeTab === tab.key ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600'"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>
        </div>

        <div class="mt-6">
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat Mutasi alat...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Tidak ada riwayat pada kategori ini.
            </p>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="item in filteredItems"
                    :key="item.id"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="8" r="3.5" />
                                    <path d="M4 20c1.5-3.5 5-5 8-5s6.5 1.5 8 5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ item.userName }}</p>
                                <p class="text-xs text-slate-500">{{ item.createdAt }}</p>
                            </div>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-[11px] font-semibold"
                            :class="statusBadge(item.status)"
                        >
                            {{ statusLabel(item.status) }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-2 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
                                <circle cx="12" cy="11" r="2.5" />
                            </svg>
                            <span>{{ areaName }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M7 8h10M7 12h6M7 16h4" />
                            </svg>
                            <span>{{ approvedLabel(item) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M7 2v4M17 2v4M3 10h18" />
                            </svg>
                            <span>{{ item.borrowDate }} - {{ item.returnDate }}</span>
                        </div>
                        <div v-if="item.pengirimNama" class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 7a4 4 0 1 0-8 0" />
                                <path d="M12 11v4" />
                                <path d="M5.5 21a6.5 6.5 0 0 1 13 0" />
                            </svg>
                            <span>Pengirim: {{ item.pengirimNama }}</span>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        {{ item.title }}
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                            type="button"
                            @click="openDetail(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            Lihat Detail
                        </button>
                        <button
                            v-if="item.suratJalanUrl"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300"
                            type="button"
                            @click="openSuratJalan(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <path d="M14 2v6h6" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                                <path d="M10 9H8" />
                            </svg>
                            Surat Jalan
                        </button>
                        <button
                            v-if="isUserRole && item.status === 'Diterima'"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:border-amber-300"
                            type="button"
                            @click="openReturn(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 7H9a4 4 0 0 0-4 4v3" />
                                <path d="M9 11l-4 4 4 4" />
                                <path d="M21 17V11a4 4 0 0 0-4-4" />
                            </svg>
                            Kembalikan
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <PeminjamanDetailModal
        :open="!!detailItem"
        :item="detailItem"
        @close="detailItem = null"
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

    <teleport to="body">
        <div
            v-if="returnItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeReturn"
        >
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Konfirmasi
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">
                            Kembalikan alat
                        </h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeReturn"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Yakin ingin mengembalikan peminjaman ini?
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ returnItem?.title || '-' }} (ID: {{ returnItem?.id || '-' }})
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        Status akan dipindahkan ke <span class="font-semibold">Dikembalikan</span> setelah dikonfirmasi.
                    </p>
                    <div class="mt-4">
                        <button
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-300 hover:text-amber-700"
                            type="button"
                            @click="toggleReturnReport"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 5v14" />
                                <path d="M5 12h14" />
                            </svg>
                            {{ showReturnReportForm ? 'Tutup Form Laporan' : '+ Kerusakan/Kehilangan' }}
                        </button>

                        <div
                            v-if="showReturnReportForm"
                            class="mt-4 space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="space-y-2 text-sm font-medium text-slate-700">
                                    <span>Kategori *</span>
                                    <select
                                        v-model="returnReport.kategori"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                    >
                                        <option value="">Pilih kategori</option>
                                        <option value="kerusakan">Kerusakan</option>
                                        <option value="kehilangan">Kehilangan</option>
                                    </select>
                                </label>
                                <label class="space-y-2 text-sm font-medium text-slate-700">
                                    <span>Alat *</span>
                                    <select
                                        v-model="returnReport.alat_id"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                    >
                                        <option value="">Pilih alat</option>
                                        <option v-for="tool in returnReportTools" :key="tool.alat_id" :value="tool.alat_id">
                                            {{ tool.name }} ({{ tool.code }})
                                        </option>
                                    </select>
                                </label>
                                <label class="space-y-2 text-sm font-medium text-slate-700">
                                    <span>Jumlah *</span>
                                    <input
                                        v-model.number="returnReport.jumlah"
                                        type="number"
                                        min="1"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                    />
                                </label>
                                <label class="space-y-2 text-sm font-medium text-slate-700">
                                    <span>Foto *</span>
                                    <input
                                        ref="returnReportFileInput"
                                        type="file"
                                        accept="image/*"
                                        class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-amber-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-amber-700"
                                        @change="handleReturnReportFileChange"
                                    />
                                </label>
                            </div>
                            <label class="block space-y-2 text-sm font-medium text-slate-700">
                                <span>Deskripsi *</span>
                                <textarea
                                    v-model="returnReport.deskripsi"
                                    rows="3"
                                    placeholder="Jelaskan kerusakan atau kehilangan yang terjadi..."
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                />
                            </label>
                            <div v-if="returnReportPreviewUrl" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                <img :src="returnReportPreviewUrl" alt="Preview laporan alat" class="h-48 w-full object-cover" />
                            </div>
                            <p v-if="returnReportError" class="text-sm font-semibold text-rose-500">
                                {{ returnReportError }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeReturn"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700"
                        type="button"
                        :disabled="isReturning"
                        :class="isReturning ? 'cursor-not-allowed opacity-70' : ''"
                        @click="confirmReturn"
                    >
                        {{ isReturning ? 'Memproses...' : 'Ya, kembalikan' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PeminjamanDetailModal from '../../Components/PeminjamanDetailModal.vue';
import SuratJalanModal from '../../Components/SuratJalanModal.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Mutasi alat',
                subtitle: 'Daftar pengiriman yang sudah diproses',
                activeMenu: 'riwayat-pengiriman',
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
const isUserRole = computed(() => roleKey.value === 'user');
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
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const detailItem = ref(null);
const suratJalanItem = ref(null);
const returnItem = ref(null);
const search = ref('');
const isReturning = ref(false);
const showReturnReportForm = ref(false);
const returnReportFileInput = ref(null);
const returnReportPreviewUrl = ref('');
const returnReportError = ref('');
const returnReport = ref({
    kategori: '',
    alat_id: '',
    jumlah: 1,
    deskripsi: '',
    foto: null,
});

const tabConfig = [
    { key: 'dikirim', label: 'Sedang Dikirim', status: 'Terkirim' },
    { key: 'diterima', label: 'Diterima', status: 'Diterima' },
    { key: 'dikembalikan', label: 'Dikembalikan', status: 'Dikembalikan' },
];

const activeTab = ref(tabConfig[0].key);

const statusLabel = (status) => {
    switch (status) {
        case 'Terkirim':
            return 'Sudah Dikirim';
        case 'Diterima':
            return 'Diterima';
        case 'Dikembalikan':
            return 'Dikembalikan';
        default:
            return status ?? '-';
    }
};

const statusBadge = (status) => {
    switch (status) {
        case 'Terkirim':
            return 'bg-blue-100 text-blue-600';
        case 'Diterima':
            return 'bg-emerald-100 text-emerald-600';
        case 'Dikembalikan':
            return 'bg-slate-200 text-slate-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const statusCountMap = computed(() => {
    const base = { Terkirim: 0, Diterima: 0, Dikembalikan: 0 };
    items.value.forEach((item) => {
        if (base[item.status] !== undefined) {
            base[item.status] += 1;
        }
    });
    return base;
});

const tabs = computed(() =>
    tabConfig.map((tab) => ({
        ...tab,
        count: statusCountMap.value[tab.status] ?? 0,
    }))
);

const activeStatus = computed(() => {
    const match = tabConfig.find((tab) => tab.key === activeTab.value);
    return match?.status ?? 'Terkirim';
});

const filteredItems = computed(() => {
    const keyword = search.value.trim().toLowerCase();
    return items.value.filter((item) => {
        if (item.status !== activeStatus.value) {
            return false;
        }
        if (!keyword) {
            return true;
        }
        return (
            item.title.toLowerCase().includes(keyword) ||
            item.createdAt.toLowerCase().includes(keyword) ||
            String(item.id).includes(keyword)
        );
    });
});

const approvedLabel = (item) => {
    const total = Number.isFinite(item?.itemCount) ? item.itemCount : 0;
    return `${total} alat disetujui`;
};

const returnReportTools = computed(() =>
    Array.isArray(returnItem.value?.tools) ? returnItem.value.tools : []
);

const normalizeHistory = (item) => {
    const tools = Array.isArray(item?.tools)
        ? item.tools.map((tool) => ({
              item_id: tool?.item_id ?? null,
              alat_id: tool?.alat_id ?? null,
              name: tool?.name ?? '-',
              code: tool?.code ?? '-',
              qty: Number.isFinite(tool?.qty) ? tool.qty : 0,
              approvedQty: Number.isFinite(tool?.approved_qty) ? tool.approved_qty : 0,
              reviewStatus: tool?.review_status ?? 'Menunggu Review',
              rejectionReason: tool?.rejection_reason ?? '',
              photos: Array.isArray(tool?.photos)
                  ? tool.photos.map((photo) => ({
                        id: photo?.id ?? null,
                        url: photo?.url ?? photo?.path ?? '',
                        originalName: photo?.original_name ?? '',
                    }))
                  : [],
          }))
        : [];

    return {
        id: item?.id ?? '',
        title: item?.title ?? '-',
        userName: item?.user_name ?? '-',
        createdAt: item?.created_at ?? '-',
        borrowDate: item?.borrow_date ?? '-',
        returnDate: item?.return_date ?? '-',
        itemCount: Number.isFinite(item?.item_count) ? item.item_count : 0,
        status: item?.status ?? 'Terkirim',
        pengirimNama: item?.pengirim_nama ?? '',
        suratJalanUrl: item?.surat_jalan_url ?? '',
        suratJalanPath: item?.surat_jalan_path ?? '',
        tools,
    };
};

const loadHistory = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const params = {};
        if (isAreaSwitcherRole.value && activeAreaId.value) {
            params.area_id = activeAreaId.value;
        }
        const response = await axios.get('/api/riwayat-pengiriman', { params });
        const data = Array.isArray(response.data) ? response.data : [];
        items.value = data.map((item) => normalizeHistory(item));
    } catch (error) {
        items.value = [];
        loadError.value = 'Gagal memuat Mutasi alat.';
        showAlert('error', loadError.value);
    } finally {
        isLoading.value = false;
    }
};

const openDetail = (item) => {
    detailItem.value = item;
};

const openSuratJalan = (item) => {
    suratJalanItem.value = item;
};

const openReturn = (item) => {
    returnItem.value = item;
    resetReturnReport();
};

const closeReturn = () => {
    returnItem.value = null;
    resetReturnReport();
};

const resetReturnReport = () => {
    showReturnReportForm.value = false;
    returnReportError.value = '';
    returnReport.value = {
        kategori: '',
        alat_id: '',
        jumlah: 1,
        deskripsi: '',
        foto: null,
    };
    if (returnReportFileInput.value) {
        returnReportFileInput.value.value = '';
    }
    if (returnReportPreviewUrl.value) {
        URL.revokeObjectURL(returnReportPreviewUrl.value);
        returnReportPreviewUrl.value = '';
    }
};

const toggleReturnReport = () => {
    showReturnReportForm.value = !showReturnReportForm.value;
    if (!showReturnReportForm.value) {
        resetReturnReport();
    }
};

const handleReturnReportFileChange = (event) => {
    const file = event.target.files?.[0] ?? null;
    returnReport.value.foto = file;
    returnReportError.value = '';
    if (returnReportPreviewUrl.value) {
        URL.revokeObjectURL(returnReportPreviewUrl.value);
        returnReportPreviewUrl.value = '';
    }
    if (file) {
        returnReportPreviewUrl.value = URL.createObjectURL(file);
    }
};

const validateReturnReport = () => {
    if (!showReturnReportForm.value) {
        return true;
    }

    if (
        !returnReport.value.kategori ||
        !returnReport.value.alat_id ||
        !returnReport.value.deskripsi.trim() ||
        !returnReport.value.foto ||
        Number(returnReport.value.jumlah) < 1
    ) {
        returnReportError.value = 'Lengkapi semua field laporan alat.';
        return false;
    }

    returnReportError.value = '';
    return true;
};

const confirmReturn = async () => {
    if (!returnItem.value?.id) {
        returnItem.value = null;
        return;
    }
    if (isReturning.value) {
        return;
    }
    if (!validateReturnReport()) {
        return;
    }
    isReturning.value = true;
    let shouldClose = false;
    try {
        const payload = new FormData();

        if (showReturnReportForm.value) {
            payload.append('laporan[kategori]', returnReport.value.kategori);
            payload.append('laporan[alat_id]', String(returnReport.value.alat_id));
            payload.append('laporan[jumlah]', String(returnReport.value.jumlah));
            payload.append('laporan[deskripsi]', returnReport.value.deskripsi.trim());
            payload.append('laporan[foto]', returnReport.value.foto);
        }

        await axios.post(`/api/pengiriman/${returnItem.value.id}/kembalikan`, payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        await loadHistory();
        showAlert('success', 'Pengembalian berhasil dikonfirmasi.');
        shouldClose = true;
    } catch (error) {
        const message =
            error?.response?.data?.message ??
            'Gagal mengembalikan peminjaman.';
        if (error?.response?.status === 422) {
            const errors = error.response?.data?.errors ?? {};
            returnReportError.value =
                errors['laporan.kategori']?.[0] ??
                errors['laporan.alat_id']?.[0] ??
                errors['laporan.jumlah']?.[0] ??
                errors['laporan.deskripsi']?.[0] ??
                errors['laporan.foto']?.[0] ??
                message;
        }
        showAlert('error', message);
    } finally {
        isReturning.value = false;
        if (shouldClose) {
            returnItem.value = null;
            resetReturnReport();
        }
    }
};

const handleSuratJalanAccepted = async () => {
    await loadHistory();
    suratJalanItem.value = null;
    showAlert('success', 'Peminjaman berhasil diterima.');
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
        try {
            await loadHistory();
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);
</script>
