<template>
    <div class="space-y-6">
        <!--
            Tiga kartu ringkas di dalam hero sebelumnya mengulang persis nilai
            Peminjaman/Aset Area/Jenis Alat pada kartu ringkasan di bawahnya. Duplikasi
            itu dihapus supaya setiap angka hanya muncul di satu tempat; nilainya sendiri
            tetap ditampilkan lengkap oleh kartu ringkasan.
        -->
        <section class="relative overflow-hidden rounded-2xl bg-slate-900 px-6 py-6 text-white shadow-xl shadow-slate-300/50 md:px-8">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-amber-400/10 via-cyan-300/10 to-transparent"></div>

            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.28em] text-cyan-200/80">Dashboard Area</p>
                    <h1 class="mt-2 text-2xl font-semibold leading-tight md:text-3xl">
                        Halo, {{ greetingName }}.
                    </h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                        Ringkasan operasional untuk
                        <span class="font-semibold text-white">{{ currentAreaName }}</span>.
                        Semua angka di bawah mengikuti area yang sedang dipilih.
                    </p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                    <label
                        v-if="canChooseDashboardArea"
                        for="dashboard-area"
                        class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300"
                    >
                        Area Aktif
                    </label>
                    <p v-else class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-300">Area Aktif</p>
                    <select
                        v-if="canChooseDashboardArea"
                        id="dashboard-area"
                        v-model="selectedAreaId"
                        class="mt-2 h-10 w-full min-w-56 rounded-lg border border-white/10 bg-slate-950/60 px-3 text-sm font-semibold text-white outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-300/30"
                        :disabled="isLoadingAreas || isLoading"
                    >
                        <option value="">Pilih area</option>
                        <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                            {{ area.name }}
                        </option>
                    </select>
                    <p v-else class="mt-1 text-lg font-semibold text-white">{{ currentAreaName }}</p>
                </div>
            </div>
        </section>

        <section v-if="loadError" class="rounded-2xl border border-rose-200 bg-rose-50 px-6 py-5 shadow-sm">
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

        <!-- Belum ada area terpilih: dashboard sengaja tidak memuat data, jadi angka 0
             tidak boleh ditampilkan seolah-olah itu hasil sebenarnya. -->
        <section
            v-else-if="isAwaitingAreaSelection"
            class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-12 text-center"
        >
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6 9 3l6 3 6-3v15l-6 3-6-3-6 3z" />
                    <path d="M9 3v15M15 6v15" />
                </svg>
            </span>
            <p class="mt-3 text-sm font-semibold text-slate-700">Pilih area terlebih dahulu</p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                Ringkasan operasional akan ditampilkan setelah Anda memilih area aktif pada bagian atas halaman.
            </p>
        </section>

        <!-- Pemuatan pertama: tampilkan kerangka, bukan angka 0 yang bisa disalahartikan
             sebagai data kosong. -->
        <section v-else-if="isInitialLoading" class="space-y-4" aria-hidden="true">
            <div class="grid gap-4 xl:grid-cols-[1.5fr,1fr]">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="card in 4"
                        :key="`skeleton-card-${card}`"
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50"
                    >
                        <div class="h-3 w-24 animate-pulse rounded bg-slate-100"></div>
                        <div class="mt-4 h-8 w-20 animate-pulse rounded bg-slate-100"></div>
                        <div class="mt-4 h-3 w-full animate-pulse rounded bg-slate-100"></div>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
                    <div class="h-3 w-28 animate-pulse rounded bg-slate-100"></div>
                    <div class="mt-5 space-y-3">
                        <div v-for="row in 3" :key="`skeleton-status-${row}`" class="h-20 animate-pulse rounded-2xl bg-slate-100"></div>
                    </div>
                </div>
            </div>
        </section>

        <template v-else>
            <section class="grid gap-4 xl:grid-cols-[1.5fr,1fr]">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="card in summaryCards"
                        :key="card.title"
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50"
                    >
                        <div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">{{ card.title }}</p>
                                <p class="mt-3 text-3xl font-semibold tabular-nums text-slate-900">{{ card.value }}</p>
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-slate-500">{{ card.description }}</p>
                    </article>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
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
                                <p class="text-2xl font-semibold tabular-nums" :class="item.valueClass">{{ item.value }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section
                v-if="showOperationalInsights"
                class="grid gap-4 xl:grid-cols-[0.95fr,1.45fr]"
            >
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
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
                            <p class="mt-3 text-3xl font-semibold tabular-nums text-amber-900">
                                {{ formatNumber(insights.kerusakan_tahunan) }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-amber-800/80">
                                Total alat rusak di {{ currentAreaName }} pada tahun berjalan.
                            </p>
                        </article>

                        <article class="rounded-2xl bg-rose-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-rose-700/80">Kehilangan</p>
                            <p class="mt-3 text-3xl font-semibold tabular-nums text-rose-900">
                                {{ formatNumber(insights.kehilangan_tahunan) }}
                            </p>
                            <p class="mt-2 text-sm leading-6 text-rose-800/80">
                                Total alat hilang di {{ currentAreaName }} pada tahun berjalan.
                            </p>
                        </article>
                    </div>

                    <article class="mt-4 rounded-2xl bg-slate-900 p-5 text-white">
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-200/80">Semua Area</p>
                        <p class="mt-3 text-3xl font-semibold tabular-nums">
                            {{ formatNumber(insights.total_aset_semua_area) }}
                        </p>
                        <p class="mt-2 text-sm leading-6 text-slate-300">
                            Total aset alat lintas semua area. Terdiri dari {{ formatNumber(insights.total_jenis_alat_semua_area) }} jenis alat.
                        </p>
                    </article>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
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

                    <!-- Seluruh nilai nol: batang kosong tanpa penjelasan mudah dikira
                         grafik gagal dimuat, jadi kondisinya dinyatakan eksplisit. -->
                    <div
                        v-if="!hasSeriesValue"
                        class="mt-6 rounded-2xl border border-dashed border-slate-200 bg-slate-50/60 px-6 py-14 text-center"
                    >
                        <p class="text-sm font-semibold text-slate-700">Belum ada kerusakan atau kehilangan</p>
                        <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                            Tidak ada laporan kerusakan maupun kehilangan yang tercatat di
                            {{ currentAreaName }} sepanjang {{ currentYearLabel }}.
                        </p>
                    </div>

                    <div v-else class="mt-6 overflow-x-auto">
                        <div class="min-w-[640px]">
                            <div class="flex gap-3">
                                <!-- Sumbu Y memberi acuan besaran, menggantikan 24 angka yang
                                     sebelumnya ditumpuk di atas setiap batang. -->
                                <div class="flex h-64 w-10 shrink-0 flex-col justify-between py-1 text-right text-[11px] tabular-nums text-slate-400">
                                    <span>{{ formatNumber(maxSeriesValue) }}</span>
                                    <span>{{ formatNumber(Math.round(maxSeriesValue / 2)) }}</span>
                                    <span>0</span>
                                </div>

                                <div class="relative min-w-0 flex-1">
                                    <div class="pointer-events-none absolute inset-0 flex h-64 flex-col justify-between" aria-hidden="true">
                                        <div class="border-t border-slate-200"></div>
                                        <div class="border-t border-dashed border-slate-200"></div>
                                        <div class="border-t border-slate-300"></div>
                                    </div>

                                    <div class="relative flex h-64 items-end gap-2">
                                        <div
                                            v-for="item in insights.series"
                                            :key="item.key"
                                            class="flex h-full min-w-0 flex-1 items-end justify-center gap-1"
                                        >
                                            <div
                                                class="w-3 rounded-t bg-amber-400 sm:w-4"
                                                :style="{ height: `${barHeight(item.kerusakan)}%` }"
                                                :title="`${item.label}: ${formatNumber(item.kerusakan)} kerusakan`"
                                            ></div>
                                            <div
                                                class="w-3 rounded-t bg-rose-400 sm:w-4"
                                                :style="{ height: `${barHeight(item.kehilangan)}%` }"
                                                :title="`${item.label}: ${formatNumber(item.kehilangan)} kehilangan`"
                                            ></div>
                                        </div>
                                    </div>

                                    <div class="mt-2 flex gap-2">
                                        <p
                                            v-for="item in insights.series"
                                            :key="`label-${item.key}`"
                                            class="min-w-0 flex-1 text-center text-[11px] font-semibold uppercase text-slate-500"
                                        >
                                            {{ item.label }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <p class="mt-3 text-right text-xs text-slate-400">
                                Januari &ndash; Desember {{ currentYearLabel }}
                            </p>
                        </div>
                    </div>

                    <!-- Padanan teks grafik untuk pembaca layar; angkanya sama persis. -->
                    <table class="sr-only">
                        <caption>Kerusakan dan kehilangan per bulan {{ currentYearLabel }} di {{ currentAreaName }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Bulan</th>
                                <th scope="col">Kerusakan</th>
                                <th scope="col">Kehilangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in insights.series" :key="`sr-${item.key}`">
                                <th scope="row">{{ item.label }}</th>
                                <td>{{ formatNumber(item.kerusakan) }}</td>
                                <td>{{ formatNumber(item.kehilangan) }}</td>
                            </tr>
                        </tbody>
                    </table>
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
import { loadAreas as loadSharedAreas } from '../lib/areas';

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
    return null;
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
        show_operational_insights: false,
    },
});

const areas = ref([]);
const selectedAreaId = ref('');
const isLoading = ref(false);
const isLoadingAreas = ref(false);
const loadError = ref('');
// Membedakan pemuatan pertama (tampilkan kerangka) dari pemuatan ulang saat
// berganti area (data lama tetap terlihat sampai data baru datang).
const hasLoaded = ref(false);

const roleKey = computed(() =>
    (page.props.auth?.user?.role?.key ?? cachedUser.value?.role?.key ?? '').toLowerCase()
);
const authUser = computed(() => page.props.auth?.user ?? cachedUser.value);
const userAreaId = computed(() => authUser.value?.area_id ?? authUser.value?.area?.id ?? '');
const canChooseDashboardArea = computed(() => ['sp_tool', 'mgr_tool'].includes(roleKey.value));
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

// Menandai kondisi yang sama persis dengan syarat keluar awal pada loadDashboard(),
// supaya dashboard tidak menampilkan angka 0 saat permintaan memang belum dijalankan.
const isAwaitingAreaSelection = computed(() => {
    if (canChooseDashboardArea.value) {
        return !selectedAreaId.value;
    }

    return isAreaSwitcherRole.value && !activeAreaId.value;
});

const isInitialLoading = computed(() => isLoading.value && !hasLoaded.value);

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value ?? 0);
const normalizeAreaId = (value) => (value === null || value === undefined || value === '' ? '' : String(value));

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
        title: 'Perlu Review',
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
        description: 'Termasuk disetujui, dikirim, dan diterima.',
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

const hasSeriesValue = computed(() => maxSeriesValue.value > 0);

const barHeight = (value) => {
    const max = maxSeriesValue.value;
    const current = Number(value ?? 0);
    if (max <= 0 || current <= 0) {
        // Sebelumnya nilai nol tetap digambar setinggi 6% sehingga bulan tanpa
        // kerusakan/kehilangan terlihat seolah punya catatan. Nol kini benar-benar nol.
        return 0;
    }

    // Nilai kecil tetap diberi tinggi minimum agar terlihat, tetapi hanya untuk
    // nilai yang memang lebih besar dari nol.
    return Math.max(4, Math.round((current / max) * 100));
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
        can_choose_dashboard_area: !!payload?.meta?.can_choose_dashboard_area,
        show_operational_insights: !!payload?.meta?.show_operational_insights,
    };
};

const buildParams = () => {
    const params = {};
    if (canChooseDashboardArea.value && selectedAreaId.value) {
        params.area_id = selectedAreaId.value;
    } else if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }
    return params;
};

const loadDashboardAreas = async () => {
    if (!canChooseDashboardArea.value) {
        areas.value = [];
        return;
    }

    isLoadingAreas.value = true;
    try {
        areas.value = await loadSharedAreas();
        if (!selectedAreaId.value && userAreaId.value) {
            selectedAreaId.value = normalizeAreaId(userAreaId.value);
        }
    } catch (error) {
        areas.value = [];
    } finally {
        isLoadingAreas.value = false;
    }
};

const loadDashboard = async () => {
    if (canChooseDashboardArea.value && !selectedAreaId.value) {
        return;
    }

    if (!canChooseDashboardArea.value && isAreaSwitcherRole.value && !activeAreaId.value) {
        return;
    }

    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/dashboard', {
            params: buildParams(),
        });
        applyPayload(response.data);
        hasLoaded.value = true;
    } catch (error) {
        loadError.value = error?.response?.data?.message ?? 'Data dashboard gagal dimuat.';
    } finally {
        isLoading.value = false;
        setAreaSwitching?.(false);
    }
};

onMounted(() => {
    cachedUser.value = loadCachedUser();
    if (canChooseDashboardArea.value) {
        selectedAreaId.value = normalizeAreaId(userAreaId.value);
        loadDashboardAreas();
        return;
    }

    if (!isAreaSwitcherRole.value || activeAreaId.value) {
        loadDashboard();
    }
});

watch(
    selectedAreaId,
    (next, prev) => {
        if (!canChooseDashboardArea.value || !next || next === prev) {
            return;
        }

        loadDashboard();
    }
);

watch(
    userAreaId,
    (next) => {
        if (!canChooseDashboardArea.value || selectedAreaId.value || !next) {
            return;
        }

        selectedAreaId.value = normalizeAreaId(next);
    }
);

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
