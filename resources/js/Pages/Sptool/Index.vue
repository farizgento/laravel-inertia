<template>
    <div
        v-if="alertMessage"
        class="mb-4 rounded-xl border px-4 py-3 text-sm font-semibold"
        :class="alertType === 'success'
            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
            : 'border-rose-200 bg-rose-50 text-rose-700'"
    >
        {{ alertMessage }}
    </div>
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
            <p class="text-sm text-slate-500">Diproses</p>
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
                <p class="mt-1 text-sm text-slate-500">Klik pada kartu untuk melihat detail</p>
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
                    <option value="Diproses">Diproses</option>
                    <option value="Terkirim">Terkirim</option>
                    <option value="Ditolak">Ditolak</option>
                </select>
            </div>
        </div>

        <div class="mt-4 space-y-4">
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat data peminjaman...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Belum ada peminjaman.
            </p>
            <article
                v-for="item in filteredItems"
                :key="item.id"
                class="rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:bg-white"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-semibold text-blue-600">
                                {{ item.status }}
                            </span>
                            <span>{{ item.createdAt }}</span>
                        </div>
                        <h3 class="mt-2 text-base font-semibold text-slate-900">{{ item.title }}</h3>
                        <p class="mt-1 text-xs font-semibold text-slate-600">
                            Peminjam: {{ item.userName }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ item.itemCount }} item - Kembali: {{ item.returnDate }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <div class="hidden items-center gap-2 md:flex">
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-full border-2"
                                    :class="stepClass(item.status, 'Menunggu Review')"
                                >
                                    <span
                                        v-if="item.status === 'Menunggu Review'"
                                        class="h-2 w-2 rounded-full bg-white"
                                    ></span>
                                </div>
                                <span class="text-xs font-semibold text-slate-700">Menunggu Review</span>
                            </div>
                            <span class="h-px w-8 bg-slate-200"></span>
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-full border-2"
                                    :class="stepClass(item.status, 'Diproses')"
                                >
                                    <span
                                        v-if="item.status === 'Diproses'"
                                        class="h-2 w-2 rounded-full bg-white"
                                    ></span>
                                </div>
                                <span class="text-xs font-semibold text-slate-500">Diproses</span>
                            </div>
                            <span class="h-px w-8 bg-slate-200"></span>
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-full border-2"
                                    :class="stepClass(item.status, 'Terkirim')"
                                >
                                    <span
                                        v-if="item.status === 'Terkirim'"
                                        class="h-2 w-2 rounded-full bg-white"
                                    ></span>
                                </div>
                                <span class="text-xs font-semibold text-slate-500">Terkirim</span>
                            </div>
                            <span class="h-px w-8 bg-slate-200"></span>
                            <div class="flex items-center gap-2">
                                <div
                                    class="flex h-6 w-6 items-center justify-center rounded-full border-2"
                                    :class="stepClass(item.status, 'Ditolak')"
                                >
                                    <span
                                        v-if="item.status === 'Ditolak'"
                                        class="h-2 w-2 rounded-full bg-white"
                                    ></span>
                                </div>
                                <span class="text-xs font-semibold text-slate-500">Ditolak</span>
                            </div>
                        </div>
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
                </div>
            </article>
        </div>
    </section>

    <ReviewPeminjamanModal
        :open="!!selectedItem"
        :item="selectedItem"
        :is-submitting="isSubmitting"
        @close="closeDetail"
        @submit="submitReview"
    />
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ReviewPeminjamanModal from '../../Components/ReviewPeminjamanModal.vue';

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
const areaName = computed(() => page.props.auth?.user?.area?.name ?? 'Area A');

const items = ref([]);
const isLoading = ref(false);
const isSubmitting = ref(false);
const loadError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
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
const processCount = computed(() => items.value.filter((item) => item.status === 'Diproses').length);
const deliveredCount = computed(() => items.value.filter((item) => item.status === 'Terkirim').length);

const openDetail = (item) => {
    selectedItem.value = item;
};

const closeDetail = () => {
    selectedItem.value = null;
};

const stepClass = (status, step) => {
    if (status === step) {
        return 'border-blue-600 bg-blue-600';
    }
    return 'border-slate-300 bg-white';
};

const normalizeHistory = (item) => ({
    id: item?.id ?? '',
    title: item?.title ?? '-',
    userName: item?.user_name ?? '-',
    reviewNote: item?.review_note ?? '',
    createdAt: item?.created_at ?? '-',
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
        const response = await axios.get('/api/review-peminjaman');
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
        await axios.post(`/api/review-peminjaman/${payload.peminjamanId}`, payload);
        await loadHistory();
        selectedItem.value = null;
        showAlert('success', 'Review peminjaman berhasil disimpan.');
    } catch (error) {
        loadError.value = error.response?.data?.message ?? 'Gagal menyimpan review.';
        showAlert('error', loadError.value);
    } finally {
        isSubmitting.value = false;
    }
};

const showAlert = (type, message) => {
    alertType.value = type;
    alertMessage.value = message;
    if (alertTimeout) {
        clearTimeout(alertTimeout);
    }
    alertTimeout = setTimeout(() => {
        alertMessage.value = '';
    }, 3000);
};

onMounted(() => {
    loadHistory();
});
</script>

