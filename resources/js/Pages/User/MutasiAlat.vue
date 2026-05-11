<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">{{ pageTitle }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ pageSubtitle }}</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-500">
                <svg
                    class="h-5 w-5"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M3 7h11v10H3z" />
                    <path d="M14 10h4l3 3v4h-7z" />
                    <circle cx="7.5" cy="19" r="1.5" />
                    <circle cx="17.5" cy="19" r="1.5" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">{{ pageTitle }}</h2>
                <p class="mt-1 text-sm text-slate-500">Filter berdasarkan status pengiriman</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <circle cx="11" cy="11" r="8" />
                        <path d="m21 21-4.3-4.3" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="text"
                    placeholder="Cari pekerjaan atau ID..."
                />
            </div>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-2">
            <div class="grid gap-2 md:grid-cols-3">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    class="flex items-center justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition border-1"
                    :class="activeTab === tab.key
                        ? 'bg-white text-blue-700 shadow-sm'
                        : 'text-slate-600 hover:bg-white/70'"
                    type="button"
                    @click="activeTab = tab.key"
                >
                    <span class="text-slate-400">
                        <svg v-if="tab.key === 'dikirim'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h11v10H3z" />
                            <path d="M14 10h4l3 3v4h-7z" />
                            <circle cx="7.5" cy="19" r="1.5" />
                            <circle cx="17.5" cy="19" r="1.5" />
                        </svg>
                        <svg v-else-if="tab.key === 'diterima'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h11v10H3z" />
                            <path d="M14 10h4l3 3v4h-7z" />
                            <circle cx="7.5" cy="19" r="1.5" />
                            <circle cx="17.5" cy="19" r="1.5" />
                            <path d="M7 12l2 2 4-4" />
                        </svg>
                        <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h13l3 5v6H3z" />
                            <circle cx="7.5" cy="18" r="1.5" />
                            <circle cx="17.5" cy="18" r="1.5" />
                            <path d="M9 12h6" />
                        </svg>
                    </span>
                    <span>{{ tab.label }}</span>
                    <span
                        class="flex h-5 min-w-[20px] items-center justify-center rounded-full px-1 text-xs font-semibold"
                        :class="activeTab === tab.key ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600'"
                    >
                        {{ tab.count }}
                    </span>
                </button>
            </div>
        </div>

        <div class="mt-6">
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat {{ pageTitle }}...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Tidak ada riwayat pada kategori ini.
            </p>

            <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="item in filteredItems"
                    :key="item.id"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-200"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="8" r="3.5" />
                                    <path d="M4 20c1.5-3.5 5-5 8-5s6.5 1.5 8 5" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ item.userName }}</p>
                                <p class="text-xs text-slate-500">{{ item.createdAt }}</p>
                            </div>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-[11px] font-semibold"
                            :class="statusBadge(item.status)"
                        >
                            {{ statusLabel(item.status) }}
                        </span>
                    </div>

                    <div class="mt-3 space-y-2 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
                                <circle cx="12" cy="11" r="2.5" />
                            </svg>
                            <span>{{ areaName }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M7 8h10M7 12h6M7 16h4" />
                            </svg>
                            <span>{{ approvedLabel(item) }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M7 2v4M17 2v4M3 10h18" />
                            </svg>
                            <span>{{ item.borrowDate }} - {{ item.returnDate }}</span>
                        </div>
                        <div v-if="item.pengirimNama" class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 7a4 4 0 1 0-8 0" />
                                <path d="M12 11v4" />
                                <path d="M5.5 21a6.5 6.5 0 0 1 13 0" />
                            </svg>
                            <span>Pengirim: {{ item.pengirimNama }}</span>
                        </div>
                    </div>

                    <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        {{ item.title }}
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                            type="button"
                            @click="openDetail(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            Lihat Detail
                        </button>
                        <button
                            v-if="item.suratJalanUrl"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300"
                            type="button"
                            @click="openSuratJalan(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                <path d="M14 2v6h6" />
                                <path d="M16 13H8" />
                                <path d="M16 17H8" />
                                <path d="M10 9H8" />
                            </svg>
                            Surat Jalan
                        </button>
                        <button
                            v-if="canAcceptSuratJalan(item)"
                            class="inline-flex flex-1 basis-full items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                            type="button"
                            :disabled="isAccepting === item.id"
                            @click="acceptPeminjaman(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            {{ isAccepting === item.id ? 'Memproses...' : 'Terima' }}
                        </button>
                        <button
                            v-if="canReturnItem(item)"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:border-amber-300"
                            type="button"
                            @click="openReturn(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 7H9a4 4 0 0 0-4 4v3" />
                                <path d="M9 11l-4 4 4 4" />
                                <path d="M21 17V11a4 4 0 0 0-4-4" />
                            </svg>
                            Kembalikan
                        </button>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <PeminjamanDetailModal
        :open="!!detailItem"
        :item="detailItem"
        @close="detailItem = null"
    />
    <SuratJalanModal
        :open="!!suratJalanItem"
        :url="suratJalanItem?.suratJalanUrl"
        :path="suratJalanItem?.suratJalanPath"
        :title="suratJalanItem?.title"
        :pengirim-name="suratJalanItem?.pengirimNama"
        :peminjaman-id="suratJalanItem?.id"
        :peminjaman-status="suratJalanItem?.status"
        :accept-enabled="false"
        @close="suratJalanItem = null"
    />

    <teleport to="body">
        <div
            v-if="returnItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeReturn"
        >
            <div class="max-h-[90vh] w-full max-w-4xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Konfirmasi
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">
                            Kembalikan alat
                        </h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeReturn"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Yakin ingin mengembalikan peminjaman ini?
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ returnItem?.title || '-' }} (ID: {{ returnItem?.id || '-' }})
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        Anda bisa mengembalikan sebagian alat dulu. Status akan berubah otomatis ke parsial atau selesai sesuai jumlah yang dikembalikan.
                    </p>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="row in returnRows"
                            :key="row.itemId"
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ row.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ row.code }}</p>
                                    <p class="mt-2 text-xs text-slate-500">
                                        Disetujui: <span class="font-semibold text-emerald-700">{{ row.approvedQty }}</span>
                                        <span class="mx-1 text-slate-300">|</span>
                                        Sudah kembali: <span class="font-semibold text-indigo-700">{{ row.returnedQty }}</span>
                                        <span class="mx-1 text-slate-300">|</span>
                                        Sisa: <span class="font-semibold text-amber-700">{{ row.remainingQty }}</span>
                                    </p>
                                </div>
                                <label class="w-full max-w-[140px] space-y-1 text-xs font-semibold text-slate-600">
                                    <span>Jumlah kembali</span>
                                    <input
                                        v-model.number="row.returnQty"
                                        type="number"
                                        min="0"
                                        :max="row.remainingQty"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                    />
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div
                            class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50 p-4"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">Laporan Alat</p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        Tambahkan form laporan sesuai kebutuhan untuk kerusakan atau kehilangan.
                                    </p>
                                </div>
                                <button
                                    class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
                                    type="button"
                                    @click="addReturnReport"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 5v14" />
                                        <path d="M5 12h14" />
                                    </svg>
                                    Tambah Laporan
                                </button>
                            </div>

                            <div v-if="!returnReports.length" class="rounded-2xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada form laporan. Klik Tambah Laporan untuk menambahkan laporan kerusakan atau kehilangan.
                            </div>

                            <div
                                v-for="report in returnReports"
                                :key="report.key"
                                class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">Laporan #{{ report.order }}</p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            Isi detail laporan alat pada pengembalian ini.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="rounded-full px-3 py-1 text-[11px] font-semibold"
                                            :class="
                                                report.kategori === 'kerusakan'
                                                    ? 'bg-amber-100 text-amber-700'
                                                    : report.kategori === 'kehilangan'
                                                      ? 'bg-rose-100 text-rose-700'
                                                      : 'bg-slate-100 text-slate-600'
                                            "
                                        >
                                            {{ report.kategori ? report.label : 'Pilih kategori' }}
                                        </span>
                                        <button
                                            class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100"
                                            type="button"
                                            @click="removeReturnReport(report.key)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <label class="space-y-2 text-sm font-medium text-slate-700">
                                        <span>Kategori</span>
                                        <select
                                            v-model="report.kategori"
                                            @change="report.label = getReturnReportLabel(report.kategori)"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                        >
                                            <option value="">Pilih kategori</option>
                                            <option value="kerusakan">Kerusakan</option>
                                            <option value="kehilangan">Kehilangan</option>
                                        </select>
                                    </label>
                                    <label class="space-y-2 text-sm font-medium text-slate-700">
                                        <span>Alat</span>
                                        <select
                                            v-model="report.alat_id"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                        >
                                            <option value="">Pilih alat</option>
                                            <option v-for="tool in returnReportTools" :key="`${report.key}-${tool.alat_id}`" :value="tool.alat_id">
                                                {{ tool.name }} ({{ tool.code }})
                                            </option>
                                        </select>
                                    </label>
                                    <label class="space-y-2 text-sm font-medium text-slate-700">
                                        <span>Jumlah</span>
                                        <input
                                            v-model.number="report.jumlah"
                                            type="number"
                                            min="1"
                                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                        />
                                    </label>
                                    <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                                        <span>Foto</span>
                                        <input
                                            :ref="(el) => setReturnReportFileInput(report.key, el)"
                                            type="file"
                                            accept="image/*"
                                            class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-amber-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-amber-700"
                                            @change="(event) => handleReturnReportFileChange(report.key, event)"
                                        />
                                    </label>
                                </div>
                                <label class="block space-y-2 text-sm font-medium text-slate-700">
                                    <span>Deskripsi</span>
                                    <textarea
                                        v-model="report.deskripsi"
                                        rows="3"
                                        :placeholder="`Jelaskan ${report.kategori ? report.label.toLowerCase() : 'laporan'} yang terjadi...`"
                                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                    />
                                </label>
                                <div v-if="report.previewUrl" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                    <img :src="report.previewUrl" :alt="`Preview ${report.label}`" class="h-48 w-full object-cover" />
                                </div>
                            </div>
                            <p v-if="returnReportError" class="text-sm font-semibold text-rose-500">
                                {{ returnReportError }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeReturn"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700"
                        type="button"
                        :disabled="isReturning"
                        :class="isReturning ? 'cursor-not-allowed opacity-70' : ''"
                        @click="confirmReturn"
                    >
                        {{ isReturning ? 'Memproses...' : 'Ya, kembalikan' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PeminjamanDetailModal from '../../Components/PeminjamanDetailModal.vue';
import SuratJalanModal from '../../Components/SuratJalanModal.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: page.props?.mutasiPageTitle ?? 'Mutasi alat',
                subtitle: page.props?.mutasiPageSubtitle ?? 'Daftar pengiriman yang sudah diproses',
                activeMenu: page.props?.mutasiActiveMenu ?? 'mutasi-alat',
            },
            () => page
        ),
});

const page = usePage();
const pageTitle = computed(() => page.props.mutasiPageTitle ?? 'Mutasi alat');
const pageSubtitle = computed(() => page.props.mutasiPageSubtitle ?? 'Daftar pengiriman yang sudah diproses');
const mutasiCategory = computed(() => page.props.mutasiCategory ?? '');
const mutasiAreaScope = computed(() => page.props.mutasiAreaScope ?? '');
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
const roleKey = computed(() =>
    (page.props.auth?.user?.role?.key ?? cachedUser.value?.role?.key ?? '').toLowerCase()
);
const isUserRole = computed(() => roleKey.value === 'user');
const isAdminRole = computed(() => ['admin', 'super_admin'].includes(roleKey.value));
const userAreaId = computed(() => page.props.auth?.user?.area_id ?? cachedUser.value?.area_id ?? null);
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const currentAreaId = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaId.value
        : userAreaId.value
);
const setAreaSwitching = inject('setAreaSwitching', null);
const activeAreaName = inject('activeAreaName', ref('Area tidak diketahui'));
const refreshMailboxActions = inject('refreshMailboxActions', async () => {});
const areaName = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaName.value
        : page.props.auth?.user?.area?.name ?? 'Area tidak diketahui'
);

const items = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const detailItem = ref(null);
const suratJalanItem = ref(null);
const returnItem = ref(null);
const search = ref('');
const isReturning = ref(false);
const isAccepting = ref(null);
const returnReportError = ref('');
const returnRows = ref([]);
const returnReportFileInputs = ref({});
let returnReportSequence = 0;
const getReturnReportLabel = (kategori) => {
    if (kategori === 'kerusakan') {
        return 'Kerusakan';
    }
    if (kategori === 'kehilangan') {
        return 'Kehilangan';
    }
    return 'Laporan';
};
const createReturnReport = () => ({
    key: `return-report-${returnReportSequence++}`,
    order: 0,
    label: 'Laporan',
    kategori: '',
    alat_id: '',
    jumlah: 1,
    deskripsi: '',
    foto: null,
    previewUrl: '',
});
const returnReports = ref([]);

const tabConfig = [
    { key: 'dikirim', label: 'Sedang Dikirim', status: 'Dikirim' },
    { key: 'diterima', label: 'Diterima', status: 'Diterima' },
    { key: 'partials', label: 'Dikembalikan Parsial', status: 'Dikembalikan Partials' },
    { key: 'dikembalikan-semuanya', label: 'Dikembalikan Semua', status: 'Dikembalikan Semuanya' },
];

const initialTab = () => {
    if (typeof window === 'undefined') {
        return tabConfig[0].key;
    }
    const queryTab = new URLSearchParams(window.location.search).get('tab');
    return tabConfig.some((tab) => tab.key === queryTab) ? queryTab : tabConfig[0].key;
};

const activeTab = ref(initialTab());

const statusLabel = (status) => {
    switch (status) {
        case 'Dikirim':
            return 'Sudah Dikirim';
        case 'Diterima':
            return 'Diterima';
        case 'Dikembalikan Partials':
            return 'Dikembalikan Parsial';
        case 'Dikembalikan Semuanya':
            return 'Dikembalikan Semua';
        default:
            return status ?? '-';
    }
};

const statusBadge = (status) => {
    switch (status) {
        case 'Dikirim':
            return 'bg-blue-100 text-blue-600';
        case 'Diterima':
            return 'bg-emerald-100 text-emerald-600';
        case 'Dikembalikan Partials':
            return 'bg-violet-100 text-violet-700';
        case 'Dikembalikan Semuanya':
            return 'bg-indigo-100 text-indigo-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const statusCountMap = computed(() => {
    const base = {
        Dikirim: 0,
        Diterima: 0,
        'Dikembalikan Partials': 0,
        'Dikembalikan Semuanya': 0,
    };
    items.value.forEach((item) => {
        if (base[item.status] !== undefined) {
            base[item.status] += 1;
        }
    });
    return base;
});

const tabs = computed(() =>
    tabConfig.map((tab) => ({
        ...tab,
        count: statusCountMap.value[tab.status] ?? 0,
    }))
);

const activeStatus = computed(() => {
    const match = tabConfig.find((tab) => tab.key === activeTab.value);
    return match?.status ?? 'Dikirim';
});

const filteredItems = computed(() => {
    const keyword = search.value.trim().toLowerCase();
    return items.value.filter((item) => {
        if (item.status !== activeStatus.value) {
            return false;
        }
        if (!keyword) {
            return true;
        }
        return (
            item.title.toLowerCase().includes(keyword) ||
            item.createdAt.toLowerCase().includes(keyword) ||
            String(item.id).includes(keyword)
        );
    });
});

const approvedLabel = (item) => {
    const total = Number.isFinite(item?.itemCount) ? item.itemCount : 0;
    return `${total} alat disetujui`;
};

const returnReportTools = computed(() =>
    returnRows.value.filter((tool) => Number(tool.returnQty) > 0)
);

const normalizeHistory = (item) => {
    const tools = Array.isArray(item?.tools)
        ? item.tools.map((tool) => ({
              item_id: tool?.item_id ?? null,
              alat_id: tool?.alat_id ?? null,
              name: tool?.name ?? '-',
              code: tool?.code ?? '-',
              qty: Number.isFinite(tool?.qty) ? tool.qty : 0,
              approvedQty: Number.isFinite(tool?.approved_qty) ? tool.approved_qty : 0,
              returnedQty: Number.isFinite(tool?.returned_qty) ? tool.returned_qty : 0,
              remainingQty: Number.isFinite(tool?.remaining_qty) ? tool.remaining_qty : 0,
              reviewStatus: tool?.review_status ?? 'Menunggu Review',
              rejectionReason: tool?.rejection_reason ?? '',
          }))
        : [];

    return {
        id: item?.id ?? '',
        title: item?.title ?? '-',
        userName: item?.user_name ?? '-',
        reviewerName: item?.reviewed_by_name ?? '-',
        requesterReviewerName: item?.requester_reviewed_by_name ?? '-',
        areaName: item?.area_name ?? '-',
        areaId: item?.area_id ?? null,
        requesterAreaName: item?.requester_area_name ?? '-',
        requesterAreaId: item?.requester_area_id ?? null,
        isInterArea: Boolean(item?.is_inter_area),
        createdAt: item?.created_at ?? '-',
        borrowDate: item?.borrow_date ?? '-',
        returnDate: item?.return_date ?? '-',
        itemCount: Number.isFinite(item?.item_count) ? item.item_count : 0,
        status: item?.status ?? 'Dikirim',
        pengirimNama: item?.pengirim_nama ?? '',
        suratJalanUrl: item?.surat_jalan_url ?? '',
        suratJalanPath: item?.surat_jalan_path ?? '',
        tools,
    };
};

const loadHistory = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const params = {};
        if (isAreaSwitcherRole.value && activeAreaId.value) {
            params.area_id = activeAreaId.value;
        }
        if (mutasiCategory.value) {
            params.kategori = mutasiCategory.value;
        }
        if (mutasiAreaScope.value) {
            params.area_scope = mutasiAreaScope.value;
        }
        const response = await axios.get('/api/riwayat-pengiriman', { params });
        const data = Array.isArray(response.data) ? response.data : [];
        items.value = data.map((item) => normalizeHistory(item));
        await refreshMailboxActions({ silent: true });
    } catch (error) {
        items.value = [];
        loadError.value = `Gagal memuat ${pageTitle.value}.`;
        showAlert('error', loadError.value);
    } finally {
        isLoading.value = false;
    }
};

const openDetail = (item) => {
    detailItem.value = item;
};

const openSuratJalan = (item) => {
    suratJalanItem.value = item;
};

const isInterAreaRequester = (item) =>
    Boolean(item?.isInterArea)
    && Number(item?.requesterAreaId) === Number(currentAreaId.value);

const canAcceptSuratJalan = (item) =>
    item?.status === 'Dikirim'
    && (
        isAdminRole.value
        || (['pic_tools', 'pic_tool'].includes(roleKey.value) && isInterAreaRequester(item))
    );

const canReturnItem = (item) =>
    ['Diterima', 'Dikembalikan Partials'].includes(item?.status)
    && (
        isUserRole.value
        || isAdminRole.value
        || (['pic_tools', 'pic_tool'].includes(roleKey.value) && isInterAreaRequester(item))
    );

const acceptPeminjaman = async (item) => {
    if (!canAcceptSuratJalan(item) || isAccepting.value) {
        return;
    }
    isAccepting.value = item.id;
    try {
        await axios.post(`/api/pengiriman/${item.id}/terima`);
        await loadHistory();
        showAlert('success', 'Peminjaman berhasil diterima.');
    } catch (error) {
        showAlert(
            'error',
            error?.response?.data?.message ?? 'Gagal menerima peminjaman.'
        );
    } finally {
        isAccepting.value = null;
    }
};

const openReturn = (item) => {
    returnItem.value = item;
    resetReturnReport();
    returnRows.value = (Array.isArray(item?.tools) ? item.tools : [])
        .filter((tool) => Number(tool?.remainingQty ?? 0) > 0)
        .map((tool) => ({
            itemId: tool.item_id,
            alat_id: tool.alat_id,
            name: tool.name,
            code: tool.code,
            approvedQty: Number(tool.approvedQty ?? 0),
            returnedQty: Number(tool.returnedQty ?? 0),
            remainingQty: Number(tool.remainingQty ?? 0),
            returnQty: Number(tool.remainingQty ?? 0),
        }));
};

const closeReturn = () => {
    returnItem.value = null;
    returnRows.value = [];
    resetReturnReport();
};

const resetReturnReport = () => {
    returnReportError.value = '';
    Object.values(returnReportFileInputs.value).forEach((input) => {
        if (input) {
            input.value = '';
        }
    });
    returnReports.value.forEach((report) => {
        if (report.previewUrl) {
            URL.revokeObjectURL(report.previewUrl);
        }
    });
    returnReportFileInputs.value = {};
    returnReports.value = [];
};

const addReturnReport = () => {
    returnReports.value.push({
        ...createReturnReport(),
        order: returnReports.value.length + 1,
    });
};

const removeReturnReport = (key) => {
    const index = returnReports.value.findIndex((report) => report.key === key);
    if (index === -1) {
        return;
    }

    const [report] = returnReports.value.splice(index, 1);
    if (report?.previewUrl) {
        URL.revokeObjectURL(report.previewUrl);
    }
    delete returnReportFileInputs.value[key];
    returnReports.value = returnReports.value.map((entry, entryIndex) => ({
        ...entry,
        order: entryIndex + 1,
        label: getReturnReportLabel(entry.kategori),
    }));
};

const setReturnReportFileInput = (key, element) => {
    if (element) {
        returnReportFileInputs.value[key] = element;
        return;
    }
    delete returnReportFileInputs.value[key];
};

const handleReturnReportFileChange = (key, event) => {
    const file = event.target.files?.[0] ?? null;
    const report = returnReports.value.find((entry) => entry.key === key);
    if (!report) {
        return;
    }

    report.foto = file;
    report.label = getReturnReportLabel(report.kategori);
    returnReportError.value = '';
    if (report.previewUrl) {
        URL.revokeObjectURL(report.previewUrl);
        report.previewUrl = '';
    }
    if (file) {
        report.previewUrl = URL.createObjectURL(file);
    }
};

const validateReturnReport = () => {
    const submittedRows = returnRows.value.filter((row) => Number(row.returnQty) > 0);
    if (!submittedRows.length) {
        returnReportError.value = 'Isi minimal satu jumlah pengembalian alat.';
        return false;
    }

    const invalidRow = submittedRows.find(
        (row) => Number(row.returnQty) < 1 || Number(row.returnQty) > Number(row.remainingQty)
    );
    if (invalidRow) {
        returnReportError.value = `Jumlah kembali ${invalidRow.name} tidak valid.`;
        return false;
    }

    const activeReports = returnReports.value.filter((report) => {
        return (
            report.kategori ||
            report.alat_id ||
            report.deskripsi.trim() ||
            report.foto ||
            Number(report.jumlah) > 1
        );
    });

    for (const report of activeReports) {
        report.label = getReturnReportLabel(report.kategori);
        if (!report.kategori || !report.alat_id || !report.deskripsi.trim() || !report.foto || Number(report.jumlah) < 1) {
            returnReportError.value = `Lengkapi semua field laporan #${report.order}.`;
            return false;
        }

        const selectedTool = submittedRows.find((row) => Number(row.alat_id) === Number(report.alat_id));
        if (!selectedTool) {
            returnReportError.value = `Pilih alat pada laporan #${report.order} dari item yang sedang dikembalikan.`;
            return false;
        }

        if (Number(report.jumlah) > Number(selectedTool.returnQty)) {
            returnReportError.value = `Jumlah laporan #${report.order} melebihi jumlah alat yang dikembalikan pada transaksi ini.`;
            return false;
        }
    }

    returnReportError.value = '';
    return true;
};

const confirmReturn = async () => {
    if (!returnItem.value?.id) {
        returnItem.value = null;
        return;
    }
    if (isReturning.value) {
        return;
    }
    if (!validateReturnReport()) {
        return;
    }
    isReturning.value = true;
    let shouldClose = false;
    let successMessage = 'Pengembalian berhasil dikonfirmasi.';
    try {
        const payload = new FormData();
        returnRows.value
            .filter((row) => Number(row.returnQty) > 0)
            .forEach((row, index) => {
                payload.append(`items[${index}][item_id]`, String(row.itemId));
                payload.append(`items[${index}][returned_qty]`, String(row.returnQty));
            });

        returnReports.value
            .filter((report) => report.kategori || report.alat_id || report.deskripsi.trim() || report.foto || Number(report.jumlah) > 1)
            .forEach((report, index) => {
                payload.append(`laporan[${index}][kategori]`, report.kategori);
                payload.append(`laporan[${index}][alat_id]`, String(report.alat_id));
                payload.append(`laporan[${index}][jumlah]`, String(report.jumlah));
                payload.append(`laporan[${index}][deskripsi]`, report.deskripsi.trim());
                payload.append(`laporan[${index}][foto]`, report.foto);
            });

        const response = await axios.post(`/api/pengiriman/${returnItem.value.id}/kembalikan`, payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });
        const nextStatus = response?.data?.status ?? '';
        if (nextStatus === 'Dikembalikan Partials') {
            successMessage = 'Pengembalian parsial berhasil disimpan.';
        } else if (nextStatus === 'Dikembalikan Semuanya') {
            successMessage = 'Semua alat berhasil dikembalikan.';
        }
        await loadHistory();
        showAlert('success', successMessage);
        shouldClose = true;
    } catch (error) {
        const message =
            error?.response?.data?.message ??
            'Gagal mengembalikan peminjaman.';
        if (error?.response?.status === 422) {
            const errors = error.response?.data?.errors ?? {};
            returnReportError.value =
                errors['laporan.0.kategori']?.[0] ??
                errors['laporan.0.alat_id']?.[0] ??
                errors['laporan.0.jumlah']?.[0] ??
                errors['laporan.0.deskripsi']?.[0] ??
                errors['laporan.0.foto']?.[0] ??
                errors['laporan.1.kategori']?.[0] ??
                errors['laporan.1.alat_id']?.[0] ??
                errors['laporan.1.jumlah']?.[0] ??
                errors['laporan.1.deskripsi']?.[0] ??
                errors['laporan.1.foto']?.[0] ??
                message;
        }
        showAlert('error', message);
    } finally {
        isReturning.value = false;
        if (shouldClose) {
            closeReturn();
        }
    }
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

onMounted(() => {
    cachedUser.value = loadCachedUser();
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(true);
    }
    loadHistory().finally(() => {
        if (isAreaSwitcherRole.value) {
            setAreaSwitching?.(false);
        }
    });
});

watch(
    () => activeAreaId.value,
    async (next, prev) => {
        if (!isAreaSwitcherRole.value) {
            return;
        }
        const shouldShow = prev !== undefined && prev !== null && next !== prev;
        if (shouldShow) {
            setAreaSwitching?.(true);
        }
        try {
            await loadHistory();
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);
</script>
