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
        <h1 class="text-2xl font-semibold text-slate-900">Pengelolaan Alat</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola master alat dan proses pengiriman</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Alat</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola inventaris alat di semua area</p>
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                type="button"
                @click="openCreate"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Alat
            </button>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1fr_auto]">
            <label class="relative">
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
                    placeholder="Cari nama atau kode alat..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
            <label class="relative">
                <span class="sr-only">Kategori</span>
                <select
                    v-model="kategoriFilter"
                    class="h-11 w-full min-w-[190px] appearance-none rounded-xl border border-slate-200 bg-white px-4 pr-10 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-[860px] w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold text-slate-500">
                            <th class="px-4 py-3">Kode</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Kategori</th>
                            <th class="px-4 py-3">Stok</th>
                            <th class="px-4 py-3">Kondisi</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-if="isLoading">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                Memuat data...
                            </td>
                        </tr>
                        <tr v-else-if="loadError">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-rose-500">
                                {{ loadError }}
                            </td>
                        </tr>
                        <tr v-else v-for="tool in tools" :key="tool.id">
                            <td class="px-4 py-4 text-xs font-semibold text-slate-900">
                                {{ tool.kode }}
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-900">
                                {{ tool.nama }}
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ tool.kategori }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ tool.total_aset }}</td>
                            <td class="px-4 py-4">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-semibold"
                                    :class="badgeClass(tool.kondisi)"
                                >
                                    {{ formatKondisi(tool.kondisi) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-blue-200 hover:text-blue-600"
                                        type="button"
                                        @click="openEdit(tool)"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                        </svg>
                                    </button>
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-500 transition hover:bg-rose-50"
                                        type="button"
                                        @click="removeTool(tool)"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 6h18" />
                                            <path d="M8 6V4h8v2" />
                                            <path d="m6 6 1 14h10l1-14" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!isLoading && !loadError && !tools.length">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                Tidak ada data alat.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <teleport to="body">
        <div
            v-if="formOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="closeForm"
        >
            <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ isEdit ? 'Edit Alat' : 'Tambah Alat' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Lengkapi detail alat yang akan disimpan.
                        </p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeForm"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>Nama Alat *</span>
                        <input
                            v-model="form.nama"
                            type="text"
                            placeholder="Nama alat"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Kategori *</span>
                        <input
                            v-model="form.kategori"
                            type="text"
                            placeholder="Kategori"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Area *</span>
                        <select
                            v-model="form.area_id"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Pilih area</option>
                            <option v-for="area in areas" :key="area.id" :value="area.id">
                                {{ area.name }}
                            </option>
                        </select>
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Stok *</span>
                        <input
                            v-model.number="form.total_aset"
                            type="number"
                            min="0"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Kondisi *</span>
                        <select
                            v-model="form.kondisi"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="baik">Baik</option>
                            <option value="rusak">Rusak</option>
                            <option value="tidak aktif">Tidak aktif</option>
                        </select>
                    </label>
                </div>

                <p v-if="formError" class="mt-3 text-sm font-semibold text-rose-500">
                    {{ formError }}
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeForm"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="isSubmitting"
                        @click="submitForm"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Pengelolaan Alat',
                subtitle: 'Kelola master alat dan proses pengiriman',
                activeMenu: 'dashboard',
            },
            () => page
        ),
});

const tools = ref([]);
const areas = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const isSubmitting = ref(false);
const formOpen = ref(false);
const formError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
let alertTimeout = null;

const search = ref('');
const kategoriFilter = ref('Semua Kategori');
const kategoriCache = ref([]);

const form = reactive({
    id: null,
    nama: '',
    kategori: '',
    total_aset: 0,
    area_id: '',
    kondisi: 'baik',
});

const isEdit = computed(() => form.id !== null);

const badgeClass = (kondisi) => {
    if (kondisi === 'baik') {
        return 'bg-emerald-50 text-emerald-600';
    }
    if (kondisi === 'rusak') {
        return 'bg-rose-50 text-rose-600';
    }
    return 'bg-slate-100 text-slate-600';
};

const formatKondisi = (kondisi) => {
    if (kondisi === 'baik') {
        return 'Baik';
    }
    if (kondisi === 'rusak') {
        return 'Rusak';
    }
    return 'Tidak aktif';
};

const kategoriOptions = computed(() => {
    if (kategoriCache.value.length) {
        return kategoriCache.value;
    }
    const categories = new Set();
    tools.value.forEach((tool) => {
        if (tool.kategori) {
            categories.add(tool.kategori);
        }
    });
    return Array.from(categories);
});

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

const resetForm = () => {
    form.id = null;
    form.nama = '';
    form.kategori = '';
    form.total_aset = 0;
    form.area_id = '';
    form.kondisi = 'baik';
    formError.value = '';
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const openEdit = (tool) => {
    form.id = tool.id;
    form.nama = tool.nama ?? '';
    form.kategori = tool.kategori ?? '';
    form.total_aset = Number(tool.total_aset ?? tool.stok ?? 0);
    form.area_id = tool.area_id ?? '';
    form.kondisi = tool.kondisi ?? 'baik';
    formError.value = '';
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const loadAreas = async () => {
    try {
        const response = await axios.get('/api/areas');
        areas.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        areas.value = [];
    }
};

const buildParams = () => {
    const params = {};
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (kategoriFilter.value !== 'Semua Kategori') {
        params.kategori = kategoriFilter.value;
    }
    return params;
};

const loadTools = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/alats', { params: buildParams() });
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        tools.value = data.map((item) => ({
            id: item.id,
            kode: item.kode ?? '-',
            nama: item.nama ?? '-',
            kategori: item.kategori ?? '-',
            total_aset: Number(item.total_aset ?? item.stok ?? 0),
            kondisi: item.kondisi ?? 'baik',
            area_name: item.area_name ?? item.lokasi ?? '-',
            area_id: item.area_id ?? '',
        }));
        if (!search.value.trim() && kategoriFilter.value === 'Semua Kategori') {
            const merged = new Set(kategoriCache.value);
            tools.value.forEach((tool) => {
                if (tool.kategori) {
                    merged.add(tool.kategori);
                }
            });
            kategoriCache.value = Array.from(merged);
        }
    } catch (error) {
        tools.value = [];
        loadError.value = 'Gagal memuat data alat.';
    } finally {
        isLoading.value = false;
    }
};

const submitForm = async () => {
    formError.value = '';
    if (!form.nama || !form.kategori || !form.area_id || form.total_aset < 0 || !form.kondisi) {
        formError.value = 'Lengkapi semua field wajib.';
        return;
    }
    isSubmitting.value = true;
    try {
        const payload = {
            nama: form.nama,
            kategori: form.kategori,
            area_id: form.area_id,
            total_aset: form.total_aset,
            kondisi: form.kondisi,
        };
        if (isEdit.value) {
            await axios.put(`/api/alats/${form.id}`, payload);
        } else {
            await axios.post('/api/alats', payload);
        }
        await loadTools();
        closeForm();
        showAlert('success', `Alat berhasil ${isEdit.value ? 'diperbarui' : 'ditambahkan'}.`);
    } catch (error) {
        formError.value = error.response?.data?.message ?? 'Gagal menyimpan data.';
        showAlert('error', formError.value);
    } finally {
        isSubmitting.value = false;
    }
};

const removeTool = async (tool) => {
    if (!confirm(`Hapus alat "${tool.nama}"?`)) {
        return;
    }
    try {
        await axios.delete(`/api/alats/${tool.id}`);
        await loadTools();
        showAlert('success', 'Alat berhasil dihapus.');
    } catch (error) {
        showAlert('error', error.response?.data?.message ?? 'Gagal menghapus data.');
    }
};

let filterTimeout = null;
watch(
    [search, kategoriFilter],
    () => {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }
        filterTimeout = setTimeout(() => {
            loadTools();
        }, 300);
    },
);

onMounted(() => {
    loadAreas();
    loadTools();
});
</script>
