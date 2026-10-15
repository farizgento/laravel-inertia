<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <section class="relative mb-5 overflow-hidden rounded-[28px] bg-slate-900 px-6 py-6 text-white shadow-2xl shadow-slate-300/50">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-gradient-to-l from-amber-400/20 via-sky-300/10 to-transparent"></div>
        <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full bg-amber-300/20 blur-3xl"></div>

        <div class="relative flex flex-wrap items-start justify-between gap-5">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.35em] text-amber-200/80">Master Data</p>
                <h1 class="mt-2 text-2xl font-semibold md:text-3xl">Pekerjaan Overhaul</h1>
                <p class="mt-2 max-w-xl text-sm leading-6 text-slate-300">
                    Jadwal overhaul beserta lama pengerjaan dan site coordinator-nya.
                </p>
            </div>
            <button
                class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-500 to-indigo-500 px-5 text-sm font-semibold text-white shadow-lg shadow-blue-900/30 transition hover:from-blue-600 hover:to-indigo-600 sm:w-auto"
                type="button"
                @click="openCreate"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Tambah Pekerjaan
            </button>
        </div>
    </section>

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

    <section class="rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Next Overhaul</h2>
                <p class="mt-0.5 text-xs text-slate-500">Diurutkan dari jadwal mulai terdekat.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                {{ pagination.total }} pekerjaan
            </span>
        </div>

        <div class="grid gap-3 px-6 py-4 md:grid-cols-[1fr,220px]">
            <label class="relative block">
                <span class="sr-only">Cari pekerjaan</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari project, jenis, atau site coordinator..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
            <select
                v-model="jenisFilter"
                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
                <option value="">Jenis - Semua</option>
                <option v-for="jenis in jenisOptions" :key="jenis" :value="jenis">{{ jenis }}</option>
            </select>
        </div>

        <div class="overflow-x-auto px-6 pb-2">
            <table class="w-full min-w-[1100px] text-sm">
                <thead>
                    <tr class="bg-rose-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-600">
                        <th class="rounded-l-lg px-4 py-3">Jenis</th>
                        <th class="px-4 py-3">Project</th>
                        <th class="px-4 py-3">Lama OH</th>
                        <th class="px-4 py-3">Site Coordinator</th>
                        <th class="px-4 py-3">Start Date</th>
                        <th class="px-4 py-3">End Date</th>
                        <th class="px-4 py-3">Created By</th>
                        <th class="px-4 py-3">Created Date</th>
                        <th class="rounded-r-lg px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="isLoading">
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">
                            <span class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-blue-500"></span>
                                Memuat data pekerjaan...
                            </span>
                        </td>
                    </tr>
                    <tr v-else-if="loadError">
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-rose-500">{{ loadError }}</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td colspan="9" class="px-4 py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="5" width="18" height="16" rx="2" />
                                    <path d="M8 3v4M16 3v4M3 11h18" />
                                </svg>
                            </div>
                            <p class="mt-3 text-sm font-semibold text-slate-700">Belum ada pekerjaan overhaul</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ search || jenisFilter ? 'Coba ubah kata kunci atau filter.' : 'Tambahkan pekerjaan pertama lewat tombol di atas.' }}
                            </p>
                        </td>
                    </tr>
                    <tr v-else v-for="row in rows" :key="row.id" class="transition hover:bg-slate-50/70">
                        <td class="px-4 py-3">
                            <span
                                v-if="row.jenis"
                                class="rounded-full px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide"
                                :class="jenisBadge(row.jenis)"
                            >
                                {{ row.jenis }}
                            </span>
                            <span v-else class="text-slate-400">-</span>
                        </td>
                        <td class="max-w-[22rem] px-4 py-3">
                            <button
                                class="block max-w-full truncate text-left font-semibold text-blue-600 transition hover:text-blue-800 hover:underline"
                                type="button"
                                :title="row.nama"
                                @click="openEdit(row)"
                            >
                                {{ row.nama }}
                            </button>
                            <span
                                class="mt-1 inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="tahapJadwal(row).badgeClass"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="tahapJadwal(row).dotClass"></span>
                                {{ tahapJadwal(row).label }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold tabular-nums text-slate-900">{{ row.lama_oh ?? '-' }}</p>
                            <p class="text-[11px] text-slate-400">hari</p>
                        </td>
                        <td class="px-4 py-3 text-slate-700">
                            <span v-if="row.siteco" class="inline-flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-semibold text-slate-600">
                                    {{ inisial(row.siteco) }}
                                </span>
                                <span class="truncate">{{ row.siteco }}</span>
                            </span>
                            <span v-else class="text-slate-300">belum ditentukan</span>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ row.tanggal_mulai ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <p class="text-slate-700">{{ row.tanggal_selesai ?? '-' }}</p>
                            <div v-if="row.lama_oh" class="mt-1.5 h-1.5 w-28 overflow-hidden rounded-full bg-slate-100">
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="tahapJadwal(row).barClass"
                                    :style="{ width: `${tahapJadwal(row).progres}%` }"
                                ></div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ row.created_by_nama }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ row.created_at ?? '-' }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <button
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-blue-300 hover:text-blue-600"
                                    type="button"
                                    title="Ubah"
                                    @click="openEdit(row)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M12 20h9" />
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                    </svg>
                                </button>
                                <button
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600 transition hover:bg-rose-100"
                                    type="button"
                                    title="Hapus"
                                    @click="deleteTarget = row"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4h8v2" />
                                        <path d="M19 6l-1 14H6L5 6" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination.lastPage > 1" class="flex flex-wrap items-center justify-between gap-3 px-6 py-4">
            <p class="text-xs text-slate-500">
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} &middot; {{ pagination.total }} pekerjaan
            </p>
            <div class="inline-flex items-center gap-1">
                <button
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 disabled:opacity-50"
                    type="button"
                    :disabled="pagination.currentPage <= 1"
                    @click="goToPage(pagination.currentPage - 1)"
                >
                    Sebelumnya
                </button>
                <button
                    v-for="nomor in pageNumbers"
                    :key="nomor"
                    class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                    :class="nomor === pagination.currentPage
                        ? 'border-blue-500 bg-blue-50 text-blue-700'
                        : 'border-slate-200 text-slate-600 hover:border-blue-200'"
                    type="button"
                    @click="goToPage(nomor)"
                >
                    {{ nomor }}
                </button>
                <button
                    class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-blue-200 disabled:opacity-50"
                    type="button"
                    :disabled="pagination.currentPage >= pagination.lastPage"
                    @click="goToPage(pagination.currentPage + 1)"
                >
                    Berikutnya
                </button>
            </div>
        </div>
    </section>

    <teleport to="body">
        <div v-if="formOpen" class="fixed inset-0 z-50 bg-slate-900/50">
            <div class="flex min-h-full items-center justify-center overflow-y-auto p-4" @click.self="closeForm">
                <div class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                    <h3 class="text-lg font-semibold text-slate-900">
                        {{ form.id ? 'Ubah Pekerjaan Overhaul' : 'Tambah Pekerjaan Overhaul' }}
                    </h3>
                    <p class="mt-1 text-sm text-slate-500">Lama OH dihitung otomatis dari tanggal mulai dan selesai.</p>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                            <span>Project *</span>
                            <input
                                v-model="form.nama"
                                type="text"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Contoh: ME PLTU 1 UBP ADIPALA"
                            />
                            <p v-if="formErrors.nama" class="text-xs font-semibold text-rose-500">{{ formErrors.nama }}</p>
                        </label>

                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Jenis</span>
                            <input
                                v-model="form.jenis"
                                type="text"
                                list="daftar-jenis-oh"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Contoh: STANDAR"
                            />
                            <datalist id="daftar-jenis-oh">
                                <option v-for="jenis in jenisOptions" :key="`opsi-${jenis}`" :value="jenis" />
                            </datalist>
                            <p v-if="formErrors.jenis" class="text-xs font-semibold text-rose-500">{{ formErrors.jenis }}</p>
                        </label>

                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Site Coordinator</span>
                            <select
                                v-model="form.siteco"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                <option value="">Belum ditentukan</option>
                                <option v-for="orang in pilihanSiteco" :key="orang" :value="orang">{{ orang }}</option>
                            </select>
                            <p v-if="!penggunaArea.length" class="text-xs text-slate-400">
                                Belum ada pengguna terdaftar pada area ini.
                            </p>
                            <p v-if="formErrors.siteco" class="text-xs font-semibold text-rose-500">{{ formErrors.siteco }}</p>
                        </label>

                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Start Date *</span>
                            <input
                                v-model="form.tanggal_mulai"
                                type="date"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                            <p v-if="formErrors.tanggal_mulai" class="text-xs font-semibold text-rose-500">{{ formErrors.tanggal_mulai }}</p>
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>End Date</span>
                            <input
                                v-model="form.tanggal_selesai"
                                type="date"
                                :min="form.tanggal_mulai || undefined"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                            <p v-if="formErrors.tanggal_selesai" class="text-xs font-semibold text-rose-500">{{ formErrors.tanggal_selesai }}</p>
                        </label>

                        <div v-if="lamaOhPreview" class="md:col-span-2 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            Lama OH: <span class="font-semibold text-slate-900">{{ lamaOhPreview }} hari</span>
                        </div>
                    </div>

                    <p v-if="formError" class="mt-3 text-sm font-semibold text-rose-500">{{ formError }}</p>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                            type="button"
                            @click="closeForm"
                        >
                            Batal
                        </button>
                        <button
                            class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:bg-blue-300"
                            type="button"
                            :disabled="isSubmitting"
                            @click="submitForm"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>

    <teleport to="body">
        <div v-if="deleteTarget" class="fixed inset-0 z-50 bg-slate-900/50">
            <div class="flex min-h-full items-center justify-center p-4" @click.self="deleteTarget = null">
                <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                    <h3 class="text-lg font-semibold text-slate-900">Hapus Pekerjaan</h3>
                    <p class="mt-2 text-sm text-slate-600">
                        Yakin menghapus <span class="font-semibold">{{ deleteTarget?.nama }}</span>?
                    </p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                            type="button"
                            @click="deleteTarget = null"
                        >
                            Batal
                        </button>
                        <button
                            class="rounded-xl bg-rose-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:bg-rose-300"
                            type="button"
                            :disabled="isDeleting"
                            @click="confirmDelete"
                        >
                            {{ isDeleting ? 'Menghapus...' : 'Hapus' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Pekerjaan Overhaul',
                subtitle: 'Daftar pekerjaan overhaul',
                activeMenu: 'pekerjaan-overhaul',
            },
            () => page
        ),
});

const rows = ref([]);
const jenisOptions = ref([]);
const penggunaArea = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const search = ref('');
const jenisFilter = ref('');
const formOpen = ref(false);
const isSubmitting = ref(false);
const isDeleting = ref(false);
const deleteTarget = ref(null);
const formError = ref('');
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

const ringkasan = reactive({
    total: 0,
    akan_datang: 0,
    berlangsung: 0,
    selesai: 0,
});

const formatAngka = (nilai) => new Intl.NumberFormat('id-ID').format(nilai ?? 0);

const form = reactive({
    id: null,
    jenis: 'STANDAR',
    nama: '',
    siteco: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
});

const formErrors = reactive({
    jenis: '',
    nama: '',
    siteco: '',
    tanggal_mulai: '',
    tanggal_selesai: '',
});

const kartuRingkasan = computed(() => [
    {
        key: 'total',
        label: 'Total Pekerjaan',
        nilai: formatAngka(ringkasan.total),
        keterangan: 'Seluruh pekerjaan sesuai filter saat ini.',
        accentClass: 'bg-slate-400',
        gradientClass: 'from-white to-slate-50',
        iconClass: 'bg-slate-100 text-slate-600',
        iconPaths: ['M4 6h16', 'M4 12h16', 'M4 18h10'],
    },
    {
        key: 'akan_datang',
        label: 'Akan Datang',
        nilai: formatAngka(ringkasan.akan_datang),
        keterangan: 'Jadwal mulainya masih di depan.',
        accentClass: 'bg-sky-400',
        gradientClass: 'from-white to-sky-50',
        iconClass: 'bg-sky-50 text-sky-600',
        iconPaths: ['M12 7v5l3 2', 'M12 3a9 9 0 1 0 9 9'],
    },
    {
        key: 'berlangsung',
        label: 'Berlangsung',
        nilai: formatAngka(ringkasan.berlangsung),
        keterangan: 'Sedang dikerjakan hari ini.',
        accentClass: 'bg-amber-400',
        gradientClass: 'from-white to-amber-50',
        iconClass: 'bg-amber-50 text-amber-600',
        iconPaths: ['M12 2v4', 'm16.2 7.8 2.9-2.9', 'M18 12h4', 'M12 18v4', 'M2 12h4', 'm4.9 4.9 2.9 2.9'],
    },
    {
        key: 'selesai',
        label: 'Selesai',
        nilai: formatAngka(ringkasan.selesai),
        keterangan: 'Tanggal selesainya sudah lewat.',
        accentClass: 'bg-emerald-400',
        gradientClass: 'from-white to-emerald-50',
        iconClass: 'bg-emerald-50 text-emerald-600',
        iconPaths: ['m5 13 4 4L19 7'],
    },
]);

const inisial = (nama) =>
    String(nama ?? '')
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((kata) => kata.charAt(0).toUpperCase())
        .join('') || '?';

// Warna chip jenis dipilih dari nama jenisnya supaya tiap jenis konsisten
// warnanya tanpa perlu daftar warna yang dikelola manual.
const paletJenis = [
    'bg-indigo-100 text-indigo-700',
    'bg-sky-100 text-sky-700',
    'bg-emerald-100 text-emerald-700',
    'bg-amber-100 text-amber-700',
    'bg-fuchsia-100 text-fuchsia-700',
    'bg-teal-100 text-teal-700',
];

const jenisBadge = (jenis) => {
    const teks = String(jenis ?? '');
    let jumlah = 0;
    for (let i = 0; i < teks.length; i += 1) {
        jumlah += teks.charCodeAt(i);
    }

    return paletJenis[jumlah % paletJenis.length];
};

// Tahap jadwal dihitung dari tanggal: belum mulai, sedang berjalan, atau selesai.
const tahapJadwal = (row) => {
    const hariIni = new Date();
    hariIni.setHours(0, 0, 0, 0);
    const mulai = row.tanggal_mulai_iso ? new Date(row.tanggal_mulai_iso) : null;
    const selesai = row.tanggal_selesai_iso ? new Date(row.tanggal_selesai_iso) : null;

    if (mulai && mulai > hariIni) {
        const sisa = Math.ceil((mulai - hariIni) / 86400000);
        return {
            label: `Mulai ${sisa} hari lagi`,
            badgeClass: 'bg-sky-50 text-sky-700',
            dotClass: 'bg-sky-500',
            barClass: 'bg-sky-400',
            progres: 0,
        };
    }

    if (selesai && selesai < hariIni) {
        return {
            label: 'Selesai',
            badgeClass: 'bg-emerald-50 text-emerald-700',
            dotClass: 'bg-emerald-500',
            barClass: 'bg-emerald-500',
            progres: 100,
        };
    }

    let progres = 50;
    let label = 'Berlangsung';
    if (mulai && selesai && selesai >= mulai) {
        const total = Math.round((selesai - mulai) / 86400000) + 1;
        const berjalan = Math.round((hariIni - mulai) / 86400000) + 1;
        progres = Math.min(100, Math.max(5, Math.round((berjalan / total) * 100)));
        label = `Hari ke-${berjalan} dari ${total}`;
    }

    return {
        label,
        badgeClass: 'bg-amber-50 text-amber-700',
        dotClass: 'bg-amber-500',
        barClass: 'bg-gradient-to-r from-amber-500 to-amber-300',
        progres,
    };
};

const pageNumbers = computed(() => {
    const start = Math.max(1, pagination.currentPage - 2);
    const end = Math.min(pagination.lastPage, pagination.currentPage + 2);
    const pages = [];

    for (let i = start; i <= end; i += 1) {
        pages.push(i);
    }

    return pages;
});

// Lama OH inklusif, sama seperti perhitungan di server.
const lamaOhPreview = computed(() => {
    if (!form.tanggal_mulai || !form.tanggal_selesai) {
        return 0;
    }

    const selisih = Math.round(
        (new Date(form.tanggal_selesai) - new Date(form.tanggal_mulai)) / 86400000
    ) + 1;

    return selisih > 0 ? selisih : 0;
});

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

const loadRows = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/pekerjaan-ohs', {
            params: {
                page: pagination.currentPage,
                per_page: pagination.perPage,
                search: search.value.trim() || undefined,
                jenis: jenisFilter.value || undefined,
            },
        });
        rows.value = Array.isArray(response.data?.data) ? response.data.data : [];
        jenisOptions.value = Array.isArray(response.data?.jenis_tersedia) ? response.data.jenis_tersedia : [];
        const meta = response.data?.meta ?? {};
        pagination.currentPage = Number(meta.current_page ?? 1);
        pagination.lastPage = Number(meta.last_page ?? 1);
        pagination.total = Number(meta.total ?? rows.value.length);
        const hasil = response.data?.ringkasan ?? {};
        ringkasan.total = Number(hasil.total ?? pagination.total);
        ringkasan.akan_datang = Number(hasil.akan_datang ?? 0);
        ringkasan.berlangsung = Number(hasil.berlangsung ?? 0);
        ringkasan.selesai = Number(hasil.selesai ?? 0);
    } catch (error) {
        rows.value = [];
        loadError.value = error?.response?.data?.message ?? 'Gagal memuat pekerjaan overhaul.';
    } finally {
        isLoading.value = false;
    }
};

const loadPenggunaArea = async () => {
    try {
        const response = await axios.get('/api/pekerjaan-ohs/pengguna-area');
        penggunaArea.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        penggunaArea.value = [];
    }
};

// Nama site coordinator yang tersimpan tapi sudah tidak ada di daftar pengguna
// tetap ditampilkan supaya data lama tidak hilang saat diubah.
const pilihanSiteco = computed(() => {
    const daftar = penggunaArea.value.map((orang) => orang.nama).filter(Boolean);
    const tersimpan = (form.siteco ?? '').trim();

    if (tersimpan && !daftar.includes(tersimpan)) {
        return [tersimpan, ...daftar];
    }

    return daftar;
});

const goToPage = (nomor) => {
    const next = Math.min(Math.max(1, nomor), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }

    pagination.currentPage = next;
    loadRows();
};

const resetFormErrors = () => {
    formError.value = '';
    Object.keys(formErrors).forEach((key) => {
        formErrors[key] = '';
    });
};

const resetForm = () => {
    form.id = null;
    form.jenis = 'STANDAR';
    form.nama = '';
    form.siteco = '';
    form.tanggal_mulai = '';
    form.tanggal_selesai = '';
    resetFormErrors();
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const openEdit = (row) => {
    resetForm();
    form.id = row.id;
    form.jenis = row.jenis ?? '';
    form.nama = row.nama;
    form.siteco = row.siteco ?? '';
    form.tanggal_mulai = row.tanggal_mulai_iso ?? '';
    form.tanggal_selesai = row.tanggal_selesai_iso ?? '';
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const submitForm = async () => {
    resetFormErrors();

    if (!form.nama.trim()) {
        formErrors.nama = 'Project wajib diisi.';
        return;
    }
    if (!form.tanggal_mulai) {
        formErrors.tanggal_mulai = 'Start date wajib diisi.';
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            jenis: form.jenis.trim() || null,
            nama: form.nama.trim(),
            siteco: form.siteco.trim() || null,
            tanggal_mulai: form.tanggal_mulai,
            tanggal_selesai: form.tanggal_selesai || null,
        };

        const response = form.id
            ? await axios.put(`/api/pekerjaan-ohs/${form.id}`, payload)
            : await axios.post('/api/pekerjaan-ohs', payload);

        await loadRows();
        closeForm();
        showAlert('success', response.data?.message ?? 'Pekerjaan overhaul berhasil disimpan.');
    } catch (error) {
        const errors = error?.response?.data?.errors ?? {};
        Object.keys(formErrors).forEach((key) => {
            formErrors[key] = errors[key]?.[0] ?? '';
        });
        formError.value = error?.response?.data?.message ?? 'Gagal menyimpan pekerjaan overhaul.';
    } finally {
        isSubmitting.value = false;
    }
};

const confirmDelete = async () => {
    if (!deleteTarget.value?.id) {
        return;
    }

    isDeleting.value = true;
    try {
        const response = await axios.delete(`/api/pekerjaan-ohs/${deleteTarget.value.id}`);
        deleteTarget.value = null;
        await loadRows();
        showAlert('success', response.data?.message ?? 'Pekerjaan overhaul berhasil dihapus.');
    } catch (error) {
        showAlert('error', error?.response?.data?.message ?? 'Gagal menghapus pekerjaan overhaul.');
    } finally {
        isDeleting.value = false;
    }
};

watch([search, jenisFilter], () => {
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }

    filterTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        loadRows();
    }, 300);
});

onMounted(() => {
    loadRows();
    loadPenggunaArea();
});
</script>
