<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Pengelolaan Alat</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola master alat dan proses pengiriman</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Daftar Alat</h2>
                <p class="mt-1 text-sm text-slate-500">Kelola inventaris alat di semua area</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    v-if="canExportTools"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-60"
                    type="button"
                    :disabled="isExporting"
                    @click="exportTools"
                >
                    <svg v-if="isExporting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-90" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-4a6 6 0 0 0-6-6V2Z" />
                    </svg>
                    <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v12" />
                        <path d="m7 10 5 5 5-5" />
                        <path d="M5 21h14" />
                    </svg>
                    {{ isExporting ? 'Mengekspor...' : 'Export CSV' }}
                </button>
                <template v-if="canManageTools">
                    <button
                        v-if="isSuperAdmin"
                        class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="!activeAreaId || isDeleting"
                        @click="removeActiveAreaTools"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18" />
                            <path d="M8 6V4h8v2" />
                            <path d="m6 6 1 14h10l1-14" />
                        </svg>
                        Hapus Semuanya
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-100"
                        type="button"
                        @click="openImport"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 3v12" />
                            <path d="m7 10 5 5 5-5" />
                            <path d="M5 21h14" />
                        </svg>
                        Import CSV/XLSX
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                        type="button"
                        @click="openCreate"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Tambah Alat
                    </button>
                </template>
            </div>
        </div>

        <div class="mt-5 grid gap-3 md:grid-cols-[minmax(0,1fr)_320px] md:items-end">
            <label class="relative">
                <span class="sr-only">Cari alat</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari nama atau jenis alat..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-10 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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
            </label>
            <label v-if="canFilterByArea" class="flex flex-col justify-end gap-2 text-sm font-medium text-slate-700">
                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Area</span>
                <select
                    v-model="selectedAreaId"
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="">Semua area</option>
                    <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                        {{ area.name }}
                    </option>
                </select>
            </label>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span v-if="hasActiveFilters" class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
                Filter aktif
            </span>
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
                v-if="canFilterByArea && selectedAreaId"
                class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
            >
                Area: {{ selectedAreaName }}
                <button
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                    type="button"
                    title="Hapus filter area"
                    aria-label="Hapus filter area"
                    @click="selectedAreaId = ''"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
            <p v-if="canFilterByArea && !search.trim() && !selectedAreaId" class="text-sm text-slate-500">
                Menampilkan data alat untuk <span class="font-semibold text-slate-700">{{ selectedAreaName }}</span>.
            </p>
        </div>

        <div class="mt-5" aria-live="polite" :aria-busy="isLoading">
            <!-- Loading -->
            <div v-if="isLoading" class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-4 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" class="opacity-25" />
                        <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                    </svg>
                    Memuat data alat...
                </div>
                <div class="divide-y divide-slate-100 bg-white">
                    <div v-for="row in 5" :key="`skeleton-${row}`" class="flex items-center gap-4 px-4 py-4">
                        <div class="h-9 w-24 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 flex-1 animate-pulse rounded-lg bg-slate-100"></div>
                        <div class="h-9 w-32 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
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
                    @click="loadTools"
                >
                    Coba lagi
                </button>
            </div>

            <!-- Empty -->
            <div v-else-if="!tools.length" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-12 text-center">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-semibold text-slate-700">
                    {{ hasActiveFilters ? 'Tidak ada alat yang cocok' : 'Belum ada data alat' }}
                </p>
                <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                    {{
                        hasActiveFilters
                            ? 'Coba ubah kata kunci atau pilih area lain untuk memperluas hasil pencarian.'
                            : 'Tambahkan alat satu per satu atau unggah sekaligus lewat Import CSV/XLSX.'
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
                        <table class="w-full min-w-[860px] text-sm">
                            <caption class="sr-only">
                                Daftar master alat beserta ketersediaan stoknya
                            </caption>
                            <thead class="sticky top-0 z-10 bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Kode</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Alat</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Area</th>
                                    <th scope="col" class="border-b border-slate-200 px-4 py-3">Ketersediaan</th>
                                    <th v-if="canManageTools" scope="col" class="border-b border-slate-200 px-4 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="tool in tools" :key="tool.id" class="align-top transition hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <span class="font-mono text-xs font-semibold text-slate-900">{{ tool.kode }}</span>
                                    </td>
                                    <td class="max-w-[22rem] px-4 py-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-semibold capitalize text-slate-900">{{ tool.nama }}</p>
                                            <span
                                                v-if="tool.is_shared_area_stock"
                                                class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                                            >
                                                Antar Area
                                            </span>
                                        </div>
                                        <p class="mt-0.5 text-xs text-slate-500">
                                            {{ tool.jenis_alat }}
                                            <span v-if="tool.klasifikasi_alat && tool.klasifikasi_alat !== '-'">
                                                &middot; {{ tool.klasifikasi_alat }}
                                            </span>
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 text-slate-600">{{ tool.area_name }}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="inline-flex min-w-[3.5rem] justify-center rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums"
                                                :class="stockBadgeClass(tool)"
                                            >
                                                {{ tool.stok_tersedia }} / {{ tool.total_aset }}
                                            </span>
                                            <span class="text-xs text-slate-500">{{ stockLabel(tool) }}</span>
                                        </div>
                                        <div class="mt-1.5 h-1.5 w-32 overflow-hidden rounded-full bg-slate-100">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="stockBarClass(tool)"
                                                :style="{ width: stockPercent(tool) + '%' }"
                                            ></div>
                                        </div>
                                    </td>
                                    <td v-if="canManageTools" class="px-4 py-4">
                                        <div v-if="!tool.is_shared_area_stock" class="flex items-center justify-end gap-1">
                                            <button
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                                                type="button"
                                                title="Edit alat"
                                                aria-label="Edit alat"
                                                @click="openEdit(tool)"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M12 20h9" />
                                                    <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                </svg>
                                            </button>
                                            <button
                                                class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-200"
                                                type="button"
                                                title="Hapus alat"
                                                aria-label="Hapus alat"
                                                @click="removeTool(tool)"
                                            >
                                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M3 6h18" />
                                                    <path d="M8 6V4h8v2" />
                                                    <path d="m6 6 1 14h10l1-14" />
                                                </svg>
                                            </button>
                                        </div>
                                        <p v-else class="text-right text-xs font-semibold text-slate-400">Pinjaman</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Kartu (di bawah md) -->
                <ul class="space-y-3 md:hidden">
                    <li
                        v-for="tool in tools"
                        :key="`card-${tool.id}`"
                        class="rounded-2xl border border-slate-200 bg-white p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold capitalize text-slate-900">{{ tool.nama }}</p>
                                <p class="mt-0.5 font-mono text-xs text-slate-500">{{ tool.kode }}</p>
                            </div>
                            <span
                                class="inline-flex shrink-0 justify-center rounded-full px-2.5 py-1 text-[11px] font-semibold tabular-nums"
                                :class="stockBadgeClass(tool)"
                            >
                                {{ tool.stok_tersedia }} / {{ tool.total_aset }}
                            </span>
                        </div>

                        <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div
                                class="h-full rounded-full"
                                :class="stockBarClass(tool)"
                                :style="{ width: stockPercent(tool) + '%' }"
                            ></div>
                        </div>

                        <dl class="mt-3 space-y-1.5 text-sm">
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Jenis</dt>
                                <dd class="min-w-0 text-slate-600">
                                    {{ tool.jenis_alat }}
                                    <span v-if="tool.klasifikasi_alat && tool.klasifikasi_alat !== '-'" class="block text-xs text-slate-500">
                                        {{ tool.klasifikasi_alat }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="w-24 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Area</dt>
                                <dd class="min-w-0 text-slate-600">
                                    {{ tool.area_name }}
                                    <span
                                        v-if="tool.is_shared_area_stock"
                                        class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700"
                                    >
                                        Antar Area
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <div v-if="canManageTools && !tool.is_shared_area_stock" class="mt-3 flex gap-2">
                            <button
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                type="button"
                                @click="openEdit(tool)"
                            >
                                Edit
                            </button>
                            <button
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-300"
                                type="button"
                                @click="removeTool(tool)"
                            >
                                Hapus
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
                        dari <span class="font-semibold text-slate-700">{{ pagination.total }}</span> alat
                    </template>
                    <template v-else>Total {{ pagination.total }} alat</template>
                </p>
                <div class="flex items-center gap-2">
                    <label for="alat-per-page" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Baris
                    </label>
                    <select
                        id="alat-per-page"
                        v-model.number="perPageChoice"
                        class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-600 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        :disabled="isLoading"
                        @change="changePerPage"
                    >
                        <option v-for="size in perPageOptions" :key="size" :value="size">{{ size }}</option>
                    </select>
                </div>
            </div>

            <nav v-if="pagination.lastPage > 1" class="flex items-center gap-1" aria-label="Navigasi halaman daftar alat">
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

    <teleport v-if="canManageTools" to="body">
        <div
            v-if="formOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 p-4"
            @click.self="closeForm"
        >
            <div class="flex min-h-full items-center justify-center">
                <div class="max-h-[calc(100vh-2rem)] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">
                                {{ isEdit ? 'Edit Alat' : 'Tambah Alat' }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Lengkapi detail alat yang akan disimpan.
                            </p>
                        </div>
                        <button
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                            type="button"
                            @click="closeForm"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18" />
                                <path d="M6 6 18 18" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                            <span>Kode Alat</span>
                            <input
                                v-model="form.kode"
                                type="text"
                                maxlength="100"
                                :placeholder="kodePlaceholder"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-mono text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                            <span class="block text-xs font-normal text-slate-500">
                                Kosongkan untuk memakai penomoran otomatis area ini.
                            </span>
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700 md:col-span-2">
                            <span>Nama Alat *</span>
                            <input
                                v-model="form.nama"
                                type="text"
                                placeholder="Nama alat"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Jenis Alat *</span>
                            <input
                                v-model="form.jenis_alat"
                                type="text"
                                placeholder="Jenis alat"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Klasifikasi Alat *</span>
                            <input
                                v-model="form.klasifikasi_alat"
                                list="classification-options"
                                type="text"
                                placeholder="Pilih atau ketik klasifikasi"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                            <datalist id="classification-options">
                                <option
                                    v-for="classification in classificationOptions"
                                    :key="classification"
                                    :value="classification"
                                />
                            </datalist>
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Area *</span>
                            <select
                                v-model="form.area_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                :disabled="isAreaLocked"
                            >
                                <option value="">Pilih area</option>
                                <option v-for="area in areas" :key="area.id" :value="area.id">
                                    {{ area.name }}
                                </option>
                            </select>
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Jumlah Aset *</span>
                            <input
                                v-model.number="form.total_aset"
                                type="number"
                                min="0"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                    </div>

                    <p v-if="formError" class="mt-3 text-sm font-semibold text-rose-500">
                        {{ formError }}
                    </p>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                            type="button"
                            @click="closeForm"
                        >
                            Batal
                        </button>
                        <button
                            class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="button"
                            :disabled="isSubmitting"
                            @click="submitForm"
                        >
                            {{ isSubmitting ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Simpan' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>

    <teleport v-if="canManageTools" to="body">
        <div
            v-if="importOpen"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 p-4"
            @click.self="!isImporting && closeImport()"
        >
            <div class="flex min-h-full items-center justify-center">
                <div class="relative max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                    <div
                        v-if="isImporting"
                        class="absolute inset-0 z-10 flex flex-col items-center justify-center rounded-2xl bg-white/90 px-8 text-center backdrop-blur-[1px]"
                    >
                        <p class="text-sm font-semibold text-emerald-700">Mengimpor data alat...</p>
                        <div class="mt-4 h-2 w-full max-w-sm overflow-hidden rounded-full bg-emerald-100">
                            <div
                                class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                :class="currentImport?.total_rows ? '' : 'animate-pulse'"
                                :style="{ width: `${importProgress}%` }"
                            ></div>
                        </div>
                        <p class="mt-3 text-xs text-slate-500">
                            {{ importSummary || 'Mohon tunggu, file sedang diproses.' }}
                        </p>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Import Data Alat</h3>
                            <p class="mt-1 text-sm text-slate-500">
                                Upload file CSV atau XLSX dengan urutan kolom: nama alat, jenis alat, klasifikasi alat, total aset, area.
                            </p>
                        </div>
                        <button
                            class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                            type="button"
                            :disabled="isImporting"
                            @click="closeImport"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6 6 18" />
                                <path d="M6 6 18 18" />
                            </svg>
                        </button>
                    </div>

                    <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="font-semibold text-slate-800">Format file</p>
                            <button
                                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60"
                                type="button"
                                :disabled="isImporting || isDownloadingTemplate"
                                @click="downloadImportTemplate"
                            >
                                <svg v-if="isDownloadingTemplate" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-90" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-4a6 6 0 0 0-6-6V2Z" />
                                </svg>
                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M12 3v12" />
                                    <path d="m7 10 5 5 5-5" />
                                    <path d="M5 21h14" />
                                </svg>
                                {{ isDownloadingTemplate ? 'Mengunduh...' : 'Unduh Template' }}
                            </button>
                        </div>
                        <p class="mt-2">Kolom 1: nama alat</p>
                        <p>Kolom 2: jenis alat</p>
                        <p>Kolom 3: klasifikasi alat</p>
                        <p>Kolom 4: total aset</p>
                        <p>Kolom 5: area</p>
                        <p class="mt-3 text-xs text-slate-500">
                            Nilai klasifikasi alat harus salah satu dari `General Tools`, `Lifting Tools`, atau `Measurement Tools`.
                        </p>
                        <p class="mt-2 text-xs text-slate-500">
                            Nilai pada kolom area harus sama dengan `slug` area, misalnya `uphk`, `I.1`, atau `kstubun`.
                        </p>
                    </div>

                    <div class="mt-5 space-y-3">
                        <input
                            ref="importInput"
                            type="file"
                            accept=".csv,.xlsx"
                            class="hidden"
                            @change="handleImportFileChange"
                        />
                        <button
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-600"
                            type="button"
                            :disabled="isImporting"
                            @click="chooseImportFile"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <path d="M7 10 12 5l5 5" />
                                <path d="M12 5v12" />
                            </svg>
                            Pilih File
                        </button>
                        <p class="text-sm text-slate-600">
                            {{ importFileName || 'Belum ada file dipilih.' }}
                        </p>
                    </div>

                    <p v-if="importError" class="mt-4 whitespace-pre-line text-sm font-semibold text-rose-500">
                        {{ importError }}
                    </p>
                    <div
                        v-if="currentImport && !isImporting && normalizedImportStatus === 'completed'"
                        class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                    >
                        Import selesai. {{ currentImport.created_count ?? 0 }} data ditambahkan, {{ currentImport.updated_count ?? 0 }} data diperbarui.
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                            type="button"
                            :disabled="isImporting"
                            @click="closeImport"
                        >
                            Batal
                        </button>
                        <button
                            class="rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-300"
                            type="button"
                            :disabled="isImporting"
                            @click="submitImport"
                        >
                            {{ isImporting ? 'Mengimpor...' : 'Import' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </teleport>

    <teleport v-if="canManageTools" to="body">
        <div
            v-if="deleteOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="!isDeleting && closeDeleteModal()"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Hapus Alat</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ deleteMode === 'area' ? 'Semua alat pada area aktif akan dihapus.' : 'Data alat yang dihapus tidak dapat dikembalikan.' }}
                        </p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="isDeleting"
                        @click="closeDeleteModal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 rounded-2xl border border-rose-100 bg-rose-50 px-4 py-3">
                    <p class="text-sm text-slate-700">
                        <template v-if="deleteMode === 'area'">
                            Yakin ingin menghapus semua alat pada
                            <span class="font-semibold text-slate-900">{{ areaName }}</span>?
                        </template>
                        <template v-else>
                            Yakin ingin menghapus alat
                            <span class="font-semibold text-slate-900">{{ deleteTarget?.nama }}</span>?
                        </template>
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-60"
                        type="button"
                        :disabled="isDeleting"
                        @click="closeDeleteModal"
                    >
                        Batal
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-rose-200 transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-rose-300"
                        type="button"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        <svg v-if="isDeleting" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-90" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-4a6 6 0 0 0-6-6V2Z" />
                        </svg>
                        {{ isDeleting ? 'Menghapus...' : 'Hapus' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';
import { loadAreas as loadSharedAreas } from '../../lib/areas';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Pengelolaan Alat',
                subtitle: 'Kelola master alat dan proses pengiriman',
                activeMenu: 'master-alat',
            },
            () => page
        ),
});

const tools = ref([]);
const areas = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const isSubmitting = ref(false);
const isImporting = ref(false);
const isDeleting = ref(false);
const isExporting = ref(false);
const isDownloadingTemplate = ref(false);
const formOpen = ref(false);
const importOpen = ref(false);
const deleteOpen = ref(false);
const formError = ref('');
const importError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
const importInput = ref(null);
const importFile = ref(null);
const deleteMode = ref('single');
const deleteTarget = ref(null);
const currentImport = ref(null);
let importStatusInterval = null;
let alertTimeout = null;

const search = ref('');
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 10,
});
const perPageOptions = [10, 25, 50, 100];
const perPageChoice = ref(10);

const form = reactive({
    id: null,
    kode: '',
    nama: '',
    jenis_alat: '',
    klasifikasi_alat: '',
    total_aset: 0,
    area_id: '',
});

const page = usePage();
const defaultClassificationOptions = ['General Tools', 'Lifting Tools', 'Measurement Tools'];
const classificationOptions = computed(() => {
    const options = [
        ...defaultClassificationOptions,
        ...tools.value.map((tool) => tool.klasifikasi_alat),
        form.klasifikasi_alat,
    ]
        .map((classification) => String(classification ?? '').trim())
        .filter((classification) => classification && classification !== '-');

    return [...new Set(options)];
});

const loadCachedUser = () => {
    return null;
};

const cachedUser = ref(loadCachedUser());
const authUser = computed(() => page.props.auth?.user ?? cachedUser.value);
const roleKey = computed(() => (authUser.value?.role?.key ?? '').toLowerCase());
const isSpTool = computed(() => roleKey.value === 'sp_tool');
const isMgrTool = computed(() => roleKey.value === 'mgr_tool');
const isSuperAdmin = computed(() => roleKey.value === 'super_admin');
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const canManageTools = computed(() => ['pic_tool', 'admin', 'super_admin'].includes(roleKey.value));
const canExportTools = computed(() => true);
const canFilterByArea = computed(() => isSpTool.value || isMgrTool.value);
const activeAreaId = inject('activeAreaId', ref(null));
const activeAreaName = inject('activeAreaName', ref('Area aktif'));
const setAreaSwitching = inject('setAreaSwitching', null);
const userAreaId = computed(() => authUser.value?.area_id ?? authUser.value?.area?.id ?? '');
const isAreaLocked = computed(() => !isSuperAdmin.value);
const normalizeAreaId = (value) => (value === null || value === undefined || value === '' ? '' : Number(value));
const areaName = computed(() => activeAreaName.value || 'area aktif');
const selectedAreaId = ref('');
const selectedAreaName = computed(() => {
    if (!selectedAreaId.value) {
        return 'semua area';
    }

    const selectedArea = areas.value.find((area) => String(area.id) === String(selectedAreaId.value));

    return selectedArea?.name ?? 'semua area';
});

const isEdit = computed(() => form.id !== null);

// Contoh kode yang akan dipakai bila field dikosongkan, memakai awalan area terpilih.
const kodePlaceholder = computed(() => {
    const area = areas.value.find((row) => String(row.id) === String(form.area_id));
    const prefix = String(area?.kode ?? '').trim() || 'AREA';

    return `Otomatis, contoh: ${prefix}-1`;
});
const importFileName = computed(() => importFile.value?.name ?? '');
const normalizedImportStatus = computed(() => normalizeImportStatus(currentImport.value?.status));
const importProgress = computed(() => {
    const processed = Number(currentImport.value?.processed_rows ?? 0);
    const total = Number(currentImport.value?.total_rows ?? 0);

    if (total > 0) {
        return Math.min(Math.max((processed / total) * 100, 8), 100);
    }

    return normalizedImportStatus.value === 'completed' ? 100 : 45;
});
const importSummary = computed(() => {
    if (!currentImport.value) {
        return '';
    }

    if (normalizedImportStatus.value === 'completed') {
        return `Selesai. ${currentImport.value.created_count ?? 0} data ditambahkan, ${currentImport.value.updated_count ?? 0} data diperbarui.`;
    }

    if (normalizedImportStatus.value === 'failed') {
        return currentImport.value.error_message || 'Import alat gagal.';
    }

    return `Diproses ${currentImport.value.processed_rows ?? 0} dari ${currentImport.value.total_rows ?? 0} baris`;
});

const hasActiveFilters = computed(
    () => search.value.trim() !== '' || (canFilterByArea.value && selectedAreaId.value !== '')
);

const resetFilters = () => {
    search.value = '';
    if (canFilterByArea.value) {
        selectedAreaId.value = '';
    }
};

const changePerPage = () => {
    pagination.perPage = Number(perPageChoice.value) || 10;
    pagination.currentPage = 1;
    loadTools();
};

const rangeStart = computed(() =>
    pagination.total === 0 ? 0 : (pagination.currentPage - 1) * pagination.perPage + 1
);

const rangeEnd = computed(() =>
    Math.min(pagination.currentPage * pagination.perPage, pagination.total)
);

// Rasio stok tersedia terhadap total aset, dipakai untuk lebar bar dan warnanya.
const stockRatio = (tool) => {
    const total = Number(tool?.total_aset ?? 0);
    const available = Number(tool?.stok_tersedia ?? 0);

    if (total <= 0) {
        return 0;
    }

    return Math.min(Math.max(available / total, 0), 1);
};

const stockPercent = (tool) => Math.round(stockRatio(tool) * 100);

const stockBadgeClass = (tool) => {
    const available = Number(tool?.stok_tersedia ?? 0);

    if (available <= 0) {
        return 'bg-rose-100 text-rose-700';
    }

    return stockRatio(tool) < 0.35 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
};

const stockBarClass = (tool) => {
    const available = Number(tool?.stok_tersedia ?? 0);

    if (available <= 0) {
        return 'bg-rose-400';
    }

    return stockRatio(tool) < 0.35 ? 'bg-amber-400' : 'bg-emerald-400';
};

const stockLabel = (tool) => {
    const total = Number(tool?.total_aset ?? 0);
    const available = Number(tool?.stok_tersedia ?? 0);

    if (total <= 0) {
        return 'Belum ada aset';
    }

    if (available <= 0) {
        return 'Habis dipinjam';
    }

    if (available >= total) {
        return 'Tersedia penuh';
    }

    return `${total - available} dipinjam`;
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

const resetForm = () => {
    form.id = null;
    form.kode = '';
    form.nama = '';
    form.jenis_alat = '';
    form.klasifikasi_alat = '';
    form.total_aset = 0;
    form.area_id = normalizeAreaId(
        isSuperAdmin.value && isAreaSwitcherRole.value ? activeAreaId.value : userAreaId.value
    );
    formError.value = '';
};

const openCreate = () => {
    if (!canManageTools.value) {
        return;
    }
    resetForm();
    formOpen.value = true;
};

const openEdit = (tool) => {
    if (!canManageTools.value) {
        return;
    }
    form.id = tool.id;
    form.kode = tool.kode && tool.kode !== '-' ? tool.kode : '';
    form.nama = tool.nama ?? '';
    form.jenis_alat = tool.jenis_alat ?? '';
    form.klasifikasi_alat = tool.klasifikasi_alat ?? '';
    form.total_aset = Number(tool.total_aset ?? tool.stok ?? 0);
    form.area_id = normalizeAreaId(
        isSuperAdmin.value
            ? tool.area_id ?? activeAreaId.value ?? ''
            : userAreaId.value ?? tool.area_id ?? ''
    );
    formError.value = '';
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const resetImport = () => {
    importFile.value = null;
    importError.value = '';
    currentImport.value = null;
    stopImportPolling();
    if (importInput.value) {
        importInput.value.value = '';
    }
};

const openImport = () => {
    if (!canManageTools.value) {
        return;
    }
    resetImport();
    importOpen.value = true;
};

const closeImport = () => {
    if (isImporting.value) {
        return;
    }
    importOpen.value = false;
    importError.value = '';
};

const chooseImportFile = () => {
    if (isImporting.value) {
        return;
    }
    importInput.value?.click();
};

const handleImportFileChange = (event) => {
    if (isImporting.value) {
        return;
    }
    const [file] = event.target.files ?? [];
    importFile.value = file ?? null;
    importError.value = '';
};

const stopImportPolling = () => {
    if (importStatusInterval) {
        clearTimeout(importStatusInterval);
        importStatusInterval = null;
    }
};

const handleImportFailure = (message) => {
    stopImportPolling();
    isImporting.value = false;
    importError.value = message;
    showAlert('error', message);
};

const normalizeImportStatus = (status) => {
    const normalized = String(status ?? '').toLowerCase();
    if (['completed', 'complete', 'done', 'success', 'succeeded'].includes(normalized)) {
        return 'completed';
    }
    if (['failed', 'fail', 'error', 'errored'].includes(normalized)) {
        return 'failed';
    }
    if (['processing', 'running', 'working'].includes(normalized)) {
        return 'processing';
    }
    return normalized || 'pending';
};

const scheduleImportPolling = (importId) => {
    stopImportPolling();
    importStatusInterval = setTimeout(() => {
        pollImportStatus(importId).catch(() => {
            handleImportFailure('Gagal memantau status import.');
        });
    }, 1500);
};

const pollImportStatus = async (importId) => {
    const response = await axios.get(`/api/alats/imports/${importId}`, {
        __skipGlobalLoading: true,
    });
    currentImport.value = response.data?.import ?? null;

    const status = normalizeImportStatus(currentImport.value?.status);
    if (status === 'completed') {
        stopImportPolling();
        isImporting.value = false;
        pagination.currentPage = 1;
        await loadTools({ skipGlobalLoading: true });
        showAlert(
            'success',
            `Import alat selesai. ${currentImport.value?.created_count ?? 0} data ditambahkan, ${currentImport.value?.updated_count ?? 0} data diperbarui.`
        );
        importOpen.value = false;
        resetImport();
        return;
    }

    if (status === 'failed') {
        stopImportPolling();
        isImporting.value = false;
        importError.value = currentImport.value?.error_message ?? 'Import alat gagal.';
        showAlert('error', 'Import alat gagal.');
        return;
    }

    scheduleImportPolling(importId);
};

const loadAreas = async () => {
    try {
        areas.value = await loadSharedAreas();
    } catch (error) {
        areas.value = [];
    }
};

const buildParams = () => {
    const params = {};
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (canFilterByArea.value && selectedAreaId.value) {
        params.area_id = selectedAreaId.value;
    }
    if (isSuperAdmin.value && isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }
    return params;
};

const loadTools = async (options = {}) => {
    const skipGlobalLoading = Boolean(options.skipGlobalLoading);
    if (!skipGlobalLoading) {
        isLoading.value = true;
    }
    loadError.value = '';
    try {
        const response = await axios.get('/api/alats', {
            params: {
                ...buildParams(),
                page: pagination.currentPage,
                per_page: pagination.perPage,
            },
            __skipGlobalLoading: skipGlobalLoading,
        });
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        tools.value = data.map((item) => ({
            id: item.id,
            kode: item.kode ?? '-',
            nama: item.nama ?? '-',
            jenis_alat: item.jenis_alat ?? '-',
            klasifikasi_alat: item.klasifikasi_alat ?? '-',
            total_aset: Number(item.total_aset ?? item.stok ?? 0),
            stok_tersedia: Number(item.stok_tersedia ?? item.stok ?? 0),
            area_name: item.area_name ?? item.lokasi ?? '-',
            area_id: item.area_id ?? '',
            is_shared_area_stock: Boolean(item.is_shared_area_stock),
        }));
        if (Array.isArray(payload)) {
            pagination.currentPage = 1;
            pagination.lastPage = 1;
            pagination.total = tools.value.length;
        } else {
            const meta = payload?.meta ?? {};
            pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
            pagination.lastPage = Number(meta.last_page ?? 1);
            pagination.perPage = Number(meta.per_page ?? pagination.perPage);
            pagination.total = Number(meta.total ?? tools.value.length);
            perPageChoice.value = pagination.perPage;
        }
    } catch (error) {
        tools.value = [];
        loadError.value = 'Gagal memuat data alat.';
    } finally {
        if (!skipGlobalLoading) {
            isLoading.value = false;
        }
    }
};

const goToPage = (page) => {
    const next = Math.min(Math.max(1, page), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }
    pagination.currentPage = next;
    loadTools();
};

const submitForm = async () => {
    if (!canManageTools.value) {
        return;
    }
    formError.value = '';
    if (!isSuperAdmin.value && !form.area_id && userAreaId.value) {
        form.area_id = normalizeAreaId(userAreaId.value);
    }
    if (
        !form.nama.trim() ||
        !form.jenis_alat.trim() ||
        !form.klasifikasi_alat.trim() ||
        !form.area_id ||
        form.total_aset < 0
    ) {
        formError.value = 'Lengkapi semua field wajib.';
        return;
    }
    isSubmitting.value = true;
    try {
        const payload = {
            // Dikirim kosong berarti minta kode default nomor urut area.
            kode: form.kode.trim(),
            nama: form.nama.trim(),
            jenis_alat: form.jenis_alat.trim(),
            klasifikasi_alat: form.klasifikasi_alat.trim(),
            area_id: form.area_id,
            total_aset: form.total_aset,
        };
        if (isEdit.value) {
            await axios.put(`/api/alats/${form.id}`, payload);
        } else {
            await axios.post('/api/alats', payload);
        }
        await loadTools();
        closeForm();
        showAlert('success', `Alat berhasil ${isEdit.value ? 'diperbarui' : 'ditambahkan'}.`);
    } catch (error) {
        formError.value = error.response?.data?.message ?? 'Gagal menyimpan data.';
        showAlert('error', formError.value);
    } finally {
        isSubmitting.value = false;
    }
};

const submitImport = async () => {
    if (!canManageTools.value) {
        return;
    }
    if (!importFile.value) {
        importError.value = 'Pilih file CSV atau XLSX terlebih dahulu.';
        return;
    }

    importError.value = '';
    isImporting.value = true;

    try {
        const payload = new FormData();
        payload.append('file', importFile.value);

        const response = await axios.post('/api/alats/import', payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            __skipGlobalLoading: true,
        });
        currentImport.value = response.data?.import ?? null;

        if (!currentImport.value?.id) {
            throw new Error('Import id tidak ditemukan.');
        }

        await pollImportStatus(currentImport.value.id);
    } catch (error) {
        stopImportPolling();
        isImporting.value = false;
        const validationErrors = error.response?.data?.errors?.file;
        importError.value = Array.isArray(validationErrors)
            ? validationErrors.join('\n')
            : error.response?.data?.message ?? 'Gagal mengimpor data.';
        showAlert('error', 'Import alat gagal.');
    }
};

const downloadImportTemplate = async () => {
    if (isDownloadingTemplate.value) {
        return;
    }

    isDownloadingTemplate.value = true;

    try {
        const response = await axios.get('/api/alats/import-template', {
            responseType: 'blob',
            __skipGlobalLoading: true,
        });

        const blob = new Blob([response.data], {
            type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        const disposition = response.headers?.['content-disposition'] ?? '';
        const match = disposition.match(/filename="?([^"]+)"?/i);

        link.href = url;
        link.setAttribute('download', match?.[1] ?? 'TEMPLATE-IMPORT-ALAT.xlsx');
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        showAlert('success', 'Template import berhasil diunduh.');
    } catch (error) {
        showAlert('error', 'Gagal mengunduh template import.');
    } finally {
        isDownloadingTemplate.value = false;
    }
};

const exportTools = async () => {
    if (isExporting.value) {
        return;
    }

    isExporting.value = true;

    try {
        const response = await axios.get('/api/alats/export', {
            params: buildParams(),
            responseType: 'blob',
        });

        const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        const timestamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');

        link.href = url;
        link.setAttribute('download', `data-alat-${timestamp}.csv`);
        document.body.appendChild(link);
        link.click();
        link.remove();
        window.URL.revokeObjectURL(url);

        showAlert('success', 'Export data alat berhasil.');
    } catch (error) {
        showAlert('error', 'Gagal mengekspor data alat.');
    } finally {
        isExporting.value = false;
    }
};

const removeTool = (tool) => {
    if (!canManageTools.value) {
        return;
    }
    deleteMode.value = 'single';
    deleteTarget.value = tool;
    deleteOpen.value = true;
};

const removeActiveAreaTools = () => {
    if (!isSuperAdmin.value || !activeAreaId.value) {
        showAlert('error', 'Pilih area aktif terlebih dahulu.');
        return;
    }
    deleteMode.value = 'area';
    deleteTarget.value = null;
    deleteOpen.value = true;
};

const closeDeleteModal = () => {
    if (isDeleting.value) {
        return;
    }
    deleteOpen.value = false;
    deleteMode.value = 'single';
    deleteTarget.value = null;
};

const confirmDelete = async () => {
    if (!canManageTools.value || (deleteMode.value !== 'area' && !deleteTarget.value)) {
        return;
    }
    isDeleting.value = true;
    let shouldCloseModal = false;
    try {
        if (deleteMode.value === 'area') {
            await axios.delete('/api/alats/area', {
                data: {
                    area_id: activeAreaId.value,
                },
            });
        } else {
            await axios.delete(`/api/alats/${deleteTarget.value.id}`);
        }
        await loadTools();
        shouldCloseModal = true;
        showAlert(
            'success',
            deleteMode.value === 'area'
                ? 'Semua alat pada area aktif berhasil dihapus.'
                : 'Alat berhasil dihapus.'
        );
    } catch (error) {
        showAlert('error', error.response?.data?.message ?? 'Gagal menghapus data.');
    } finally {
        isDeleting.value = false;
        if (shouldCloseModal) {
            closeDeleteModal();
        }
    }
};

let filterTimeout = null;
watch(
    search,
    () => {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }
        filterTimeout = setTimeout(() => {
            pagination.currentPage = 1;
            loadTools();
        }, 300);
    },
);

watch(
    selectedAreaId,
    (next, prev) => {
        if (!canFilterByArea.value || next === prev) {
            return;
        }
        pagination.currentPage = 1;
        loadTools();
    }
);

watch(
    userAreaId,
    (next) => {
        if (!isSuperAdmin.value && next) {
            form.area_id = normalizeAreaId(next);
        }
    },
    { immediate: true }
);

watch(
    () => activeAreaId.value,
    async (next, prev) => {
        if (!isSuperAdmin.value || !isAreaSwitcherRole.value) {
            return;
        }
        const shouldShow = prev !== undefined && prev !== null && next !== prev;
        if (shouldShow) {
            setAreaSwitching?.(true);
        }
        if (next && !form.area_id) {
            form.area_id = normalizeAreaId(next);
        }
        pagination.currentPage = 1;
        try {
            await loadTools();
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);

onMounted(() => {
    cachedUser.value = loadCachedUser();
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(true);
    }

    // Untuk super admin, area aktif kadang sudah tersedia saat halaman ini
    // dibuat ulang oleh key area di AppLayout. Dalam kondisi itu watcher
    // activeAreaId tidak terpanggil lagi, jadi data harus dimuat saat mount.
    const areaWatcherWillLoadTools = isSuperAdmin.value && isAreaSwitcherRole.value && !activeAreaId.value;
    const tasks = areaWatcherWillLoadTools ? [loadAreas()] : [loadAreas(), loadTools()];

    Promise.all(tasks).finally(() => {
        if (isAreaSwitcherRole.value) {
            setAreaSwitching?.(false);
        }
    });
});

onBeforeUnmount(() => {
    stopImportPolling();
});
</script>
