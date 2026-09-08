<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Riwayat Peminjaman - {{ areaName }}</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar riwayat peminjaman yang tersedia</p>
    </div>

    <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div
            v-for="card in summaryCards"
            :key="card.label"
            class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50"
        >
            <div class="flex items-start justify-between gap-3">
                <p class="text-sm text-slate-500">{{ card.label }}</p>
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl" :class="card.iconClass">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path :d="card.icon" />
                    </svg>
                </span>
            </div>
            <p class="mt-2 text-2xl font-semibold tabular-nums" :class="card.valueClass">{{ card.value }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ card.hint }}</p>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
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
                    <path d="M3 4h18l-7 8v6l-4 2v-8L3 4z" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Peminjaman</h2>
                <p class="mt-1 text-sm text-slate-500">Tampilkan riwayat dalam format tabel sederhana</p>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center">
            <div class="relative min-w-0 flex-1">
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
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-10 pr-10 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    type="text"
                    placeholder="Cari pekerjaan atau ID..."
                />
                <button
                    v-if="search"
                    class="absolute inset-y-0 right-2 my-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                    type="button"
                    title="Kosongkan pencarian"
                    aria-label="Kosongkan pencarian"
                    @click="search = ''"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="w-full lg:w-56">
                <select
                    v-model="statusFilter"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    <option value="Semua">Semua Status</option>
                    <option value="Perlu Direview">Perlu Direview</option>
                    <option value="Perlu Disetujui">Perlu Disetujui</option>
                    <option value="Disetujui">Disetujui</option>
                    <option value="Dikirim">Dikirim</option>
                    <option value="Diterima">Diterima</option>
                    <option value="Dikembalikan Partials">Dikembalikan Partials</option>
                    <option value="Dikembalikan Semuanya">Dikembalikan Semuanya</option>
                    <option value="Selesai">Selesai</option>
                    <option value="Ditolak">Ditolak</option>
                </select>
            </div>
            <div class="w-full lg:w-56">
                <select
                    v-model="kategoriFilter"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    <option value="Semua">Semua Kategori</option>
                    <option value="Intra Area">Intra Area</option>
                    <option value="Antar Area">Antar Area</option>
                </select>
            </div>
            <button
                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 transition hover:border-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                type="button"
                :disabled="isExporting"
                @click="exportHistory"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 3v12" />
                    <path d="m7 10 5 5 5-5" />
                    <path d="M5 21h14" />
                </svg>
                {{ isExporting ? 'Mengunduh...' : 'Export CSV' }}
            </button>
            <button
                v-if="canDeleteAreaPeminjaman"
                class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 text-sm font-semibold text-rose-700 transition hover:border-rose-300 disabled:cursor-not-allowed disabled:opacity-60"
                type="button"
                :disabled="isBulkDeleting"
                @click="deleteActiveAreaPeminjaman"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 6h18" />
                    <path d="M8 6V4h8v2" />
                    <path d="M19 6l-1 14H6L5 6" />
                </svg>
                {{ isBulkDeleting ? 'Menghapus...' : 'Hapus Semuanya' }}
            </button>
        </div>

        <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Filter aktif</span>
            <span
                v-if="search.trim()"
                class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                <span class="truncate">Pencarian: "{{ search.trim() }}"</span>
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter pencarian"
                    aria-label="Hapus filter pencarian"
                    @click="search = ''"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
            <span
                v-if="statusFilter !== 'Semua'"
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                Status: {{ statusFilter }}
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter status"
                    aria-label="Hapus filter status"
                    @click="statusFilter = 'Semua'"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
            <span
                v-if="kategoriFilter !== 'Semua'"
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                Kategori: {{ kategoriFilter }}
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter kategori"
                    aria-label="Hapus filter kategori"
                    @click="kategoriFilter = 'Semua'"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
            <button
                class="text-xs font-semibold text-slate-500 underline underline-offset-2 transition hover:text-slate-700"
                type="button"
                @click="resetFilters"
            >
                Reset semua
            </button>
        </div>

        <div class="mt-5" aria-live="polite" :aria-busy="isLoading">
            <!-- Loading -->
            <div v-if="isLoading" class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" class="opacity-25" />
                        <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                    </svg>
                    Memuat data peminjaman...
                </div>
                <div class="divide-y divide-slate-100 bg-white">
                    <div v-for="row in 5" :key="`skeleton-${row}`" class="flex items-center gap-4 px-4 py-4">
                        <div class="h-9 w-24 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 flex-1 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 w-32 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-6 w-24 shrink-0 animate-pulse rounded-full bg-slate-100"></div>
                        <div class="h-9 w-28 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                    </div>
                </div>
            </div>

            <!-- Error -->
            <div
                v-else-if="loadError"
                class="flex flex-col items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-5 sm:flex-row sm:items-center sm:justify-between"
                role="alert"
            >
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M10.3 4.3 2.9 17.1a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z" />
                            <path d="M12 9v4M12 17h.01" />
                        </svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-rose-800">Gagal memuat data</p>
                        <p class="mt-0.5 text-sm text-rose-700">{{ loadError }}</p>
                    </div>
                </div>
                <button
                    class="h-10 shrink-0 rounded-xl border border-rose-300 bg-white px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"
                    type="button"
                    @click="loadHistory"
                >
                    Coba lagi
                </button>
            </div>

            <!-- Empty -->
            <div v-else-if="!filteredItems.length" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-12 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M3 4h18l-7 8v6l-4 2v-8L3 4z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-700">
                    {{ hasActiveFilters ? 'Tidak ada peminjaman yang cocok' : 'Belum ada peminjaman' }}
                </p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                    {{
                        hasActiveFilters
                            ? 'Coba ubah kata kunci, status, atau kategori untuk memperluas hasil pencarian.'
                            : 'Riwayat peminjaman akan muncul di sini setelah ada pengajuan peminjaman alat.'
                    }}
                </p>
                <button
                    v-if="hasActiveFilters"
                    class="mt-4 h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                    type="button"
                    @click="resetFilters"
                >
                    Reset filter
                </button>
            </div>

            <!-- Tabel (md ke atas) -->
            <div v-else>
                <div class="hidden overflow-hidden rounded-2xl border border-slate-200 md:block">
                    <div class="max-h-[68vh] overflow-auto">
                        <table class="w-full min-w-[960px] text-sm">
                            <caption class="sr-only">
                                Daftar riwayat peminjaman beserta status dan periode pinjamnya
                            </caption>
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Dibuat</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Pekerjaan</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Peminjam</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Status</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Periode</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr
                                    v-for="item in filteredItems"
                                    :key="item.id"
                                    class="align-top transition hover:bg-slate-50/70"
                                >
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <p class="font-medium text-slate-700">{{ splitDateTime(item.createdAt).date }}</p>
                                        <p class="mt-0.5 text-xs tabular-nums text-slate-500">
                                            {{ splitDateTime(item.createdAt).time }}
                                        </p>
                                    </td>
                                    <td class="max-w-[22rem] px-4 py-4">
                                        <p class="line-clamp-2 font-semibold text-slate-900" :title="item.title">{{ item.title }}</p>
                                        <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                            <span class="font-mono">#{{ item.id }}</span>
                                            <span aria-hidden="true">&middot;</span>
                                            <span>{{ item.itemCount }} item</span>
                                            <span
                                                class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                                :class="kategoriClass(item.kategori)"
                                            >
                                                {{ item.kategori }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="font-semibold text-slate-900">{{ item.userName }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            Disetujui: {{ reviewApprovalLabel(item) }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-[11px] font-semibold"
                                            :class="statusClass(item.status)"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(item.status)" aria-hidden="true"></span>
                                            {{ item.status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-slate-600">
                                        <p class="text-xs">{{ item.borrowDate }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">s/d {{ item.returnDate }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                v-if="hasSuratJalan(item)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200"
                                                type="button"
                                                title="Lihat surat jalan"
                                                aria-label="Lihat surat jalan"
                                                @click="openSuratJalan(item)"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                    <path d="M14 2v6h6" />
                                                    <path d="M16 13H8" />
                                                    <path d="M16 17H8" />
                                                </svg>
                                            </button>
                                            <button
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                                                type="button"
                                                title="Lihat detail peminjaman"
                                                aria-label="Lihat detail peminjaman"
                                                @click="openDetail(item)"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                                    <circle cx="12" cy="12" r="3" />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="canRepeatPeminjaman(item)"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-cyan-200 bg-cyan-50 text-cyan-700 transition hover:border-cyan-300 hover:bg-cyan-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-200"
                                                type="button"
                                                title="Ajukan ulang peminjaman ini"
                                                aria-label="Ajukan ulang peminjaman ini"
                                                @click="repeatPeminjaman(item)"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M17 1l4 4-4 4" />
                                                    <path d="M3 11V9a4 4 0 0 1 4-4h14" />
                                                    <path d="M7 23l-4-4 4-4" />
                                                    <path d="M21 13v2a4 4 0 0 1-4 4H3" />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="canManagePeminjaman"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition hover:border-blue-300 hover:bg-blue-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                                                type="button"
                                                title="Edit peminjaman"
                                                aria-label="Edit peminjaman"
                                                @click="openEdit(item)"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                </svg>
                                            </button>
                                            <button
                                                v-if="canManagePeminjaman"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-200 disabled:cursor-not-allowed disabled:opacity-60"
                                                type="button"
                                                :disabled="deletingId === item.id"
                                                :title="deletingId === item.id ? 'Menghapus...' : 'Hapus peminjaman'"
                                                :aria-label="deletingId === item.id ? 'Menghapus peminjaman' : 'Hapus peminjaman'"
                                                @click="deletePeminjaman(item)"
                                            >
                                                <svg v-if="deletingId === item.id" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <circle cx="12" cy="12" r="9" class="opacity-25" />
                                                    <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                                                </svg>
                                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
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
                </div>

                <!-- Kartu (di bawah md) -->
                <ul class="space-y-3 md:hidden">
                    <li
                        v-for="item in filteredItems"
                        :key="`card-${item.id}`"
                        class="rounded-2xl border border-slate-200 bg-white p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ item.title }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">#{{ item.id }}</p>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                                :class="statusClass(item.status)"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(item.status)" aria-hidden="true"></span>
                                {{ item.status }}
                            </span>
                        </div>

                        <dl class="mt-3 space-y-1.5 text-sm">
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Peminjam</dt>
                                <dd class="min-w-0 text-slate-600">
                                    <span class="font-semibold text-slate-900">{{ item.userName }}</span>
                                    <span class="block text-xs text-slate-500">Disetujui: {{ reviewApprovalLabel(item) }}</span>
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Periode</dt>
                                <dd class="min-w-0 text-slate-600">{{ item.borrowDate }} s/d {{ item.returnDate }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Dibuat</dt>
                                <dd class="min-w-0 text-slate-600">{{ item.createdAt }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Item</dt>
                                <dd class="min-w-0 text-slate-600">
                                    {{ item.itemCount }} item
                                    <span
                                        class="ml-1 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="kategoriClass(item.kategori)"
                                    >
                                        {{ item.kategori }}
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                type="button"
                                @click="openDetail(item)"
                            >
                                Detail
                            </button>
                            <button
                                v-if="hasSuratJalan(item)"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300"
                                type="button"
                                @click="openSuratJalan(item)"
                            >
                                Surat Jalan
                            </button>
                            <button
                                v-if="canRepeatPeminjaman(item)"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-2 text-xs font-semibold text-cyan-700 transition hover:border-cyan-300"
                                type="button"
                                @click="repeatPeminjaman(item)"
                            >
                                Ajukan Ulang
                            </button>
                            <button
                                v-if="canManagePeminjaman"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300"
                                type="button"
                                @click="openEdit(item)"
                            >
                                Edit
                            </button>
                            <button
                                v-if="canManagePeminjaman"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-300 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                :disabled="deletingId === item.id"
                                @click="deletePeminjaman(item)"
                            >
                                {{ deletingId === item.id ? 'Menghapus...' : 'Hapus' }}
                            </button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <p class="text-sm text-slate-500">
                    <template v-if="pagination.total">
                        Menampilkan <span class="font-semibold text-slate-700">{{ rangeStart }}&ndash;{{ rangeEnd }}</span>
                        dari <span class="font-semibold text-slate-700">{{ pagination.total }}</span> peminjaman
                    </template>
                    <template v-else>Total {{ pagination.total }} peminjaman</template>
                </p>
                <div class="flex items-center gap-2">
                    <label for="history-per-page" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Baris
                    </label>
                    <select
                        id="history-per-page"
                        v-model.number="perPageChoice"
                        class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-600 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        :disabled="isLoading"
                        @change="changePerPage"
                    >
                        <option v-for="size in perPageOptions" :key="size" :value="size">{{ size }}</option>
                    </select>
                </div>
            </div>

            <nav v-if="pagination.lastPage > 1" class="flex items-center gap-1" aria-label="Navigasi halaman riwayat peminjaman">
                <button
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="pagination.currentPage <= 1 || isLoading"
                    title="Halaman sebelumnya"
                    @click="goToPage(pagination.currentPage - 1)"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                    <span class="hidden sm:inline">Sebelumnya</span>
                </button>

                <button
                    v-for="page in pageNumbers"
                    :key="`page-${page}`"
                    class="min-w-[2.25rem] rounded-lg border px-2 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 disabled:cursor-not-allowed"
                    :class="
                        page === pagination.currentPage
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                    "
                    type="button"
                    :disabled="isLoading"
                    :aria-current="page === pagination.currentPage ? 'page' : undefined"
                    :title="`Halaman ${page}`"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>

                <button
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="pagination.currentPage >= pagination.lastPage || isLoading"
                    title="Halaman berikutnya"
                    @click="goToPage(pagination.currentPage + 1)"
                >
                    <span class="hidden sm:inline">Berikutnya</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 6 6 6-6 6" />
                    </svg>
                </button>
            </nav>
        </div>
    </section>

    <PeminjamanDetailModal
        :open="!!selectedItem"
        :item="selectedItem"
        @close="closeDetail"
    />
    <SuratJalanModal
        :open="!!suratJalanItem"
        :url="suratJalanItem?.suratJalanUrl"
        :path="suratJalanItem?.suratJalanPath"
        :title="suratJalanItem?.title"
        :pengirim-name="suratJalanItem?.pengirimNama"
        :documents="suratJalanDocuments(suratJalanItem)"
        :peminjaman-id="suratJalanItem?.id"
        :peminjaman-status="suratJalanItem?.status"
        @close="suratJalanItem = null"
        @accepted="handleSuratJalanAccepted"
    />

    <teleport to="body">
        <div
            v-if="deleteModal.open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeDeleteModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Konfirmasi</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">{{ deleteModal.title }}</h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeDeleteModal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ deleteModal.heading }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ deleteModal.description }}</p>
                        </div>
                    </div>
                    <label v-if="deleteModal.type === 'area'" class="mt-4 block space-y-2 text-sm font-medium text-slate-700">
                        <span>Masukkan password Anda untuk konfirmasi</span>
                        <input
                            v-model="deletePassword"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Password"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            @keyup.enter="confirmDelete"
                        />
                    </label>
                    <p v-if="deleteError" class="mt-3 text-sm font-semibold text-rose-500">{{ deleteError }}</p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeDeleteModal"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-rose-300"
                        type="button"
                        :disabled="isDeleteSubmitting || (deleteModal.type === 'area' && !deletePassword)"
                        @click="confirmDelete"
                    >
                        {{ isDeleteSubmitting ? 'Menghapus...' : 'Ya, hapus' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="editItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeEdit"
        >
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Peminjaman</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Edit data peminjaman</h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeEdit"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <label class="block space-y-2 text-sm font-medium text-slate-700">
                        <span>Pekerjaan</span>
                        <textarea
                            v-model="editForm.pekerjaan"
                            rows="3"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                    <label class="block space-y-2 text-sm font-medium text-slate-700">
                        <span>Resi</span>
                        <input
                            v-model="editForm.resi"
                            type="text"
                            maxlength="255"
                            placeholder="Opsional"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block space-y-2 text-sm font-medium text-slate-700">
                            <span>Tanggal pinjam</span>
                            <input
                                v-model="editForm.tanggal_pinjam"
                                type="date"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label class="block space-y-2 text-sm font-medium text-slate-700">
                            <span>Tanggal kembali</span>
                            <input
                                v-model="editForm.tanggal_kembali"
                                type="date"
                                :min="editForm.tanggal_pinjam || undefined"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                    </div>
                    <label class="block space-y-2 text-sm font-medium text-slate-700">
                        <span>Status</span>
                        <select
                            v-model="editForm.status"
                            :disabled="isWorkflowStatusProtected"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option v-for="status in editableStatuses" :key="status" :value="status">
                                {{ status }}
                            </option>
                        </select>
                        <span v-if="isWorkflowStatusProtected" class="block text-xs font-normal text-slate-500">
                            Status ini hanya dapat diubah melalui alur operasional terkait.
                        </span>
                    </label>
                    <p v-if="editError" class="text-sm font-semibold text-rose-500">{{ editError }}</p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeEdit"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="isSavingEdit"
                        @click="submitEdit"
                    >
                        {{ isSavingEdit ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PeminjamanDetailModal from '../../Components/PeminjamanDetailModal.vue';
import SuratJalanModal from '../../Components/SuratJalanModal.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Riwayat Peminjaman',
                subtitle: 'Pantau riwayat dan status peminjaman',
                activeMenu: 'riwayat',
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
const activeAreaId = inject('activeAreaId', ref(null));
const setAreaSwitching = inject('setAreaSwitching', null);
const activeAreaName = inject('activeAreaName', ref('Area tidak diketahui'));
const areaName = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaName.value
        : page.props.auth?.user?.area?.name ?? 'Area tidak diketahui'
);
const roleKey = computed(() =>
    String(page.props.auth?.user?.role?.key ?? cachedUser.value?.role?.key ?? '').toLowerCase()
);
const userAreaId = computed(() => page.props.auth?.user?.area_id ?? cachedUser.value?.area_id ?? null);
const currentAreaId = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaId.value
        : userAreaId.value
);
const canManagePeminjaman = computed(() => ['admin', 'super_admin'].includes(roleKey.value));
const canDeleteAreaPeminjaman = computed(() => roleKey.value === 'super_admin' && !!currentAreaId.value);

const items = ref([]);
const isLoading = ref(false);
const isExporting = ref(false);
const isBulkDeleting = ref(false);
const loadError = ref('');
const statusCounts = ref({});
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 8,
});
const perPageOptions = [8, 16, 32, 64];
const perPageChoice = ref(8);

const search = ref('');
const statusFilter = ref('Semua');
const kategoriFilter = ref('Semua');
const selectedItem = ref(null);
const suratJalanItem = ref(null);
const editItem = ref(null);
const isSavingEdit = ref(false);
const deletingId = ref(null);
const deleteError = ref('');
const deletePassword = ref('');
const deleteModal = reactive({
    open: false,
    type: '',
    item: null,
    title: '',
    heading: '',
    description: '',
});
const editError = ref('');
const editForm = reactive({
    pekerjaan: '',
    resi: '',
    tanggal_pinjam: '',
    tanggal_kembali: '',
    status: '',
});
const safeEditableStatuses = [
    'Perlu Disetujui',
    'Perlu Direview',
    'Disetujui',
    'Ditolak',
];
const workflowProtectedStatuses = new Set([
    'Dikirim',
    'Diterima',
    'Dikembalikan Partials',
    'Dikembalikan Semuanya',
    'Selesai',
]);
const isWorkflowStatusProtected = computed(() => workflowProtectedStatuses.has(editItem.value?.status));
const editableStatuses = computed(() =>
    isWorkflowStatusProtected.value ? [editItem.value.status] : safeEditableStatuses
);
const REPEAT_DRAFT_STORAGE_KEY = 'peminjaman_repeat_draft_v1';

const filteredItems = computed(() => items.value);

const totalCount = computed(() => (pagination.total ? pagination.total : items.value.length));

// Rekap status datang dari backend (status_counts) sehingga mencakup seluruh data
// yang lolos filter, bukan hanya baris yang kebetulan tampil di halaman ini.
const countByStatus = (...statuses) =>
    statuses.reduce((total, status) => total + Number(statusCounts.value[status] ?? 0), 0);

const reviewCount = computed(() => countByStatus('Perlu Direview', 'Perlu Disetujui'));
const processCount = computed(() => countByStatus('Disetujui'));
const deliveredCount = computed(() => countByStatus('Dikirim'));

const summaryHint = computed(() => (hasActiveFilters.value ? 'Sesuai filter aktif' : 'Seluruh riwayat'));

const summaryCards = computed(() => [
    {
        label: 'Total',
        value: totalCount.value,
        hint: summaryHint.value,
        icon: 'M3 4h18l-7 8v6l-4 2v-8L3 4z',
        iconClass: 'bg-slate-100 text-slate-500',
        valueClass: 'text-slate-900',
    },
    {
        label: 'Perlu Review',
        value: reviewCount.value,
        hint: 'Menunggu review/persetujuan',
        icon: 'M12 8v4l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z',
        iconClass: 'bg-blue-50 text-blue-600',
        valueClass: 'text-blue-600',
    },
    {
        label: 'Disetujui',
        value: processCount.value,
        hint: 'Siap dikirim',
        icon: 'm9 12 2 2 4-4M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18Z',
        iconClass: 'bg-amber-50 text-amber-600',
        valueClass: 'text-amber-500',
    },
    {
        label: 'Dikirim',
        value: deliveredCount.value,
        hint: 'Menunggu diterima',
        icon: 'M3 7h11v8H3zM14 10h4l3 3v2h-7zM7.5 18.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3ZM17.5 18.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z',
        iconClass: 'bg-emerald-50 text-emerald-600',
        valueClass: 'text-emerald-600',
    },
]);

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || statusFilter.value !== 'Semua' || kategoriFilter.value !== 'Semua'
);

const resetFilters = () => {
    search.value = '';
    statusFilter.value = 'Semua';
    kategoriFilter.value = 'Semua';
};

const changePerPage = () => {
    pagination.perPage = Number(perPageChoice.value) || 8;
    pagination.currentPage = 1;
    loadHistory();
};

const rangeStart = computed(() =>
    pagination.total === 0 ? 0 : (pagination.currentPage - 1) * pagination.perPage + 1
);

const rangeEnd = computed(() =>
    Math.min(pagination.currentPage * pagination.perPage, pagination.total)
);

// createdAt dikirim backend sebagai "d M Y H:i", dipisah hanya untuk tampilan dan
// otomatis kembali menampilkan nilai apa adanya bila formatnya berbeda.
const splitDateTime = (value) => {
    const raw = String(value ?? '').trim();
    if (!raw) {
        return { date: '-', time: '' };
    }

    const separatorIndex = raw.lastIndexOf(' ');
    if (separatorIndex === -1) {
        return { date: raw, time: '' };
    }

    const time = raw.slice(separatorIndex + 1);
    if (!/^\d{1,2}:\d{2}(:\d{2})?$/.test(time)) {
        return { date: raw, time: '' };
    }

    return { date: raw.slice(0, separatorIndex), time };
};

const pageNumbers = computed(() => {
    const total = pagination.lastPage;
    const current = pagination.currentPage;
    const delta = 2;
    const start = Math.max(1, current - delta);
    const end = Math.min(total, current + delta);
    const pages = [];
    for (let i = start; i <= end; i += 1) {
        pages.push(i);
    }
    return pages;
});

const openDetail = (item) => {
    selectedItem.value = item;
};

const openSuratJalan = (item) => {
    suratJalanItem.value = item;
};

const suratJalanDocuments = (item) => {
    if (!item) {
        return [];
    }

    if (Array.isArray(item.suratJalanItems) && item.suratJalanItems.length) {
        return item.suratJalanItems.filter((document) => document.url || document.path || document.downloadUrl);
    }

    return [
        item.suratJalanUrl || item.suratJalanPath || item.suratJalanDownloadUrl
            ? {
                  label: 'Surat Jalan Pengiriman',
                  url: item.suratJalanUrl,
                  path: item.suratJalanPath,
                  downloadUrl: item.suratJalanDownloadUrl,
                  originalName: item.suratJalanOriginalName,
                  type: item.suratJalanType,
                  pengirimName: item.pengirimNama,
              }
            : null,
        item.returnSuratJalanUrl || item.returnSuratJalanPath || item.returnSuratJalanDownloadUrl
            ? {
                  label: 'Surat Jalan Pengembalian',
                  url: item.returnSuratJalanUrl,
                  path: item.returnSuratJalanPath,
                  downloadUrl: item.returnSuratJalanDownloadUrl,
                  originalName: item.returnSuratJalanOriginalName,
                  type: item.returnSuratJalanType,
                  pengirimName: item.pengembaliNama,
              }
            : null,
    ].filter(Boolean);
};

const hasSuratJalan = (item) => suratJalanDocuments(item).length > 0;
const canCreateIntraPeminjaman = computed(() => ['user', 'admin', 'super_admin'].includes(roleKey.value));
const canRepeatPeminjaman = (item) =>
    canCreateIntraPeminjaman.value
    && item?.kategori === 'Intra Area'
    && !item?.isInterArea
    && Array.isArray(item?.tools)
    && item.tools.some((tool) => tool.alat_id && Number(tool.qty ?? 0) > 0);

const closeDetail = () => {
    selectedItem.value = null;
};

const repeatPeminjaman = (item) => {
    if (!canRepeatPeminjaman(item) || typeof window === 'undefined') {
        return;
    }

    const draftItems = item.tools
        .map((tool) => {
            const qty = Number(tool?.qty ?? 0);
            const stockHint = Math.max(
                qty,
                Number(tool?.approvedQty ?? 0),
                Number(tool?.remainingQty ?? 0),
                1
            );

            return {
                id: tool?.alat_id ?? null,
                qty,
                kode: tool?.code ?? '-',
                nama: tool?.name ?? '-',
                stok: stockHint,
            };
        })
        .filter((tool) => tool.id && tool.qty > 0);

    if (!draftItems.length) {
        return;
    }

    window.sessionStorage.setItem(
        REPEAT_DRAFT_STORAGE_KEY,
        JSON.stringify({
            source_id: item.id,
            pekerjaan: item.title && item.title !== '-' ? item.title : '',
            tanggal_pinjam: item.borrowDateValue ?? '',
            tanggal_kembali: item.returnDateValue ?? '',
            area_id: item.areaId ?? currentAreaId.value ?? null,
            items: draftItems,
        })
    );

    router.visit('/peminjaman', {
        data: {
            repeat_from: item.id,
        },
    });
};

const openEdit = (item) => {
    editItem.value = item;
    editError.value = '';
    editForm.pekerjaan = item?.title ?? '';
    editForm.resi = item?.resi ?? '';
    editForm.tanggal_pinjam = item?.borrowDateValue ?? '';
    editForm.tanggal_kembali = item?.returnDateValue ?? '';
    editForm.status = item?.status ?? 'Perlu Disetujui';
};

const closeEdit = () => {
    if (isSavingEdit.value) {
        return;
    }
    editItem.value = null;
    editError.value = '';
};

const submitEdit = async () => {
    if (!editItem.value?.id || isSavingEdit.value) {
        return;
    }
    if (!editForm.pekerjaan.trim() || !editForm.tanggal_pinjam || !editForm.tanggal_kembali || !editForm.status) {
        editError.value = 'Lengkapi pekerjaan, periode, dan status.';
        return;
    }
    if (editForm.tanggal_kembali < editForm.tanggal_pinjam) {
        editError.value = 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.';
        return;
    }

    isSavingEdit.value = true;
    try {
        await axios.put(`/api/peminjaman/${editItem.value.id}`, {
            pekerjaan: editForm.pekerjaan.trim(),
            resi: editForm.resi?.trim() || null,
            tanggal_pinjam: editForm.tanggal_pinjam,
            tanggal_kembali: editForm.tanggal_kembali,
            status: editForm.status,
        });
        editItem.value = null;
        editError.value = '';
        await loadHistory();
    } catch (error) {
        const errors = error?.response?.data?.errors ?? {};
        editError.value =
            errors.pekerjaan?.[0] ??
            errors.resi?.[0] ??
            errors.tanggal_pinjam?.[0] ??
            errors.tanggal_kembali?.[0] ??
            errors.status?.[0] ??
            error?.response?.data?.message ??
            'Gagal menyimpan data peminjaman.';
    } finally {
        isSavingEdit.value = false;
    }
};

const deletePeminjaman = async (item) => {
    if (!item?.id || deletingId.value) {
        return;
    }
    deleteModal.open = true;
    deleteModal.type = 'single';
    deleteModal.item = item;
    deleteModal.title = 'Hapus peminjaman';
    deleteModal.heading = `Hapus peminjaman #${item.id}?`;
    deleteModal.description = `${item.title || '-'} akan dihapus beserta data terkaitnya.`;
    deleteError.value = '';
};

const deleteActiveAreaPeminjaman = async () => {
    if (!canDeleteAreaPeminjaman.value || isBulkDeleting.value) {
        return;
    }
    deleteModal.open = true;
    deleteModal.type = 'area';
    deleteModal.item = null;
    deleteModal.title = 'Hapus semua peminjaman';
    deleteModal.heading = `Hapus semua data pada ${areaName.value}?`;
    deleteModal.description = 'Semua data peminjaman pada area aktif akan dihapus beserta data terkaitnya.';
    deleteError.value = '';
    deletePassword.value = '';
};

const isDeleteSubmitting = computed(() =>
    deleteModal.type === 'area'
        ? isBulkDeleting.value
        : !!deletingId.value
);

const closeDeleteModal = () => {
    if (isDeleteSubmitting.value) {
        return;
    }
    deleteModal.open = false;
    deleteModal.type = '';
    deleteModal.item = null;
    deleteError.value = '';
    deletePassword.value = '';
};

const confirmDelete = async () => {
    if (!deleteModal.open || isDeleteSubmitting.value) {
        return;
    }

    if (deleteModal.type === 'area') {
        await confirmDeleteArea();
        return;
    }

    await confirmDeleteSingle();
};

const confirmDeleteSingle = async () => {
    const item = deleteModal.item;
    if (!item?.id) {
        closeDeleteModal();
        return;
    }

    deletingId.value = item.id;
    try {
        await axios.delete(`/api/peminjaman/${item.id}`);
        deletingId.value = null;
        closeDeleteModal();
        await loadHistory();
    } catch (error) {
        deleteError.value = error?.response?.data?.message ?? 'Gagal menghapus peminjaman.';
    } finally {
        deletingId.value = null;
    }
};

const confirmDeleteArea = async () => {
    if (!canDeleteAreaPeminjaman.value) {
        closeDeleteModal();
        return;
    }
    if (!deletePassword.value) {
        deleteError.value = 'Masukkan password Anda untuk konfirmasi.';
        return;
    }

    isBulkDeleting.value = true;
    deleteError.value = '';
    try {
        await axios.delete('/api/peminjaman/area', {
            data: { area_id: currentAreaId.value, password: deletePassword.value },
        });
        pagination.currentPage = 1;
        isBulkDeleting.value = false;
        closeDeleteModal();
        await loadHistory();
    } catch (error) {
        deleteError.value = error?.response?.data?.message ?? 'Gagal menghapus semua peminjaman pada area aktif.';
    } finally {
        isBulkDeleting.value = false;
    }
};

const handleSuratJalanAccepted = async () => {
    await loadHistory();
    suratJalanItem.value = null;
};

const normalizeHistory = (item) => {
    const tools = Array.isArray(item?.tools)
        ? item.tools.map((tool) => ({
              alat_id: tool?.alat_id ?? null,
              name: tool?.name ?? '-',
              code: tool?.code ?? '-',
              qty: Number.isFinite(tool?.qty) ? tool.qty : 0,
              approvedQty: Number.isFinite(tool?.approved_qty) ? tool.approved_qty : null,
              returnedQty: Number.isFinite(tool?.returned_qty) ? tool.returned_qty : 0,
              remainingQty: Number.isFinite(tool?.remaining_qty) ? tool.remaining_qty : 0,
              reviewStatus: tool?.review_status ?? 'Menunggu Review',
              rejectionReason: tool?.rejection_reason ?? '',
              reports: Array.isArray(tool?.reports)
                  ? tool.reports.map((report) => ({
                        id: report?.id ?? null,
                        alatId: report?.alat_id ?? tool?.alat_id ?? null,
                        alatName: report?.alat_name ?? tool?.name ?? '-',
                        alatCode: report?.alat_code ?? tool?.code ?? '-',
                        kategori: report?.kategori ?? '-',
                        status: report?.status ?? 'Dilaporkan',
                        jumlah: Number.isFinite(report?.jumlah) ? report.jumlah : 0,
                        deskripsi: report?.deskripsi ?? '',
                        createdAt: report?.created_at ?? '-',
                        url: report?.url ?? report?.path ?? '',
                        path: report?.path ?? '',
                        originalName: report?.original_name ?? '',
                    }))
                  : [],
          }))
        : [];

    return {
        id: item?.id ?? '',
        title: item?.title ?? '-',
        resi: item?.resi ?? '',
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
        borrowDateValue: item?.borrow_date_value ?? '',
        returnDate: item?.return_date ?? '-',
        returnDateValue: item?.return_date_value ?? '',
        itemCount: Number.isFinite(item?.item_count) ? item.item_count : 0,
        status: item?.status ?? 'Perlu Disetujui',
        kategori: item?.kategori ?? 'Intra Area',
        pengirimNama: item?.pengirim_nama ?? '',
        suratJalanUrl: item?.surat_jalan_url ?? '',
        suratJalanPath: item?.surat_jalan_path ?? '',
        suratJalanDownloadUrl: item?.surat_jalan_download_url ?? item?.surat_jalan_url ?? '',
        suratJalanOriginalName: item?.surat_jalan_original_name ?? '',
        suratJalanType: item?.surat_jalan_type ?? '',
        pengembaliNama: item?.pengembali_nama ?? '',
        returnSuratJalanUrl: item?.surat_jalan_pengembalian_url ?? '',
        returnSuratJalanPath: item?.surat_jalan_pengembalian_path ?? '',
        returnSuratJalanDownloadUrl: item?.surat_jalan_pengembalian_download_url ?? item?.surat_jalan_pengembalian_url ?? '',
        returnSuratJalanOriginalName: item?.surat_jalan_pengembalian_original_name ?? '',
        returnSuratJalanType: item?.surat_jalan_pengembalian_type ?? '',
        suratJalanItems: Array.isArray(item?.surat_jalan_items)
            ? item.surat_jalan_items.map((document, index) => ({
                  id: document?.id ?? null,
                  type: document?.type ?? document?.document_type ?? '',
                  label: document?.label ?? `Surat Jalan ${index + 1}`,
                  url: document?.url ?? '',
                  path: document?.path ?? '',
                  downloadUrl: document?.download_url ?? document?.downloadUrl ?? document?.url ?? '',
                  originalName: document?.original_name ?? document?.originalName ?? '',
                  mimeType: document?.mime_type ?? document?.mimeType ?? '',
                  pengirimName: document?.pengirim_nama ?? '',
                  createdAt: document?.created_at ?? '',
              }))
            : [],
        tools,
        reports: Array.isArray(item?.reports)
            ? item.reports.map((report) => ({
                  id: report?.id ?? null,
                  alatId: report?.alat_id ?? null,
                  alatName: report?.alat_name ?? '-',
                  alatCode: report?.alat_code ?? '-',
                  kategori: report?.kategori ?? '-',
                  status: report?.status ?? 'Dilaporkan',
                  jumlah: Number.isFinite(report?.jumlah) ? report.jumlah : 0,
                  deskripsi: report?.deskripsi ?? '',
                  createdAt: report?.created_at ?? '-',
                  url: report?.url ?? report?.path ?? '',
                  path: report?.path ?? '',
                  originalName: report?.original_name ?? '',
              }))
            : [],
    };
};

const reviewApprovalLabel = (item) => {
    const reviewer = item?.requesterReviewerName && item.requesterReviewerName !== '-'
        ? item.requesterReviewerName
        : item?.reviewerName && item.reviewerName !== '-'
            ? item.reviewerName
            : '-';
    const approver = item?.reviewerName && item.reviewerName !== '-' ? item.reviewerName : '-';

    return item?.kategori === 'Antar Area' ? `${reviewer}/${approver}` : approver;
};

const statusClass = (status) => {
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
        case 'Dikembalikan':
            return 'bg-indigo-50 text-indigo-600';
        case 'Ditolak':
            return 'bg-rose-50 text-rose-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

// Titik warna hanya penegas; label teks status tetap menjadi pembeda utamanya.
const statusDotClass = (status) => {
    switch (status) {
        case 'Perlu Direview':
        case 'Perlu Disetujui':
            return 'bg-blue-500';
        case 'Disetujui':
            return 'bg-cyan-500';
        case 'Dikirim':
            return 'bg-emerald-500';
        case 'Diterima':
            return 'bg-teal-500';
        case 'Dikembalikan Partials':
            return 'bg-violet-500';
        case 'Dikembalikan Semuanya':
        case 'Dikembalikan':
            return 'bg-indigo-500';
        case 'Selesai':
            return 'bg-slate-500';
        case 'Ditolak':
            return 'bg-rose-500';
        default:
            return 'bg-slate-400';
    }
};

const kategoriClass = (kategori) => {
    switch (kategori) {
        case 'Antar Area':
            return 'bg-purple-50 text-purple-700';
        case 'Intra Area':
            return 'bg-sky-50 text-sky-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

let filterTimeout = null;

const buildFilterParams = () => {
    const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage,
    };
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (statusFilter.value && statusFilter.value !== 'Semua') {
        params.status = statusFilter.value;
    }
    if (kategoriFilter.value && kategoriFilter.value !== 'Semua') {
        params.kategori = kategoriFilter.value;
    }
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }
    return params;
};

const goToPage = (page) => {
    const next = Math.min(Math.max(1, page), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }
    pagination.currentPage = next;
    loadHistory();
};

const buildStatusCountsFromItems = (rows) =>
    rows.reduce((counts, row) => {
        counts[row.status] = (counts[row.status] ?? 0) + 1;

        return counts;
    }, {});

const loadHistory = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/peminjaman', {
            params: buildFilterParams(),
        });
        const payload = response.data;
        if (Array.isArray(payload)) {
            items.value = payload.map((item) => normalizeHistory(item));
            pagination.total = items.value.length;
            pagination.lastPage = 1;
            pagination.currentPage = 1;
            statusCounts.value = buildStatusCountsFromItems(items.value);
            return;
        }
        const data = Array.isArray(payload?.data) ? payload.data : [];
        const meta = payload?.meta ?? payload ?? {};
        items.value = data.map((item) => normalizeHistory(item));
        pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
        pagination.lastPage = Number(meta.last_page ?? pagination.lastPage) || 1;
        pagination.perPage = Number(meta.per_page ?? pagination.perPage) || pagination.perPage;
        pagination.total = Number(meta.total ?? pagination.total) || items.value.length;
        perPageChoice.value = pagination.perPage;
        // Backend lama belum mengirim status_counts; jatuh kembali ke hitungan
        // halaman aktif supaya kartu tetap terisi alih-alih menampilkan nol.
        statusCounts.value = payload?.status_counts ?? buildStatusCountsFromItems(items.value);
    } catch (error) {
        items.value = [];
        statusCounts.value = {};
        loadError.value = 'Gagal memuat data peminjaman.';
    } finally {
        isLoading.value = false;
    }
};

const exportHistory = async () => {
    isExporting.value = true;
    try {
        const response = await axios.get('/api/peminjaman/export', {
            params: buildFilterParams(),
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        const disposition = response.headers['content-disposition'] ?? '';
        const match = disposition.match(/filename="?([^"]+)"?/i);
        link.href = url;
        link.download = match?.[1] ?? 'riwayat-peminjaman.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        loadError.value = 'Gagal mengunduh export riwayat peminjaman.';
    } finally {
        isExporting.value = false;
    }
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
        pagination.currentPage = 1;
        try {
            await loadHistory();
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);

watch(
    [() => search.value, () => statusFilter.value, () => kategoriFilter.value],
    () => {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }
        filterTimeout = setTimeout(() => {
            pagination.currentPage = 1;
            loadHistory();
        }, 300);
    },
);
</script>
