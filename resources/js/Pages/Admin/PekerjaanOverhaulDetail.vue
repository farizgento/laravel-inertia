<template>
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <button
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 transition hover:text-blue-600"
                type="button"
                @click="kembali"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                Kembali ke daftar
            </button>
            <h1 class="mt-2 truncate text-2xl font-semibold text-slate-900">
                {{ pekerjaan?.nama ?? 'Memuat...' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ periode }}
                <span v-if="pekerjaan?.lama_oh"> &middot; {{ pekerjaan.lama_oh }} hari</span>
                <span v-if="pekerjaan?.area_nama"> &middot; Area {{ pekerjaan.area_nama }}</span>
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span
                v-if="pekerjaan?.jenis"
                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-slate-600"
            >
                {{ pekerjaan.jenis }}
            </span>
            <span
                v-if="pekerjaan?.status"
                class="rounded-full px-3 py-1 text-xs font-semibold"
                :class="statusPekerjaanBadge(pekerjaan.status)"
            >
                {{ labelStatusPekerjaan(pekerjaan.status) }}
            </span>
        </div>
    </div>

    <section class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article
            v-for="kartu in kartuRingkasan"
            :key="kartu.label"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50"
        >
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ kartu.label }}</p>
            <p class="mt-2 text-2xl font-semibold tabular-nums" :class="kartu.warna ?? 'text-slate-900'">
                {{ kartu.nilai }}
            </p>
            <p class="mt-1 text-xs text-slate-400">{{ kartu.keterangan }}</p>
        </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Peminjaman pada pekerjaan ini</h2>
                <p class="mt-1 text-sm text-slate-500">
                    Klik satu peminjaman untuk melihat daftar alat beserta laporan kerusakannya.
                </p>
            </div>
            <div v-if="areaTerlibat.length" class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Area terlibat</span>
                <span
                    v-for="area in areaTerlibat"
                    :key="area"
                    class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700"
                >
                    {{ area }}
                </span>
            </div>
        </div>

        <p v-if="isLoading" class="py-10 text-center text-sm text-slate-500">Memuat detail pekerjaan...</p>
        <p v-else-if="loadError" class="mt-5 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-600">
            {{ loadError }}
        </p>
        <div
            v-else-if="!peminjamans.length"
            class="mt-5 rounded-2xl border border-dashed border-slate-300 px-4 py-12 text-center"
        >
            <p class="text-sm font-semibold text-slate-600">Belum ada peminjaman</p>
            <p class="mt-1 text-xs text-slate-400">Pekerjaan ini belum dipakai pada peminjaman mana pun.</p>
        </div>

        <div v-else class="mt-5 space-y-3">
            <article
                v-for="peminjaman in peminjamans"
                :key="peminjaman.id"
                class="overflow-hidden rounded-2xl border transition"
                :class="terbuka === peminjaman.id
                    ? 'border-blue-300 bg-blue-50/40 shadow-sm'
                    : 'border-slate-200 bg-white hover:border-blue-200'"
            >
                <button
                    class="flex w-full items-center gap-3 px-4 py-3 text-left"
                    type="button"
                    @click="toggle(peminjaman.id)"
                >
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-900 text-xs font-bold text-white">
                        #{{ peminjaman.id }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-sm font-semibold text-slate-900">{{ peminjaman.peminjam }}</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="statusPeminjamanBadge(peminjaman.status)"
                            >
                                {{ peminjaman.status }}
                            </span>
                            <span
                                v-if="peminjaman.total_kerusakan"
                                class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-600"
                            >
                                {{ peminjaman.total_kerusakan }} rusak
                            </span>
                            <span
                                v-if="peminjaman.total_kehilangan"
                                class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                            >
                                {{ peminjaman.total_kehilangan }} hilang
                            </span>
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-slate-500">
                            {{ peminjaman.area_peminjam ?? peminjaman.area ?? '-' }}
                            &middot; {{ peminjaman.tanggal_pinjam ?? '-' }} s/d {{ peminjaman.tanggal_kembali ?? '-' }}
                            <span v-if="peminjaman.bidang"> &middot; {{ peminjaman.bidang }}</span>
                        </span>
                    </span>
                    <span class="hidden shrink-0 text-right sm:block">
                        <span class="block text-sm font-semibold tabular-nums text-slate-900">
                            {{ peminjaman.total_disetujui }}
                        </span>
                        <span class="block text-[11px] text-slate-400">alat disetujui</span>
                    </span>
                    <svg
                        class="h-4 w-4 shrink-0 text-slate-400 transition"
                        :class="terbuka === peminjaman.id ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                    >
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>

                <div v-if="terbuka === peminjaman.id" class="border-t border-blue-200/70 bg-white px-4 py-4">
                    <div v-if="!peminjaman.items?.length" class="py-4 text-center text-xs text-slate-400">
                        Tidak ada alat pada peminjaman ini.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="alat in peminjaman.items"
                            :key="alat.id"
                            class="rounded-xl border border-slate-200"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ alat.nama }}</p>
                                    <p class="mt-0.5 font-mono text-[11px] text-slate-500">
                                        {{ alat.kode ?? '-' }}
                                        <span v-if="alat.jenis" class="font-sans"> &middot; {{ alat.jenis }}</span>
                                    </p>
                                </div>
                                <div class="flex shrink-0 items-center gap-4 text-center">
                                    <div>
                                        <p class="text-sm font-semibold tabular-nums text-slate-900">{{ alat.qty_disetujui }}</p>
                                        <p class="text-[11px] text-slate-400">disetujui</p>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-semibold tabular-nums"
                                            :class="alat.jumlah_kerusakan ? 'text-rose-600' : 'text-slate-300'"
                                        >
                                            {{ alat.jumlah_kerusakan }}
                                        </p>
                                        <p class="text-[11px] text-slate-400">rusak</p>
                                    </div>
                                    <div>
                                        <p
                                            class="text-sm font-semibold tabular-nums"
                                            :class="alat.jumlah_kehilangan ? 'text-amber-600' : 'text-slate-300'"
                                        >
                                            {{ alat.jumlah_kehilangan }}
                                        </p>
                                        <p class="text-[11px] text-slate-400">hilang</p>
                                    </div>
                                </div>
                            </div>

                            <div v-if="alat.laporan?.length" class="space-y-2 border-t border-slate-100 bg-slate-50/70 px-4 py-3">
                                <div
                                    v-for="laporan in alat.laporan"
                                    :key="laporan.id"
                                    class="rounded-lg bg-white px-3 py-2.5 shadow-sm ring-1 ring-slate-200"
                                >
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide"
                                            :class="laporan.kategori === 'kerusakan'
                                                ? 'bg-rose-50 text-rose-600'
                                                : 'bg-amber-50 text-amber-700'"
                                        >
                                            {{ laporan.kategori }}
                                        </span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                            :class="statusLaporanBadge(laporan.status)"
                                        >
                                            {{ laporan.status }}
                                        </span>
                                        <span class="text-[11px] font-semibold tabular-nums text-slate-600">
                                            {{ laporan.jumlah }} unit
                                        </span>
                                        <span class="ml-auto text-[11px] text-slate-400">
                                            {{ laporan.tanggal ?? '-' }}
                                            <template v-if="laporan.pelapor"> &middot; {{ laporan.pelapor }}</template>
                                        </span>
                                    </div>
                                    <p v-if="laporan.deskripsi" class="mt-1.5 text-xs leading-5 text-slate-600">
                                        {{ laporan.deskripsi }}
                                    </p>
                                </div>
                            </div>
                            <p v-else class="border-t border-slate-100 px-4 py-2.5 text-[11px] text-slate-400">
                                Tidak ada laporan kerusakan maupun kehilangan untuk alat ini.
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>
</template>

<script setup>
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    pekerjaanId: {
        type: [Number, String],
        required: true,
    },
});

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Detail Pekerjaan',
                subtitle: 'Peminjaman dan laporan alat pada satu pekerjaan',
                activeMenu: 'pekerjaan-overhaul',
            },
            () => page
        ),
});

const data = ref(null);
const isLoading = ref(false);
const loadError = ref('');
const terbuka = ref(null);

const pekerjaan = computed(() => data.value?.pekerjaan ?? null);
const peminjamans = computed(() => data.value?.peminjamans ?? []);
const ringkasan = computed(() => data.value?.ringkasan ?? {});
const areaTerlibat = computed(() => {
    const daftar = ringkasan.value?.area_terlibat;
    return Array.isArray(daftar) ? daftar : [];
});

const periode = computed(() => {
    const mulai = pekerjaan.value?.tanggal_mulai;
    const selesai = pekerjaan.value?.tanggal_selesai;

    if (mulai && selesai) {
        return `${mulai} s/d ${selesai}`;
    }

    return mulai ?? selesai ?? 'Periode belum diisi';
});

const kartuRingkasan = computed(() => [
    {
        label: 'Peminjaman',
        nilai: ringkasan.value?.jumlah_peminjaman ?? 0,
        keterangan: 'Memakai pekerjaan ini',
    },
    {
        label: 'Alat disetujui',
        nilai: ringkasan.value?.total_alat_disetujui ?? 0,
        keterangan: 'Total unit yang disetujui',
    },
    {
        label: 'Alat rusak',
        nilai: ringkasan.value?.total_kerusakan ?? 0,
        keterangan: 'Dilaporkan rusak',
        warna: (ringkasan.value?.total_kerusakan ?? 0) > 0 ? 'text-rose-600' : 'text-slate-900',
    },
    {
        label: 'Alat hilang',
        nilai: ringkasan.value?.total_kehilangan ?? 0,
        keterangan: 'Dilaporkan hilang',
        warna: (ringkasan.value?.total_kehilangan ?? 0) > 0 ? 'text-amber-600' : 'text-slate-900',
    },
]);

const STATUS_PEKERJAAN_LABEL = {
    berlangsung: 'Berlangsung',
    akan_datang: 'Akan Datang',
    selesai: 'Selesai',
};

const labelStatusPekerjaan = (status) => STATUS_PEKERJAAN_LABEL[status] ?? status;

const statusPekerjaanBadge = (status) => {
    switch (status) {
        case 'berlangsung':
            return 'bg-emerald-50 text-emerald-700';
        case 'akan_datang':
            return 'bg-amber-50 text-amber-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

// Warna status peminjaman mengikuti halaman Riwayat Peminjaman.
const statusPeminjamanBadge = (status) => {
    switch (status) {
        case 'Perlu Direview':
        case 'Perlu Disetujui':
            return 'bg-blue-50 text-blue-600';
        case 'Disetujui':
            return 'bg-cyan-50 text-cyan-700';
        case 'Dikirim':
            return 'bg-emerald-50 text-emerald-700';
        case 'Diterima':
            return 'bg-teal-50 text-teal-700';
        case 'Dikembalikan Partials':
            return 'bg-violet-50 text-violet-700';
        case 'Dikembalikan Semuanya':
            return 'bg-indigo-50 text-indigo-700';
        case 'Selesai':
            return 'bg-slate-200 text-slate-700';
        case 'Ditolak':
            return 'bg-rose-50 text-rose-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const statusLaporanBadge = (status) => {
    switch (status) {
        case 'Disetujui':
            return 'bg-emerald-50 text-emerald-700';
        case 'Ditolak':
            return 'bg-rose-50 text-rose-700';
        case 'Selesai':
            return 'bg-slate-200 text-slate-700';
        default:
            return 'bg-blue-50 text-blue-600';
    }
};

const toggle = (id) => {
    terbuka.value = terbuka.value === id ? null : id;
};

const kembali = () => router.visit('/pekerjaan-overhaul');

const loadDetail = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get(`/api/pekerjaan-ohs/${props.pekerjaanId}/detail`, {
            __skipGlobalLoading: true,
        });
        data.value = response.data;

        // Satu peminjaman langsung dibuka supaya isinya tidak tersembunyi.
        if (response.data?.peminjamans?.length === 1) {
            terbuka.value = response.data.peminjamans[0].id;
        }
    } catch (error) {
        loadError.value = error?.response?.data?.message ?? 'Gagal memuat detail pekerjaan.';
    } finally {
        isLoading.value = false;
    }
};

onMounted(loadDetail);
</script>
