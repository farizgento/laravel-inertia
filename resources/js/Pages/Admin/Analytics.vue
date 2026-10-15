<template>
    <section class="relative mb-5 overflow-hidden rounded-[28px] bg-slate-900 px-6 py-6 text-white shadow-2xl shadow-slate-300/50">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-violet-400/20 via-sky-300/10 to-transparent"></div>
        <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-violet-300/20 blur-3xl"></div>

        <div class="relative flex flex-wrap items-start justify-between gap-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-violet-200/80">Analytics</p>
                <h1 class="mt-2 text-2xl font-semibold md:text-3xl">Keaktifan Semua Area</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">
                    Diukur dari aktivitas login pengguna dan seluruh log aktivitas aplikasi.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="opsi in opsiPeriode"
                    :key="opsi.hari"
                    class="h-9 rounded-xl px-3 text-xs font-semibold transition"
                    :class="hari === opsi.hari
                        ? 'bg-white text-slate-900'
                        : 'border border-white/20 bg-white/5 text-slate-200 hover:bg-white/10'"
                    type="button"
                    @click="gantiPeriode(opsi.hari)"
                >
                    {{ opsi.label }}
                </button>
            </div>
        </div>
    </section>

    <section v-if="loadError" class="rounded-3xl border border-rose-200 bg-rose-50 px-6 py-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-rose-700">Gagal memuat analytics</h2>
                <p class="mt-1 text-sm text-rose-600">{{ loadError }}</p>
            </div>
            <button
                class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700"
                type="button"
                @click="loadData"
            >
                Muat Ulang
            </button>
        </div>
    </section>

    <template v-else>
        <section class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="kartu in kartuRingkasan"
                :key="kartu.key"
                class="relative overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br p-5 shadow-lg shadow-slate-200/50 transition hover:-translate-y-0.5 hover:shadow-xl"
                :class="kartu.gradientClass"
            >
                <span class="absolute inset-x-0 top-0 h-1" :class="kartu.accentClass"></span>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">{{ kartu.label }}</p>
                        <p class="mt-3 text-3xl font-semibold text-slate-900">{{ kartu.nilai }}</p>
                    </div>
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl" :class="kartu.iconClass">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path v-for="(d, index) in kartu.iconPaths" :key="index" :d="d" />
                        </svg>
                    </span>
                </div>
                <p class="mt-3 text-sm text-slate-500">{{ kartu.keterangan }}</p>
            </article>
        </section>

        <section class="mb-5 grid gap-4 xl:grid-cols-2">
            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Keaktifan Area</p>
                        <h2 class="mt-2 text-base font-semibold text-slate-900">Porsi aktivitas tiap area</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ periode.mulai }} - {{ periode.selesai }}
                    </span>
                </div>

                <DonutChart
                    v-if="adaAktivitas"
                    class="mt-4"
                    :segmen="segmenAktivitas"
                    label-pusat="Aktivitas"
                    aria-label="Porsi aktivitas tiap area"
                />
                <p v-else class="mt-6 rounded-2xl bg-slate-50 px-4 py-10 text-center text-sm text-slate-500">
                    {{ isLoading ? 'Memuat data...' : 'Belum ada aktivitas pada periode ini.' }}
                </p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Login Pengguna</p>
                        <h2 class="mt-2 text-base font-semibold text-slate-900">Porsi login tiap area</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                        {{ formatAngka(ringkasan.total_login) }} login
                    </span>
                </div>

                <DonutChart
                    v-if="adaLogin"
                    class="mt-4"
                    :segmen="segmenLogin"
                    label-pusat="Login"
                    aria-label="Porsi login tiap area"
                />
                <p v-else class="mt-6 rounded-2xl bg-amber-50 px-4 py-6 text-sm text-amber-800">
                    Belum ada data login pada periode ini. Login mulai dicatat sejak fitur Analytics aktif,
                    jadi angkanya akan terisi seiring pengguna masuk kembali.
                </p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Rincian per Area</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Diurutkan dari area paling aktif.</p>
                </div>
                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                    {{ ringkasan.area_aktif }} dari {{ ringkasan.total_area }} area aktif
                </span>
            </div>

            <div class="overflow-x-auto px-6 pb-4">
                <table class="w-full min-w-[840px] text-sm">
                    <thead>
                        <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 pr-4">Area</th>
                            <th class="px-4 py-3">Aktivitas</th>
                            <th class="px-4 py-3">Login</th>
                            <th class="px-4 py-3">Pengguna Aktif</th>
                            <th class="px-4 py-3">Terakhir Aktif</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="isLoading">
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-blue-500"></span>
                                    Memuat data area...
                                </span>
                            </td>
                        </tr>
                        <tr v-else-if="!area.length">
                            <td colspan="5" class="py-10 text-center text-sm text-slate-500">Belum ada area terdaftar.</td>
                        </tr>
                        <tr v-else v-for="baris in area" :key="baris.key" class="transition hover:bg-slate-50/70">
                            <td class="py-3 pr-4">
                                <div class="flex items-center gap-3">
                                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: baris.warna }"></span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-900">{{ baris.label }}</p>
                                        <p class="text-xs text-slate-400">{{ baris.kode }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold tabular-nums text-slate-900">{{ formatAngka(baris.aktivitas) }}</p>
                                <div class="mt-1 h-1.5 w-28 overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full transition-all duration-700"
                                        :style="{ width: `${lebarAktivitas(baris)}%`, backgroundColor: baris.warna }"
                                    ></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 tabular-nums text-slate-700">{{ formatAngka(baris.login) }}</td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                    :class="badgeAktif(baris)"
                                >
                                    {{ baris.pengguna_aktif }} / {{ baris.pengguna }} ({{ baris.persen_pengguna_aktif }}%)
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500">{{ baris.terakhir_aktif ?? 'belum ada' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section v-if="segmenAksi.length" class="mt-5 rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Jenis Aktivitas</p>
            <h2 class="mt-2 text-base font-semibold text-slate-900">Komposisi tindakan pengguna</h2>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div
                    v-for="aksi in segmenAksi"
                    :key="aksi.label"
                    class="rounded-2xl border border-slate-100 bg-gradient-to-br from-white to-slate-50 px-4 py-3"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">{{ aksi.label }}</p>
                    <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatAngka(aksi.nilai) }}</p>
                    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100">
                        <div
                            class="h-full rounded-full bg-gradient-to-r from-violet-500 to-sky-400"
                            :style="{ width: `${aksi.persen}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </section>
    </template>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import DonutChart from '../../Components/DonutChart.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Analytics',
                subtitle: 'Keaktifan pengguna seluruh area',
                activeMenu: 'analytics',
            },
            () => page
        ),
});

const opsiPeriode = [
    { hari: 7, label: '7 Hari' },
    { hari: 30, label: '30 Hari' },
    { hari: 90, label: '90 Hari' },
    { hari: 365, label: '1 Tahun' },
];

const hari = ref(30);
const isLoading = ref(false);
const loadError = ref('');
const area = ref([]);
const rincianAksi = ref({});

const periode = reactive({ mulai: '', selesai: '' });
const ringkasan = reactive({
    total_aktivitas: 0,
    total_login: 0,
    pengguna_aktif: 0,
    total_pengguna: 0,
    area_aktif: 0,
    total_area: 0,
});

const formatAngka = (nilai) => new Intl.NumberFormat('id-ID').format(nilai ?? 0);

const segmenAktivitas = computed(() =>
    area.value
        .filter((baris) => baris.aktivitas > 0)
        .map((baris) => ({ key: baris.key, label: baris.label, nilai: baris.aktivitas, warna: baris.warna }))
);

const segmenLogin = computed(() =>
    area.value
        .filter((baris) => baris.login > 0)
        .map((baris) => ({ key: `login-${baris.key}`, label: baris.label, nilai: baris.login, warna: baris.warna }))
);

const adaAktivitas = computed(() => segmenAktivitas.value.length > 0);
const adaLogin = computed(() => segmenLogin.value.length > 0);

const segmenAksi = computed(() => {
    const isi = Object.entries(rincianAksi.value ?? {});
    const total = Math.max(1, isi.reduce((carry, [, nilai]) => carry + Number(nilai), 0));

    return isi.map(([label, nilai]) => ({
        label,
        nilai: Number(nilai),
        persen: Math.max(4, Math.round((Number(nilai) / total) * 100)),
    }));
});

const aktivitasTerbanyak = computed(() =>
    area.value.reduce((carry, baris) => Math.max(carry, Number(baris.aktivitas ?? 0)), 0)
);

const lebarAktivitas = (baris) => {
    if (aktivitasTerbanyak.value <= 0) {
        return 0;
    }

    return Math.max(4, Math.round((Number(baris.aktivitas ?? 0) / aktivitasTerbanyak.value) * 100));
};

const badgeAktif = (baris) => {
    if (baris.pengguna === 0) {
        return 'bg-slate-100 text-slate-500';
    }

    if (baris.persen_pengguna_aktif >= 60) {
        return 'bg-emerald-100 text-emerald-700';
    }

    return baris.persen_pengguna_aktif > 0 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700';
};

const kartuRingkasan = computed(() => [
    {
        key: 'aktivitas',
        label: 'Total Aktivitas',
        nilai: formatAngka(ringkasan.total_aktivitas),
        keterangan: `Seluruh log aktivitas ${hari.value} hari terakhir.`,
        accentClass: 'bg-violet-400',
        gradientClass: 'from-white to-violet-50',
        iconClass: 'bg-violet-50 text-violet-600',
        iconPaths: ['M3 3v18h18', 'm7 14 3-3 3 3 5-5'],
    },
    {
        key: 'login',
        label: 'Total Login',
        nilai: formatAngka(ringkasan.total_login),
        keterangan: 'Jumlah pengguna masuk ke aplikasi.',
        accentClass: 'bg-sky-400',
        gradientClass: 'from-white to-sky-50',
        iconClass: 'bg-sky-50 text-sky-600',
        iconPaths: ['M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4', 'm10 17 5-5-5-5', 'M15 12H3'],
    },
    {
        key: 'pengguna',
        label: 'Pengguna Aktif',
        nilai: `${formatAngka(ringkasan.pengguna_aktif)} / ${formatAngka(ringkasan.total_pengguna)}`,
        keterangan: 'Pengguna yang tercatat melakukan aktivitas.',
        accentClass: 'bg-emerald-400',
        gradientClass: 'from-white to-emerald-50',
        iconClass: 'bg-emerald-50 text-emerald-600',
        iconPaths: ['M16 21v-2a4 4 0 0 0-8 0v2', 'M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z'],
    },
    {
        key: 'area',
        label: 'Area Aktif',
        nilai: `${formatAngka(ringkasan.area_aktif)} / ${formatAngka(ringkasan.total_area)}`,
        keterangan: 'Area yang punya aktivitas pada periode ini.',
        accentClass: 'bg-amber-400',
        gradientClass: 'from-white to-amber-50',
        iconClass: 'bg-amber-50 text-amber-600',
        iconPaths: ['M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z', 'M12 11h.01'],
    },
]);

const loadData = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/analytics', { params: { hari: hari.value } });
        area.value = Array.isArray(response.data?.area) ? response.data.area : [];
        rincianAksi.value = response.data?.rincian_aksi ?? {};
        periode.mulai = response.data?.periode?.mulai ?? '';
        periode.selesai = response.data?.periode?.selesai ?? '';
        Object.keys(ringkasan).forEach((kunci) => {
            ringkasan[kunci] = Number(response.data?.ringkasan?.[kunci] ?? 0);
        });
    } catch (error) {
        area.value = [];
        loadError.value = error?.response?.data?.message ?? 'Data analytics gagal dimuat.';
    } finally {
        isLoading.value = false;
    }
};

const gantiPeriode = (nilai) => {
    if (hari.value === nilai) {
        return;
    }

    hari.value = nilai;
    loadData();
};

onMounted(loadData);
</script>
