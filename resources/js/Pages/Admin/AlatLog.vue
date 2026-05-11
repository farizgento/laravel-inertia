<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Log Alat</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau aktivitas penambahan, perubahan stok, update data, dan penghapusan alat.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Cari Log Alat
                </label>
                <input
                    v-model="draftSearch"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="text"
                    placeholder="Cari nama alat, pengubah, aksi, atau detail..."
                    @keyup.enter="applyFilters"
                />
            </div>

            <div class="w-full lg:w-52">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Aksi
                </label>
                <select
                    v-model="draftAction"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    <option value="Semua">Semua Aksi</option>
                    <option value="create">Penambahan</option>
                    <option value="update">Update</option>
                    <option value="delete">Penghapusan</option>
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    class="h-11 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="isExporting"
                    @click="exportCsv"
                >
                    {{ isExporting ? 'Mengunduh...' : 'Export CSV' }}
                </button>
                <button
                    class="h-11 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700"
                    type="button"
                    @click="applyFilters"
                >
                    Terapkan
                </button>
                <button
                    class="h-11 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    type="button"
                    @click="resetFilters"
                >
                    Reset
                </button>
            </div>
        </div>

        <div class="mt-5">
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat log alat...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!logs.length" class="text-sm text-slate-500">Belum ada log alat.</p>

            <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-[1320px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Pengubah</th>
                                <th class="px-4 py-3">Aksi</th>
                                <th class="px-4 py-3">Data Alat</th>
                                <th class="px-4 py-3">Area</th>
                                <th class="px-4 py-3">Total Aset</th>
                                <th class="px-4 py-3">Perubahan</th>
                                <th class="px-4 py-3">Detail</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="log in logs" :key="log.id" class="align-top">
                                <td class="px-4 py-4 text-slate-600">{{ log.created_at }}</td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ log.actor_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ log.actor_role_label }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="actionBadgeClass(log.action_label)"
                                    >
                                        {{ log.action_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ log.alat_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ log.alat_code }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ log.jenis_alat }} - {{ log.klasifikasi_alat }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ log.area_name }}</td>
                                <td class="px-4 py-4 text-slate-600">
                                    {{ log.total_aset_before }} -> {{ log.total_aset_after }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="stockDeltaClass(log.stock_delta)"
                                    >
                                        {{ log.stock_delta_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div v-if="log.changes?.length" class="space-y-2">
                                        <p v-for="change in log.changes" :key="`${log.id}-${change.field}`">
                                            <span class="font-semibold text-slate-700">{{ change.label }}:</span>
                                            {{ formatValue(change.before) }} -> {{ formatValue(change.after) }}
                                        </p>
                                    </div>
                                    <p v-else>-</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">Total {{ pagination.total }} log alat</p>
            <div class="flex items-center gap-2">
                <button
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="pagination.currentPage <= 1 || isLoading"
                    @click="goToPage(pagination.currentPage - 1)"
                >
                    Sebelumnya
                </button>
                <span class="text-xs font-semibold text-slate-500">
                    Halaman {{ pagination.currentPage }} / {{ pagination.lastPage }}
                </span>
                <button
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="pagination.currentPage >= pagination.lastPage || isLoading"
                    @click="goToPage(pagination.currentPage + 1)"
                >
                    Berikutnya
                </button>
            </div>
        </div>
    </section>
</template>

<script setup>
import axios from 'axios';
import { inject, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Log Alat',
                subtitle: 'Pantau aktivitas perubahan data alat',
                activeMenu: 'alat-log',
            },
            () => page
        ),
});

const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const setAreaSwitching = inject('setAreaSwitching', null);

const logs = ref([]);
const isLoading = ref(false);
const isExporting = ref(false);
const loadError = ref('');

const filters = reactive({
    search: '',
    action: 'Semua',
});

const draftSearch = ref('');
const draftAction = ref('Semua');

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
});

const buildParams = () => {
    const params = {};

    if (filters.search.trim()) {
        params.search = filters.search.trim();
    }
    if (filters.action !== 'Semua') {
        params.action = filters.action;
    }
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }

    return params;
};

const loadLogs = async (pageNumber = 1) => {
    isLoading.value = true;
    loadError.value = '';

    try {
        const response = await axios.get('/api/alat-logs', {
            params: {
                page: pageNumber,
                per_page: pagination.perPage,
                ...buildParams(),
            },
        });
        logs.value = Array.isArray(response.data?.data) ? response.data.data : [];
        pagination.currentPage = Number(response.data?.meta?.current_page ?? 1);
        pagination.lastPage = Number(response.data?.meta?.last_page ?? 1);
        pagination.perPage = Number(response.data?.meta?.per_page ?? 10);
        pagination.total = Number(response.data?.meta?.total ?? logs.value.length);
    } catch (error) {
        logs.value = [];
        pagination.currentPage = 1;
        pagination.lastPage = 1;
        pagination.total = 0;
        loadError.value = error.response?.data?.message ?? 'Gagal memuat log alat.';
    } finally {
        isLoading.value = false;
    }
};

const applyFilters = () => {
    filters.search = draftSearch.value;
    filters.action = draftAction.value;
    loadLogs(1);
};

const resetFilters = () => {
    draftSearch.value = '';
    draftAction.value = 'Semua';
    filters.search = '';
    filters.action = 'Semua';
    loadLogs(1);
};

const goToPage = (pageNumber) => {
    if (pageNumber < 1 || pageNumber > pagination.lastPage || isLoading.value) {
        return;
    }

    loadLogs(pageNumber);
};

const exportCsv = async () => {
    isExporting.value = true;

    try {
        const response = await axios.get('/api/alat-logs/export', {
            params: buildParams(),
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        const disposition = response.headers['content-disposition'] ?? '';
        const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
        link.href = url;
        link.download = match?.[1] ?? 'log-alat.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        loadError.value = 'Gagal mengunduh file CSV log alat.';
    } finally {
        isExporting.value = false;
    }
};

const actionBadgeClass = (label) => {
    if (label === 'Penambahan Alat Baru' || label === 'Penambahan Stok') {
        return 'bg-emerald-100 text-emerald-700';
    }
    if (label === 'Pengurangan Stok') {
        return 'bg-amber-100 text-amber-700';
    }
    if (label === 'Penghapusan Alat') {
        return 'bg-rose-100 text-rose-700';
    }
    return 'bg-blue-100 text-blue-700';
};

const stockDeltaClass = (delta) => {
    if (Number(delta) > 0) {
        return 'bg-emerald-100 text-emerald-700';
    }
    if (Number(delta) < 0) {
        return 'bg-amber-100 text-amber-700';
    }
    return 'bg-slate-100 text-slate-600';
};

const formatValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'null';
    }

    return typeof value === 'object' ? JSON.stringify(value) : String(value);
};

onMounted(async () => {
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(true);
    }
    await loadLogs();
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(false);
    }
});

watch(
    () => activeAreaId.value,
    async (next, prev) => {
        if (!isAreaSwitcherRole.value || !next || next === prev) {
            return;
        }

        setAreaSwitching?.(true);
        try {
            await loadLogs(1);
        } finally {
            setAreaSwitching?.(false);
        }
    }
);
</script>
