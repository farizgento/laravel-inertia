<template>
    <section class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/60 space-y-6">
        <div
            v-if="alertMessage"
            class="rounded-xl border px-4 py-3 text-sm font-semibold"
            :class="alertType === 'success'
                ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                : 'border-rose-200 bg-rose-50 text-rose-700'"
        >
            {{ alertMessage }}
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">Katalog Alat - Area A</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih alat yang ingin dipinjam dan checkout</p>
            </div>
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

        <div class="grid gap-3 md:grid-cols-[1fr_auto]">
            <label class="relative">
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
            <label class="relative">
                <span class="sr-only">Kategori</span>
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 5h16l-6 7v6l-4 2v-8L4 5Z" />
                    </svg>
                </span>
                <select
                    v-model="filters.kategori"
                    class="w-full min-w-[210px] appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="Semua Kategori">Semua Kategori</option>
                    <option v-for="kategori in kategoriOptions" :key="kategori" :value="kategori">
                        {{ kategori }}
                    </option>
                </select>
                <span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </span>
            </label>
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

        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="tool in filteredTools"
                :key="tool.id"
                :class="[
                    'relative rounded-2xl border bg-white p-5 shadow-sm transition',
                    isInCart(tool.id) ? 'border-blue-300 bg-blue-50/60 shadow-blue-100' : 'border-slate-200',
                ]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                            {{ tool.kode }}
                        </p>
                        <h3 class="text-lg font-semibold text-slate-900">{{ tool.nama }}</h3>
                    </div>
                    <span
                        v-if="isInCart(tool.id)"
                        class="rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white"
                    >
                        Di Keranjang
                    </span>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-slate-600">
                    <span class="rounded-full bg-slate-100 px-2.5 py-1 font-semibold text-slate-700">
                        {{ tool.kategori }}
                    </span>
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

                <p v-if="tool.deskripsi" class="mt-3 text-sm text-slate-500">{{ tool.deskripsi }}</p>

                <div v-if="tool.lokasi" class="mt-4 flex items-center gap-2 text-xs text-slate-500">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
                        <circle cx="12" cy="11" r="2.5" />
                    </svg>
                    <span>{{ tool.lokasi }}</span>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <div class="flex items-center rounded-full bg-slate-100 px-2 py-1">
                        <button
                            class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:text-slate-300"
                            type="button"
                            :disabled="isOutOfStock(tool)"
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
                            :disabled="isOutOfStock(tool)"
                            @click="increaseTool(tool)"
                        >
                            +
                        </button>
                    </div>
                    <button
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-200"
                        type="button"
                        :disabled="isInCart(tool.id) || isOutOfStock(tool)"
                        @click="addToCart(tool)"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 6h15l-2 9H8L6 6Z" />
                            <path d="M6 6 5 3H2" />
                            <circle cx="9" cy="20" r="1.5" />
                            <circle cx="18" cy="20" r="1.5" />
                        </svg>
                        <span>
                            {{ isOutOfStock(tool) ? 'Stok Habis' : isInCart(tool.id) ? 'Sudah Ditambah' : 'Tambah' }}
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

    <teleport to="body">
        <div
            v-if="drawerOpen"
            class="fixed inset-0 z-40 bg-slate-900/50"
            @click.self="drawerOpen = false"
        >
            <aside class="absolute right-0 top-0 flex h-full w-full max-w-sm flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Keranjang Peminjaman</h3>
                        <p class="text-sm text-slate-500">
                            {{ uniqueItems }} jenis alat - {{ totalItems }} total item
                        </p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="drawerOpen = false"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="flex flex-1 flex-col overflow-hidden">
                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                        <p v-if="!cartItems.length" class="text-sm text-slate-500">
                            Keranjang masih kosong.
                        </p>
                        <div
                            v-for="item in cartItems"
                            :key="item.id"
                            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        {{ item.kode }}
                                    </p>
                                    <p class="text-sm font-semibold text-slate-900">{{ item.nama }}</p>
                                    <span
                                        class="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                                    >
                                        {{ item.kategori }}
                                    </span>
                                </div>
                                <button
                                    class="text-red-500 transition hover:text-red-600"
                                    type="button"
                                    @click="removeFromCart(item.id)"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4h8v2" />
                                        <path d="m6 6 1 14h10l1-14" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center rounded-full bg-slate-100 px-2 py-1">
                                    <button
                                        class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700"
                                        type="button"
                                        @click="decreaseCart(item.id)"
                                    >
                                        -
                                    </button>
                                    <span class="w-8 text-center text-sm font-semibold text-slate-700">
                                        {{ item.qty }}
                                    </span>
                                    <button
                                        class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700"
                                        type="button"
                                        @click="increaseCart(item.id)"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-xs text-slate-400">Stok: {{ item.stok }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 px-5 py-4">
                        <button
                            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                            type="button"
                            :disabled="!cartItems.length"
                            @click="openCheckout"
                        >
                            Checkout ({{ totalItems }} item)
                        </button>
                    </div>
                </div>
            </aside>
        </div>

        <div
            v-if="checkoutOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="checkoutOpen = false"
        >
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">Checkout Peminjaman</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Lengkapi informasi peminjaman untuk {{ uniqueItems }} jenis alat ({{ totalItems }}
                            item)
                        </p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="checkoutOpen = false"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-6 space-y-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                                <path d="m3.3 7 8.7 5 8.7-5" />
                                <path d="M12 12v9" />
                            </svg>
                            Daftar Alat
                        </div>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="item in cartItems"
                                :key="item.id"
                                class="flex items-center justify-between rounded-lg bg-white px-3 py-2 text-sm text-slate-700"
                            >
                                <span>{{ item.nama }}</span>
                                <span class="font-semibold text-slate-500">x{{ item.qty }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Tanggal Pinjam *</span>
                            <input
                                v-model="form.tanggal_pinjam"
                                type="date"
                                :min="todayValue"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Tanggal Kembali *</span>
                            <input
                                v-model="form.tanggal_kembali"
                                type="date"
                                :min="form.tanggal_pinjam || todayValue"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Keperluan *</span>
                        <textarea
                            v-model="form.keperluan"
                            rows="3"
                            placeholder="Jelaskan keperluan peminjaman..."
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Catatan (Opsional)</span>
                        <textarea
                            v-model="form.catatan"
                            rows="3"
                            placeholder="Catatan tambahan..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="checkoutOpen = false"
                    >
                        Batal
                    </button>
                    <div class="flex flex-1 flex-col items-end gap-2">
                        <p v-if="checkoutError" class="text-right text-xs font-semibold text-rose-500">
                            {{ checkoutError }}
                        </p>
                        <button
                            class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="button"
                            :disabled="isSubmitting"
                            @click="submitCheckout"
                        >
                            {{ isSubmitting ? 'Memproses...' : 'Buat Peminjaman' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';

const STORAGE_KEY_PREFIX = 'peminjaman_cart_v1';
const page = usePage();
const cachedUserId = ref(null);

const loadCachedUserId = () => {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        const cached = window.localStorage.getItem('auth_user');
        return cached ? JSON.parse(cached)?.id ?? null : null;
    } catch (error) {
        return null;
    }
};

const katalog = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const kategoriCache = ref([]);

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 8,
});

const userId = computed(() => page.props.auth?.user?.id ?? cachedUserId.value);
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

const todayValue = computed(() => new Date().toISOString().split('T')[0]);

const filters = reactive({
    search: '',
    kategori: 'Semua Kategori',
});

const kategoriOptions = computed(() => {
    if (kategoriCache.value.length) {
        return kategoriCache.value;
    }
    const categories = new Set();
    katalog.value.forEach((item) => {
        if (item.kategori) {
            categories.add(item.kategori);
        }
    });
    return Array.from(categories);
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
let alertTimeout = null;

const katalogMap = computed(() => new Map(katalog.value.map((item) => [item.id, item])));
const isOutOfStock = (tool) => (tool?.stok ?? 0) <= 0;

const normalizeCart = (items) => {
    const map = katalogMap.value;
    return items
        .filter((item) => map.has(item.id) && Number.isFinite(item.qty) && item.qty > 0)
        .map((item) => {
            const tool = map.get(item.id);
            const max = tool ? tool.stok : item.qty;
            return {
                id: item.id,
                qty: Math.min(item.qty, max),
            };
        })
        .filter((item) => item.qty > 0);
};

const normalizeTool = (item) => {
    const id = item?.id ?? '';
    const stok = Number.isFinite(item?.stok) ? item.stok : Number(item?.total_aset ?? 0);
    return {
        id,
        kode: item?.kode ?? `ALT-${String(id).padStart(3, '0')}`,
        nama: item?.nama ?? '-',
        kategori: item?.kategori ?? 'Umum',
        stok: Number.isFinite(stok) ? stok : 0,
        deskripsi: item?.deskripsi ?? '',
        lokasi: item?.lokasi ?? item?.area_name ?? '',
    };
};

let filterTimeout = null;

const buildFilterParams = () => {
    const params = {};
    const searchText = filters.search.trim();
    if (searchText) {
        params.search = searchText;
    }
    if (filters.kategori && filters.kategori !== 'Semua Kategori') {
        params.kategori = filters.kategori;
    }
    return params;
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
        if (!filters.search.trim() && filters.kategori === 'Semua Kategori') {
            const merged = new Set(kategoriCache.value);
            katalog.value.forEach((item) => {
                if (item.kategori) {
                    merged.add(item.kategori);
                }
            });
            kategoriCache.value = Array.from(merged);
        }
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
        loadCartFromStorage();
    }
    loadKatalog(buildFilterParams());
});

watch(
    [() => filters.search, () => filters.kategori],
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
    () => userId.value,
    (value, previous) => {
        if (value === previous) {
            return;
        }
        if (typeof window === 'undefined') {
            return;
        }
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
            const tool = katalogMap.value.get(item.id);
            if (!tool) {
                return null;
            }
            return { ...tool, qty: item.qty };
        })
        .filter((item) => item && item.qty > 0),
);

const totalItems = computed(() => cart.value.reduce((total, item) => total + item.qty, 0));
const uniqueItems = computed(() => cart.value.length);

const findCartItem = (toolId) => cart.value.find((item) => item.id === toolId);
const isInCart = (toolId) => !!findCartItem(toolId);

const getDraftQty = (toolId) => {
    const tool = katalogMap.value.get(toolId);
    if (!tool || tool.stok <= 0) {
        return 0;
    }
    return cartDrafts[toolId] ?? 1;
};
const setDraftQty = (toolId, qty) => {
    const tool = katalogMap.value.get(toolId);
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
    if (isInCart(tool.id)) {
        return;
    }
    if (tool.stok <= 0) {
        return;
    }
    const qty = Math.min(getDraftQty(tool.id), tool.stok);
    if (qty <= 0) {
        return;
    }
    cart.value.push({ id: tool.id, qty });
};

const removeFromCart = (toolId) => {
    cart.value = cart.value.filter((item) => item.id !== toolId);
};

const increaseCart = (toolId) => {
    const cartItem = findCartItem(toolId);
    const tool = katalogMap.value.get(toolId);
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
    keperluan: '',
    catatan: '',
});

const openCheckout = () => {
    if (!cartItems.value.length) {
        return;
    }
    checkoutError.value = '';
    checkoutOpen.value = true;
};

const resetCheckout = () => {
    form.value = {
        tanggal_pinjam: '',
        tanggal_kembali: '',
        keperluan: '',
        catatan: '',
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
    if (!form.value.tanggal_pinjam || !form.value.tanggal_kembali || !form.value.keperluan) {
        checkoutError.value = 'Lengkapi tanggal pinjam, tanggal kembali, dan keperluan.';
        return;
    }
    if (!cartItems.value.length) {
        checkoutError.value = 'Keranjang masih kosong.';
        return;
    }
    isSubmitting.value = true;
    checkoutError.value = '';
    try {
        const payload = {
            tanggal_pinjam: form.value.tanggal_pinjam,
            tanggal_kembali: form.value.tanggal_kembali,
            keperluan: form.value.keperluan,
            catatan: form.value.catatan || null,
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
    alertMessage.value = message;
    if (alertTimeout) {
        clearTimeout(alertTimeout);
    }
    alertTimeout = setTimeout(() => {
        alertMessage.value = '';
    }, 3000);
};
</script>
