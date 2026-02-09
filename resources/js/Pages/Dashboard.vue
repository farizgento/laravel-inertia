<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">
            Selamat datang, {{ greetingName }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Berikut ringkasan data peminjaman dan inventaris alat Anda.
        </p>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/60">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600">Jumlah Peminjaman</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ formatNumber(totalPeminjaman) }}
                    </p>
                </div>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="16" rx="2" />
                        <path d="M7 8h10M7 12h6M7 16h4" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-sm text-slate-500">
                Total pengajuan peminjaman yang tercatat di sistem.
            </p>
        </div>

        <div class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/60">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-600">Jumlah Tools</p>
                    <p class="mt-2 text-3xl font-semibold text-slate-900">
                        {{ formatNumber(totalTools) }}
                    </p>
                </div>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                        <path d="m3.3 7 8.7 5 8.7-5" />
                        <path d="M12 12v9" />
                    </svg>
                </span>
            </div>
            <p class="mt-4 text-sm text-slate-500">
                Total alat yang tersedia di katalog.
            </p>
        </div>
    </div>
</template>

<script setup>
import AppLayout from '../Layouts/AppLayout.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Dashboard',
                subtitle: 'Ringkasan peminjaman alat',
                activeMenu: 'dashboard',
            },
            () => page
        ),
});

defineProps({
    totalPeminjaman: {
        type: Number,
        default: 0,
    },
    totalTools: {
        type: Number,
        default: 0,
    },
    greetingName: {
        type: String,
        default: 'Pengguna',
    },
});

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value ?? 0);
</script>
