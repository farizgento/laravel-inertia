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
        <p class="mt-1 text-sm text-slate-500">Daftar peminjaman yang memerlukan aksi review atau persetujuan</p>
    </div>

    <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="card in summaryCards"
            :key="card.label"
            class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50"
        >
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm text-slate-500">{{ card.label }}</p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="card.iconClass">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path :d="card.icon" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-semibold tabular-nums" :class="card.valueClass">{{ card.value }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
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
            <div class="relative min-w-0 flex-1">
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
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="text"
                    placeholder="Cari pekerjaan atau ID..."
                />
                <button
                    v-if="search"
                    class="absolute inset-y-0 right-2 my-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                    type="button"
                    title="Kosongkan pencarian"
                    aria-label="Kosongkan pencarian"
                    @click="search = ''"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="w-full lg:w-56">
                <select
                    v-model="statusFilter"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    <option value="Semua">Semua Status</option>
                    <option value="Perlu Direview">Perlu Direview</option>
                    <option value="Perlu Disetujui">Perlu Disetujui</option>
                </select>
            </div>
        </div>

        <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Filter aktif</span>
            <span
                v-if="search.trim()"
                class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                <span class="truncate">Pencarian: "{{ search.trim() }}"</span>
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter pencarian"
                    aria-label="Hapus filter pencarian"
                    @click="search = ''"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
            <span
                v-if="statusFilter !== 'Semua'"
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                Status: {{ statusFilter }}
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter status"
                    aria-label="Hapus filter status"
                    @click="statusFilter = 'Semua'"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
            <button
                class="text-xs font-semibold text-slate-500 underline underline-offset-2 transition hover:text-slate-700"
                type="button"
                @click="resetFilters"
            >
                Reset semua
            </button>
        </div>

        <div class="mt-5" aria-live="polite" :aria-busy="isLoading">
            <!-- Loading -->
            <div v-if="isLoading" class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" class="opacity-25" />
                        <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                    </svg>
                    Memuat data peminjaman...
                </div>
                <div class="divide-y divide-slate-100 bg-white">
                    <div v-for="row in 4" :key="`skeleton-${row}`" class="flex items-center gap-4 px-4 py-4">
                        <div class="h-9 w-24 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 flex-1 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 w-32 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-6 w-28 shrink-0 animate-pulse rounded-full bg-slate-100"></div>
                        <div class="h-9 w-24 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                    </div>
                </div>
            </div>

            <!-- Error -->
            <div
                v-else-if="loadError"
                class="flex flex-col items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5 sm:flex-row sm:items-center sm:justify-between"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.3 4.3 2.9 17.1a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z" />
                            <path d="M12 9v4M12 17h.01" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-rose-800">Gagal memuat data</p>
                        <p class="mt-0.5 text-sm text-rose-700">{{ loadError }}</p>
                    </div>
                </div>
                <button
                    class="h-10 shrink-0 rounded-xl border border-rose-300 bg-white px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"
                    type="button"
                    @click="loadHistory"
                >
                    Coba lagi
                </button>
            </div>

            <!-- Empty -->
            <div v-else-if="!filteredItems.length" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-12 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 12 2 2 4-4" />
                        <path d="M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-700">
                    {{ hasActiveFilters ? 'Tidak ada peminjaman yang cocok' : 'Tidak ada yang perlu ditindak' }}
                </p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                    {{
                        hasActiveFilters
                            ? 'Coba ubah kata kunci atau pilih status lain untuk memperluas hasil pencarian.'
                            : 'Semua pengajuan peminjaman sudah direview. Pengajuan baru akan muncul di sini.'
                    }}
                </p>
                <button
                    v-if="hasActiveFilters"
                    class="mt-4 h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                    type="button"
                    @click="resetFilters"
                >
                    Reset filter
                </button>
            </div>

            <!-- Tabel (md ke atas) -->
            <div v-else>
                <div class="hidden overflow-hidden rounded-2xl border border-slate-200 md:block">
                    <div class="max-h-[68vh] overflow-auto">
                        <table class="w-full min-w-[900px] text-sm">
                            <caption class="sr-only">
                                Daftar peminjaman yang menunggu review atau persetujuan
                            </caption>
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Dibuat</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Pekerjaan</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Peminjam</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Status</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Periode</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="item in filteredItems"
                                    :key="item.id"
                                    class="align-top transition hover:bg-slate-50/70"
                                >
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <p class="font-medium text-slate-700">{{ splitDateTime(item.createdAt).date }}</p>
                                        <p class="mt-0.5 text-xs tabular-nums text-slate-500">
                                            {{ splitDateTime(item.createdAt).time }}
                                        </p>
                                    </td>
                                    <td class="max-w-[22rem] px-4 py-4">
                                        <p class="line-clamp-2 font-semibold text-slate-900" :title="item.title">{{ item.title }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                            <span class="font-mono">#{{ item.id }}</span>
                                            <span aria-hidden="true">&middot;</span>
                                            <span>{{ item.itemCount }} item</span>
                                            <span
                                                class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                :class="kategoriClass(item.kategori)"
                                            >
                                                {{ item.kategori }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-900">{{ item.userName }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Direview: {{ reviewApprovalLabel(item) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-[11px] font-semibold"
                                            :class="statusClass(item.status)"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(item.status)" aria-hidden="true"></span>
                                            {{ item.status }}
                                        </span>
                                        <p v-if="isReviewableItem(item)" class="mt-1 text-[11px] font-semibold text-blue-600">
                                            Menunggu aksi Anda
                                        </p>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                        <p class="text-xs">{{ item.borrowDate }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">s/d {{ item.returnDate }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <button
                                            class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2"
                                            :class="
                                                isReviewableItem(item)
                                                    ? 'bg-blue-600 text-white hover:bg-blue-700 focus-visible:ring-blue-300'
                                                    : 'border border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:text-blue-700 focus-visible:ring-blue-200'
                                            "
                                            type="button"
                                            @click="openDetail(item)"
                                        >
                                            <svg
                                                v-if="isReviewableItem(item)"
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                                            >
                                                <path d="M9 11l3 3L22 4" />
                                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                                            </svg>
                                            <svg
                                                v-else
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                                            >
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            {{ isReviewableItem(item) ? 'Review' : 'Detail' }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kartu (di bawah md) -->
                <ul class="space-y-3 md:hidden">
                    <li
                        v-for="item in filteredItems"
                        :key="`card-${item.id}`"
                        class="rounded-2xl border bg-white p-4"
                        :class="isReviewableItem(item) ? 'border-blue-200 ring-1 ring-blue-100' : 'border-slate-200'"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ item.title }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">#{{ item.id }}</p>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                                :class="statusClass(item.status)"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(item.status)" aria-hidden="true"></span>
                                {{ item.status }}
                            </span>
                        </div>

                        <dl class="mt-3 space-y-1.5 text-sm">
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Peminjam</dt>
                                <dd class="min-w-0 text-slate-600">
                                    <span class="font-semibold text-slate-900">{{ item.userName }}</span>
                                    <span class="block text-xs text-slate-500">Direview: {{ reviewApprovalLabel(item) }}</span>
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Periode</dt>
                                <dd class="min-w-0 text-slate-600">{{ item.borrowDate }} s/d {{ item.returnDate }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Item</dt>
                                <dd class="min-w-0 text-slate-600">
                                    {{ item.itemCount }} item
                                    <span
                                        class="ml-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="kategoriClass(item.kategori)"
                                    >
                                        {{ item.kategori }}
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <button
                            class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl px-3 py-2 text-xs font-semibold transition"
                            :class="
                                isReviewableItem(item)
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'border border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:text-blue-700'
                            "
                            type="button"
                            @click="openDetail(item)"
                        >
                            {{ isReviewableItem(item) ? 'Review sekarang' : 'Lihat detail' }}
                        </button>
                    </li>
                </ul>

                <p class="mt-4 border-t border-slate-200 pt-4 text-sm text-slate-500">
                    Menampilkan <span class="font-semibold text-slate-700">{{ filteredItems.length }}</span>
                    dari <span class="font-semibold text-slate-700">{{ totalCount }}</span> peminjaman
                </p>
            </div>
        </div>
    </section>

    <ReviewPeminjamanModal
        :open="!!selectedItem"
        :item="selectedItem"
        :is-submitting="isSubmitting"
        :read-only="!isReviewableItem(selectedItem)"
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
    return null;
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
const currentReviewAreaId = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaId.value
        : page.props.auth?.user?.area_id ?? page.props.auth?.user?.area?.id ?? cachedUser.value?.area_id ?? null
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
let hasLoadedInitialReviewData = false;
let loadHistoryRequestId = 0;

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

const isReviewableItem = (item) => {
    if (!item || !currentReviewAreaId.value) {
        return false;
    }
    if (item.status === 'Perlu Direview') {
        return Number(item.requesterAreaId) === Number(currentReviewAreaId.value);
    }
    if (item.status === 'Perlu Disetujui') {
        return Number(item.areaId) === Number(currentReviewAreaId.value);
    }
    return false;
};

const totalCount = computed(() => items.value.length);
const reviewCount = computed(() => items.value.filter((item) => item.status === 'Perlu Direview').length);
const approvalCount = computed(() => items.value.filter((item) => item.status === 'Perlu Disetujui').length);
const interAreaCount = computed(() => items.value.filter((item) => item.kategori === 'Antar Area').length);
const actionableCount = computed(() => items.value.filter((item) => isReviewableItem(item)).length);

const summaryCards = computed(() => [
    {
        label: 'Total Perlu Aksi',
        value: totalCount.value,
        hint: actionableCount.value
            ? `${actionableCount.value} menunggu aksi Anda`
            : 'Tidak ada yang menunggu Anda',
        icon: 'M3 4h18l-7 8v6l-4 2v-8L3 4z',
        iconClass: 'bg-slate-100 text-slate-500',
        valueClass: 'text-slate-900',
    },
    {
        label: 'Perlu Direview',
        value: reviewCount.value,
        hint: 'Menunggu review area peminjam',
        icon: 'M12 8v4l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z',
        iconClass: 'bg-blue-50 text-blue-600',
        valueClass: 'text-blue-600',
    },
    {
        label: 'Perlu Disetujui',
        value: approvalCount.value,
        hint: 'Menunggu persetujuan area pemilik',
        icon: 'm9 12 2 2 4-4M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z',
        iconClass: 'bg-amber-50 text-amber-600',
        valueClass: 'text-amber-500',
    },
    {
        label: 'Antar Area',
        value: interAreaCount.value,
        hint: 'Pengajuan lintas area',
        icon: 'M4 7h11M4 7l3-3M4 7l3 3M20 17H9m11 0-3-3m3 3-3 3',
        iconClass: 'bg-emerald-50 text-emerald-600',
        valueClass: 'text-emerald-600',
    },
]);

const hasActiveFilters = computed(() => search.value.trim() !== '' || statusFilter.value !== 'Semua');

const resetFilters = () => {
    search.value = '';
    statusFilter.value = 'Semua';
};

const statusClass = (status) =>
    status === 'Perlu Direview' ? 'bg-blue-50 text-blue-700' : 'bg-amber-50 text-amber-700';

// Titik warna hanya penegas; label teks status tetap menjadi pembeda utamanya.
const statusDotClass = (status) => (status === 'Perlu Direview' ? 'bg-blue-500' : 'bg-amber-500');

// createdAt dikirim backend sebagai "d M Y H:i", dipisah hanya untuk tampilan dan
// otomatis kembali menampilkan nilai apa adanya bila formatnya berbeda.
const splitDateTime = (value) => {
    const raw = String(value ?? '').trim();
    if (!raw) {
        return { date: '-', time: '' };
    }

    const separatorIndex = raw.lastIndexOf(' ');
    if (separatorIndex === -1) {
        return { date: raw, time: '' };
    }

    const time = raw.slice(separatorIndex + 1);
    if (!/^\d{1,2}:\d{2}(:\d{2})?$/.test(time)) {
        return { date: raw, time: '' };
    }

    return { date: raw.slice(0, separatorIndex), time };
};

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
    areaName: item?.area_name ?? '-',
    requesterAreaId: item?.requester_area_id ?? null,
    requesterAreaName: item?.requester_area_name ?? '-',
    isInterArea: Boolean(item?.is_inter_area),
    title: item?.title ?? '-',
    resi: item?.resi ?? '',
    userName: item?.user_name ?? '-',
    reviewerName: item?.reviewed_by_name ?? '-',
    requesterReviewerName: item?.requester_reviewed_by_name ?? '-',
    reviewNote: item?.review_note ?? '',
    requesterReviewNote: item?.requester_review_note ?? '',
    createdAt: item?.created_at ?? '-',
    borrowDate: item?.borrow_date ?? '-',
    returnDate: item?.return_date ?? '-',
    itemCount: Number.isFinite(item?.item_count) ? item.item_count : 0,
    status: item?.status ?? 'Perlu Disetujui',
    kategori: item?.kategori ?? 'Intra Area',
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

const reviewApprovalLabel = (item) => {
    const reviewer = item?.requesterReviewerName && item.requesterReviewerName !== '-'
        ? item.requesterReviewerName
        : item?.reviewerName && item.reviewerName !== '-'
            ? item.reviewerName
            : '-';
    const approver = item?.reviewerName && item.reviewerName !== '-' ? item.reviewerName : '-';

    return item?.kategori === 'Antar Area' ? `${reviewer}/${approver}` : approver;
};

const kategoriClass = (kategori) => {
    switch (kategori) {
        case 'Antar Area':
            return 'bg-purple-50 text-purple-700';
        case 'Intra Area':
            return 'bg-sky-50 text-sky-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const loadHistory = async () => {
    const reviewAreaId = currentReviewAreaId.value;
    const requestId = loadHistoryRequestId + 1;
    loadHistoryRequestId = requestId;

    if (isAreaSwitcherRole.value && !reviewAreaId) {
        items.value = [];
        loadError.value = '';
        isLoading.value = false;
        return;
    }

    isLoading.value = true;
    loadError.value = '';
    try {
        const params = {};
        if (isAreaSwitcherRole.value && reviewAreaId) {
            params.area_id = reviewAreaId;
        }
        const response = await axios.get('/api/review-peminjaman', { params });
        const data = Array.isArray(response.data) ? response.data : [];
        if (requestId !== loadHistoryRequestId) {
            return;
        }
        items.value = data.map((item) => normalizeHistory(item));
    } catch (error) {
        if (requestId !== loadHistoryRequestId) {
            return;
        }
        items.value = [];
        loadError.value = 'Gagal memuat data peminjaman.';
    } finally {
        if (requestId === loadHistoryRequestId) {
            isLoading.value = false;
        }
    }
};

const submitReview = async (payload) => {
    if (!payload?.peminjamanId) {
        return;
    }
    isSubmitting.value = true;
    loadError.value = '';
    try {
        const reviewAreaId = selectedItem.value?.status === 'Perlu Direview'
            ? selectedItem.value?.requesterAreaId
            : selectedItem.value?.areaId ?? activeAreaId.value ?? null;
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
    if (!isAreaSwitcherRole.value || currentReviewAreaId.value) {
        hasLoadedInitialReviewData = true;
        loadHistory();
    }
});

watch(
    () => [isAreaSwitcherRole.value, currentReviewAreaId.value],
    async ([isSwitcher, nextAreaId], [, prevAreaId] = []) => {
        if (isSwitcher && !nextAreaId) {
            return;
        }
        const shouldShow = isSwitcher && hasLoadedInitialReviewData && nextAreaId !== prevAreaId;
        if (shouldShow) {
            setAreaSwitching?.(true);
        }
        try {
            hasLoadedInitialReviewData = true;
            await loadHistory();
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);
</script>
