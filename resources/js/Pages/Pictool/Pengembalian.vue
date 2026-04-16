<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Pengembalian</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola peminjaman yang sudah mulai dikembalikan</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Daftar Pengembalian</h2>
            <p class="mt-1 text-sm text-slate-500">Pantau pengembalian parsial dan selesaikan peminjaman yang sudah kembali semua</p>
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
            <div class="grid gap-2 md:grid-cols-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === tab.key ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-600 hover:bg-white/70'"
                    type="button"
                    @click="activeTab = tab.key"
                >
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
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat data pengembalian...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Tidak ada data pengembalian pada kategori ini.
            </p>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="item in filteredItems"
                    :key="item.id"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ item.userName }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ item.createdAt }}</p>
                        </div>
                        <span class="rounded-full px-3 py-1 text-[11px] font-semibold" :class="statusBadge(item.status)">
                            {{ statusLabel(item.status) }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-2 text-xs text-slate-600">
                        <div>{{ item.title }}</div>
                        <div>{{ item.areaName }}</div>
                        <div>{{ item.borrowDate }} - {{ item.returnDate }}</div>
                    </div>

                    <div class="mt-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                        <div
                            v-for="tool in item.tools"
                            :key="tool.item_id"
                            class="flex items-center justify-between gap-3 py-1"
                        >
                            <span class="truncate">{{ tool.name }}</span>
                            <span class="shrink-0">
                                {{ tool.returnedQty }}/{{ tool.approvedQty }} kembali
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            class="inline-flex flex-1 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                            type="button"
                            @click="openDetail(item)"
                        >
                            Lihat Detail
                        </button>
                        <button
                            v-if="item.suratJalanUrl"
                            class="inline-flex flex-1 items-center justify-center rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300"
                            type="button"
                            @click="openSuratJalan(item)"
                        >
                            Surat Jalan
                        </button>
                        <button
                            v-if="item.status === 'Dikembalikan Semuanya'"
                            class="inline-flex flex-1 items-center justify-center rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="button"
                            :disabled="finishingId === item.id"
                            @click="openCompleteModal(item)"
                        >
                            {{ finishingId === item.id ? 'Memproses...' : 'Selesai' }}
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
    />

    <teleport to="body">
        <div
            v-if="completeItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeCompleteModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Konfirmasi
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">
                            Selesaikan peminjaman
                        </h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeCompleteModal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Tandai peminjaman ini sebagai selesai?
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ completeItem?.title || '-' }} (ID: {{ completeItem?.id || '-' }})
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        Aksi ini akan mengubah status peminjaman menjadi <span class="font-semibold">Selesai</span>.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeCompleteModal"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="finishingId === completeItem?.id"
                        @click="confirmComplete"
                    >
                        {{ finishingId === completeItem?.id ? 'Memproses...' : 'Ya, Selesai' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
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
                title: 'Pengembalian',
                subtitle: 'Kelola pengembalian alat',
                activeMenu: 'pengembalian',
            },
            () => page
        ),
});

const page = usePage();
const fallbackAreaName = computed(() => page.props.auth?.user?.area?.name ?? 'Area tidak diketahui');

const items = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const search = ref('');
const detailItem = ref(null);
const suratJalanItem = ref(null);
const completeItem = ref(null);
const finishingId = ref(null);
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const tabConfig = [
    { key: 'partials', label: 'Dikembalikan Parsial', status: 'Dikembalikan Partials' },
    { key: 'full', label: 'Dikembalikan Semua', status: 'Dikembalikan Semuanya' },
];

const activeTab = ref(tabConfig[0].key);

const statusLabel = (status) => {
    switch (status) {
        case 'Dikembalikan Partials':
            return 'Dikembalikan Parsial';
        case 'Dikembalikan Semuanya':
            return 'Dikembalikan Semua';
        default:
            return status ?? '-';
    }
};

const statusBadge = (status) => {
    switch (status) {
        case 'Dikembalikan Partials':
            return 'bg-violet-100 text-violet-700';
        case 'Dikembalikan Semuanya':
            return 'bg-indigo-100 text-indigo-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const statusCountMap = computed(() => {
    const base = {
        'Dikembalikan Partials': 0,
        'Dikembalikan Semuanya': 0,
    };
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

const activeStatus = computed(() => tabConfig.find((tab) => tab.key === activeTab.value)?.status ?? 'Dikembalikan Partials');

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
            item.userName.toLowerCase().includes(keyword) ||
            item.createdAt.toLowerCase().includes(keyword) ||
            String(item.id).includes(keyword)
        );
    });
});

const normalizeItem = (item) => ({
    id: item?.id ?? '',
    title: item?.title ?? '-',
    userName: item?.user_name ?? '-',
    areaName: item?.area_name ?? fallbackAreaName.value,
    createdAt: item?.created_at ?? '-',
    borrowDate: item?.borrow_date ?? '-',
    returnDate: item?.return_date ?? '-',
    status: item?.status ?? 'Dikembalikan Partials',
    pengirimNama: item?.pengirim_nama ?? '',
    suratJalanUrl: item?.surat_jalan_url ?? '',
    suratJalanPath: item?.surat_jalan_path ?? '',
    tools: Array.isArray(item?.tools)
        ? item.tools.map((tool) => ({
              item_id: tool?.item_id ?? null,
              alat_id: tool?.alat_id ?? null,
              name: tool?.name ?? '-',
              code: tool?.code ?? '-',
              qty: Number.isFinite(tool?.qty) ? tool.qty : 0,
              approvedQty: Number.isFinite(tool?.approved_qty) ? tool.approved_qty : 0,
              returnedQty: Number.isFinite(tool?.returned_qty) ? tool.returned_qty : 0,
              remainingQty: Number.isFinite(tool?.remaining_qty) ? tool.remaining_qty : 0,
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
        : [],
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
});

const loadReturns = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/pengembalian');
        const data = Array.isArray(response.data) ? response.data : [];
        items.value = data.map((item) => normalizeItem(item));
    } catch (error) {
        items.value = [];
        loadError.value = 'Gagal memuat data pengembalian.';
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

const openCompleteModal = (item) => {
    completeItem.value = item;
};

const closeCompleteModal = () => {
    if (finishingId.value) {
        return;
    }
    completeItem.value = null;
};

const markAsCompleted = async (item) => {
    if (!item?.id || finishingId.value) {
        return;
    }

    finishingId.value = item.id;
    try {
        await axios.post(`/api/pengembalian/${item.id}/selesai`);
        await loadReturns();
        showAlert('success', 'Peminjaman berhasil diselesaikan.');
        completeItem.value = null;
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Gagal menyelesaikan peminjaman.';
        showAlert('error', message);
    } finally {
        finishingId.value = null;
    }
};

const confirmComplete = async () => {
    if (!completeItem.value) {
        return;
    }

    await markAsCompleted(completeItem.value);
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
    loadReturns();
});
</script>
