<template>
    <div class="space-y-6">
        <section class="relative overflow-hidden rounded-[32px] bg-slate-900 px-6 py-7 text-white shadow-2xl shadow-slate-300/50 md:px-8">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-amber-400/20 via-cyan-300/10 to-transparent"></div>
            <div class="absolute -right-16 -top-16 h-40 w-40 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-32 w-32 rounded-full bg-amber-300/20 blur-3xl"></div>

            <div class="relative">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="max-w-3xl">
                        <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-200/80">Dashboard Area</p>
                        <h1 class="mt-3 text-3xl font-semibold leading-tight md:text-4xl">
                            Halo, {{ greetingName }}.
                        </h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                            Ringkasan operasional untuk {{ currentAreaName }}. Semua angka di bawah mengikuti area aktif pengguna yang login.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">Area Aktif</p>
                        <p class="mt-2 text-lg font-semibold text-white">{{ currentAreaName }}</p>
                        <p class="mt-1 text-xs text-slate-400">Diperbarui {{ generatedAtLabel }}</p>
                        <p v-if="isLoading" class="mt-2 text-xs font-semibold text-cyan-200">Memuat insight dashboard...</p>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Peminjaman</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ formatNumber(summary.total_peminjaman) }}</p>
                        <p class="mt-1 text-sm text-slate-300">Total transaksi pada area aktif.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Aset Area</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ formatNumber(summary.total_aset_area) }}</p>
                        <p class="mt-1 text-sm text-slate-300">Jumlah aset alat yang tersedia di area ini.</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-4 backdrop-blur">
                        <p class="text-xs uppercase tracking-[0.25em] text-slate-400">Jenis Alat</p>
                        <p class="mt-2 text-2xl font-semibold text-white">{{ formatNumber(summary.total_jenis_alat_area) }}</p>
                        <p class="mt-1 text-sm text-slate-300">Jumlah katalog alat aktif di area ini.</p>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="loadError" class="rounded-3xl border border-rose-200 bg-rose-50 px-6 py-5 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-rose-700">Gagal memuat dashboard</h2>
                    <p class="mt-1 text-sm text-rose-600">{{ loadError }}</p>
                </div>
                <button
                    type="button"
                    class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                    @click="loadDashboard()"
                >
                    Muat Ulang
                </button>
            </div>
        </section>

        <template v-else>
            <section class="grid gap-4 xl:grid-cols-[1.5fr,1fr]">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in summaryCards"
                        :key="card.title"
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50"
                    >
                        <div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">{{ card.title }}</p>
                                <p class="mt-3 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-500">{{ card.description }}</p>
                    </article>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Status Area</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">Pergerakan peminjaman</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">
                            {{ currentAreaName }}
                        </span>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div
                            v-for="item in statusCards"
                            :key="item.title"
                            class="rounded-2xl border px-4 py-4"
                            :class="item.wrapperClass"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold" :class="item.titleClass">{{ item.title }}</p>
                                    <p class="mt-1 text-xs" :class="item.captionClass">{{ item.description }}</p>
                                </div>
                                <p class="text-2xl font-semibold" :class="item.valueClass">{{ item.value }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="showOperationalInsights"
                class="grid gap-4 xl:grid-cols-[0.95fr,1.45fr]"
            >
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Insight Tahunan</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">Kerusakan dan kehilangan</h2>
                        </div>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">{{ currentYearLabel }}</span>
                    </div>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <article class="rounded-2xl bg-amber-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-amber-700/80">Kerusakan</p>
                            <p class="mt-3 text-3xl font-semibold text-amber-900">
                                {{ formatNumber(insights.kerusakan_tahunan) }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-amber-800/80">
                                Total laporan kerusakan di {{ currentAreaName }} pada tahun berjalan.
                            </p>
                        </article>

                        <article class="rounded-2xl bg-rose-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-rose-700/80">Kehilangan</p>
                            <p class="mt-3 text-3xl font-semibold text-rose-900">
                                {{ formatNumber(insights.kehilangan_tahunan) }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-rose-800/80">
                                Total laporan kehilangan di {{ currentAreaName }} pada tahun berjalan.
                            </p>
                        </article>
                    </div>

                    <article class="mt-4 rounded-2xl bg-slate-900 p-5 text-white">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-200/80">Semua Area</p>
                        <p class="mt-3 text-3xl font-semibold">
                            {{ formatNumber(insights.total_aset_semua_area) }}
                        </p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Total aset alat lintas semua area. Terdiri dari {{ formatNumber(insights.total_jenis_alat_semua_area) }} jenis alat.
                        </p>
                    </article>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Grafik Batang Tahunan</p>
                            <h2 class="mt-2 text-xl font-semibold text-slate-900">Kerusakan dan kehilangan per bulan</h2>
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-3 text-xs font-semibold text-slate-500">
                            <span class="rounded-full bg-slate-100 px-3 py-1">{{ currentYearLabel }}</span>
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                Kerusakan
                            </span>
                            <span class="inline-flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                                Kehilangan
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 overflow-x-auto">
                        <div class="min-w-[720px]">
                            <div class="flex h-72 items-end gap-3 rounded-3xl border border-slate-100 bg-slate-50 px-4 py-5">
                                <div
                                    v-for="item in insights.series"
                                    :key="item.key"
                                    class="flex min-w-0 flex-1 flex-col items-center gap-3"
                                >
                                    <p class="text-xs font-semibold text-slate-500">
                                        {{ formatNumber(item.kerusakan) }} / {{ formatNumber(item.kehilangan) }}
                                    </p>
                                    <div class="flex h-full items-end gap-2">
                                        <div class="flex h-full items-end">
                                            <div
                                                class="w-4 rounded-t-full bg-amber-400 transition-all duration-500 sm:w-5"
                                                :style="{ height: `${barHeight(item.kerusakan)}%` }"
                                            ></div>
                                        </div>
                                        <div class="flex h-full items-end">
                                            <div
                                                class="w-4 rounded-t-full bg-rose-400 transition-all duration-500 sm:w-5"
                                                :style="{ height: `${barHeight(item.kehilangan)}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        {{ item.label }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3 text-xs text-slate-400">
                                <span>Kerusakan / Kehilangan</span>
                                <span>Januari - Desember {{ currentYearLabel }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Dashboard',
                subtitle: 'Ringkasan operasional berbasis area',
                activeMenu: 'dashboard',
            },
            () => page
        ),
});

const page = usePage();

const loadCachedUser = () => {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        const cached = window.localStorage.getItem('auth_user');
        return cached ? JSON.parse(cached) : null;
    } catch (err) {
        return null;
    }
};

const cachedUser = ref(loadCachedUser());
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const setAreaSwitching = inject('setAreaSwitching', null);
const activeAreaId = inject('activeAreaId', ref(null));
const activeAreaName = inject('activeAreaName', ref('Area belum dipilih'));

const dashboard = reactive({
    area: {
        id: null,
        name: 'Area belum dipilih',
    },
    summary: {
        total_peminjaman: 0,
        total_aset_area: 0,
        total_jenis_alat_area: 0,
        menunggu_review: 0,
        sedang_berjalan: 0,
        selesai: 0,
        laporan_aktif: 0,
    },
    insights: {
        kerusakan_tahunan: 0,
        kehilangan_tahunan: 0,
        total_aset_semua_area: 0,
        total_jenis_alat_semua_area: 0,
        series: [],
    },
    meta: {
        role_key: '',
        generated_at: '',
        show_operational_insights: false,
    },
});

const isLoading = ref(false);
const loadError = ref('');

const roleKey = computed(() =>
    (page.props.auth?.user?.role?.key ?? cachedUser.value?.role?.key ?? '').toLowerCase()
);
const greetingName = computed(() => page.props.auth?.user?.name ?? cachedUser.value?.name ?? 'Pengguna');
const currentAreaName = computed(() =>
    (dashboard.area.id ? dashboard.area.name : '') ||
    (isAreaSwitcherRole.value ? activeAreaName.value : page.props.auth?.user?.area?.name) ||
    'Area belum dipilih'
);
const summary = computed(() => dashboard.summary);
const insights = computed(() => dashboard.insights);
const showOperationalInsights = computed(() => !!dashboard.meta.show_operational_insights);
const currentYearLabel = computed(() => new Date().getFullYear());
const generatedAtLabel = computed(() => {
    if (!dashboard.meta.generated_at) {
        return 'baru saja';
    }

    const date = new Date(dashboard.meta.generated_at);
    if (Number.isNaN(date.getTime())) {
        return 'baru saja';
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
});

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value ?? 0);

const summaryCards = computed(() => [
    {
        title: 'Jumlah Peminjaman',
        value: formatNumber(summary.value.total_peminjaman),
        description: 'Akumulasi pengajuan peminjaman untuk area aktif.',
    },
    {
        title: 'Total Aset Area',
        value: formatNumber(summary.value.total_aset_area),
        description: 'Total aset yang saat ini tercatat pada area yang sedang dibuka.',
    },
    {
        title: 'Jenis Alat',
        value: formatNumber(summary.value.total_jenis_alat_area),
        description: 'Jumlah katalog atau jenis alat yang tersedia di area ini.',
    },
    {
        title: 'Laporan Aktif',
        value: formatNumber(summary.value.laporan_aktif),
        description: 'Laporan yang masih berstatus dilaporkan atau sudah disetujui.',
    },
]);

const statusCards = computed(() => [
    {
        title: 'Menunggu Review',
        value: formatNumber(summary.value.menunggu_review),
        description: 'Butuh tindak lanjut review peminjaman.',
        wrapperClass: 'border-amber-200 bg-amber-50/70',
        titleClass: 'text-amber-900',
        captionClass: 'text-amber-700/80',
        valueClass: 'text-amber-900',
    },
    {
        title: 'Sedang Berjalan',
        value: formatNumber(summary.value.sedang_berjalan),
        description: 'Termasuk dipesan, disiapkan, terkirim, dan diterima.',
        wrapperClass: 'border-blue-200 bg-blue-50/70',
        titleClass: 'text-blue-900',
        captionClass: 'text-blue-700/80',
        valueClass: 'text-blue-900',
    },
    {
        title: 'Selesai',
        value: formatNumber(summary.value.selesai),
        description: 'Peminjaman yang sudah kembali dan selesai diproses.',
        wrapperClass: 'border-emerald-200 bg-emerald-50/70',
        titleClass: 'text-emerald-900',
        captionClass: 'text-emerald-700/80',
        valueClass: 'text-emerald-900',
    },
]);

const maxSeriesValue = computed(() => {
    if (!showOperationalInsights.value || !Array.isArray(insights.value.series)) {
        return 0;
    }

    return insights.value.series.reduce((carry, item) => {
        const highest = Math.max(Number(item?.kerusakan ?? 0), Number(item?.kehilangan ?? 0));
        return Math.max(carry, highest);
    }, 0);
});

const barHeight = (value) => {
    const max = maxSeriesValue.value;
    if (max <= 0) {
        return 0;
    }

    return Math.max(6, Math.round((Number(value ?? 0) / max) * 100));
};

const applyPayload = (payload = {}) => {
    dashboard.area = {
        id: payload?.area?.id ?? null,
        name: payload?.area?.name ?? 'Area belum dipilih',
    };
    dashboard.summary = {
        total_peminjaman: Number(payload?.summary?.total_peminjaman ?? 0),
        total_aset_area: Number(payload?.summary?.total_aset_area ?? 0),
        total_jenis_alat_area: Number(payload?.summary?.total_jenis_alat_area ?? 0),
        menunggu_review: Number(payload?.summary?.menunggu_review ?? 0),
        sedang_berjalan: Number(payload?.summary?.sedang_berjalan ?? 0),
        selesai: Number(payload?.summary?.selesai ?? 0),
        laporan_aktif: Number(payload?.summary?.laporan_aktif ?? 0),
    };
    dashboard.insights = {
        kerusakan_tahunan: Number(payload?.insights?.kerusakan_tahunan ?? 0),
        kehilangan_tahunan: Number(payload?.insights?.kehilangan_tahunan ?? 0),
        total_aset_semua_area: Number(payload?.insights?.total_aset_semua_area ?? 0),
        total_jenis_alat_semua_area: Number(payload?.insights?.total_jenis_alat_semua_area ?? 0),
        series: Array.isArray(payload?.insights?.series) ? payload.insights.series : [],
    };
    dashboard.meta = {
        role_key: payload?.meta?.role_key ?? roleKey.value,
        generated_at: payload?.meta?.generated_at ?? '',
        show_operational_insights: !!payload?.meta?.show_operational_insights,
    };
};

const buildParams = () => {
    const params = {};
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }
    return params;
};

const loadDashboard = async () => {
    if (isAreaSwitcherRole.value && !activeAreaId.value) {
        return;
    }

    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/dashboard', {
            params: buildParams(),
        });
        applyPayload(response.data);
    } catch (error) {
        loadError.value = error?.response?.data?.message ?? 'Data dashboard gagal dimuat.';
    } finally {
        isLoading.value = false;
        setAreaSwitching?.(false);
    }
};

onMounted(() => {
    cachedUser.value = loadCachedUser();
    if (!isAreaSwitcherRole.value || activeAreaId.value) {
        loadDashboard();
    }
});

watch(
    () => activeAreaId.value,
    (next, prev) => {
        if (!isAreaSwitcherRole.value || !next) {
            return;
        }

        const shouldShow = prev !== undefined && prev !== null && next !== prev;
        if (shouldShow) {
            setAreaSwitching?.(true);
        }

        loadDashboard();
    }
);
</script>
