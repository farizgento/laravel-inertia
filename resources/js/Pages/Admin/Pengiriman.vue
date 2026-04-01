<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Pengiriman</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola persiapan dan pengiriman peminjaman alat</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div>
            <h2 class="text-lg font-semibold text-slate-900">Pengiriman Peminjaman</h2>
            <p class="mt-1 text-sm text-slate-500">Kelola persiapan dan pengiriman peminjaman alat</p>
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
                        <svg v-if="tab.key === 'menunggu-disiapkan'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="16" rx="2" />
                            <path d="M7 8h10M7 12h6M7 16h4" />
                        </svg>
                        <svg v-else-if="tab.key === 'menunggu-pengiriman'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h11v10H3z" />
                            <path d="M14 10h4l3 3v4h-7z" />
                            <circle cx="7.5" cy="19" r="1.5" />
                            <circle cx="17.5" cy="19" r="1.5" />
                        </svg>
                        <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h13l3 5v6H3z" />
                            <circle cx="7.5" cy="18" r="1.5" />
                            <circle cx="17.5" cy="18" r="1.5" />
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
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat data peminjaman...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Tidak ada peminjaman pada kategori ini.
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
                            v-if="item.status === 'Terkirim' && item.suratJalanUrl"
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
                            v-if="item.status === 'Disiapkan'"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-700 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                            type="button"
                            @click="openShipping(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7h11v10H3z" />
                                <path d="M14 10h4l3 3v4h-7z" />
                                <circle cx="7.5" cy="19" r="1.5" />
                                <circle cx="17.5" cy="19" r="1.5" />
                            </svg>
                            Pengiriman
                        </button>
                        <button
                            v-if="item.status === 'Dipesan'"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700"
                            type="button"
                            @click="openPrepare(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M7 8h10M7 12h6M7 16h4" />
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                            </svg>
                            Siapkan
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
    <PreparePengirimanModal
        :open="!!prepareItem"
        :item="prepareItem"
        :is-submitting="isSubmitting"
        @close="prepareItem = null"
        @submit="submitPrepare"
    />
    <KirimPengirimanModal
        :open="!!shippingItem"
        :item="shippingItem"
        :is-submitting="isShipping"
        @close="shippingItem = null"
        @submit="submitShipping"
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
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PeminjamanDetailModal from '../../Components/PeminjamanDetailModal.vue';
import PreparePengirimanModal from '../../Components/PreparePengirimanModal.vue';
import KirimPengirimanModal from '../../Components/KirimPengirimanModal.vue';
import SuratJalanModal from '../../Components/SuratJalanModal.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Pengiriman',
                subtitle: 'Kelola persiapan dan pengiriman peminjaman alat',
                activeMenu: 'pengiriman',
            },
            () => page
        ),
});

const page = usePage();
const areaName = computed(() => page.props.auth?.user?.area?.name ?? 'Area tidak diketahui');

const items = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const isShipping = ref(false);
const loadError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const detailItem = ref(null);
const prepareItem = ref(null);
const shippingItem = ref(null);
const suratJalanItem = ref(null);

const tabConfig = [
    { key: 'menunggu-disiapkan', label: 'Menunggu Disiapkan', status: 'Dipesan' },
    { key: 'menunggu-pengiriman', label: 'Menunggu Pengiriman', status: 'Disiapkan' },
    { key: 'dikirim', label: 'Dalam Perjalanan', status: 'Terkirim' },
];

const activeTab = ref(tabConfig[0].key);

const statusLabel = (status) => {
    switch (status) {
        case 'Dipesan':
            return 'Menunggu Disiapkan';
        case 'Disiapkan':
            return 'Menunggu Pengiriman';
        case 'Terkirim':
            return 'Dalam Perjalanan';
        default:
            return status ?? '-';
    }
};

const statusBadge = (status) => {
    switch (status) {
        case 'Dipesan':
            return 'bg-amber-100 text-amber-600';
        case 'Disiapkan':
            return 'bg-blue-100 text-blue-600';
        case 'Terkirim':
            return 'bg-emerald-100 text-emerald-600';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const statusCountMap = computed(() => {
    const base = { Dipesan: 0, Disiapkan: 0, Terkirim: 0 };
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
    return match?.status ?? 'Dipesan';
});

const filteredItems = computed(() =>
    items.value.filter((item) => item.status === activeStatus.value)
);

const approvedLabel = (item) => {
    const total = Number.isFinite(item?.itemCount) ? item.itemCount : 0;
    return `${total} alat disetujui`;
};

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
            status: item?.status ?? 'Dipesan',
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
        const response = await axios.get('/api/pengiriman');
        const data = Array.isArray(response.data) ? response.data : [];
        items.value = data.map((item) => normalizeHistory(item));
    } catch (error) {
        items.value = [];
        loadError.value = 'Gagal memuat data peminjaman.';
        showAlert('error', loadError.value);
    } finally {
        isLoading.value = false;
    }
};

const openDetail = (item) => {
    detailItem.value = item;
};

const openPrepare = (item) => {
    prepareItem.value = item;
};

const openShipping = (item) => {
    shippingItem.value = item;
};

const openSuratJalan = (item) => {
    suratJalanItem.value = item;
};

const handleSuratJalanAccepted = async () => {
    await loadHistory();
    suratJalanItem.value = null;
    showAlert('success', 'Peminjaman berhasil diterima.');
};

const submitPrepare = async (payload) => {
    if (!payload?.peminjamanId) {
        return;
    }
    isSubmitting.value = true;
    loadError.value = '';
    try {
        const formData = new FormData();
        payload.items.forEach((row, index) => {
            formData.append(`items[${index}][item_id]`, row.item_id);
            row.files.forEach((file) => {
                formData.append(`items[${index}][photos][]`, file);
            });
        });
        await axios.post(`/api/pengiriman/${payload.peminjamanId}/siapkan`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await loadHistory();
        prepareItem.value = null;
        showAlert('success', 'Peminjaman berhasil disiapkan.');
    } catch (error) {
        loadError.value = error.response?.data?.message ?? 'Gagal menyiapkan peminjaman.';
        showAlert('error', loadError.value);
    } finally {
        isSubmitting.value = false;
    }
};

const submitShipping = async (payload) => {
    if (!payload?.peminjamanId) {
        return;
    }
    isShipping.value = true;
    loadError.value = '';
    try {
        const formData = new FormData();
        formData.append('pengirim_nama', payload.pengirimNama ?? '');
        formData.append('surat_jalan', payload.suratJalan ?? null);
        await axios.post(`/api/pengiriman/${payload.peminjamanId}/kirim`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await loadHistory();
        shippingItem.value = null;
        showAlert('success', 'Pengiriman berhasil dikirim.');
    } catch (error) {
        loadError.value = error.response?.data?.message ?? 'Gagal mengirim peminjaman.';
        showAlert('error', loadError.value);
    } finally {
        isShipping.value = false;
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
    loadHistory();
});
</script>
