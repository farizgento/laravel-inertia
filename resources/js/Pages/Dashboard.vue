<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-[32px] bg-slate-900 px-6 py-7 text-white shadow-2xl shadow-slate-300/50 md:px-8">
            <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-amber-400/20 via-cyan-300/10 to-transparent"></div>
            <div class="absolute -right-16 -top-16 h-40 w-40 rounded-full bg-cyan-300/20 blur-3xl"></div>
            <div class="absolute bottom-0 left-1/3 h-32 w-32 rounded-full bg-amber-300/20 blur-3xl"></div>

            <div class="relative flex flex-wrap items-start justify-between gap-5">
                <div class="max-w-2xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-cyan-200/80">Dashboard Area</p>
                    <h1 class="mt-3 text-3xl font-semibold leading-tight md:text-4xl">Halo, {{ greetingName }}.</h1>
                    <p class="mt-3 text-sm leading-6 text-slate-300">
                        Ringkasan operasional {{ currentAreaName }}. Semua angka mengikuti area yang sedang dipilih.
                    </p>
                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-300">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1">
                            <span class="h-2 w-2 rounded-full" :class="isLoading ? 'animate-pulse bg-amber-300' : 'bg-emerald-400'"></span>
                            {{ isLoading ? 'Memuat data...' : 'Data terbaru' }}
                        </span>
                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1">{{ hariIni }}</span>
                    </div>
                </div>

                <div class="w-full max-w-xs rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-300">Area Aktif</p>
                    <select
                        v-if="canChooseDashboardArea"
                        v-model="selectedAreaId"
                        class="mt-2 h-10 w-full rounded-xl border border-white/10 bg-slate-950/60 px-3 text-sm font-semibold text-white outline-none transition focus:border-cyan-300 focus:ring-2 focus:ring-cyan-300/20"
                        :disabled="isLoadingAreas || isLoading"
                    >
                        <option value="">Pilih area</option>
                        <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                            {{ area.name }}
                        </option>
                    </select>
                    <p v-else class="mt-2 text-lg font-semibold text-white">{{ currentAreaName }}</p>

                    <button
                        type="button"
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20 disabled:opacity-50"
                        :disabled="isLoading"
                        @click="loadDashboard()"
                    >
                        <svg class="h-4 w-4" :class="{ 'animate-spin': isLoading }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 12a9 9 0 1 1-2.6-6.4" />
                            <path d="M21 3v6h-6" />
                        </svg>
                        Muat Ulang
                    </button>
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
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in summaryCards"
                    :key="card.title"
                    class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50 transition hover:-translate-y-0.5 hover:shadow-xl"
                >
                    <span class="absolute inset-x-0 top-0 h-1" :class="card.accentClass"></span>
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">{{ card.title }}</p>
                            <p class="mt-3 text-3xl font-semibold text-slate-900">{{ card.value }}</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl" :class="card.iconClass">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path v-for="(d, index) in card.iconPaths" :key="index" :d="d" />
                            </svg>
                        </span>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-500">{{ card.description }}</p>
                </article>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Alur Peminjaman</p>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900">Posisi transaksi saat ini</h2>
                    </div>
                    <p class="text-xs font-semibold text-slate-500">
                        {{ formatNumber(statusTotal) }} transaksi aktif tercatat
                    </p>
                </div>

                <div class="mt-4 flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
                    <div
                        v-for="item in statusCards"
                        :key="`bar-${item.title}`"
                        class="h-full transition-all duration-700"
                        :class="item.barClass"
                        :style="{ width: `${item.persen}%` }"
                    ></div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div
                        v-for="item in statusCards"
                        :key="item.title"
                        class="rounded-2xl border px-4 py-3"
                        :class="item.wrapperClass"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-semibold" :class="item.titleClass">{{ item.title }}</p>
                            <p class="text-2xl font-semibold" :class="item.valueClass">{{ item.value }}</p>
                        </div>
                        <p class="mt-1 text-xs" :class="item.captionClass">{{ item.description }} &middot; {{ item.persen }}%</p>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Utilisasi Alat</p>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900">Alat yang sering dan jarang dipakai</h2>
                        <p class="mt-1 text-sm text-slate-500">Periode {{ periodeUtilisasi }}</p>
                    </div>

                    <div class="flex flex-col items-end gap-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                v-for="preset in presetRentang"
                                :key="preset.key"
                                type="button"
                                class="rounded-full px-3 py-1 text-xs font-semibold transition"
                                :class="presetAktif === preset.key
                                    ? 'bg-slate-900 text-white'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                @click="terapkanPreset(preset.key)"
                            >
                                {{ preset.label }}
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <input
                                v-model="rentang.mulai"
                                type="date"
                                class="h-9 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                :max="rentang.selesai"
                            />
                            <span class="text-xs text-slate-400">s/d</span>
                            <input
                                v-model="rentang.selesai"
                                type="date"
                                class="h-9 rounded-xl border border-slate-200 px-3 text-xs font-semibold text-slate-700 outline-none transition focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                :min="rentang.mulai"
                            />
                            <button
                                type="button"
                                class="h-9 rounded-xl bg-sky-600 px-4 text-xs font-semibold text-white transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-300"
                                :disabled="isLoading || !rentangValid"
                                @click="terapkanRentang"
                            >
                                Terapkan
                            </button>
                        </div>
                        <p v-if="!rentangValid" class="text-xs font-semibold text-rose-600">
                            Tanggal mulai tidak boleh melewati tanggal selesai.
                        </p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 xl:grid-cols-[300px,1fr]">
                    <div class="rounded-3xl border border-slate-100 bg-slate-50/80 p-5">
                        <div class="flex items-center justify-center">
                            <div class="relative">
                                <svg class="h-40 w-40 -rotate-90" viewBox="0 0 120 120" aria-hidden="true">
                                    <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" stroke-width="14" />
                                    <circle
                                        cx="60"
                                        cy="60"
                                        r="52"
                                        fill="none"
                                        stroke="#0ea5e9"
                                        stroke-width="14"
                                        stroke-linecap="round"
                                        :stroke-dasharray="donutDash"
                                        class="transition-all duration-700"
                                    />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <p class="text-3xl font-semibold text-slate-900">{{ formatPersen(utilRingkasan.persen_utilisasi) }}%</p>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Terpakai</p>
                                </div>
                            </div>
                        </div>

                        <dl class="mt-4 space-y-2 text-sm">
                            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2">
                                <dt class="flex items-center gap-2 text-slate-600">
                                    <span class="h-2.5 w-2.5 rounded-full bg-sky-500"></span>
                                    Jenis alat terpakai
                                </dt>
                                <dd class="font-semibold text-slate-900">{{ formatNumber(utilRingkasan.jenis_terpakai) }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2">
                                <dt class="flex items-center gap-2 text-slate-600">
                                    <span class="h-2.5 w-2.5 rounded-full bg-slate-300"></span>
                                    Belum pernah dipakai
                                </dt>
                                <dd class="font-semibold text-slate-900">{{ formatNumber(utilRingkasan.jenis_tidak_terpakai) }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-white px-3 py-2">
                                <dt class="text-slate-600">Unit dipinjam</dt>
                                <dd class="font-semibold text-slate-900">{{ formatNumber(utilRingkasan.total_unit_dipinjam) }}</dd>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-amber-50 px-3 py-2">
                                <dt class="text-amber-800">Aset menganggur</dt>
                                <dd class="font-semibold text-amber-900">{{ formatNumber(utilRingkasan.aset_menganggur) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="rounded-3xl border border-slate-100 p-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="m3 17 6-6 4 4 7-7" />
                                        <path d="M17 8h4v4" />
                                    </svg>
                                </span>
                                <h3 class="text-sm font-semibold text-slate-900">Paling sering dipakai</h3>
                            </div>

                            <ul v-if="alatPalingSering.length" class="mt-4 space-y-3">
                                <li v-for="item in alatPalingSering" :key="`sering-${item.id}`">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <p class="truncate text-sm font-medium text-slate-700" :title="item.nama">
                                            {{ item.nama }}
                                        </p>
                                        <p class="shrink-0 text-xs font-semibold text-slate-500">
                                            {{ formatNumber(item.frekuensi) }}x &middot; {{ formatNumber(item.total_qty) }} unit
                                        </p>
                                    </div>
                                    <div class="mt-1.5 h-2 rounded-full bg-slate-100">
                                        <div
                                            class="h-2 rounded-full bg-gradient-to-r from-sky-400 to-emerald-400 transition-all duration-700"
                                            :style="{ width: `${frekuensiWidth(item.frekuensi)}%` }"
                                        ></div>
                                    </div>
                                    <p class="mt-1 text-[11px] text-slate-400">
                                        {{ item.kode }} &middot; terakhir {{ item.terakhir_dipinjam ?? '-' }}
                                    </p>
                                </li>
                            </ul>
                            <p v-else class="mt-6 rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada peminjaman pada rentang ini.
                            </p>
                        </div>

                        <div class="rounded-3xl border border-slate-100 p-4">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" />
                                        <path d="M12 7v5l3 2" />
                                    </svg>
                                </span>
                                <h3 class="text-sm font-semibold text-slate-900">Jarang / belum dipakai</h3>
                            </div>

                            <ul v-if="alatJarangDipakai.length" class="mt-4 space-y-2">
                                <li
                                    v-for="item in alatJarangDipakai"
                                    :key="`jarang-${item.id}`"
                                    class="flex items-center justify-between gap-3 rounded-2xl bg-slate-50 px-3 py-2"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-slate-700" :title="item.nama">{{ item.nama }}</p>
                                        <p class="truncate text-[11px] text-slate-400">
                                            {{ item.kode }} &middot; {{ item.jenis_alat ?? '-' }}
                                        </p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                            :class="item.frekuensi > 0 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700'"
                                        >
                                            {{ item.frekuensi > 0 ? `${formatNumber(item.frekuensi)}x dipakai` : 'Belum dipakai' }}
                                        </span>
                                        <p class="mt-1 text-[11px] text-slate-400">{{ formatNumber(item.total_aset) }} aset</p>
                                    </div>
                                </li>
                            </ul>
                            <p v-else class="mt-6 rounded-2xl bg-slate-50 px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada data alat pada area ini.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mt-4 rounded-3xl border border-slate-100 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-slate-900">Tren peminjaman per bulan</h3>
                        <p class="text-xs font-semibold text-slate-500">
                            {{ formatNumber(totalPeminjamanTren) }} transaksi pada periode ini
                        </p>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <div class="min-w-[620px]">
                            <svg viewBox="0 0 620 180" class="h-48 w-full" role="img" aria-label="Grafik tren peminjaman per bulan">
                                <defs>
                                    <linearGradient id="trenGradient" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.35" />
                                        <stop offset="100%" stop-color="#0ea5e9" stop-opacity="0" />
                                    </linearGradient>
                                </defs>
                                <line
                                    v-for="line in [30, 63, 96, 129, 160]"
                                    :key="`grid-${line}`"
                                    x1="20"
                                    x2="600"
                                    :y1="line"
                                    :y2="line"
                                    stroke="#e2e8f0"
                                    stroke-width="1"
                                />
                                <path v-if="trenArea" :d="trenArea" fill="url(#trenGradient)" />
                                <polyline
                                    v-if="trenGaris"
                                    :points="trenGaris"
                                    fill="none"
                                    stroke="#0ea5e9"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                                <g v-for="point in trenKoordinat" :key="`titik-${point.key}`">
                                    <circle :cx="point.x" :cy="point.y" r="4" fill="#ffffff" stroke="#0ea5e9" stroke-width="3">
                                        <title>{{ point.label_panjang }}: {{ point.total }} transaksi</title>
                                    </circle>
                                    <text
                                        v-if="point.total > 0"
                                        :x="point.x"
                                        :y="point.y - 10"
                                        text-anchor="middle"
                                        class="fill-slate-500 text-[10px] font-semibold"
                                    >
                                        {{ point.total }}
                                    </text>
                                </g>
                            </svg>

                            <div class="mt-1 flex justify-between px-4 text-[11px] font-semibold uppercase tracking-[0.15em] text-slate-400">
                                <span v-for="point in trenKoordinat" :key="`label-${point.key}`">{{ point.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="showOperationalInsights" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg shadow-slate-200/50">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-400">Kondisi Alat</p>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900">Kerusakan dan kehilangan per bulan</h2>
                        <p class="mt-1 text-sm text-slate-500">Tahun {{ currentYearLabel }} di {{ currentAreaName }}.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                        <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-amber-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            Kerusakan {{ formatNumber(insights.kerusakan_tahunan) }}
                        </span>
                        <span class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-rose-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span>
                            Kehilangan {{ formatNumber(insights.kehilangan_tahunan) }}
                        </span>
                    </div>
                </div>

                <div class="mt-5 overflow-x-auto">
                    <div class="min-w-[720px]">
                        <div class="flex h-64 items-end gap-3 rounded-3xl border border-slate-100 bg-slate-50 px-4 py-5">
                            <div
                                v-for="item in insights.series"
                                :key="item.key"
                                class="flex min-w-0 flex-1 flex-col items-center gap-3"
                            >
                                <p class="text-[11px] font-semibold text-slate-500">
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
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    {{ item.label }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="mt-4 rounded-2xl bg-slate-900 px-4 py-3 text-sm text-slate-300">
                    Lintas semua area tercatat
                    <span class="font-semibold text-white">{{ formatNumber(insights.total_aset_semua_area) }} aset</span>
                    dari
                    <span class="font-semibold text-white">{{ formatNumber(insights.total_jenis_alat_semua_area) }} jenis alat</span>.
                </p>
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
    utilisasi: {
        periode: { mulai: '', selesai: '', label: '' },
        ringkasan: {
            jenis_alat: 0,
            jenis_terpakai: 0,
            jenis_tidak_terpakai: 0,
            persen_utilisasi: 0,
            total_unit_dipinjam: 0,
            aset_menganggur: 0,
        },
        paling_sering: [],
        jarang_dipakai: [],
        tren_bulanan: [],
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

const toDateInput = (date) => {
    const bulan = String(date.getMonth() + 1).padStart(2, '0');
    const tanggal = String(date.getDate()).padStart(2, '0');
    return `${date.getFullYear()}-${bulan}-${tanggal}`;
};

const tahunBerjalan = new Date().getFullYear();
// Default: satu tahun penuh pada tahun berjalan.
const rentangDefault = {
    mulai: `${tahunBerjalan}-01-01`,
    selesai: `${tahunBerjalan}-12-31`,
};

const rentang = reactive({ ...rentangDefault });
const rentangTerpakai = reactive({ ...rentangDefault });

const presetRentang = [
    { key: 'tahun_ini', label: 'Tahun Ini' },
    { key: '6_bulan', label: '6 Bulan' },
    { key: '30_hari', label: '30 Hari' },
];

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
const currentYearLabel = computed(() => tahunBerjalan);
const hariIni = computed(() =>
    new Intl.DateTimeFormat('id-ID', { dateStyle: 'full' }).format(new Date())
);

const formatNumber = (value) => new Intl.NumberFormat('id-ID').format(value ?? 0);
const formatPersen = (value) =>
    new Intl.NumberFormat('id-ID', { maximumFractionDigits: 1 }).format(value ?? 0);
const normalizeAreaId = (value) => (value === null || value === undefined || value === '' ? '' : String(value));

const rentangValid = computed(
    () => !!rentang.mulai && !!rentang.selesai && rentang.mulai <= rentang.selesai
);

const presetAktif = computed(() => {
    if (rentangTerpakai.mulai === rentangDefault.mulai && rentangTerpakai.selesai === rentangDefault.selesai) {
        return 'tahun_ini';
    }

    const hitung = (bulan, hari) => {
        const akhir = new Date();
        const awal = new Date();
        if (bulan) {
            awal.setMonth(awal.getMonth() - bulan);
        } else {
            awal.setDate(awal.getDate() - hari);
        }
        return { mulai: toDateInput(awal), selesai: toDateInput(akhir) };
    };

    const enamBulan = hitung(6, 0);
    if (rentangTerpakai.mulai === enamBulan.mulai && rentangTerpakai.selesai === enamBulan.selesai) {
        return '6_bulan';
    }

    const tigaPuluhHari = hitung(0, 29);
    if (rentangTerpakai.mulai === tigaPuluhHari.mulai && rentangTerpakai.selesai === tigaPuluhHari.selesai) {
        return '30_hari';
    }

    return 'custom';
});

const summaryCards = computed(() => [
    {
        title: 'Jumlah Peminjaman',
        value: formatNumber(summary.value.total_peminjaman),
        description: 'Akumulasi pengajuan peminjaman untuk area aktif.',
        accentClass: 'bg-sky-400',
        iconClass: 'bg-sky-50 text-sky-600',
        iconPaths: ['M4 7h16', 'M4 12h10', 'M4 17h7'],
    },
    {
        title: 'Total Aset Area',
        value: formatNumber(summary.value.total_aset_area),
        description: 'Total unit aset alat yang tercatat di area ini.',
        accentClass: 'bg-indigo-400',
        iconClass: 'bg-indigo-50 text-indigo-600',
        iconPaths: ['M21 8 12 3 3 8l9 5 9-5Z', 'm3 16 9 5 9-5', 'm3 12 9 5 9-5'],
    },
    {
        title: 'Jenis Alat',
        value: formatNumber(summary.value.total_jenis_alat_area),
        description: 'Banyaknya katalog alat berbeda di area ini.',
        accentClass: 'bg-emerald-400',
        iconClass: 'bg-emerald-50 text-emerald-600',
        iconPaths: ['M4 5h16v14H4z', 'M9 5v14', 'M14 9h4'],
    },
    {
        title: 'Laporan Aktif',
        value: formatNumber(summary.value.laporan_aktif),
        description: 'Laporan kerusakan atau kehilangan yang belum tuntas.',
        accentClass: 'bg-rose-400',
        iconClass: 'bg-rose-50 text-rose-600',
        iconPaths: ['M12 9v4', 'M12 17h.01', 'M10.3 4 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4a2 2 0 0 0-3.4 0Z'],
    },
]);

const statusTotal = computed(
    () => summary.value.menunggu_review + summary.value.sedang_berjalan + summary.value.selesai
);

const persenStatus = (value) => {
    const total = statusTotal.value;
    if (total <= 0) {
        return 0;
    }

    return Math.round((Number(value ?? 0) / total) * 100);
};

const statusCards = computed(() => [
    {
        title: 'Perlu Review',
        value: formatNumber(summary.value.menunggu_review),
        persen: persenStatus(summary.value.menunggu_review),
        description: 'Menunggu tindak lanjut',
        wrapperClass: 'border-amber-200 bg-amber-50/70',
        titleClass: 'text-amber-900',
        captionClass: 'text-amber-700/80',
        valueClass: 'text-amber-900',
        barClass: 'bg-amber-400',
    },
    {
        title: 'Sedang Berjalan',
        value: formatNumber(summary.value.sedang_berjalan),
        persen: persenStatus(summary.value.sedang_berjalan),
        description: 'Disetujui, dikirim, diterima',
        wrapperClass: 'border-blue-200 bg-blue-50/70',
        titleClass: 'text-blue-900',
        captionClass: 'text-blue-700/80',
        valueClass: 'text-blue-900',
        barClass: 'bg-blue-500',
    },
    {
        title: 'Selesai',
        value: formatNumber(summary.value.selesai),
        persen: persenStatus(summary.value.selesai),
        description: 'Alat sudah kembali',
        wrapperClass: 'border-emerald-200 bg-emerald-50/70',
        titleClass: 'text-emerald-900',
        captionClass: 'text-emerald-700/80',
        valueClass: 'text-emerald-900',
        barClass: 'bg-emerald-500',
    },
]);

const utilisasi = computed(() => dashboard.utilisasi);
const utilRingkasan = computed(() => utilisasi.value.ringkasan);
const alatPalingSering = computed(() => utilisasi.value.paling_sering);
const alatJarangDipakai = computed(() => utilisasi.value.jarang_dipakai);
const trenBulanan = computed(() => utilisasi.value.tren_bulanan);
const periodeUtilisasi = computed(() => utilisasi.value.periode.label || 'Tahun berjalan');

const maxFrekuensi = computed(() =>
    alatPalingSering.value.reduce((carry, item) => Math.max(carry, Number(item?.frekuensi ?? 0)), 0)
);

const frekuensiWidth = (value) => {
    const max = maxFrekuensi.value;
    if (max <= 0) {
        return 0;
    }

    return Math.max(8, Math.round((Number(value ?? 0) / max) * 100));
};

// Donut utilisasi: lingkaran r=52 sehingga keliling ~326.7.
const DONUT_CIRCUMFERENCE = 2 * Math.PI * 52;
const donutDash = computed(() => {
    const persen = Math.min(100, Math.max(0, Number(utilRingkasan.value.persen_utilisasi ?? 0)));
    const terpakai = (persen / 100) * DONUT_CIRCUMFERENCE;
    return `${terpakai} ${DONUT_CIRCUMFERENCE - terpakai}`;
});

const trenMax = computed(() =>
    trenBulanan.value.reduce((carry, item) => Math.max(carry, Number(item?.total ?? 0)), 0)
);

// Koordinat grafik garis pada viewBox 0 0 620 180.
const trenKoordinat = computed(() => {
    const rows = trenBulanan.value;
    if (!rows.length) {
        return [];
    }

    const max = Math.max(1, trenMax.value);
    const step = rows.length > 1 ? 580 / (rows.length - 1) : 0;

    return rows.map((item, index) => ({
        ...item,
        x: rows.length > 1 ? 20 + index * step : 310,
        y: 160 - (Number(item?.total ?? 0) / max) * 130,
    }));
});

const trenGaris = computed(() => trenKoordinat.value.map((point) => `${point.x},${point.y}`).join(' '));
const trenArea = computed(() => {
    const points = trenKoordinat.value;
    if (points.length < 2) {
        return '';
    }

    const garis = points.map((point) => `L ${point.x} ${point.y}`).join(' ');
    return `M ${points[0].x} 160 ${garis} L ${points[points.length - 1].x} 160 Z`;
});

const totalPeminjamanTren = computed(() =>
    trenBulanan.value.reduce((carry, item) => carry + Number(item?.total ?? 0), 0)
);

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
    dashboard.utilisasi = {
        periode: {
            mulai: payload?.utilisasi?.periode?.mulai ?? '',
            selesai: payload?.utilisasi?.periode?.selesai ?? '',
            label: payload?.utilisasi?.periode?.label ?? '',
        },
        ringkasan: {
            jenis_alat: Number(payload?.utilisasi?.ringkasan?.jenis_alat ?? 0),
            jenis_terpakai: Number(payload?.utilisasi?.ringkasan?.jenis_terpakai ?? 0),
            jenis_tidak_terpakai: Number(payload?.utilisasi?.ringkasan?.jenis_tidak_terpakai ?? 0),
            persen_utilisasi: Number(payload?.utilisasi?.ringkasan?.persen_utilisasi ?? 0),
            total_unit_dipinjam: Number(payload?.utilisasi?.ringkasan?.total_unit_dipinjam ?? 0),
            aset_menganggur: Number(payload?.utilisasi?.ringkasan?.aset_menganggur ?? 0),
        },
        paling_sering: Array.isArray(payload?.utilisasi?.paling_sering) ? payload.utilisasi.paling_sering : [],
        jarang_dipakai: Array.isArray(payload?.utilisasi?.jarang_dipakai) ? payload.utilisasi.jarang_dipakai : [],
        tren_bulanan: Array.isArray(payload?.utilisasi?.tren_bulanan) ? payload.utilisasi.tren_bulanan : [],
    };
    dashboard.meta = {
        role_key: payload?.meta?.role_key ?? roleKey.value,
        can_choose_dashboard_area: !!payload?.meta?.can_choose_dashboard_area,
        show_operational_insights: !!payload?.meta?.show_operational_insights,
    };

    if (payload?.utilisasi?.periode?.mulai && payload?.utilisasi?.periode?.selesai) {
        rentangTerpakai.mulai = payload.utilisasi.periode.mulai;
        rentangTerpakai.selesai = payload.utilisasi.periode.selesai;
    }
};

const buildParams = () => {
    const params = {
        mulai: rentangTerpakai.mulai,
        selesai: rentangTerpakai.selesai,
    };

    if (canChooseDashboardArea.value && selectedAreaId.value) {
        params.area_id = selectedAreaId.value;
    } else if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }

    return params;
};

const terapkanRentang = () => {
    if (!rentangValid.value) {
        return;
    }

    rentangTerpakai.mulai = rentang.mulai;
    rentangTerpakai.selesai = rentang.selesai;
    loadDashboard();
};

const terapkanPreset = (key) => {
    const akhir = new Date();
    const awal = new Date();

    if (key === 'tahun_ini') {
        rentang.mulai = rentangDefault.mulai;
        rentang.selesai = rentangDefault.selesai;
    } else if (key === '6_bulan') {
        awal.setMonth(awal.getMonth() - 6);
        rentang.mulai = toDateInput(awal);
        rentang.selesai = toDateInput(akhir);
    } else {
        awal.setDate(awal.getDate() - 29);
        rentang.mulai = toDateInput(awal);
        rentang.selesai = toDateInput(akhir);
    }

    terapkanRentang();
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
