<template>
    <div class="min-h-screen bg-slate-100">
        <div
            class="pointer-events-none absolute left-0 top-0 h-full w-full bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_55%)]"
        ></div>
        <div
            class="pointer-events-none absolute bottom-0 right-0 h-64 w-64 -translate-y-10 translate-x-10 rounded-full bg-blue-100 blur-3xl"
        ></div>

        <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="text-center">
                    <div
                        class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-200"
                    >
                        <svg
                            class="h-7 w-7 text-white"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" />
                        </svg>
                    </div>
                    <h1 class="mt-4 text-2xl font-semibold text-slate-900">ToolArea Lending</h1>
                    <p class="mt-1 text-sm text-slate-500">Sistem Peminjaman Alat</p>
                </div>

                <div class="mt-8 rounded-2xl bg-white p-8 shadow-xl shadow-slate-200">
                    <div class="text-center">
                        <h2 class="text-lg font-semibold text-slate-900">Buat Akun Baru</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Daftar untuk mulai menggunakan sistem
                        </p>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Nama Lengkap</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Nama lengkap Anda"
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                            <p v-if="errors.name" class="mt-1 text-xs text-red-500">
                                {{ errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Email</label>
                            <div class="relative mt-2">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"
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
                                        <path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z" />
                                        <path d="m22 6-10 7L2 6" />
                                    </svg>
                                </span>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="nama@perusahaan.com"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>
                            <p v-if="errors.email" class="mt-1 text-xs text-red-500">
                                {{ errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Area</label>
                            <div class="relative mt-2">
                                <select
                                    v-model="form.area_id"
                                    class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                >
                                    <option disabled value="">Pilih area Anda</option>
                                    <option v-for="area in areas" :key="area.id" :value="area.id">
                                        {{ area.name }}
                                    </option>
                                </select>
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-400"
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
                                        <path d="M6 9l6 6 6-6" />
                                    </svg>
                                </span>
                            </div>
                            <p v-if="errors.area_id" class="mt-1 text-xs text-red-500">
                                {{ errors.area_id }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">Password</label>
                            <div class="relative mt-2">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"
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
                                        <rect x="3" y="11" width="18" height="10" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </span>
                                <input
                                    v-model="form.password"
                                    type="password"
                                    placeholder="Masukkan password"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>
                            <p v-if="errors.password" class="mt-1 text-xs text-red-500">
                                {{ errors.password }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-slate-700">
                                Konfirmasi Password
                            </label>
                            <div class="relative mt-2">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400"
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
                                        <rect x="3" y="11" width="18" height="10" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </span>
                                <input
                                    v-model="form.password_confirmation"
                                    type="password"
                                    placeholder="Konfirmasi password"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>
                        </div>

                        <p v-if="errors.general" class="text-sm text-red-500">
                            {{ errors.general }}
                        </p>

                        <button
                            type="submit"
                            :disabled="processing"
                            class="mt-2 w-full rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ processing ? 'Memproses...' : 'Daftar' }}
                        </button>
                    </form>

                    <p class="mt-5 text-center text-sm text-slate-500">
                        Sudah punya akun?
                        <Link class="font-semibold text-blue-600 hover:text-blue-700" href="/login">
                            Masuk
                        </Link>
                    </p>
                </div>

                <p class="mt-6 text-center text-xs text-slate-400">
                    2024 ToolArea Lending. Semua hak dilindungi.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const form = ref({
    name: '',
    email: '',
    area_id: '',
    password: '',
    password_confirmation: '',
});

const areas = ref([]);
const errors = ref({});
const processing = ref(false);
const roleRoutes = {
    user: '/dashboard',
    sp_tool: '/dashboard',
    pic_tools: '/dashboard',
    mgr_tool: '/dashboard',
    admin: '/dashboard',
    super_admin: '/dashboard',
};

const resolveRoleRoute = (roleKey) => roleRoutes[roleKey] ?? '/alat';

const normalizeErrors = (payload) => {
    if (!payload) {
        return {};
    }

    const normalized = {};
    Object.entries(payload).forEach(([key, value]) => {
        normalized[key] = Array.isArray(value) ? value[0] : value;
    });
    return normalized;
};

const loadAreas = async () => {
    try {
        const response = await axios.get('/api/areas');
        areas.value = response.data;
    } catch (err) {
        errors.value = {
            general: 'Gagal memuat data area. Coba refresh halaman.',
        };
    }
};

const submit = async () => {
    errors.value = {};
    processing.value = true;

    try {
        const response = await axios.post('/api/auth/register', form.value);
        const { token, user } = response.data;

        window.localStorage.setItem('auth_token', token);
        window.localStorage.setItem('auth_user', JSON.stringify(user));
        axios.defaults.headers.common.Authorization = `Bearer ${token}`;

        router.visit(resolveRoleRoute(user?.role?.key));
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = normalizeErrors(err.response.data.errors);
        } else {
            errors.value = {
                general: err.response?.data?.message ?? 'Gagal mendaftar. Coba lagi.',
            };
        }
    } finally {
        processing.value = false;
    }
};

onMounted(() => {
    loadAreas();
});
</script>
