<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Log Activity</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau perubahan data yang dilakukan pengguna di sistem.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="flex-1">
                <label class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Cari Aktivitas
                </label>
                <input
                    v-model="draftSearch"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="text"
                    placeholder="Cari user, data, deskripsi, atau route..."
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
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                    <option value="register">Register</option>
                </select>
            </div>

            <div class="flex gap-2">
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
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat log activity...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!logs.length" class="text-sm text-slate-500">Belum ada activity log.</p>

            <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-[1280px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">User</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3">Area</th>
                                <th class="px-4 py-3">Aksi</th>
                                <th class="px-4 py-3">Data</th>
                                <th class="px-4 py-3">Deskripsi</th>
                                <th class="px-4 py-3">Sebelum</th>
                                <th class="px-4 py-3">Sesudah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="log in logs" :key="log.id" class="align-top">
                                <td class="px-4 py-4 text-slate-600">{{ log.created_at }}</td>
                                <td class="px-4 py-4 font-semibold text-slate-900">{{ log.actor_name }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ log.actor_role_label }}</td>
                                <td class="px-4 py-4 text-slate-600">{{ log.area_name }}</td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="actionBadgeClass(log.action)"
                                    >
                                        {{ log.action_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ log.subject_type }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ log.subject_label }}</p>
                                </td>
                                <td class="px-4 py-4 text-slate-600">{{ log.description }}</td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div v-if="log.before_changes?.length" class="space-y-2">
                                        <p v-for="change in log.before_changes" :key="`${log.id}-before-${change.field}`">
                                            <span class="font-semibold text-slate-700">{{ change.label }}:</span>
                                            <a
                                                v-if="isUrl(formatValue(change.value))"
                                                :href="formatValue(change.value)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-blue-600 underline underline-offset-2 hover:text-blue-700"
                                            >
                                                Download File
                                            </a>
                                            <template v-else>{{ formatValue(change.value) }}</template>
                                        </p>
                                    </div>
                                    <p v-else>Tidak ada data.</p>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    <div v-if="log.after_changes?.length" class="space-y-2">
                                        <p v-for="change in log.after_changes" :key="`${log.id}-after-${change.field}`">
                                            <span class="font-semibold text-slate-700">{{ change.label }}:</span>
                                            <a
                                                v-if="isUrl(formatValue(change.value))"
                                                :href="formatValue(change.value)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-blue-600 underline underline-offset-2 hover:text-blue-700"
                                            >
                                                Download File
                                            </a>
                                            <template v-else>{{ formatValue(change.value) }}</template>
                                        </p>
                                    </div>
                                    <p v-else>Tidak ada data.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-slate-500">
                Total {{ pagination.total }} aktivitas
            </p>
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
                title: 'Log Activity',
                subtitle: 'Pantau aktivitas pengguna di sistem',
                activeMenu: 'activity-log',
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

const loadLogs = async (pageNumber = 1) => {
    isLoading.value = true;
    loadError.value = '';

    try {
        const params = {
            page: pageNumber,
            per_page: pagination.perPage,
            ...buildParams(),
        };

        const response = await axios.get('/api/activity-logs', { params });
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
        loadError.value = error.response?.data?.message ?? 'Gagal memuat activity log.';
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

const exportCsv = async () => {
    isExporting.value = true;

    try {
        const response = await axios.get('/api/activity-logs/export', {
            params: buildParams(),
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        const disposition = response.headers['content-disposition'] ?? '';
        const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
        link.href = url;
        link.download = match?.[1] ?? 'activity-logs.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        loadError.value = 'Gagal mengunduh file CSV.';
    } finally {
        isExporting.value = false;
    }
};

const actionBadgeClass = (action) => {
    switch (action) {
        case 'create':
            return 'bg-emerald-100 text-emerald-700';
        case 'update':
            return 'bg-blue-100 text-blue-700';
        case 'delete':
            return 'bg-rose-100 text-rose-700';
        case 'register':
            return 'bg-amber-100 text-amber-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
};

const formatValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'null';
    }

    return typeof value === 'object' ? JSON.stringify(value) : String(value);
};

const isUrl = (value) => /^https?:\/\//i.test(String(value ?? '').trim());

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
