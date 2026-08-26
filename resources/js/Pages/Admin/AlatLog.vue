<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Log Alat</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau aktivitas penambahan, perubahan stok, update data, dan penghapusan alat.</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
        <!-- Filter -->
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="min-w-0 flex-1">
                <label for="alat-log-search" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Cari Log Alat
                </label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </span>
                    <input
                        id="alat-log-search"
                        v-model="draftSearch"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-9 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        type="text"
                        placeholder="Cari nama alat, pengubah, aksi, atau detail..."
                        aria-describedby="alat-log-search-hint"
                        @keyup.enter="applyFilters"
                    />
                    <button
                        v-if="draftSearch"
                        class="absolute inset-y-0 right-2 my-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                        type="button"
                        title="Kosongkan pencarian"
                        aria-label="Kosongkan pencarian"
                        @click="draftSearch = ''"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p id="alat-log-search-hint" class="mt-1.5 text-xs text-slate-400">
                    Pencarian mencakup nama alat, nama pengubah, dan keterangan perubahan.
                </p>
            </div>

            <div class="w-full lg:w-52">
                <label for="alat-log-action" class="mb-2 block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                    Aksi
                </label>
                <select
                    id="alat-log-action"
                    v-model="draftAction"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                >
                    <option value="Semua">Semua Aksi</option>
                    <option value="create">Penambahan</option>
                    <option value="update">Update</option>
                    <option value="delete">Penghapusan</option>
                </select>
            </div>

            <div class="flex flex-wrap gap-2">
                <button
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 text-sm font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="isExporting || isLoading"
                    :title="isExporting ? 'Sedang mengunduh berkas CSV' : 'Unduh hasil sesuai filter aktif sebagai CSV'"
                    @click="exportCsv"
                >
                    <svg v-if="isExporting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" class="opacity-25" />
                        <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                    </svg>
                    <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <path d="M7 10l5 5 5-5" />
                        <path d="M12 15V3" />
                    </svg>
                    {{ isExporting ? 'Mengunduh...' : 'Export CSV' }}
                </button>
                <button
                    class="relative inline-flex h-11 items-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 focus-visible:ring-offset-2"
                    type="button"
                    title="Terapkan pencarian dan filter"
                    @click="applyFilters"
                >
                    Terapkan
                    <span
                        v-if="hasPendingChanges"
                        class="absolute -right-1 -top-1 flex h-3 w-3"
                        title="Ada perubahan filter yang belum diterapkan"
                    >
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-300 opacity-75"></span>
                        <span class="relative inline-flex h-3 w-3 rounded-full border-2 border-white bg-amber-400"></span>
                    </span>
                </button>
                <button
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300 disabled:cursor-not-allowed disabled:opacity-50"
                    type="button"
                    :disabled="!hasActiveFilters && !hasPendingChanges"
                    title="Kembalikan filter ke kondisi awal"
                    @click="resetFilters"
                >
                    Reset
                </button>
            </div>
        </div>

        <!-- Filter aktif -->
        <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Filter aktif</span>
            <span
                v-if="filters.search.trim()"
                class="inline-flex max-w-full items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                <span class="truncate">Pencarian: "{{ filters.search.trim() }}"</span>
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter pencarian"
                    aria-label="Hapus filter pencarian"
                    @click="clearSearchFilter"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
            <span
                v-if="filters.action !== 'Semua'"
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                Aksi: {{ actionOptionLabel(filters.action) }}
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter aksi"
                    aria-label="Hapus filter aksi"
                    @click="clearActionFilter"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
        </div>

        <!-- Hasil -->
        <div class="mt-5" aria-live="polite" :aria-busy="isLoading">
            <!-- Loading -->
            <div v-if="isLoading" class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" class="opacity-25" />
                        <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                    </svg>
                    Memuat log alat...
                </div>
                <div class="divide-y divide-slate-100 bg-white">
                    <div v-for="row in 5" :key="`skeleton-${row}`" class="flex items-center gap-4 px-4 py-4">
                        <div class="h-9 w-24 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 w-48 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-6 w-28 shrink-0 animate-pulse rounded-full bg-slate-100"></div>
                        <div class="h-9 w-24 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 flex-1 animate-pulse rounded-lg bg-slate-100"></div>
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
                    @click="loadLogs(pagination.currentPage)"
                >
                    Coba lagi
                </button>
            </div>

            <!-- Empty -->
            <div v-else-if="!logs.length" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-12 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6.6 6.6a2 2 0 0 0 2.8 2.8l6.6-6.6a4 4 0 0 0 5.4-5.4l-3 3-2.8-2.8 3-3Z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-700">
                    {{ hasActiveFilters ? 'Tidak ada log alat yang cocok' : 'Belum ada log alat' }}
                </p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                    {{
                        hasActiveFilters
                            ? 'Coba ubah kata kunci atau pilih aksi lain untuk memperluas hasil pencarian.'
                            : 'Riwayat penambahan, perubahan stok, dan penghapusan alat akan muncul di sini.'
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
                                Riwayat perubahan data alat beserta pergerakan stok dan pengubahnya
                            </caption>
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Waktu</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Alat</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Aktivitas</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3 text-right">Total Aset</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Pengubah</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Keterangan</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3 text-right">Detail</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <template v-for="log in logs" :key="log.id">
                                    <tr class="align-top transition hover:bg-slate-50/70">
                                        <td class="whitespace-nowrap px-4 py-4">
                                            <p class="font-medium text-slate-700">{{ splitDateTime(log.created_at).date }}</p>
                                            <p class="mt-0.5 text-xs tabular-nums text-slate-500">
                                                {{ splitDateTime(log.created_at).time }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span
                                                class="inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold tracking-tight text-slate-600"
                                                title="Tools ID"
                                            >
                                                {{ log.alat_code }}
                                            </span>
                                            <p class="mt-1 line-clamp-2 font-semibold text-slate-900" :title="log.alat_name">
                                                {{ log.alat_name }}
                                            </p>
                                            <p class="mt-0.5 line-clamp-1 text-xs text-slate-500" :title="`${log.jenis_alat} - ${log.klasifikasi_alat}`">
                                                {{ log.jenis_alat }} &middot; {{ log.klasifikasi_alat }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span
                                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                                                :class="actionBadgeClass(log.action_label)"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full" :class="actionDotClass(log.action_label)" aria-hidden="true"></span>
                                                {{ log.action_label }}
                                            </span>
                                        </td>
                                        <td class="whitespace-nowrap px-4 py-4 text-right">
                                            <p class="tabular-nums text-slate-600">
                                                <span class="text-slate-400">{{ log.total_aset_before }}</span>
                                                <span class="mx-1 text-slate-300" aria-label="menjadi">&rarr;</span>
                                                <span class="font-semibold text-slate-900">{{ log.total_aset_after }}</span>
                                            </p>
                                            <span
                                                v-if="log.stock_delta !== null && log.stock_delta !== undefined"
                                                class="mt-1 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums"
                                                :class="stockDeltaClass(log.stock_delta)"
                                                :title="stockDeltaTitle(log.stock_delta)"
                                            >
                                                <svg
                                                    v-if="Number(log.stock_delta) !== 0"
                                                    class="h-3 w-3"
                                                    :class="Number(log.stock_delta) > 0 ? '' : 'rotate-180'"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                                                >
                                                    <path d="m5 15 7-7 7 7" />
                                                </svg>
                                                {{ log.stock_delta_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <p class="font-medium text-slate-800">{{ log.actor_name }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ log.actor_role_label }}
                                                <span v-if="log.area_name && log.area_name !== '-'"> &middot; {{ log.area_name }}</span>
                                            </p>
                                        </td>
                                        <td class="max-w-[20rem] px-4 py-4">
                                            <p class="line-clamp-2 text-slate-600" :title="log.change_summary">
                                                {{ log.change_summary }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-4 text-right">
                                            <button
                                                v-if="log.changes?.length"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                                                type="button"
                                                :aria-expanded="isExpanded(log.id)"
                                                :aria-controls="`alat-log-detail-${log.id}`"
                                                :title="isExpanded(log.id) ? 'Sembunyikan detail perubahan' : 'Lihat detail perubahan'"
                                                @click="toggleExpanded(log.id)"
                                            >
                                                {{ isExpanded(log.id) ? 'Tutup' : 'Detail' }}
                                                <svg
                                                    class="h-3.5 w-3.5 transition-transform"
                                                    :class="isExpanded(log.id) ? 'rotate-180' : ''"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                                                >
                                                    <path d="m6 9 6 6 6-6" />
                                                </svg>
                                            </button>
                                            <span v-else class="text-xs italic text-slate-400">Tidak ada</span>
                                        </td>
                                    </tr>
                                    <tr v-if="isExpanded(log.id)" :key="`${log.id}-detail`" class="bg-slate-50/80">
                                        <td :id="`alat-log-detail-${log.id}`" colspan="7" class="px-4 py-4">
                                            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                                                <table class="w-full text-xs">
                                                    <thead class="bg-slate-50">
                                                        <tr class="text-left font-semibold uppercase tracking-[0.14em] text-slate-500">
                                                            <th scope="col" class="px-3 py-2">Field</th>
                                                            <th scope="col" class="px-3 py-2">Sebelum</th>
                                                            <th scope="col" class="px-3 py-2">Sesudah</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <tr v-for="change in log.changes" :key="`${log.id}-${change.field}`">
                                                            <th scope="row" class="px-3 py-2 text-left font-semibold text-slate-600">
                                                                {{ change.label }}
                                                            </th>
                                                            <td class="px-3 py-2 text-rose-700">
                                                                <span class="break-words" :class="isEmptyValue(change.before) ? 'italic text-slate-400' : ''">
                                                                    {{ formatValue(change.before) }}
                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2 text-emerald-700">
                                                                <span class="break-words" :class="isEmptyValue(change.after) ? 'italic text-slate-400' : ''">
                                                                    {{ formatValue(change.after) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kartu (di bawah md) -->
                <ul class="space-y-3 md:hidden">
                    <li
                        v-for="log in logs"
                        :key="`card-${log.id}`"
                        class="rounded-2xl border border-slate-200 bg-white p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span
                                    class="inline-flex items-center rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600"
                                    title="Tools ID"
                                >
                                    {{ log.alat_code }}
                                </span>
                                <p class="mt-1 font-semibold text-slate-900">{{ log.alat_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ log.jenis_alat }} &middot; {{ log.klasifikasi_alat }}</p>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                                :class="actionBadgeClass(log.action_label)"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="actionDotClass(log.action_label)" aria-hidden="true"></span>
                                {{ log.action_label }}
                            </span>
                        </div>

                        <dl class="mt-3 space-y-1.5 text-sm">
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Waktu</dt>
                                <dd class="text-slate-600">
                                    {{ splitDateTime(log.created_at).date }}
                                    <span class="tabular-nums text-slate-500">{{ splitDateTime(log.created_at).time }}</span>
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Total Aset</dt>
                                <dd class="tabular-nums text-slate-600">
                                    <span class="text-slate-400">{{ log.total_aset_before }}</span>
                                    <span class="mx-1 text-slate-300">&rarr;</span>
                                    <span class="font-semibold text-slate-900">{{ log.total_aset_after }}</span>
                                    <span
                                        v-if="log.stock_delta !== null && log.stock_delta !== undefined"
                                        class="ml-1.5 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="stockDeltaClass(log.stock_delta)"
                                    >
                                        {{ log.stock_delta_label }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Pengubah</dt>
                                <dd class="min-w-0 text-slate-600">
                                    {{ log.actor_name }}
                                    <span class="block text-xs text-slate-500">
                                        {{ log.actor_role_label }}
                                        <span v-if="log.area_name && log.area_name !== '-'"> &middot; {{ log.area_name }}</span>
                                    </span>
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Keterangan</dt>
                                <dd class="min-w-0 break-words text-slate-600">{{ log.change_summary }}</dd>
                            </div>
                        </dl>

                        <button
                            v-if="log.changes?.length"
                            class="mt-3 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                            type="button"
                            :aria-expanded="isExpanded(log.id)"
                            :aria-controls="`alat-log-detail-card-${log.id}`"
                            @click="toggleExpanded(log.id)"
                        >
                            {{ isExpanded(log.id) ? 'Tutup detail perubahan' : 'Lihat detail perubahan' }}
                            <svg
                                class="h-3.5 w-3.5 transition-transform"
                                :class="isExpanded(log.id) ? 'rotate-180' : ''"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                            >
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <dl
                            v-if="isExpanded(log.id)"
                            :id="`alat-log-detail-card-${log.id}`"
                            class="mt-3 space-y-2 rounded-xl border border-slate-200 bg-slate-50/70 p-3"
                        >
                            <div v-for="change in log.changes" :key="`card-${log.id}-${change.field}`">
                                <dt class="text-xs font-semibold text-slate-600">{{ change.label }}</dt>
                                <dd class="mt-0.5 text-xs">
                                    <span class="break-words text-rose-700">{{ formatValue(change.before) }}</span>
                                    <span class="mx-1 text-slate-400">&rarr;</span>
                                    <span class="break-words text-emerald-700">{{ formatValue(change.after) }}</span>
                                </dd>
                            </div>
                        </dl>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer: rentang, jumlah baris, dan navigasi halaman -->
        <div class="mt-4 flex flex-col gap-3 border-t border-slate-200 pt-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <p class="text-sm text-slate-500">
                    <template v-if="pagination.total">
                        Menampilkan <span class="font-semibold text-slate-700">{{ rangeStart }}&ndash;{{ rangeEnd }}</span>
                        dari <span class="font-semibold text-slate-700">{{ pagination.total }}</span> log alat
                    </template>
                    <template v-else>Total {{ pagination.total }} log alat</template>
                </p>
                <div class="flex items-center gap-2">
                    <label for="alat-log-per-page" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Baris
                    </label>
                    <select
                        id="alat-log-per-page"
                        v-model.number="perPageChoice"
                        class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-600 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        :disabled="isLoading"
                        @change="changePerPage"
                    >
                        <option v-for="size in perPageOptions" :key="size" :value="size">{{ size }}</option>
                    </select>
                </div>
            </div>

            <nav class="flex items-center gap-1" aria-label="Navigasi halaman log alat">
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
                    v-for="pageNumber in pageNumbers"
                    :key="`page-${pageNumber}`"
                    class="min-w-[2.25rem] rounded-lg border px-2 py-2 text-xs font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200 disabled:cursor-not-allowed"
                    :class="
                        pageNumber === pagination.currentPage
                            ? 'border-blue-600 bg-blue-600 text-white'
                            : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                    "
                    type="button"
                    :disabled="isLoading"
                    :aria-current="pageNumber === pagination.currentPage ? 'page' : undefined"
                    :title="`Halaman ${pageNumber}`"
                    @click="goToPage(pageNumber)"
                >
                    {{ pageNumber }}
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
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Log Alat',
                subtitle: 'Pantau aktivitas perubahan data alat',
                activeMenu: 'alat-log',
            },
            () => page
        ),
});

const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const setAreaSwitching = inject('setAreaSwitching', null);

const logs = ref([]);
const isLoading = ref(false);
const isExporting = ref(false);
const loadError = ref('');

const filters = reactive({
    search: '',
    action: 'Semua',
});

const draftSearch = ref('');
const draftAction = ref('Semua');

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    perPage: 10,
    total: 0,
});

const perPageOptions = [10, 25, 50, 100];
const perPageChoice = ref(10);
const expandedIds = ref(new Set());

const actionOptions = {
    create: 'Penambahan',
    update: 'Update',
    delete: 'Penghapusan',
};

const buildParams = () => {
    const params = {};

    if (filters.search.trim()) {
        params.search = filters.search.trim();
    }
    if (filters.action !== 'Semua') {
        params.action = filters.action;
    }
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }

    return params;
};

const loadLogs = async (pageNumber = 1) => {
    isLoading.value = true;
    loadError.value = '';

    try {
        const response = await axios.get('/api/alat-logs', {
            params: {
                page: pageNumber,
                per_page: pagination.perPage,
                ...buildParams(),
            },
        });
        logs.value = Array.isArray(response.data?.data) ? response.data.data : [];
        pagination.currentPage = Number(response.data?.meta?.current_page ?? 1);
        pagination.lastPage = Number(response.data?.meta?.last_page ?? 1);
        pagination.perPage = Number(response.data?.meta?.per_page ?? 10);
        pagination.total = Number(response.data?.meta?.total ?? logs.value.length);
        perPageChoice.value = pagination.perPage;
        // Detail yang terbuka direset agar tidak menempel pada baris halaman lain.
        expandedIds.value = new Set();
    } catch (error) {
        logs.value = [];
        pagination.currentPage = 1;
        pagination.lastPage = 1;
        pagination.total = 0;
        loadError.value = error.response?.data?.message ?? 'Gagal memuat log alat.';
    } finally {
        isLoading.value = false;
    }
};

const applyFilters = () => {
    filters.search = draftSearch.value;
    filters.action = draftAction.value;
    loadLogs(1);
};

const resetFilters = () => {
    draftSearch.value = '';
    draftAction.value = 'Semua';
    filters.search = '';
    filters.action = 'Semua';
    loadLogs(1);
};

const goToPage = (pageNumber) => {
    if (pageNumber < 1 || pageNumber > pagination.lastPage || isLoading.value) {
        return;
    }

    loadLogs(pageNumber);
};

const exportCsv = async () => {
    isExporting.value = true;

    try {
        const response = await axios.get('/api/alat-logs/export', {
            params: buildParams(),
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        const disposition = response.headers['content-disposition'] ?? '';
        const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
        link.href = url;
        link.download = match?.[1] ?? 'log-alat.csv';
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);
    } catch (error) {
        loadError.value = 'Gagal mengunduh file CSV log alat.';
    } finally {
        isExporting.value = false;
    }
};

const hasActiveFilters = computed(
    () => filters.search.trim() !== '' || filters.action !== 'Semua'
);

const hasPendingChanges = computed(
    () => draftSearch.value !== filters.search || draftAction.value !== filters.action
);

const clearSearchFilter = () => {
    draftSearch.value = '';
    filters.search = '';
    loadLogs(1);
};

const clearActionFilter = () => {
    draftAction.value = 'Semua';
    filters.action = 'Semua';
    loadLogs(1);
};

const changePerPage = () => {
    pagination.perPage = Number(perPageChoice.value) || 10;
    loadLogs(1);
};

const rangeStart = computed(() =>
    pagination.total === 0 ? 0 : (pagination.currentPage - 1) * pagination.perPage + 1
);

const rangeEnd = computed(() =>
    Math.min(pagination.currentPage * pagination.perPage, pagination.total)
);

const pageNumbers = computed(() => {
    const total = Math.max(pagination.lastPage, 1);
    const current = pagination.currentPage;
    const delta = 2;
    const start = Math.max(1, Math.min(current - delta, total - delta * 2));
    const end = Math.min(total, Math.max(current + delta, delta * 2 + 1));
    const pages = [];

    for (let page = start; page <= end; page += 1) {
        pages.push(page);
    }

    return pages;
});

const isExpanded = (id) => expandedIds.value.has(id);

const toggleExpanded = (id) => {
    const next = new Set(expandedIds.value);
    next.has(id) ? next.delete(id) : next.add(id);
    expandedIds.value = next;
};

const actionOptionLabel = (action) => actionOptions[action] ?? action;

const actionBadgeClass = (label) => {
    if (label === 'Penambahan Alat Baru' || label === 'Penambahan Stok') {
        return 'bg-emerald-100 text-emerald-700';
    }
    if (label === 'Pengurangan Stok') {
        return 'bg-amber-100 text-amber-700';
    }
    if (label === 'Penghapusan Alat') {
        return 'bg-rose-100 text-rose-700';
    }
    return 'bg-blue-100 text-blue-700';
};

// Titik warna hanya penegas; label teks aktivitas tetap pembeda utamanya.
const actionDotClass = (label) => {
    if (label === 'Penambahan Alat Baru' || label === 'Penambahan Stok') {
        return 'bg-emerald-500';
    }
    if (label === 'Pengurangan Stok') {
        return 'bg-amber-500';
    }
    if (label === 'Penghapusan Alat') {
        return 'bg-rose-500';
    }
    return 'bg-blue-500';
};

const stockDeltaClass = (delta) => {
    if (Number(delta) > 0) {
        return 'bg-emerald-100 text-emerald-700';
    }
    if (Number(delta) < 0) {
        return 'bg-amber-100 text-amber-700';
    }
    return 'bg-slate-100 text-slate-600';
};

const stockDeltaTitle = (delta) => {
    const value = Number(delta);
    if (value > 0) {
        return `Total aset bertambah ${value}`;
    }
    if (value < 0) {
        return `Total aset berkurang ${Math.abs(value)}`;
    }

    return 'Total aset tidak berubah';
};

const formatValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'null';
    }

    return typeof value === 'object' ? JSON.stringify(value) : String(value);
};

const isEmptyValue = (value) => value === null || value === undefined || value === '';

// created_at dikirim backend sebagai "d M Y H:i:s". Pemisahan hanya untuk tampilan
// dan otomatis kembali menampilkan nilai apa adanya bila formatnya berbeda.
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

onMounted(async () => {
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(true);
    }
    await loadLogs();
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(false);
    }
});

watch(
    () => activeAreaId.value,
    async (next, prev) => {
        if (!isAreaSwitcherRole.value || !next || next === prev) {
            return;
        }

        setAreaSwitching?.(true);
        try {
            await loadLogs(1);
        } finally {
            setAreaSwitching?.(false);
        }
    }
);
</script>
