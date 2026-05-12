<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Kelola Pengguna</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola akun operasional dengan filter role, pencarian, dan form modal.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Pengguna</h2>
                <p class="mt-1 text-sm text-slate-500">
                    {{ isSuperAdmin
                        ? 'Super Admin dapat mengelola user, SP Tool, Pic Tool, Mgr Tool, dan Admin.'
                        : 'Admin dapat mengelola user, SP Tool, Pic Tool, dan Mgr Tool pada area sendiri.' }}
                </p>
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                type="button"
                @click="openCreate"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Pengguna
            </button>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_220px_220px]">
            <label class="relative">
                <span class="sr-only">Cari pengguna</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama, username, email, role, atau area..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>

            <label class="relative">
                <span class="sr-only">Filter role</span>
                <select
                    v-model="selectedRole"
                    class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 pr-10 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="">Semua Role</option>
                    <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                        {{ role.label }}
                    </option>
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </span>
            </label>

            <label v-if="isSuperAdmin" class="relative">
                <span class="sr-only">Filter area</span>
                <select
                    v-model="selectedArea"
                    class="h-11 w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 pr-10 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="">Semua Area</option>
                    <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                        {{ area.name }}
                    </option>
                </select>
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 9l6 6 6-6" />
                    </svg>
                </span>
            </label>
        </div>

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-[920px] w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left font-semibold text-slate-500">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Username</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Role</th>
                            <th class="px-4 py-3">Area</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-if="isLoading">
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                Memuat data pengguna...
                            </td>
                        </tr>
                        <tr v-else-if="loadError">
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-rose-500">
                                {{ loadError }}
                            </td>
                        </tr>
                        <tr v-else v-for="user in users" :key="user.id">
                            <td class="px-4 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ user.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">ID #{{ user.id }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ user.username }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ user.email }}</td>
                            <td class="px-4 py-4">
                                <span
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="roleBadgeClass(user.role_key)"
                                >
                                    {{ resolveRoleLabel(user.role_key) }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ user.area_name }}</td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-blue-200 hover:text-blue-600"
                                        type="button"
                                        @click="openEdit(user)"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                        </svg>
                                    </button>
                                    <button
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-rose-200 text-rose-500 transition hover:bg-rose-50"
                                        type="button"
                                        @click="removeUser(user)"
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
                        <tr v-if="!isLoading && !loadError && !users.length">
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">
                                Tidak ada data pengguna.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="pagination.lastPage > 1"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            <span>
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} | Total {{ pagination.total }} pengguna
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
            v-if="formOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="closeForm"
        >
            <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ isEdit ? 'Edit Pengguna' : 'Tambah Pengguna' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ isEdit ? 'Perbarui data akun operasional yang dipilih.' : 'Isi data akun operasional baru.' }}
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
                        <span>Nama Lengkap *</span>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="Masukkan nama pengguna"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        <p v-if="errors.name" class="text-xs text-rose-500">{{ errors.name }}</p>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>Username *</span>
                        <input
                            v-model="form.username"
                            type="text"
                            placeholder="Username untuk login"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        <p v-if="errors.username" class="text-xs text-rose-500">{{ errors.username }}</p>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                        <span>Email *</span>
                        <input
                            v-model="form.email"
                            type="email"
                            placeholder="nama@perusahaan.com"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        <p v-if="errors.email" class="text-xs text-rose-500">{{ errors.email }}</p>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Role *</span>
                        <select
                            v-model="form.role_key"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Pilih role</option>
                            <option v-for="role in roleOptions" :key="role.value" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                        <p v-if="errors.role_key" class="text-xs text-rose-500">{{ errors.role_key }}</p>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Area *</span>
                        <select
                            v-model="form.area_id"
                            :disabled="isAreaLocked || !availableAreas.length"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100"
                        >
                            <option v-if="!isAreaLocked" value="">Pilih area</option>
                            <option v-for="area in availableAreas" :key="area.id" :value="String(area.id)">
                                {{ area.name }}
                            </option>
                        </select>
                        <p v-if="errors.area_id" class="text-xs text-rose-500">{{ errors.area_id }}</p>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>{{ isEdit ? 'Password Baru' : 'Password' }} {{ isEdit ? '' : '*' }}</span>
                        <input
                            v-model="form.password"
                            type="password"
                            :placeholder="isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter, 1 kapital, 1 angka'"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        <p v-if="errors.password" class="text-xs text-rose-500">{{ errors.password }}</p>
                    </label>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Konfirmasi Password{{ isEdit ? ' Baru' : '' }}</span>
                        <input
                            v-model="form.password_confirmation"
                            type="password"
                            :placeholder="isEdit ? 'Ikuti password baru' : 'Ulangi password'"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                </div>

                <p v-if="errors.general || formError" class="mt-3 text-sm font-semibold text-rose-500">
                    {{ errors.general || formError }}
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
import { usePage } from '@inertiajs/vue3';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Kelola Pengguna',
                subtitle: 'Kelola akun operasional per area',
                activeMenu: 'tambah-pengguna',
            },
            () => page
        ),
});

const baseRoleOptions = [
    { value: 'user', label: 'User' },
    { value: 'sp_tool', label: 'SP Tool' },
    { value: 'pic_tools', label: 'Pic Tool' },
    { value: 'mgr_tool', label: 'Mgr Tool' },
];

const superAdminExtraRoleOptions = [
    { value: 'admin', label: 'Admin' },
];

const page = usePage();
const users = ref([]);
const areas = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const isSubmitting = ref(false);
const formOpen = ref(false);
const formError = ref('');
const errors = ref({});
const search = ref('');
const selectedRole = ref('');
const selectedArea = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;
let filterTimeout = null;

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 10,
});

const form = reactive({
    id: null,
    name: '',
    username: '',
    email: '',
    role_key: '',
    area_id: '',
    password: '',
    password_confirmation: '',
});

const isEdit = computed(() => form.id !== null);
const authUser = computed(() => page.props.auth?.user ?? null);
const isSuperAdmin = computed(() => authUser.value?.role?.key === 'super_admin');
const adminAreaId = computed(() => authUser.value?.area?.id ? String(authUser.value.area.id) : '');
const isAdminRole = computed(() => authUser.value?.role?.key === 'admin');
const activeAreaId = inject('activeAreaId', ref(null));
const roleOptions = computed(() =>
    isSuperAdmin.value
        ? [...baseRoleOptions, ...superAdminExtraRoleOptions]
        : baseRoleOptions
);
const availableAreas = computed(() => {
    if (!isAdminRole.value || !adminAreaId.value) {
        return areas.value;
    }

    return areas.value.filter((area) => String(area.id) === adminAreaId.value);
});
const isAreaLocked = computed(() => isAdminRole.value && !!availableAreas.value.length);

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

const normalizeErrors = (payload) => {
    if (!payload) {
        return {};
    }

    return Object.fromEntries(
        Object.entries(payload).map(([key, value]) => [key, Array.isArray(value) ? value[0] : value])
    );
};

const resolveRoleLabel = (roleKey) => {
    const matchedRole = roleOptions.value.find((role) => role.value === roleKey)
        ?? superAdminExtraRoleOptions.find((role) => role.value === roleKey)
        ?? baseRoleOptions.find((role) => role.value === roleKey);

    return matchedRole?.label ?? roleKey ?? '-';
};

const roleBadgeClass = (roleKey) => {
    if (roleKey === 'admin') {
        return 'bg-rose-100 text-rose-700';
    }
    if (roleKey === 'mgr_tool') {
        return 'bg-violet-100 text-violet-700';
    }
    if (roleKey === 'pic_tools') {
        return 'bg-amber-100 text-amber-700';
    }
    if (roleKey === 'sp_tool') {
        return 'bg-emerald-100 text-emerald-700';
    }
    return 'bg-slate-100 text-slate-700';
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

const syncAreaSelection = () => {
    if (isAreaLocked.value) {
        form.area_id = adminAreaId.value;
        return;
    }

    if (!availableAreas.value.length) {
        form.area_id = '';
    }
};

const resetForm = () => {
    form.id = null;
    form.name = '';
    form.username = '';
    form.email = '';
    form.role_key = '';
    form.area_id = '';
    form.password = '';
    form.password_confirmation = '';
    form.area_id = adminAreaId.value;
    errors.value = {};
    formError.value = '';
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const openEdit = (user) => {
    form.id = user.id;
    form.name = user.name ?? '';
    form.username = user.username ?? '';
    form.email = user.email ?? '';
    form.role_key = user.role_key ?? '';
    form.area_id = isAreaLocked.value ? adminAreaId.value : user.area_id ? String(user.area_id) : '';
    form.password = '';
    form.password_confirmation = '';
    errors.value = {};
    formError.value = '';
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
    resetForm();
};

const loadAreas = async () => {
    try {
        const response = await axios.get('/api/areas');
        areas.value = Array.isArray(response.data) ? response.data : [];
        if (isSuperAdmin.value && !selectedArea.value && activeAreaId.value) {
            selectedArea.value = String(activeAreaId.value);
        }
        syncAreaSelection();
    } catch (error) {
        areas.value = [];
        syncAreaSelection();
    }
};

const buildParams = () => {
    const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage,
    };

    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }

    if (selectedRole.value) {
        params.role = selectedRole.value;
    }

    if (isSuperAdmin.value && selectedArea.value) {
        params.area_id = Number(selectedArea.value);
    }

    return params;
};

const loadUsers = async () => {
    isLoading.value = true;
    loadError.value = '';

    try {
        const response = await axios.get('/api/users', { params: buildParams() });
        const payload = response.data ?? {};
        users.value = Array.isArray(payload.data) ? payload.data : [];
        pagination.currentPage = Number(payload.meta?.current_page ?? 1) || 1;
        pagination.lastPage = Number(payload.meta?.last_page ?? 1) || 1;
        pagination.perPage = Number(payload.meta?.per_page ?? pagination.perPage) || 10;
        pagination.total = Number(payload.meta?.total ?? users.value.length) || 0;
    } catch (error) {
        users.value = [];
        loadError.value = error.response?.data?.message ?? 'Gagal memuat data pengguna.';
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
    loadUsers();
};

const submitForm = async () => {
    isSubmitting.value = true;
    errors.value = {};
    formError.value = '';

    try {
        const payload = {
            name: form.name,
            username: form.username,
            email: form.email,
            role_key: form.role_key,
            area_id: form.area_id ? Number(form.area_id) : null,
        };

        if (isEdit.value) {
            if (form.password) {
                payload.password = form.password;
                payload.password_confirmation = form.password_confirmation;
            }
            await axios.put(`/api/users/${form.id}`, payload);
        } else {
            payload.password = form.password;
            payload.password_confirmation = form.password_confirmation;
            await axios.post('/api/users', payload);
        }

        await loadUsers();
        showAlert('success', `Pengguna berhasil ${isEdit.value ? 'diperbarui' : 'ditambahkan'}.`);
        closeForm();
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = normalizeErrors(error.response.data?.errors);
            formError.value = error.response?.data?.message ?? 'Validasi gagal.';
        } else if (error.response?.status === 403) {
            formError.value = 'Anda tidak memiliki akses untuk mengelola pengguna.';
        } else {
            formError.value = error.response?.data?.message ?? 'Gagal menyimpan data pengguna.';
        }
    } finally {
        isSubmitting.value = false;
    }
};

const removeUser = async (user) => {
    if (!confirm(`Hapus pengguna "${user.name}"?`)) {
        return;
    }

    try {
        await axios.delete(`/api/users/${user.id}`);

        if (users.value.length === 1 && pagination.currentPage > 1) {
            pagination.currentPage -= 1;
        }

        await loadUsers();
        showAlert('success', 'Pengguna berhasil dihapus.');
    } catch (error) {
        showAlert('error', error.response?.data?.message ?? 'Gagal menghapus pengguna.');
    }
};

watch(
    () => form.role_key,
    () => {
        syncAreaSelection();
        errors.value = {
            ...errors.value,
            role_key: '',
            area_id: '',
        };
    }
);

watch([adminAreaId, availableAreas], () => {
    syncAreaSelection();
});

watch(
    search,
    () => {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }

        filterTimeout = setTimeout(() => {
            pagination.currentPage = 1;
            loadUsers();
        }, 300);
    }
);

watch(selectedRole, () => {
    pagination.currentPage = 1;
    loadUsers();
});

watch(selectedArea, () => {
    pagination.currentPage = 1;
    loadUsers();
});

watch(
    () => activeAreaId.value,
    (next, prev) => {
        if (!isSuperAdmin.value || !next || selectedArea.value === '') {
            return;
        }
        const normalized = String(next);
        if (prev === undefined || prev === null || selectedArea.value === String(prev)) {
            selectedArea.value = normalized;
        }
    },
    { immediate: true }
);

onMounted(() => {
    Promise.all([loadAreas(), loadUsers()]);
});
</script>
