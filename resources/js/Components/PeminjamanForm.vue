<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <section class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/60 space-y-6">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Katalog Alat - {{ areaName }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">Pilih alat yang ingin dipinjam dan checkout</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300"
                    type="button"
                    @click="toggleView"
                >
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <svg
                            v-if="viewMode === 'grid'"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M8 6h13" />
                            <path d="M8 12h13" />
                            <path d="M8 18h13" />
                            <path d="M3 6h1" />
                            <path d="M3 12h1" />
                            <path d="M3 18h1" />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="3" width="8" height="8" rx="2" />
                            <rect x="13" y="3" width="8" height="8" rx="2" />
                            <rect x="13" y="13" width="8" height="8" rx="2" />
                            <rect x="3" y="13" width="8" height="8" rx="2" />
                        </svg>
                    </span>
                    <span>{{ viewMode === 'grid' ? 'Tampilan List' : 'Tampilan Kartu' }}</span>
                </button>
                <button
                    class="relative inline-flex items-center gap-3 rounded-2xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 shadow-sm transition hover:border-blue-300"
                    type="button"
                    @click="drawerOpen = true"
                >
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 6h15l-2 9H8L6 6Z" />
                            <path d="M6 6 5 3H2" />
                            <circle cx="9" cy="20" r="1.5" />
                            <circle cx="18" cy="20" r="1.5" />
                        </svg>
                    </span>
                    <span>Keranjang</span>
                    <span
                        v-if="totalItems"
                        class="absolute -right-2 -top-2 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-blue-600 px-1 text-xs font-semibold text-white"
                    >
                        {{ totalItems }}
                    </span>
                </button>
            </div>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <label v-if="canBrowseCatalogAreas" class="relative md:w-80 md:shrink-0">
                <span class="sr-only">Area katalog</span>
                <select
                    v-model="selectedAreaId"
                    class="h-full min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option
                        v-for="area in areas"
                        :key="area.id"
                        :value="String(area.id)"
                    >
                        {{ area.name }}
                    </option>
                </select>
            </label>
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Cari alat</span>
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Cari nama atau kode alat..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-4 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
        </div>

        <div
            v-if="isViewingOtherArea"
            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700"
        >
            Anda sedang melihat katalog area lain. Data hanya dapat dilihat, peminjaman tetap hanya bisa dibuat dari area akun Anda.
        </div>

        <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                    <path d="m3.3 7 8.7 5 8.7-5" />
                    <path d="M12 12v9" />
                </svg>
            </span>
            <span v-if="isLoading">Memuat data alat...</span>
            <span v-else>{{ filteredTools.length }} alat tersedia</span>
            <span v-if="loadError" class="text-rose-500">{{ loadError }}</span>
        </div>

        <div v-if="viewMode === 'list'" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                            <th class="w-12 px-4 py-3 text-center">Pilih</th>
                            <th class="min-w-[260px] px-4 py-3">Alat</th>
                            <th class="w-32 px-4 py-3">Stok</th>
                            <th class="w-56 px-4 py-3">Jumlah Dipinjam</th>
                            <th class="w-40 px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr
                            v-for="tool in filteredTools"
                            :key="tool.id"
                            :class="[
                                'transition hover:bg-slate-50',
                                isInCart(tool.id) ? 'bg-blue-50/60' : '',
                                isOutOfStock(tool) ? 'text-slate-400' : 'text-slate-700',
                            ]"
                        >
                            <td class="px-4 py-3 text-center align-middle">
                                <input
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                                    type="checkbox"
                                    :checked="isInCart(tool.id)"
                                    :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                    :aria-label="`Pilih ${tool.nama}`"
                                    @change="toggleCartSelection(tool, $event.target.checked)"
                                />
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-400">{{ tool.kode }}</p>
                                    <p class="mt-0.5 font-semibold capitalize text-slate-900">{{ tool.nama }}</p>
                                    <p v-if="tool.lokasi" class="mt-1 text-xs text-slate-500">{{ tool.lokasi }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span
                                    :class="[
                                        'inline-flex min-w-[64px] items-center justify-center rounded-lg px-2.5 py-1 text-xs font-semibold',
                                        isOutOfStock(tool)
                                            ? 'bg-rose-50 text-rose-600'
                                            : 'bg-slate-100 text-slate-700',
                                    ]"
                                >
                                    {{ tool.stok }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="inline-flex h-10 items-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                    <button
                                        class="flex h-10 w-10 items-center justify-center text-base font-semibold text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300"
                                        type="button"
                                        :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                        @click="decreaseTool(tool)"
                                    >
                                        -
                                    </button>
                                    <input
                                        class="h-10 w-16 border-x border-slate-200 text-center text-sm font-semibold text-slate-800 outline-none focus:bg-blue-50 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                                        type="number"
                                        min="1"
                                        :max="tool.stok"
                                        :value="displayQty(tool.id)"
                                        :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                        @input="setToolQty(tool, $event.target.value)"
                                    />
                                    <button
                                        class="flex h-10 w-10 items-center justify-center text-base font-semibold text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300"
                                        type="button"
                                        :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                        @click="increaseTool(tool)"
                                    >
                                        +
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span
                                    v-if="isReadOnlyCatalog"
                                    class="inline-flex rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                                >
                                    Lihat Saja
                                </span>
                                <span
                                    v-else-if="isOutOfStock(tool)"
                                    class="inline-flex rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600"
                                >
                                    Stok Habis
                                </span>
                                <span
                                    v-else-if="isInCart(tool.id)"
                                    class="inline-flex rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"
                                >
                                    Di Keranjang
                                </span>
                                <span
                                    v-else
                                    class="inline-flex rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                >
                                    Belum Dipilih
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!filteredTools.length && !isLoading">
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-500">
                                Data alat tidak ditemukan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="tool in filteredTools"
                :key="tool.id"
                :class="[
                    'relative rounded-2xl border bg-white p-5 shadow-sm transition',
                    isInCart(tool.id) ? 'border-blue-300 bg-blue-50/60 shadow-blue-100' : 'border-slate-200',
                ]"
            >
                <div class="flex flex-1 flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-400">
                                {{ tool.kode }}
                            </p>
                            <h3 class="text-base font-semibold capitalize text-slate-900">{{ tool.nama }}</h3>
                        </div>
                        <span
                            v-if="isInCart(tool.id)"
                            :class="[
                                'rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white',
                                'absolute right-4 top-4',
                            ]"
                        >
                            Di Keranjang
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span class="flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                                <path d="m3.3 7 8.7 5 8.7-5" />
                                <path d="M12 12v9" />
                            </svg>
                            Stok: {{ tool.stok }}
                        </span>
                        <span
                            v-if="isOutOfStock(tool)"
                            class="rounded-full bg-rose-100 px-2.5 py-1 font-semibold text-rose-600"
                        >
                            Stok habis
                        </span>
                    </div>

                    <p v-if="tool.deskripsi" class="text-sm text-slate-500">
                        {{ tool.deskripsi }}
                    </p>

                    <div v-if="tool.lokasi" class="flex items-center gap-2 text-xs text-slate-500">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
                            <circle cx="12" cy="11" r="2.5" />
                        </svg>
                        <span>{{ tool.lokasi }}</span>
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <div class="flex items-center rounded-full bg-slate-100 px-2 py-1">
                        <button
                            class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:text-slate-300"
                            type="button"
                            :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                            @click="decreaseTool(tool)"
                        >
                            -
                        </button>
                        <span class="w-8 text-center text-sm font-semibold text-slate-700">
                            {{ displayQty(tool.id) }}
                        </span>
                        <button
                            class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:text-slate-300"
                            type="button"
                            :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                            @click="increaseTool(tool)"
                        >
                            +
                        </button>
                    </div>
                    <button
                        :class="[
                            'flex items-center justify-center gap-2 rounded-xl bg-blue-600 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-200',
                            'flex-1 px-4 py-2.5',
                        ]"
                        type="button"
                        :disabled="isReadOnlyCatalog || isInCart(tool.id) || isOutOfStock(tool)"
                        @click="addToCart(tool)"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 6h15l-2 9H8L6 6Z" />
                            <path d="M6 6 5 3H2" />
                            <circle cx="9" cy="20" r="1.5" />
                            <circle cx="18" cy="20" r="1.5" />
                        </svg>
                        <span>
                            {{ isReadOnlyCatalog ? 'Lihat Saja' : isOutOfStock(tool) ? 'Stok Habis' : isInCart(tool.id) ? 'Telah ditambahkan' : 'Tambah' }}
                        </span>
                    </button>
                </div>
            </article>
        </div>

        <div
            v-if="pagination.lastPage > 1"
            class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            <span>
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} · Total {{ pagination.total }} alat
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

    <CartModal
        v-model="drawerOpen"
        :cart-items="cartItems"
        :unique-items="uniqueItems"
        :total-items="totalItems"
        @remove="removeFromCart"
        @decrease="decreaseCart"
        @increase="increaseCart"
        @checkout="openCheckout"
    />
    <CheckoutModal
        v-model="checkoutOpen"
        :cart-items="cartItems"
        :unique-items="uniqueItems"
        :total-items="totalItems"
        :form="form"
        :date-limits="checkoutDateLimits"
        :is-submitting="isSubmitting"
        :checkout-error="checkoutError"
        @submit="submitCheckout"
    />
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import CartModal from './CartModal.vue';
import CheckoutModal from './CheckoutModal.vue';
import ToastNotification from './ToastNotification.vue';
import { loadAreas as loadSharedAreas } from '../lib/areas';

const STORAGE_KEY_PREFIX = 'peminjaman_cart_v1';
const REPEAT_DRAFT_STORAGE_KEY = 'peminjaman_repeat_draft_v1';
const page = usePage();
const cachedUserId = ref(null);
const cachedUser = ref(null);
const initialUserAreaId = page.props.auth?.user?.area?.id ?? '';

const loadCachedUserId = () => {
    return null;
};

const loadCachedUser = () => {
    return null;
};

const katalog = ref([]);
const areas = ref([]);
const selectedAreaId = ref(initialUserAreaId ? String(initialUserAreaId) : '');
const isLoading = ref(false);
const loadError = ref('');
const toolCache = reactive({});

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 8,
});

const userId = computed(() => page.props.auth?.user?.id ?? cachedUserId.value);
const userAreaId = computed(() => page.props.auth?.user?.area?.id ?? cachedUser.value?.area?.id ?? null);
const roleKey = computed(() =>
    (page.props.auth?.user?.role?.key ?? cachedUser.value?.role?.key ?? 'user').toLowerCase(),
);
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const activeAreaName = inject('activeAreaName', ref('Area tidak diketahui'));
const areaId = computed(
    () => {
        if (isAreaSwitcherRole.value) {
            return activeAreaId.value ?? null;
        }
        return selectedAreaId.value || userAreaId.value || null;
    },
);
const shouldRestrictArea = computed(
    () => roleKey.value !== 'admin' || isAreaSwitcherRole.value,
);
const areaName = computed(
    () => {
        if (isAreaSwitcherRole.value) {
            return activeAreaName.value;
        }
        const selected = areas.value.find((area) => String(area.id) === String(areaId.value));
        if (selected) {
            return selected.name;
        }
        return (
            page.props.auth?.user?.area?.name ??
            cachedUser.value?.area?.name ??
            'Area tidak diketahui'
        );
    },
);
const isViewingOtherArea = computed(() =>
    roleKey.value === 'user'
    && selectedAreaId.value
    && userAreaId.value
    && String(selectedAreaId.value) !== String(userAreaId.value)
);
const canBrowseCatalogAreas = computed(() => roleKey.value === 'user');
const isReadOnlyCatalog = computed(() => isViewingOtherArea.value);
const storageKey = computed(() =>
    userId.value ? `${STORAGE_KEY_PREFIX}_${userId.value}` : `${STORAGE_KEY_PREFIX}_guest`,
);

const loadCartFromStorage = () => {
    if (typeof window === 'undefined') {
        return;
    }
    const stored = window.localStorage.getItem(storageKey.value);
    if (!stored) {
        cart.value = [];
        return;
    }
    try {
        const parsed = JSON.parse(stored);
        cart.value = Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        cart.value = [];
    }
};

const loadRepeatDraft = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    const stored = window.sessionStorage.getItem(REPEAT_DRAFT_STORAGE_KEY);
    if (!stored) {
        return null;
    }

    window.sessionStorage.removeItem(REPEAT_DRAFT_STORAGE_KEY);

    try {
        const parsed = JSON.parse(stored);
        return parsed && Array.isArray(parsed.items) ? parsed : null;
    } catch (error) {
        return null;
    }
};

const filters = reactive({
    search: '',
});

const filteredTools = computed(() => katalog.value);
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

const cart = ref([]);
const cartDrafts = reactive({});
const drawerOpen = ref(false);
const checkoutOpen = ref(false);
const isSubmitting = ref(false);
const checkoutError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const katalogMap = computed(() => new Map(katalog.value.map((item) => [item.id, item])));
const getToolById = (toolId) => toolCache[toolId] ?? katalogMap.value.get(toolId);
const isOutOfStock = (tool) => (tool?.stok ?? 0) <= 0;

const normalizeCart = (items) => {
    return items
        .filter((item) => Number.isFinite(item.qty) && item.qty > 0)
        .map((item) => {
            const tool = getToolById(item.id);
            if (!tool) {
                return null;
            }
            const max = tool.stok;
            return {
                id: item.id,
                qty: Math.min(item.qty, max),
            };
        })
        .filter((item) => item && item.qty > 0);
};

const normalizeTool = (item) => {
    const id = item?.id ?? '';
    const stok = Number.isFinite(item?.stok) ? item.stok : Number(item?.total_aset ?? 0);
    return {
        id,
        kode: item?.kode ?? item?.code ?? '-',
        nama: item?.nama ?? '-',
        stok: Number.isFinite(stok) ? stok : 0,
        deskripsi: item?.deskripsi ?? '',
        lokasi: item?.lokasi ?? item?.area_name ?? '',
        isSharedAreaStock: Boolean(item?.is_shared_area_stock),
        sourcePeminjamanId: item?.source_peminjaman_id ?? null,
        sourceBorrowDate: item?.source_borrow_date ?? '',
        sourceReturnDate: item?.source_return_date ?? '',
    };
};

const cacheTool = (tool) => {
    if (!tool?.id) {
        return;
    }
    toolCache[tool.id] = {
        ...toolCache[tool.id],
        ...tool,
    };
};

const applyRepeatDraft = (draft) => {
    if (!draft || isReadOnlyCatalog.value) {
        return;
    }

    const draftItems = draft.items
        .map((item) => {
            const id = item?.id ?? item?.alat_id ?? null;
            const qty = Math.max(1, Math.floor(Number(item?.qty ?? 0)));
            const stok = Math.max(qty, Math.floor(Number(item?.stok ?? qty)));

            if (!id || !Number.isFinite(qty) || qty <= 0) {
                return null;
            }

            cacheTool({
                id,
                kode: item?.kode ?? item?.code ?? '-',
                nama: item?.nama ?? item?.name ?? '-',
                stok,
                deskripsi: item?.deskripsi ?? '',
                lokasi: item?.lokasi ?? '',
            });

            cartDrafts[id] = Math.min(qty, stok);

            return {
                id,
                qty: Math.min(qty, stok),
            };
        })
        .filter(Boolean);

    if (!draftItems.length) {
        return;
    }

    cart.value = draftItems;
    form.value = {
        tanggal_pinjam: draft.tanggal_pinjam ?? '',
        tanggal_kembali: draft.tanggal_kembali ?? '',
        pekerjaan: draft.pekerjaan ?? '',
    };
    drawerOpen.value = true;
    showAlert('success', 'Draft peminjaman ulang sudah dimuat. Silakan sesuaikan alat, jumlah, atau periode sebelum checkout.');
};

let filterTimeout = null;

const buildFilterParams = () => {
    const params = {};
    const searchText = filters.search.trim();
    if (searchText) {
        params.search = searchText;
    }
    if (shouldRestrictArea.value && areaId.value) {
        params.area_id = areaId.value;
    }
    if (roleKey.value === 'user' && selectedAreaId.value) {
        params.browse_area = true;
    }
    return params;
};

const loadAreas = async () => {
    try {
        areas.value = await loadSharedAreas();
    } catch (error) {
        areas.value = [];
    }
};

const loadKatalog = async (params = {}) => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/alats', {
            params: {
                ...params,
                page: pagination.currentPage,
                per_page: pagination.perPage,
            },
        });
        const payload = response.data;
        if (Array.isArray(payload)) {
            katalog.value = payload.map((item) => normalizeTool(item));
            pagination.total = katalog.value.length;
            pagination.lastPage = 1;
            pagination.currentPage = 1;
        } else {
            const data = Array.isArray(payload?.data) ? payload.data : [];
            const meta = payload?.meta ?? {};
            katalog.value = data.map((item) => normalizeTool(item));
            pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
            pagination.lastPage = Number(meta.last_page ?? 1);
            pagination.perPage = Number(meta.per_page ?? pagination.perPage);
            pagination.total = Number(meta.total ?? katalog.value.length);
        }
        katalog.value.forEach((tool) => cacheTool(tool));
        cart.value = normalizeCart(cart.value);
    } catch (error) {
        katalog.value = [];
        loadError.value = 'Gagal memuat data alat.';
        pagination.total = 0;
        pagination.lastPage = 1;
        pagination.currentPage = 1;
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
    loadKatalog(buildFilterParams());
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        cachedUserId.value = loadCachedUserId();
        cachedUser.value = loadCachedUser();
        loadCartFromStorage();
        applyRepeatDraft(loadRepeatDraft());
    }
    loadAreas();
    loadKatalog(buildFilterParams());
});

watch(
    () => filters.search,
    () => {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }
        filterTimeout = setTimeout(() => {
            pagination.currentPage = 1;
            loadKatalog(buildFilterParams());
        }, 300);
    },
);

watch(
    () => activeAreaId.value,
    (next, prev) => {
        if (!isAreaSwitcherRole.value || next === prev) {
            return;
        }
        pagination.currentPage = 1;
        loadKatalog(buildFilterParams());
    },
);

watch(
    () => selectedAreaId.value,
    (next, prev) => {
        if (next === prev || isAreaSwitcherRole.value) {
            return;
        }
        pagination.currentPage = 1;
        Object.keys(cartDrafts).forEach((key) => {
            delete cartDrafts[key];
        });
        cart.value = [];
        loadKatalog(buildFilterParams());
    },
);

watch(
    () => userId.value,
    (value, previous) => {
        if (value === previous) {
            return;
        }
        if (typeof window === 'undefined') {
            return;
        }
        cachedUser.value = loadCachedUser();
        loadCartFromStorage();
        Object.keys(cartDrafts).forEach((key) => {
            delete cartDrafts[key];
        });
    },
);

watch(
    cart,
    (value) => {
        if (typeof window === 'undefined') {
            return;
        }
        window.localStorage.setItem(storageKey.value, JSON.stringify(value));
    },
    { deep: true },
);

const cartItems = computed(() =>
    cart.value
        .map((item) => {
            const tool = getToolById(item.id);
            if (!tool) {
                return null;
            }
            return { ...tool, qty: item.qty };
        })
        .filter((item) => item && item.qty > 0),
);

const checkoutDateLimits = computed(() => {
    const sharedItems = cartItems.value.filter((item) => item.isSharedAreaStock);
    if (!sharedItems.length) {
        return {
            minBorrowDate: '',
            maxReturnDate: '',
            message: '',
        };
    }

    const borrowDates = sharedItems
        .map((item) => item.sourceBorrowDate)
        .filter(Boolean)
        .sort();
    const returnDates = sharedItems
        .map((item) => item.sourceReturnDate)
        .filter(Boolean)
        .sort();
    const minBorrowDate = borrowDates[borrowDates.length - 1] ?? '';
    const maxReturnDate = returnDates[0] ?? '';

    return {
        minBorrowDate,
        maxReturnDate,
        message: minBorrowDate && maxReturnDate
            ? `Tanggal peminjaman alat antar area dibatasi ${minBorrowDate} sampai ${maxReturnDate}.`
            : 'Tanggal peminjaman alat antar area dibatasi sesuai periode peminjaman antar area.',
    };
});

const totalItems = computed(() => cart.value.reduce((total, item) => total + item.qty, 0));
const uniqueItems = computed(() => cart.value.length);
const viewMode = ref('list');

const findCartItem = (toolId) => cart.value.find((item) => item.id === toolId);
const isInCart = (toolId) => !!findCartItem(toolId);

const getDraftQty = (toolId) => {
    const tool = getToolById(toolId);
    if (!tool || tool.stok <= 0) {
        return 0;
    }
    return cartDrafts[toolId] ?? 1;
};
const setDraftQty = (toolId, qty) => {
    const tool = getToolById(toolId);
    const max = tool ? tool.stok : qty;
    if (max <= 0) {
        cartDrafts[toolId] = 0;
        return;
    }
    cartDrafts[toolId] = Math.max(1, Math.min(qty, max));
};

const displayQty = (toolId) => {
    const item = findCartItem(toolId);
    return item ? item.qty : getDraftQty(toolId);
};

const setToolQty = (tool, value) => {
    if (!tool || tool.stok <= 0) {
        return;
    }

    const parsed = Number(value);
    const qty = Number.isFinite(parsed) ? Math.floor(parsed) : 1;
    const normalizedQty = Math.max(1, Math.min(qty, tool.stok));
    const item = findCartItem(tool.id);

    if (item) {
        item.qty = normalizedQty;
        return;
    }

    setDraftQty(tool.id, normalizedQty);
};

const toggleCartSelection = (tool, checked) => {
    if (isReadOnlyCatalog.value || !tool || tool.stok <= 0) {
        return;
    }

    if (!checked) {
        removeFromCart(tool.id);
        return;
    }

    if (!isInCart(tool.id)) {
        addToCart(tool);
    }
};

const increaseTool = (tool) => {
    if (tool.stok <= 0) {
        return;
    }
    const item = findCartItem(tool.id);
    if (item) {
        if (item.qty < tool.stok) {
            item.qty += 1;
        }
        return;
    }
    setDraftQty(tool.id, getDraftQty(tool.id) + 1);
};

const decreaseTool = (tool) => {
    if (tool.stok <= 0) {
        return;
    }
    const item = findCartItem(tool.id);
    if (item) {
        if (item.qty > 1) {
            item.qty -= 1;
        }
        return;
    }
    setDraftQty(tool.id, getDraftQty(tool.id) - 1);
};

const addToCart = (tool) => {
    if (isReadOnlyCatalog.value) {
        return;
    }
    if (isInCart(tool.id)) {
        return;
    }
    if (tool.stok <= 0) {
        return;
    }
    cacheTool(tool);
    const qty = Math.min(getDraftQty(tool.id), tool.stok);
    if (qty <= 0) {
        return;
    }
    cart.value.push({ id: tool.id, qty });
};

const toggleView = () => {
    viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
};

const removeFromCart = (toolId) => {
    cart.value = cart.value.filter((item) => item.id !== toolId);
};

const increaseCart = (toolId) => {
    const cartItem = findCartItem(toolId);
    const tool = getToolById(toolId);
    if (!cartItem || !tool) {
        return;
    }
    if (cartItem.qty < tool.stok) {
        cartItem.qty += 1;
    }
};

const decreaseCart = (toolId) => {
    const cartItem = findCartItem(toolId);
    if (!cartItem) {
        return;
    }
    if (cartItem.qty > 1) {
        cartItem.qty -= 1;
    }
};

const form = ref({
    tanggal_pinjam: '',
    tanggal_kembali: '',
    pekerjaan: '',
});

const openCheckout = () => {
    if (isReadOnlyCatalog.value) {
        checkoutError.value = 'Peminjaman hanya dapat dibuat dari area akun Anda.';
        return;
    }
    if (!cartItems.value.length) {
        return;
    }
    checkoutError.value = '';
    if (checkoutDateLimits.value.minBorrowDate && (!form.value.tanggal_pinjam || form.value.tanggal_pinjam < checkoutDateLimits.value.minBorrowDate)) {
        form.value.tanggal_pinjam = checkoutDateLimits.value.minBorrowDate;
    }
    if (checkoutDateLimits.value.maxReturnDate && (!form.value.tanggal_kembali || form.value.tanggal_kembali > checkoutDateLimits.value.maxReturnDate)) {
        form.value.tanggal_kembali = checkoutDateLimits.value.maxReturnDate;
    }
    checkoutOpen.value = true;
};

const resetCheckout = () => {
    form.value = {
        tanggal_pinjam: '',
        tanggal_kembali: '',
        pekerjaan: '',
    };
    cart.value = [];
    Object.keys(cartDrafts).forEach((key) => {
        delete cartDrafts[key];
    });
    checkoutOpen.value = false;
    drawerOpen.value = false;
    checkoutError.value = '';
};

const submitCheckout = async () => {
    if (isReadOnlyCatalog.value) {
        checkoutError.value = 'Peminjaman hanya dapat dibuat dari area akun Anda.';
        return;
    }
    if (!form.value.tanggal_pinjam || !form.value.tanggal_kembali || !form.value.pekerjaan) {
        checkoutError.value = 'Lengkapi tanggal pinjam, tanggal kembali, dan pekerjaan.';
        return;
    }
    if (!cartItems.value.length) {
        checkoutError.value = 'Keranjang masih kosong.';
        return;
    }
    if (checkoutDateLimits.value.minBorrowDate && form.value.tanggal_pinjam < checkoutDateLimits.value.minBorrowDate) {
        checkoutError.value = checkoutDateLimits.value.message;
        return;
    }
    if (checkoutDateLimits.value.maxReturnDate && form.value.tanggal_kembali > checkoutDateLimits.value.maxReturnDate) {
        checkoutError.value = checkoutDateLimits.value.message;
        return;
    }
    isSubmitting.value = true;
    checkoutError.value = '';
    try {
        const payload = {
            tanggal_pinjam: form.value.tanggal_pinjam,
            tanggal_kembali: form.value.tanggal_kembali,
            pekerjaan: form.value.pekerjaan,
            ...(roleKey.value === 'super_admin' && areaId.value ? { area_id: areaId.value } : {}),
            items: cartItems.value.map((item) => ({ id: item.id, qty: item.qty })),
        };
        await axios.post('/api/peminjaman', payload);
        resetCheckout();
        loadKatalog(buildFilterParams());
        showAlert('success', 'Peminjaman berhasil dibuat.');
    } catch (error) {
        const message =
            error?.response?.data?.message ||
            error?.response?.data?.errors?.items?.[0] ||
            'Gagal membuat peminjaman.';
        checkoutError.value = message;
        showAlert('error', message);
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
</script>
