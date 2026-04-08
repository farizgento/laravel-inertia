<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Review Peminjaman - {{ areaName }}</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar semua peminjaman yang telah Anda buat</p>
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
                <p class="mt-1 text-sm text-slate-500">Klik tombol review atau detail pada baris yang ingin dibuka</p>
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
                    <table class="min-w-[920px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Peminjam</th>
                                <th class="px-4 py-3">Dibuat</th>
                                <th class="px-4 py-3">Direview</th>
                                <th class="px-4 py-3">Keperluan</th>
                                <th class="px-4 py-3">Periode</th>
                                <th class="px-4 py-3 text-center">Item</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                                v-for="item in filteredItems"
                                :key="item.id"
                                class="transition hover:bg-slate-50"
                            >
                                <td class="px-4 py-4 align-top">
                                    <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-600">
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-700">
                                    {{ item.userName }}
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ item.createdAt }}
                                </td>
                                <td class="px-4 py-4 align-top text-slate-700">
                                    {{ item.reviewerName }}
                                </td>
                                <td class="px-4 py-4 align-top">
                                    <p class="font-semibold text-slate-900">{{ item.title }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID #{{ item.id }}</p>
                                </td>
                                <td class="px-4 py-4 align-top text-slate-600">
                                    {{ item.borrowDate }} - {{ item.returnDate }}
                                </td>
                                <td class="px-4 py-4 text-center align-top font-semibold text-slate-700">
                                    {{ item.itemCount }}
                                </td>
                                <td class="px-4 py-4 text-right align-top">
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
                                        {{ item.status === 'Menunggu Review' ? 'Review' : 'Detail' }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <ReviewPeminjamanModal
        :open="!!selectedItem"
        :item="selectedItem"
        :is-submitting="isSubmitting"
        :read-only="selectedItem?.status !== 'Menunggu Review'"
        @close="closeDetail"
        @submit="submitReview"
    />
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ReviewPeminjamanModal from '../../Components/ReviewPeminjamanModal.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Review Peminjaman',
                subtitle: 'Pantau status pengajuan peminjaman Anda',
                activeMenu: 'review',
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
const activeAreaName = inject('activeAreaName', ref('Area Tidak Diketahui'));
const refreshReviewPendingCount = inject('refreshReviewPendingCount', async () => {});
const areaName = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaName.value
        : page.props.auth?.user?.area?.name ?? 'Area Tidak Diketahui'
);

const items = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const loadError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const search = ref('');
const statusFilter = ref('Semua');
const selectedItem = ref(null);

const filteredItems = computed(() => {
    const keyword = search.value.trim().toLowerCase();
    return items.value.filter((item) => {
        const matchStatus = statusFilter.value === 'Semua' || item.status === statusFilter.value;
        const matchKeyword =
            !keyword ||
            item.title.toLowerCase().includes(keyword) ||
            item.createdAt.toLowerCase().includes(keyword) ||
            String(item.id).includes(keyword);
        return matchStatus && matchKeyword;
    });
});

const totalCount = computed(() => items.value.length);
const reviewCount = computed(() => items.value.filter((item) => item.status === 'Menunggu Review').length);
const processCount = computed(() => items.value.filter((item) => item.status === 'Disiapkan').length);
const deliveredCount = computed(() => items.value.filter((item) => item.status === 'Terkirim').length);

const openDetail = (item) => {
    selectedItem.value = {
        ...item,
        areaId: item?.areaId ?? activeAreaId.value ?? null,
    };
};

const closeDetail = () => {
    selectedItem.value = null;
};

const normalizeHistory = (item) => ({
    id: item?.id ?? '',
    areaId: item?.area_id ?? activeAreaId.value ?? null,
    title: item?.title ?? '-',
    userName: item?.user_name ?? '-',
    reviewerName: item?.reviewed_by_name ?? '-',
    reviewNote: item?.review_note ?? '',
    createdAt: item?.created_at ?? '-',
    borrowDate: item?.borrow_date ?? '-',
    returnDate: item?.return_date ?? '-',
    itemCount: Number.isFinite(item?.item_count) ? item.item_count : 0,
    status: item?.status ?? 'Menunggu Review',
    tools: Array.isArray(item?.tools)
        ? item.tools.map((tool) => ({
              item_id: tool?.item_id ?? null,
              alat_id: tool?.alat_id ?? null,
              name: tool?.name ?? '-',
              code: tool?.code ?? '-',
              qty: Number.isFinite(tool?.qty) ? tool.qty : 0,
              approved_qty: Number.isFinite(tool?.approved_qty) ? tool.approved_qty : 0,
              review_status: tool?.review_status ?? 'Menunggu Review',
              rejection_reason: tool?.rejection_reason ?? '',
          }))
        : [],
});

const loadHistory = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const params = {};
        if (isAreaSwitcherRole.value && activeAreaId.value) {
            params.area_id = activeAreaId.value;
        }
        const response = await axios.get('/api/review-peminjaman', { params });
        const data = Array.isArray(response.data) ? response.data : [];
        items.value = data.map((item) => normalizeHistory(item));
    } catch (error) {
        items.value = [];
        loadError.value = 'Gagal memuat data peminjaman.';
    } finally {
        isLoading.value = false;
    }
};

const submitReview = async (payload) => {
    if (!payload?.peminjamanId) {
        return;
    }
    isSubmitting.value = true;
    loadError.value = '';
    try {
        const reviewAreaId = selectedItem.value?.areaId ?? activeAreaId.value ?? null;
        const body = {
            ...payload,
            ...(isAreaSwitcherRole.value && reviewAreaId ? { area_id: reviewAreaId } : {}),
        };
        await axios.post(`/api/review-peminjaman/${payload.peminjamanId}`, body);
        await loadHistory();
        await refreshReviewPendingCount();
        selectedItem.value = null;
        showAlert('success', 'Review peminjaman berhasil disimpan.');
    } catch (error) {
        loadError.value = error.response?.status === 403
            ? 'Review gagal disimpan. Pastikan area aktif sesuai dengan peminjaman yang direview.'
            : error.response?.data?.message ?? 'Gagal menyimpan review.';
        showAlert('error', loadError.value);
    } finally {
        isSubmitting.value = false;
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

