<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Template Peminjaman</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola daftar alat siap pakai untuk peminjaman intra area dan antar area.</p>
            </div>
            <button
                class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                type="button"
                @click="startCreate"
            >
                Template Baru
            </button>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
            <div class="grid gap-3 md:grid-cols-3">
                    <label class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Kategori</span>
                        <select
                            v-model="filters.kategori"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Semua kategori</option>
                            <option value="Intra Area">Intra Area</option>
                            <option value="Antar Area">Antar Area</option>
                        </select>
                    </label>
                    <label v-if="isSuperAdmin" class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Area</span>
                        <select
                            v-model="filters.area_id"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Semua area</option>
                            <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                                {{ area.name }}
                            </option>
                        </select>
                    </label>
                    <label class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Cari</span>
                        <input
                            v-model="filters.search"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Nama template"
                            type="text"
                        />
                    </label>
            </div>

            <div class="mt-5 overflow-hidden rounded-xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase text-slate-500">
                                    <th class="min-w-[220px] px-4 py-3">Template</th>
                                    <th class="min-w-[150px] px-4 py-3">Kategori</th>
                                    <th class="min-w-[180px] px-4 py-3">Area</th>
                                    <th class="w-28 px-4 py-3 text-right">Item</th>
                                    <th class="w-36 px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-if="isLoading">
                                    <td class="px-4 py-8 text-center text-slate-500" colspan="5">Memuat template...</td>
                                </tr>
                                <tr v-else-if="!filteredTemplates.length">
                                    <td class="px-4 py-8 text-center text-slate-500" colspan="5">Belum ada template.</td>
                                </tr>
                                <template v-else>
                                    <tr v-for="template in filteredTemplates" :key="template.id" class="hover:bg-slate-50">
                                        <td class="px-4 py-3 align-top">
                                            <p class="font-semibold text-slate-900">{{ template.nama }}</p>
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <span class="inline-flex rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                                {{ template.kategori }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 align-top text-slate-600">
                                            <p>{{ template.area_name }}</p>
                                            <p v-if="template.source_area_name" class="mt-1 text-xs text-slate-400">
                                                Sumber: {{ template.source_area_name }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-3 text-right align-top font-semibold text-slate-700">
                                            {{ template.items_count }}
                                        </td>
                                        <td class="px-4 py-3 align-top">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-300 hover:text-blue-700"
                                                    type="button"
                                                    @click="editTemplate(template)"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50"
                                                    type="button"
                                                    @click="deleteTemplate(template)"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                    </table>
                </div>
            </div>
        </section>

        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 py-6"
            @click.self="closeModal"
        >
            <form
                class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20"
                @submit.prevent="submit"
            >
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-slate-900">{{ editingId ? 'Edit Template' : 'Template Baru' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ selectedItems.length }} alat dipilih</p>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50"
                        type="button"
                        @click="closeModal"
                    >
                        <span class="sr-only">Tutup</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <label class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Nama Template</span>
                        <input
                            v-model="form.nama"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Contoh: SI Unit 1 PLTU Suralaya"
                            type="text"
                        />
                    </label>

                    <label class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Jenis Peminjaman</span>
                        <select
                            v-model="form.kategori"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="Intra Area">Intra Area</option>
                            <option value="Antar Area">Antar Area</option>
                        </select>
                    </label>

                    <label v-if="isSuperAdmin" class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Area Peminjam</span>
                        <select
                            v-model="form.area_id"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Pilih area</option>
                            <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                                {{ area.name }}
                            </option>
                        </select>
                    </label>

                    <label v-if="form.kategori === 'Antar Area'" class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Area Sumber</span>
                        <select
                            v-model="form.source_area_id"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Pilih area sumber</option>
                            <option v-for="area in sourceAreas" :key="area.id" :value="String(area.id)">
                                {{ area.name }}
                            </option>
                        </select>
                    </label>

                    <div class="rounded-xl border border-slate-200">
                        <div class="border-b border-slate-200 p-3">
                            <label class="relative block">
                                <span class="sr-only">Cari alat</span>
                                <input
                                    v-model="toolSearch"
                                    class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    placeholder="Cari alat area template..."
                                    type="text"
                                />
                            </label>
                        </div>
                        <div class="max-h-[360px] divide-y divide-slate-100 overflow-y-auto">
                            <p v-if="toolLoading" class="px-3 py-6 text-center text-sm text-slate-500">Memuat alat...</p>
                            <p v-else-if="!toolAreaId" class="px-3 py-6 text-center text-sm text-slate-500">Pilih area terlebih dahulu.</p>
                            <p v-else-if="!tools.length" class="px-3 py-6 text-center text-sm text-slate-500">Alat tidak ditemukan.</p>
                            <template v-else>
                                <div
                                    v-for="tool in tools"
                                    :key="tool.id"
                                    class="flex items-center gap-3 px-3 py-3"
                                >
                                    <input
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        type="checkbox"
                                        :checked="qtyFor(tool.id) > 0"
                                        @change="toggleTool(tool)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ tool.nama }}</p>
                                        <p class="text-xs text-slate-500">
                                            {{ tool.kode }} | Total aset {{ tool.total_aset }} | Stok {{ tool.stok_tersedia }}
                                        </p>
                                    </div>
                                    <input
                                        class="h-9 w-20 rounded-lg border border-slate-200 px-2 text-center text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        :max="tool.total_aset"
                                        type="number"
                                        :value="qtyFor(tool.id) || 0"
                                        @input="setQty(tool, $event.target.value)"
                                    />
                                </div>
                            </template>
                        </div>
                    </div>

                    <p v-if="formError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-600">
                        {{ formError }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        type="button"
                        @click="closeModal"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="submit"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : 'Simpan Template' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';
import { loadAreas as loadSharedAreas } from '../../lib/areas';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Template Peminjaman',
                subtitle: 'Daftar alat siap pakai untuk peminjaman',
                activeMenu: 'template-peminjaman',
            },
            () => page
        ),
});

const page = usePage();
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const areas = ref([]);
const templates = ref([]);
const tools = ref([]);
const selectedQty = reactive({});
const isLoading = ref(false);
const toolLoading = ref(false);
const isSubmitting = ref(false);
const isHydratingForm = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);
const formError = ref('');
const toolSearch = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;
let toolSearchTimeout = null;

const filters = reactive({
    kategori: '',
    area_id: '',
    search: '',
});

const form = reactive({
    nama: '',
    kategori: 'Intra Area',
    area_id: '',
    source_area_id: '',
});

const authUser = computed(() => page.props.auth?.user ?? null);
const roleKey = computed(() => (authUser.value?.role?.key ?? '').toLowerCase());
const isSuperAdmin = computed(() => roleKey.value === 'super_admin');
const userAreaId = computed(() => authUser.value?.area_id ?? authUser.value?.area?.id ?? '');
const activeManagedAreaId = computed(() =>
    isSuperAdmin.value
        ? form.area_id
        : isAreaSwitcherRole.value
          ? activeAreaId.value ?? userAreaId.value
          : userAreaId.value
);
const toolAreaId = computed(() => (form.kategori === 'Antar Area' ? form.source_area_id : activeManagedAreaId.value));
const sourceAreas = computed(() => areas.value.filter((area) => Number(area.id) !== Number(activeManagedAreaId.value)));
const selectedItems = computed(() =>
    Object.entries(selectedQty)
        .filter(([, qty]) => Number(qty) > 0)
        .map(([id, qty]) => ({ id: Number(id), qty: Number(qty) }))
);
const filteredTemplates = computed(() => {
    const keyword = filters.search.trim().toLowerCase();
    return templates.value.filter((template) => {
        if (keyword && !String(template.nama ?? '').toLowerCase().includes(keyword)) {
            return false;
        }
        return true;
    });
});

const resetSelection = () => {
    Object.keys(selectedQty).forEach((key) => {
        delete selectedQty[key];
    });
};

const resetForm = () => {
    editingId.value = null;
    form.nama = '';
    form.kategori = 'Intra Area';
    form.area_id = isSuperAdmin.value ? (filters.area_id || '') : String(userAreaId.value || '');
    form.source_area_id = '';
    formError.value = '';
    toolSearch.value = '';
    resetSelection();
};

const normalizeTool = (item) => ({
    id: Number(item?.id ?? 0),
    kode: item?.kode ?? '-',
    nama: item?.nama ?? '-',
    total_aset: Number(item?.total_aset ?? 0),
    stok_tersedia: Number(item?.stok_tersedia ?? item?.stok ?? item?.total_aset ?? 0),
});

const loadAreas = async () => {
    try {
        areas.value = await loadSharedAreas();
    } catch (error) {
        areas.value = [];
    }
};

const loadTemplates = async () => {
    isLoading.value = true;
    try {
        const params = {};
        if (filters.kategori) {
            params.kategori = filters.kategori;
        }
        if (isSuperAdmin.value && filters.area_id) {
            params.area_id = filters.area_id;
        }
        const response = await axios.get('/api/peminjaman-templates', { params });
        templates.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        templates.value = [];
        showAlert('error', 'Gagal memuat template peminjaman.');
    } finally {
        isLoading.value = false;
    }
};

const loadTools = async () => {
    if (!toolAreaId.value) {
        tools.value = [];
        return;
    }

    toolLoading.value = true;
    try {
        const response = await axios.get('/api/alats', {
            params: {
                area_id: toolAreaId.value,
                search: toolSearch.value.trim() || undefined,
                inter_area_source: form.kategori === 'Antar Area' ? 1 : undefined,
                per_page: 100,
            },
        });
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        tools.value = data.map((item) => normalizeTool(item));
    } catch (error) {
        tools.value = [];
        showAlert('error', 'Gagal memuat alat template.');
    } finally {
        toolLoading.value = false;
    }
};

const qtyFor = (toolId) => Number(selectedQty[toolId] ?? 0);

const setQty = (tool, value) => {
    const max = Math.max(1, Number(tool?.total_aset ?? tool?.stok_tersedia ?? 1));
    const qty = Math.max(1, Math.min(Math.floor(Number(value) || 1), max));
    selectedQty[tool.id] = qty;
};

const toggleTool = (tool) => {
    if (qtyFor(tool.id) > 0) {
        delete selectedQty[tool.id];
        return;
    }
    setQty(tool, 1);
};

const startCreate = () => {
    resetForm();
    modalOpen.value = true;
    loadTools();
};

const editTemplate = (template) => {
    isHydratingForm.value = true;
    editingId.value = template.id;
    form.nama = template.nama ?? '';
    form.kategori = template.kategori ?? 'Intra Area';
    form.area_id = template.area_id ? String(template.area_id) : '';
    form.source_area_id = template.source_area_id ? String(template.source_area_id) : '';
    resetSelection();
    (template.items ?? []).forEach((item) => {
        selectedQty[item.alat_id ?? item.id] = Number(item.qty ?? 1);
    });
    formError.value = '';
    modalOpen.value = true;
    loadTools().finally(() => {
        isHydratingForm.value = false;
    });
};

const closeModal = () => {
    if (isSubmitting.value) {
        return;
    }
    modalOpen.value = false;
    resetForm();
};

const submit = async () => {
    if (!form.nama || !form.kategori || !selectedItems.value.length) {
        formError.value = 'Lengkapi nama template, jenis peminjaman, dan minimal satu alat.';
        return;
    }
    if (isSuperAdmin.value && !form.area_id) {
        formError.value = 'Area peminjam wajib dipilih.';
        return;
    }
    if (form.kategori === 'Antar Area' && !form.source_area_id) {
        formError.value = 'Area sumber wajib dipilih.';
        return;
    }

    isSubmitting.value = true;
    formError.value = '';
    try {
        const payload = {
            nama: form.nama,
            kategori: form.kategori,
            area_id: activeManagedAreaId.value,
            source_area_id: form.kategori === 'Antar Area' ? form.source_area_id : null,
            items: selectedItems.value,
        };
        if (editingId.value) {
            await axios.put(`/api/peminjaman-templates/${editingId.value}`, payload);
        } else {
            await axios.post('/api/peminjaman-templates', payload);
        }
        showAlert('success', 'Template peminjaman berhasil disimpan.');
        modalOpen.value = false;
        resetForm();
        await loadTemplates();
        await loadTools();
    } catch (error) {
        const errors = error.response?.data?.errors ?? {};
        formError.value =
            error.response?.data?.message ||
            errors.items?.[0] ||
            errors.area_id?.[0] ||
            errors.source_area_id?.[0] ||
            'Gagal menyimpan template.';
    } finally {
        isSubmitting.value = false;
    }
};

const deleteTemplate = async (template) => {
    if (!window.confirm(`Hapus template "${template.nama}"?`)) {
        return;
    }

    try {
        await axios.delete(`/api/peminjaman-templates/${template.id}`);
        showAlert('success', 'Template peminjaman berhasil dihapus.');
        if (editingId.value === template.id) {
            resetForm();
        }
        await loadTemplates();
    } catch (error) {
        showAlert('error', error.response?.data?.message || 'Gagal menghapus template.');
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

watch(
    () => [filters.kategori, filters.area_id],
    () => loadTemplates(),
);

watch(
    () => [form.kategori, form.area_id, form.source_area_id, activeAreaId.value],
    () => {
        if (isHydratingForm.value) {
            return;
        }
        resetSelection();
        loadTools();
    },
);

watch(toolSearch, () => {
    if (toolSearchTimeout) {
        clearTimeout(toolSearchTimeout);
    }
    toolSearchTimeout = setTimeout(() => {
        loadTools();
    }, 300);
});

onMounted(async () => {
    await loadAreas();
    resetForm();
    await Promise.all([loadTemplates(), loadTools()]);
});
</script>
