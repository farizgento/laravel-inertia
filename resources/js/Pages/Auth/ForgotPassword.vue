<template>
    <Head title="Lupa Password | IPTOOLS Lite" />

    <div class="min-h-screen bg-slate-100">
        <div
            class="pointer-events-none absolute left-0 top-0 h-full w-full bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_55%)]"
        ></div>

        <div class="relative z-10 flex min-h-screen items-center justify-center px-4 py-12">
            <div class="w-full max-w-md">
                <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 shadow-lg shadow-blue-200">
                        <svg class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" />
                        </svg>
                    </div>
                    <h1 class="mt-4 text-2xl font-semibold text-slate-900">IP TOOLS Lite <i>BETA</i></h1>
                    <p class="mt-1 text-sm text-slate-500">Reset password akun Anda</p>
                </div>

                <div class="mt-8 rounded-2xl bg-white p-8 shadow-xl shadow-slate-200">
                    <div class="text-center">
                        <h2 class="text-lg font-semibold text-slate-900">Lupa Password</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Masukkan email akun Anda untuk menerima link reset password.
                        </p>
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="text-sm font-medium text-slate-700">Email</label>
                            <div class="relative mt-2">
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="5" width="18" height="14" rx="2" />
                                        <path d="m3 7 9 6 9-6" />
                                    </svg>
                                </span>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    placeholder="nama@email.com"
                                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>
                            <p v-if="errors.email" class="mt-1 text-xs text-red-500">
                                {{ errors.email }}
                            </p>
                        </div>

                        <p v-if="message" class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
                            {{ message }}
                        </p>
                        <p v-if="errors.general" class="text-sm text-red-500">
                            {{ errors.general }}
                        </p>

                        <button
                            type="submit"
                            :disabled="processing"
                            class="w-full rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                        >
                            {{ processing ? 'Mengirim...' : 'Kirim Link Reset' }}
                        </button>
                    </form>

                    <div class="mt-4 text-center">
                        <Link href="/login" class="text-sm font-semibold text-blue-600 transition hover:text-blue-700">
                            Kembali ke login
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const form = ref({
    email: '',
});
const errors = ref({});
const message = ref('');
const processing = ref(false);

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

const submit = async () => {
    errors.value = {};
    message.value = '';
    processing.value = true;

    try {
        const response = await axios.post('/api/auth/forgot-password', form.value);
        message.value = response.data?.message ?? 'Link reset password sudah dikirim.';
    } catch (err) {
        if (err.response?.status === 422 && err.response.data?.errors) {
            errors.value = normalizeErrors(err.response.data.errors);
        } else {
            errors.value = {
                general: err.response?.data?.message ?? 'Gagal mengirim link reset password.',
            };
        }
    } finally {
        processing.value = false;
    }
};
</script>
