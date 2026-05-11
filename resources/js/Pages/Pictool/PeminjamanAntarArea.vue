<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Peminjaman Antar Area</h1>
        <p class="mt-1 text-sm text-slate-500">Ajukan peminjaman alat dari area lain untuk area Anda.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="grid gap-4 lg:grid-cols-4">
            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>Area Sumber</span>
                <select
                    v-model="sourceAreaId"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="">Pilih area sumber</option>
                    <option
                        v-for="area in sourceAreas"
                        :key="area.id"
                        :value="area.id"
                    >
                        {{ area.name }}
                    </option>
                </select>
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>Tanggal Pinjam</span>
                <input
                    v-model="form.tanggal_pinjam"
                    type="date"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>Tanggal Kembali</span>
                <input
                    v-model="form.tanggal_kembali"
                    type="date"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>

            <label class="space-y-2 text-sm font-medium text-slate-700">
                <span>Pekerjaan</span>
                <input
                    v-model="form.pekerjaan"
                    type="text"
                    placeholder="Nama pekerjaan"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1fr_240px]">
            <label class="relative block">
                <span class="sr-only">Cari alat</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama atau jenis alat..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
            <label class="block">
                <span class="sr-only">Filter klasifikasi</span>
                <select
                    v-model="classificationFilter"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="">Semua klasifikasi</option>
                    <option
                        v-for="classification in classificationOptions"
                        :key="classification"
                        :value="classification"
                    >
                        {{ classification }}
                    </option>
                </select>
            </label>
        </div>

        <div class="mt-6">
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat alat area sumber...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!sourceAreaId" class="text-sm text-slate-500">Pilih area sumber terlebih dahulu.</p>
            <p v-else-if="!tools.length" class="text-sm text-slate-500">Tidak ada alat tersedia di area sumber.</p>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="tool in tools"
                    :key="tool.id"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold text-slate-400">{{ tool.kode }}</p>
                            <h3 class="mt-1 text-sm font-semibold capitalize text-slate-900">{{ tool.nama }}</h3>
                            <p class="mt-1 text-xs text-slate-500">{{ tool.jenis_alat }}</p>
                            <p class="mt-2 inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                {{ tool.klasifikasi_alat }}
                            </p>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            Stok {{ tool.stok_tersedia }}
                        </span>
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <div class="flex items-center rounded-full bg-slate-100 px-2 py-1">
                            <button
                                class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700 disabled:text-slate-300"
                                type="button"
                                :disabled="qtyFor(tool.id) <= 0"
                                @click="setQty(tool, qtyFor(tool.id) - 1)"
                            >
                                -
                            </button>
                            <span class="w-8 text-center text-sm font-semibold text-slate-700">
                                {{ qtyFor(tool.id) }}
                            </span>
                            <button
                                class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700 disabled:text-slate-300"
                                type="button"
                                :disabled="qtyFor(tool.id) >= tool.stok_tersedia"
                                @click="setQty(tool, qtyFor(tool.id) + 1)"
                            >
                                +
                            </button>
                        </div>
                        <button
                            class="rounded-xl px-4 py-2 text-xs font-semibold transition"
                            :class="qtyFor(tool.id) > 0 ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            type="button"
                            :disabled="tool.stok_tersedia < 1"
                            @click="toggleTool(tool)"
                        >
                            {{ qtyFor(tool.id) > 0 ? 'Dipilih' : 'Pilih' }}
                        </button>
                    </div>
                </article>
            </div>

            <div
                v-if="pagination.lastPage > 1"
                class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
            >
                <span>
                    Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} | Total {{ pagination.total }} alat
                </span>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:text-slate-300"
                        type="button"
                        :disabled="pagination.currentPage === 1 || isLoading"
                        @click="goToPage(pagination.currentPage - 1)"
                    >
                        Sebelumnya
                    </button>
                    <button
                        v-for="pageNumber in pageNumbers"
                        :key="pageNumber"
                        class="h-9 min-w-[36px] rounded-lg border px-3 text-sm font-semibold transition"
                        :class="pageNumber === pagination.currentPage
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                        type="button"
                        :disabled="isLoading"
                        @click="goToPage(pageNumber)"
                    >
                        {{ pageNumber }}
                    </button>
                    <button
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:text-slate-300"
                        type="button"
                        :disabled="pagination.currentPage === pagination.lastPage || isLoading"
                        @click="goToPage(pagination.currentPage + 1)"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5">
            <p class="text-sm font-semibold text-slate-600">
                {{ selectedItems.length }} alat dipilih
            </p>
            <button
                class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                type="button"
                :disabled="isSubmitting"
                @click="submit"
            >
                {{ isSubmitting ? 'Mengajukan...' : 'Ajukan Peminjaman' }}
            </button>
        </div>
    </section>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Peminjaman Antar Area',
                subtitle: 'Ajukan peminjaman alat dari area lain',
                activeMenu: 'peminjaman-antar-area',
            },
            () => page
        ),
});

const page = usePage();
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const cachedUser = ref(null);
const areas = ref([]);
const tools = ref([]);
const selectedQty = reactive({});
const sourceAreaId = ref('');
const search = ref('');
const classificationFilter = ref('');
const isLoading = ref(false);
const isSubmitting = ref(false);
const loadError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;
let searchTimeout = null;

const classificationOptions = ['General Tools', 'Lifting Tools', 'Measurement Tools'];
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 9,
});

const form = reactive({
    tanggal_pinjam: '',
    tanggal_kembali: '',
    pekerjaan: '',
});

const loadCachedUser = () => {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        const cached = window.localStorage.getItem('auth_user');
        return cached ? JSON.parse(cached) : null;
    } catch (error) {
        return null;
    }
};

const authUser = computed(() => page.props.auth?.user ?? cachedUser.value);
const userAreaId = computed(() => authUser.value?.area_id ?? authUser.value?.area?.id ?? '');
const requesterAreaId = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaId.value ?? ''
        : userAreaId.value
);
const sourceAreas = computed(() => areas.value.filter((area) => Number(area.id) !== Number(requesterAreaId.value)));
const selectedItems = computed(() =>
    Object.entries(selectedQty)
        .filter(([, qty]) => Number(qty) > 0)
        .map(([id, qty]) => ({ id: Number(id), qty: Number(qty) }))
);
const pageNumbers = computed(() => {
    const total = pagination.lastPage;
    const current = pagination.currentPage;
    const delta = 2;
    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);
    const pages = [];

    for (let pageNumber = start; pageNumber <= end; pageNumber += 1) {
        pages.push(pageNumber);
    }

    return pages;
});

const qtyFor = (toolId) => Number(selectedQty[toolId] ?? 0);

const setQty = (tool, qty) => {
    const next = Math.max(0, Math.min(Number(qty) || 0, Number(tool.stok_tersedia ?? 0)));
    if (next === 0) {
        delete selectedQty[tool.id];
        return;
    }
    selectedQty[tool.id] = next;
};

const toggleTool = (tool) => {
    if (qtyFor(tool.id) > 0) {
        delete selectedQty[tool.id];
        return;
    }
    setQty(tool, 1);
};

const resetSelection = () => {
    Object.keys(selectedQty).forEach((key) => {
        delete selectedQty[key];
    });
};

const normalizeTool = (item) => ({
    id: item?.id ?? '',
    kode: item?.kode ?? '-',
    nama: item?.nama ?? '-',
    jenis_alat: item?.jenis_alat ?? '-',
    klasifikasi_alat: item?.klasifikasi_alat ?? '-',
    stok_tersedia: Number(item?.stok_tersedia ?? item?.stok ?? 0),
});

const loadAreas = async () => {
    try {
        const response = await axios.get('/api/areas');
        areas.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        areas.value = [];
    }
};

const loadTools = async () => {
    if (!sourceAreaId.value) {
        tools.value = [];
        pagination.currentPage = 1;
        pagination.lastPage = 1;
        pagination.total = 0;
        return;
    }

    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/alats', {
            params: {
                area_id: sourceAreaId.value,
                search: search.value.trim() || undefined,
                klasifikasi_alat: classificationFilter.value || undefined,
                inter_area_source: 1,
                page: pagination.currentPage,
                per_page: pagination.perPage,
            },
        });
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        tools.value = data.map((item) => normalizeTool(item));
        if (payload?.meta) {
            pagination.currentPage = Number(payload.meta.current_page ?? pagination.currentPage) || 1;
            pagination.lastPage = Number(payload.meta.last_page ?? 1) || 1;
            pagination.perPage = Number(payload.meta.per_page ?? pagination.perPage) || pagination.perPage;
            pagination.total = Number(payload.meta.total ?? tools.value.length) || 0;
        } else {
            pagination.currentPage = 1;
            pagination.lastPage = 1;
            pagination.total = tools.value.length;
        }
    } catch (error) {
        tools.value = [];
        pagination.currentPage = 1;
        pagination.lastPage = 1;
        pagination.total = 0;
        loadError.value = 'Gagal memuat alat area sumber.';
    } finally {
        isLoading.value = false;
    }
};

const goToPage = (pageNumber) => {
    const next = Math.min(Math.max(1, pageNumber), pagination.lastPage || 1);
    if (next === pagination.currentPage || isLoading.value) {
        return;
    }

    pagination.currentPage = next;
    loadTools();
};

const submit = async () => {
    if (!sourceAreaId.value || !form.tanggal_pinjam || !form.tanggal_kembali || !form.pekerjaan || !selectedItems.value.length) {
        showAlert('error', 'Lengkapi area sumber, tanggal, pekerjaan, dan alat yang dipinjam.');
        return;
    }

    isSubmitting.value = true;
    try {
        await axios.post('/api/peminjaman-antar-area', {
            source_area_id: sourceAreaId.value,
            requester_area_id: requesterAreaId.value,
            tanggal_pinjam: form.tanggal_pinjam,
            tanggal_kembali: form.tanggal_kembali,
            pekerjaan: form.pekerjaan,
            items: selectedItems.value,
        });
        resetSelection();
        form.pekerjaan = '';
        await loadTools();
        showAlert('success', 'Peminjaman antar area berhasil diajukan.');
    } catch (error) {
        const message =
            error.response?.data?.message ||
            error.response?.data?.errors?.items?.[0] ||
            error.response?.data?.errors?.source_area_id?.[0] ||
            'Gagal mengajukan peminjaman antar area.';
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

watch(sourceAreaId, () => {
    resetSelection();
    pagination.currentPage = 1;
    loadTools();
});

watch(requesterAreaId, () => {
    if (Number(sourceAreaId.value) === Number(requesterAreaId.value)) {
        sourceAreaId.value = '';
    }
    resetSelection();
});

watch(search, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        loadTools();
    }, 300);
});

watch(classificationFilter, () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        loadTools();
    }, 300);
});

onMounted(async () => {
    cachedUser.value = loadCachedUser();
    await loadAreas();
});
</script>
