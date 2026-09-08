<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <section class="rounded-2xl bg-white p-6 shadow-xl shadow-slate-200/60 space-y-6">

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Katalog Alat - {{ areaName }}
                </h2>
                <p class="mt-1 text-sm text-slate-500">Pilih alat yang ingin dipinjam dan checkout</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button
                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300"
                    type="button"
                    @click="toggleView"
                >
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                        <svg
                            v-if="viewMode === 'grid'"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path d="M8 6h13" />
                            <path d="M8 12h13" />
                            <path d="M8 18h13" />
                            <path d="M3 6h1" />
                            <path d="M3 12h1" />
                            <path d="M3 18h1" />
                        </svg>
                        <svg
                            v-else
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <rect x="3" y="3" width="8" height="8" rx="2" />
                            <rect x="13" y="3" width="8" height="8" rx="2" />
                            <rect x="13" y="13" width="8" height="8" rx="2" />
                            <rect x="3" y="13" width="8" height="8" rx="2" />
                        </svg>
                    </span>
                    <span>{{ viewMode === 'grid' ? 'Tampilan List' : 'Tampilan Kartu' }}</span>
                </button>
                <button
                    class="relative inline-flex items-center gap-3 rounded-2xl border border-blue-200 bg-white px-4 py-2 text-sm font-semibold text-blue-600 shadow-sm transition hover:border-blue-300"
                    type="button"
                    @click="drawerOpen = true"
                >
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 6h15l-2 9H8L6 6Z" />
                            <path d="M6 6 5 3H2" />
                            <circle cx="9" cy="20" r="1.5" />
                            <circle cx="18" cy="20" r="1.5" />
                        </svg>
                    </span>
                    <span>Keranjang</span>
                    <span
                        v-if="totalItems"
                        class="absolute -right-2 -top-2 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-blue-600 px-1 text-xs font-semibold text-white"
                    >
                        {{ totalItems }}
                    </span>
                </button>
            </div>
        </div>

        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <label v-if="canBrowseCatalogAreas" class="relative md:w-80 md:shrink-0">
                <span class="sr-only">Area katalog</span>
                <select
                    v-model="selectedAreaId"
                    class="h-full min-h-[42px] w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option
                        v-for="area in areas"
                        :key="area.id"
                        :value="String(area.id)"
                    >
                        {{ area.name }}
                    </option>
                </select>
            </label>
            <label class="relative min-w-0 flex-1">
                <span class="sr-only">Cari alat</span>
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    ref="searchInput"
                    v-model="filters.search"
                    type="text"
                    placeholder="Cari nama atau kode alat..."
                    class="w-full rounded-xl border border-slate-200 bg-white py-2.5 pl-11 pr-11 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
                <button
                    v-if="filters.search"
                    class="absolute right-2 top-1/2 flex h-7 w-7 -translate-y-1/2 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                    type="button"
                    title="Kosongkan pencarian"
                    aria-label="Kosongkan pencarian"
                    @click="filters.search = ''"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </label>
        </div>

        <div
            v-if="templates.length || templatesLoading"
            class="flex flex-col gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-3 md:flex-row md:items-center"
        >
            <label class="min-w-0 flex-1">
                <span class="sr-only">Template peminjaman</span>
                <select
                    v-model="selectedTemplateId"
                    class="h-11 w-full rounded-xl border border-blue-100 bg-white px-3 text-sm font-semibold text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    :disabled="templatesLoading || isReadOnlyCatalog"
                >
                    <option value="">{{ templatesLoading ? 'Memuat template...' : 'Pilih template peminjaman' }}</option>
                    <option v-for="template in templates" :key="template.id" :value="String(template.id)">
                        {{ template.nama }} - {{ template.items_count }} alat
                    </option>
                </select>
            </label>
            <button
                class="h-11 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                type="button"
                :disabled="!selectedTemplateId || isReadOnlyCatalog"
                @click="applySelectedTemplate"
            >
                Gunakan Template
            </button>
        </div>

        <div
            v-if="isViewingOtherArea"
            class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700"
        >
            Anda sedang melihat katalog area lain. Data hanya dapat dilihat, peminjaman tetap hanya bisa dibuat dari area akun Anda.
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                        <path d="m3.3 7 8.7 5 8.7-5" />
                        <path d="M12 12v9" />
                    </svg>
                </span>
                <span v-if="isLoading">Memuat data alat...</span>
                <span v-else-if="pagination.total">
                    Menampilkan <span class="font-semibold text-slate-700">{{ rangeStart }}&ndash;{{ rangeEnd }}</span>
                    dari <span class="font-semibold text-slate-700">{{ pagination.total }}</span> alat
                </span>
                <span v-else>{{ filteredTools.length }} alat tersedia</span>

                <span class="flex items-center gap-2">
                    <label for="katalog-per-page" class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">
                        Tampilkan
                    </label>
                    <select
                        id="katalog-per-page"
                        v-model.number="perPageChoice"
                        class="h-9 rounded-lg border border-slate-200 bg-white px-2 text-xs font-semibold text-slate-600 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="isLoading"
                        @change="changePerPage"
                    >
                        <option v-for="size in perPageOptions" :key="size" :value="size">{{ size }}</option>
                    </select>
                </span>
            </div>

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
                    @click="filters.search = ''"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12" />
                    </svg>
                </button>
            </span>
        </div>

        <!-- Loading -->
        <div v-if="isLoading" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="row in 6"
                :key="`skeleton-${row}`"
                class="rounded-2xl border border-slate-200 bg-white p-5"
            >
                <div class="h-3 w-20 animate-pulse rounded bg-slate-100"></div>
                <div class="mt-2 h-5 w-3/4 animate-pulse rounded bg-slate-100"></div>
                <div class="mt-4 h-6 w-24 animate-pulse rounded-full bg-slate-100"></div>
                <div class="mt-5 flex items-center gap-3">
                    <div class="h-9 w-28 animate-pulse rounded-full bg-slate-100"></div>
                    <div class="h-10 flex-1 animate-pulse rounded-xl bg-slate-100"></div>
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
                    <p class="text-sm font-semibold text-rose-800">Gagal memuat katalog</p>
                    <p class="mt-0.5 text-sm text-rose-700">{{ loadError }}</p>
                </div>
            </div>
            <button
                class="h-10 shrink-0 rounded-xl border border-rose-300 bg-white px-4 text-sm font-semibold text-rose-700 transition hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"
                type="button"
                @click="loadKatalog(buildFilterParams())"
            >
                Coba lagi
            </button>
        </div>

        <!-- Empty -->
        <div v-else-if="!filteredTools.length" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-12 text-center">
            <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76Z" />
                </svg>
            </span>
            <p class="mt-3 text-sm font-semibold text-slate-700">
                {{ filters.search.trim() ? 'Alat tidak ditemukan' : 'Belum ada alat di katalog' }}
            </p>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                {{
                    filters.search.trim()
                        ? 'Coba kata kunci lain, misalnya sebagian nama atau kode alat.'
                        : 'Alat yang terdaftar pada area ini akan muncul di sini.'
                }}
            </p>
            <button
                v-if="filters.search.trim()"
                class="mt-4 h-10 rounded-xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-300"
                type="button"
                @click="filters.search = ''"
            >
                Kosongkan pencarian
            </button>
        </div>

        <div v-else-if="viewMode === 'list'" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="max-h-[calc(100vh-15rem)] min-h-[34rem] overflow-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="sticky top-0 z-10 bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                            <th scope="col" class="w-12 border-b border-slate-200 px-4 py-3 text-center">Pilih</th>
                            <th scope="col" class="min-w-[260px] border-b border-slate-200 px-4 py-3">Alat</th>
                            <th scope="col" class="w-32 border-b border-slate-200 px-4 py-3">Stok</th>
                            <th scope="col" class="w-56 border-b border-slate-200 px-4 py-3">Jumlah Dipinjam</th>
                            <th scope="col" class="w-40 border-b border-slate-200 px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr
                            v-for="tool in filteredTools"
                            :key="tool.id"
                            :class="[
                                'transition hover:bg-slate-50',
                                isInCart(tool.id) ? 'bg-blue-50/60' : '',
                                isOutOfStock(tool) ? 'text-slate-400' : 'text-slate-700',
                            ]"
                        >
                            <td class="px-4 py-3 text-center align-middle">
                                <input
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                                    type="checkbox"
                                    :checked="isInCart(tool.id)"
                                    :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                    :aria-label="`Pilih ${tool.nama}`"
                                    @change="toggleCartSelection(tool, $event.target.checked)"
                                />
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-400">{{ tool.kode }}</p>
                                    <p class="mt-0.5 font-semibold capitalize text-slate-900">{{ tool.nama }}</p>
                                    <p v-if="tool.lokasi" class="mt-1 text-xs text-slate-500">{{ tool.lokasi }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span
                                    class="inline-flex min-w-[64px] items-center justify-center rounded-lg px-2.5 py-1 text-xs font-semibold tabular-nums"
                                    :class="stockBadgeClass(tool)"
                                >
                                    {{ tool.stok }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="inline-flex h-10 items-center overflow-hidden rounded-lg border border-slate-200 bg-white">
                                    <button
                                        class="flex h-10 w-10 items-center justify-center text-base font-semibold text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300"
                                        type="button"
                                        :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                        @click="decreaseTool(tool)"
                                    >
                                        -
                                    </button>
                                    <input
                                        class="h-10 w-16 border-x border-slate-200 text-center text-sm font-semibold text-slate-800 outline-none focus:bg-blue-50 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400"
                                        type="number"
                                        min="1"
                                        :max="tool.stok"
                                        :value="displayQty(tool.id)"
                                        :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                        @input="setToolQty(tool, $event.target.value)"
                                    />
                                    <button
                                        class="flex h-10 w-10 items-center justify-center text-base font-semibold text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-300"
                                        type="button"
                                        :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                                        @click="increaseTool(tool)"
                                    >
                                        +
                                    </button>
                                </div>
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <span
                                    v-if="isReadOnlyCatalog"
                                    class="inline-flex whitespace-nowrap rounded-lg bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"
                                >
                                    Lihat Saja
                                </span>
                                <span
                                    v-else-if="isOutOfStock(tool)"
                                    class="inline-flex whitespace-nowrap rounded-lg bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-600"
                                >
                                    Stok Habis
                                </span>
                                <span
                                    v-else-if="isInCart(tool.id)"
                                    class="inline-flex whitespace-nowrap rounded-lg bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700"
                                >
                                    Di Keranjang
                                </span>
                                <span
                                    v-else
                                    class="inline-flex whitespace-nowrap rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600"
                                >
                                    Belum Dipilih
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="tool in filteredTools"
                :key="tool.id"
                :class="[
                    'relative flex flex-col rounded-2xl border bg-white p-5 shadow-sm transition hover:shadow-md',
                    isInCart(tool.id) ? 'border-blue-300 bg-blue-50/60 shadow-blue-100' : 'border-slate-200',
                    isOutOfStock(tool) && !isInCart(tool.id) ? 'opacity-75' : '',
                ]"
            >
                <div class="flex flex-1 flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-slate-400">
                                {{ tool.kode }}
                            </p>
                            <h3 class="text-base font-semibold capitalize text-slate-900">{{ tool.nama }}</h3>
                        </div>
                        <span
                            v-if="isInCart(tool.id)"
                            :class="[
                                'rounded-full bg-blue-600 px-3 py-1 text-xs font-semibold text-white',
                                'absolute right-4 top-4',
                            ]"
                        >
                            Di Keranjang
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                        <span
                            class="flex items-center gap-1 rounded-full px-2.5 py-1 font-semibold tabular-nums"
                            :class="stockBadgeClass(tool)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                                <path d="m3.3 7 8.7 5 8.7-5" />
                                <path d="M12 12v9" />
                            </svg>
                            Stok: {{ tool.stok }}
                        </span>
                        <span
                            v-if="isOutOfStock(tool)"
                            class="rounded-full bg-rose-100 px-2.5 py-1 font-semibold text-rose-600"
                        >
                            Stok habis
                        </span>
                    </div>

                    <p v-if="tool.deskripsi" class="text-sm text-slate-500">
                        {{ tool.deskripsi }}
                    </p>

                    <div v-if="tool.lokasi" class="flex items-center gap-2 text-xs text-slate-500">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 22s7-4.5 7-11a7 7 0 1 0-14 0c0 6.5 7 11 7 11Z" />
                            <circle cx="12" cy="11" r="2.5" />
                        </svg>
                        <span>{{ tool.lokasi }}</span>
                    </div>
                </div>

                <div class="mt-auto flex items-center gap-3 pt-5">
                    <div class="flex items-center rounded-full bg-slate-100 px-2 py-1">
                        <button
                            class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:text-slate-300"
                            type="button"
                            :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                            @click="decreaseTool(tool)"
                        >
                            -
                        </button>
                        <span class="w-8 text-center text-sm font-semibold text-slate-700">
                            {{ displayQty(tool.id) }}
                        </span>
                        <button
                            class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700 disabled:cursor-not-allowed disabled:text-slate-300"
                            type="button"
                            :disabled="isReadOnlyCatalog || isOutOfStock(tool)"
                            @click="increaseTool(tool)"
                        >
                            +
                        </button>
                    </div>
                    <button
                        :class="[
                            'flex items-center justify-center gap-2 rounded-xl bg-blue-600 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700',
                            'disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500 disabled:shadow-none',
                            'flex-1 px-4 py-2.5',
                        ]"
                        type="button"
                        :disabled="isReadOnlyCatalog || isInCart(tool.id) || isOutOfStock(tool)"
                        @click="addToCart(tool)"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 6h15l-2 9H8L6 6Z" />
                            <path d="M6 6 5 3H2" />
                            <circle cx="9" cy="20" r="1.5" />
                            <circle cx="18" cy="20" r="1.5" />
                        </svg>
                        <span>
                            {{ isReadOnlyCatalog ? 'Lihat Saja' : isOutOfStock(tool) ? 'Stok Habis' : isInCart(tool.id) ? 'Telah ditambahkan' : 'Tambah' }}
                        </span>
                    </button>
                </div>
            </article>
        </div>

        <div
            v-if="pagination.lastPage > 1"
            class="flex flex-col gap-3 border-t border-slate-200 pt-4 lg:flex-row lg:items-center lg:justify-between"
        >
            <p class="text-sm text-slate-500">
                Halaman <span class="font-semibold text-slate-700">{{ pagination.currentPage }}</span>
                dari <span class="font-semibold text-slate-700">{{ pagination.lastPage }}</span>
            </p>

            <nav class="flex items-center gap-1" aria-label="Navigasi halaman katalog alat">
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

    <CartModal
        v-model="drawerOpen"
        :cart-items="cartItems"
        :unique-items="uniqueItems"
        :total-items="totalItems"
        :template-warnings="templateWarnings"
        @remove="removeFromCart"
        @decrease="decreaseCart"
        @increase="increaseCart"
        @checkout="openCheckout"
        @add-more="addMoreTools"
    />
    <CheckoutModal
        v-model="checkoutOpen"
        :cart-items="cartItems"
        :unique-items="uniqueItems"
        :total-items="totalItems"
        :form="form"
        :date-limits="checkoutDateLimits"
        :is-submitting="isSubmitting"
        :checkout-error="checkoutError"
        @submit="submitCheckout"
    />
</template>

<script setup>
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, inject, nextTick, onMounted, reactive, ref, watch } from 'vue';
import CartModal from './CartModal.vue';
import CheckoutModal from './CheckoutModal.vue';
import ToastNotification from './ToastNotification.vue';
import { loadAreas as loadSharedAreas } from '../lib/areas';

const STORAGE_KEY_PREFIX = 'peminjaman_cart_v1';
const REPEAT_DRAFT_STORAGE_KEY = 'peminjaman_repeat_draft_v1';
const page = usePage();
const cachedUserId = ref(null);
const cachedUser = ref(null);
const initialUserAreaId = page.props.auth?.user?.area?.id ?? '';

const loadCachedUserId = () => {
    return null;
};

const loadCachedUser = () => {
    return null;
};

const katalog = ref([]);
const areas = ref([]);
const selectedAreaId = ref(initialUserAreaId ? String(initialUserAreaId) : '');
const isLoading = ref(false);
const loadError = ref('');
const toolCache = reactive({});

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 8,
});
// Nilai selain 8 dipilih agar habis dibagi 2 dan 3, sehingga baris terakhir pada
// tampilan kartu (2 kolom di md, 3 kolom di xl) tidak menyisakan kartu tunggal.
// Batas atas 100 mengikuti aturan per_page pada endpoint /api/alats.
const perPageOptions = [8, 24, 48, 96];
const perPageChoice = ref(8);

const userId = computed(() => page.props.auth?.user?.id ?? cachedUserId.value);
const userAreaId = computed(() => page.props.auth?.user?.area?.id ?? cachedUser.value?.area?.id ?? null);
const roleKey = computed(() =>
    (page.props.auth?.user?.role?.key ?? cachedUser.value?.role?.key ?? 'user').toLowerCase(),
);
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const activeAreaName = inject('activeAreaName', ref('Area tidak diketahui'));
const areaId = computed(
    () => {
        if (isAreaSwitcherRole.value) {
            return activeAreaId.value ?? null;
        }
        return selectedAreaId.value || userAreaId.value || null;
    },
);
const shouldRestrictArea = computed(
    () => roleKey.value !== 'admin' || isAreaSwitcherRole.value,
);
const areaName = computed(
    () => {
        if (isAreaSwitcherRole.value) {
            return activeAreaName.value;
        }
        const selected = areas.value.find((area) => String(area.id) === String(areaId.value));
        if (selected) {
            return selected.name;
        }
        return (
            page.props.auth?.user?.area?.name ??
            cachedUser.value?.area?.name ??
            'Area tidak diketahui'
        );
    },
);
const isViewingOtherArea = computed(() =>
    roleKey.value === 'user'
    && selectedAreaId.value
    && userAreaId.value
    && String(selectedAreaId.value) !== String(userAreaId.value)
);
const canBrowseCatalogAreas = computed(() => roleKey.value === 'user');
const isReadOnlyCatalog = computed(() => isViewingOtherArea.value);
const storageKey = computed(() =>
    userId.value ? `${STORAGE_KEY_PREFIX}_${userId.value}` : `${STORAGE_KEY_PREFIX}_guest`,
);

const loadCartFromStorage = () => {
    if (typeof window === 'undefined') {
        return;
    }
    const stored = window.localStorage.getItem(storageKey.value);
    if (!stored) {
        cart.value = [];
        return;
    }
    try {
        const parsed = JSON.parse(stored);
        cart.value = Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        cart.value = [];
    }
};

const loadRepeatDraft = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    const stored = window.sessionStorage.getItem(REPEAT_DRAFT_STORAGE_KEY);
    if (!stored) {
        return null;
    }

    window.sessionStorage.removeItem(REPEAT_DRAFT_STORAGE_KEY);

    try {
        const parsed = JSON.parse(stored);
        return parsed && Array.isArray(parsed.items) ? parsed : null;
    } catch (error) {
        return null;
    }
};

const filters = reactive({
    search: '',
});

const filteredTools = computed(() => katalog.value);
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

const cart = ref([]);
const templates = ref([]);
const templatesLoading = ref(false);
const selectedTemplateId = ref('');
const templateWarnings = ref([]);
const cartDrafts = reactive({});
const drawerOpen = ref(false);
const checkoutOpen = ref(false);
const searchInput = ref(null);
const isSubmitting = ref(false);
const checkoutError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const katalogMap = computed(() => new Map(katalog.value.map((item) => [item.id, item])));
const getToolById = (toolId) => toolCache[toolId] ?? katalogMap.value.get(toolId);
const isOutOfStock = (tool) => (tool?.stok ?? 0) <= 0;

// Warna stok mengikuti pola halaman master alat: merah habis, kuning menipis
// (di bawah 35% dari total aset), hijau aman. Kalau total aset tidak diketahui,
// ambang menipis memakai jumlah absolut supaya tetap memberi peringatan.
const stockBadgeClass = (tool) => {
    const available = Number(tool?.stok ?? 0);

    if (available <= 0) {
        return 'bg-rose-100 text-rose-700';
    }

    const total = Number(tool?.totalAset ?? 0);
    const menipis = total > 0 ? available / total < 0.35 : available <= 2;

    return menipis ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700';
};

const changePerPage = () => {
    pagination.perPage = Number(perPageChoice.value) || 8;
    pagination.currentPage = 1;
    loadKatalog(buildFilterParams());
};

const rangeStart = computed(() =>
    pagination.total === 0 ? 0 : (pagination.currentPage - 1) * pagination.perPage + 1
);

const rangeEnd = computed(() =>
    Math.min(pagination.currentPage * pagination.perPage, pagination.total)
);

const normalizeCart = (items) => {
    return items
        .filter((item) => Number.isFinite(item.qty) && item.qty > 0)
        .map((item) => {
            const tool = getToolById(item.id);
            if (!tool) {
                return null;
            }
            const max = tool.stok;
            return {
                id: item.id,
                qty: Math.min(item.qty, max),
            };
        })
        .filter((item) => item && item.qty > 0);
};

const normalizeTool = (item) => {
    const id = item?.id ?? '';
    const stok = Number.isFinite(item?.stok) ? item.stok : Number(item?.total_aset ?? 0);
    return {
        id,
        kode: item?.kode ?? item?.code ?? '-',
        nama: item?.nama ?? '-',
        stok: Number.isFinite(stok) ? stok : 0,
        totalAset: Number(item?.total_aset ?? stok ?? 0),
        deskripsi: item?.deskripsi ?? '',
        lokasi: item?.lokasi ?? item?.area_name ?? '',
        isSharedAreaStock: Boolean(item?.is_shared_area_stock),
        sourcePeminjamanId: item?.source_peminjaman_id ?? null,
        sourceBorrowDate: item?.source_borrow_date ?? '',
        sourceReturnDate: item?.source_return_date ?? '',
    };
};

const cacheTool = (tool) => {
    if (!tool?.id) {
        return;
    }
    toolCache[tool.id] = {
        ...toolCache[tool.id],
        ...tool,
    };
};

const applyRepeatDraft = async (draft) => {
    if (!draft || isReadOnlyCatalog.value) {
        return;
    }

    const requestedItems = draft.items
        .map((item) => {
            const id = item?.id ?? item?.alat_id ?? null;
            const qty = Math.max(1, Math.floor(Number(item?.qty ?? 0)));

            if (!id || !Number.isFinite(qty) || qty <= 0) {
                return null;
            }

            return { id, qty, nama: item?.nama ?? item?.name ?? '-' };
        })
        .filter(Boolean);

    if (!requestedItems.length) {
        return;
    }

    form.value = {
        tanggal_pinjam: draft.tanggal_pinjam ?? '',
        tanggal_kembali: draft.tanggal_kembali ?? '',
        pekerjaan: draft.pekerjaan ?? '',
    };

    const targetAreaId = draft.area_id ?? areaId.value;
    if (!targetAreaId) {
        return;
    }

    let availableItems = [];
    try {
        const response = await axios.post('/api/alats/availability', {
            area_id: targetAreaId,
            items: requestedItems.map(({ id, qty }) => ({ id, qty })),
        });
        availableItems = Array.isArray(response.data?.data) ? response.data.data : [];
    } catch (error) {
        showAlert('error', 'Gagal memeriksa stok terkini untuk peminjaman ulang.');
        return;
    }

    const availabilityMap = new Map(availableItems.map((item) => [Number(item.id), item]));
    const warnings = [];
    const nextCart = requestedItems
        .map((requested) => {
            const item = availabilityMap.get(Number(requested.id));
            if (!item) {
                warnings.push({
                    id: requested.id,
                    nama: requested.nama,
                    requested_qty: requested.qty,
                    available_qty: 0,
                    usable_qty: 0,
                });
                return null;
            }

            const availableQty = Math.max(0, Number(item.available_qty ?? item.stok ?? 0));
            const usableQty = Math.min(requested.qty, Math.max(0, Number(item.usable_qty ?? availableQty)));
            if (usableQty < requested.qty) {
                warnings.push({
                    id: item.id,
                    nama: item.nama ?? requested.nama,
                    requested_qty: requested.qty,
                    available_qty: availableQty,
                    usable_qty: usableQty,
                });
            }
            if (usableQty <= 0) {
                return null;
            }

            cacheTool({
                id: item.id,
                kode: item?.kode ?? '-',
                nama: item?.nama ?? item?.name ?? '-',
                stok: availableQty,
                totalAset: Number(item?.total_aset ?? availableQty),
                deskripsi: '',
                lokasi: item?.area_name ?? areaName.value,
            });
            cartDrafts[item.id] = usableQty;

            return {
                id: item.id,
                qty: usableQty,
            };
        })
        .filter(Boolean);

    templateWarnings.value = warnings;
    drawerOpen.value = true;

    const outOfStockNames = warnings.filter((w) => w.usable_qty <= 0).map((w) => w.nama);
    if (!nextCart.length) {
        showAlert('error', `Stok seluruh alat pada peminjaman ini sudah habis, tidak ada yang ditambahkan ke keranjang: ${outOfStockNames.join(', ')}.`);
        return;
    }

    cart.value = nextCart;

    if (outOfStockNames.length) {
        showAlert('error', `Alat berikut tidak ditambahkan ke keranjang karena stok habis: ${outOfStockNames.join(', ')}.`);
        return;
    }

    if (warnings.length) {
        showAlert('error', 'Sebagian alat disesuaikan dengan stok yang tersedia. Detail ada di keranjang.');
        return;
    }

    showAlert('success', 'Draft peminjaman ulang sudah dimuat. Silakan sesuaikan alat, jumlah, atau periode sebelum checkout.');
};

let filterTimeout = null;

const buildFilterParams = () => {
    const params = {};
    const searchText = filters.search.trim();
    if (searchText) {
        params.search = searchText;
    }
    if (shouldRestrictArea.value && areaId.value) {
        params.area_id = areaId.value;
    }
    if (roleKey.value === 'user' && selectedAreaId.value) {
        params.browse_area = true;
    }
    return params;
};

const loadAreas = async () => {
    try {
        areas.value = await loadSharedAreas();
    } catch (error) {
        areas.value = [];
    }
};

const loadKatalog = async (params = {}) => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/alats', {
            params: {
                ...params,
                page: pagination.currentPage,
                per_page: pagination.perPage,
            },
        });
        const payload = response.data;
        if (Array.isArray(payload)) {
            katalog.value = payload.map((item) => normalizeTool(item));
            pagination.total = katalog.value.length;
            pagination.lastPage = 1;
            pagination.currentPage = 1;
        } else {
            const data = Array.isArray(payload?.data) ? payload.data : [];
            const meta = payload?.meta ?? {};
            katalog.value = data.map((item) => normalizeTool(item));
            pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
            pagination.lastPage = Number(meta.last_page ?? 1);
            pagination.perPage = Number(meta.per_page ?? pagination.perPage);
            pagination.total = Number(meta.total ?? katalog.value.length);
            // Selaraskan dropdown dengan nilai yang benar-benar dipakai server,
            // termasuk bila server membatasinya.
            if (perPageOptions.includes(pagination.perPage)) {
                perPageChoice.value = pagination.perPage;
            }
        }
        katalog.value.forEach((tool) => cacheTool(tool));
        cart.value = normalizeCart(cart.value);
    } catch (error) {
        katalog.value = [];
        loadError.value = 'Gagal memuat data alat.';
        pagination.total = 0;
        pagination.lastPage = 1;
        pagination.currentPage = 1;
    } finally {
        isLoading.value = false;
    }
};

const loadTemplates = async () => {
    const targetAreaId = areaId.value;
    if (!targetAreaId || isReadOnlyCatalog.value) {
        templates.value = [];
        selectedTemplateId.value = '';
        return;
    }

    templatesLoading.value = true;
    try {
        const response = await axios.get('/api/peminjaman-templates', {
            params: {
                kategori: 'Intra Area',
                area_id: targetAreaId,
            },
        });
        templates.value = Array.isArray(response.data) ? response.data : [];
        if (!templates.value.some((template) => String(template.id) === String(selectedTemplateId.value))) {
            selectedTemplateId.value = '';
        }
    } catch (error) {
        templates.value = [];
        selectedTemplateId.value = '';
    } finally {
        templatesLoading.value = false;
    }
};

const goToPage = (page) => {
    const next = Math.min(Math.max(1, page), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }
    pagination.currentPage = next;
    loadKatalog(buildFilterParams());
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        cachedUserId.value = loadCachedUserId();
        cachedUser.value = loadCachedUser();
        loadCartFromStorage();
        applyRepeatDraft(loadRepeatDraft());
    }
    loadAreas();
    loadKatalog(buildFilterParams());
    loadTemplates();
});

watch(
    () => filters.search,
    () => {
        if (filterTimeout) {
            clearTimeout(filterTimeout);
        }
        filterTimeout = setTimeout(() => {
            pagination.currentPage = 1;
            loadKatalog(buildFilterParams());
        }, 300);
    },
);

watch(
    () => activeAreaId.value,
    (next, prev) => {
        if (!isAreaSwitcherRole.value || next === prev) {
            return;
        }
        pagination.currentPage = 1;
        loadKatalog(buildFilterParams());
    },
);

watch(
    () => selectedAreaId.value,
    (next, prev) => {
        if (next === prev || isAreaSwitcherRole.value) {
            return;
        }
        pagination.currentPage = 1;
        Object.keys(cartDrafts).forEach((key) => {
            delete cartDrafts[key];
        });
        cart.value = [];
        templateWarnings.value = [];
        loadKatalog(buildFilterParams());
        loadTemplates();
    },
);

watch(
    () => [areaId.value, isReadOnlyCatalog.value],
    () => {
        loadTemplates();
    },
);

watch(
    () => userId.value,
    (value, previous) => {
        if (value === previous) {
            return;
        }
        if (typeof window === 'undefined') {
            return;
        }
        cachedUser.value = loadCachedUser();
        loadCartFromStorage();
        Object.keys(cartDrafts).forEach((key) => {
            delete cartDrafts[key];
        });
    },
);

watch(
    cart,
    (value) => {
        if (typeof window === 'undefined') {
            return;
        }
        window.localStorage.setItem(storageKey.value, JSON.stringify(value));
    },
    { deep: true },
);

const cartItems = computed(() =>
    cart.value
        .map((item) => {
            const tool = getToolById(item.id);
            if (!tool) {
                return null;
            }
            return { ...tool, qty: item.qty };
        })
        .filter((item) => item && item.qty > 0),
);

const checkoutDateLimits = computed(() => {
    const sharedItems = cartItems.value.filter((item) => item.isSharedAreaStock);
    if (!sharedItems.length) {
        return {
            minBorrowDate: '',
            maxReturnDate: '',
            message: '',
        };
    }

    const borrowDates = sharedItems
        .map((item) => item.sourceBorrowDate)
        .filter(Boolean)
        .sort();
    const returnDates = sharedItems
        .map((item) => item.sourceReturnDate)
        .filter(Boolean)
        .sort();
    const minBorrowDate = borrowDates[borrowDates.length - 1] ?? '';
    const maxReturnDate = returnDates[0] ?? '';

    return {
        minBorrowDate,
        maxReturnDate,
        message: minBorrowDate && maxReturnDate
            ? `Tanggal peminjaman alat antar area dibatasi ${minBorrowDate} sampai ${maxReturnDate}.`
            : 'Tanggal peminjaman alat antar area dibatasi sesuai periode peminjaman antar area.',
    };
});

const totalItems = computed(() => cart.value.reduce((total, item) => total + item.qty, 0));
const uniqueItems = computed(() => cart.value.length);
const viewMode = ref('list');

const findCartItem = (toolId) => cart.value.find((item) => item.id === toolId);
const isInCart = (toolId) => !!findCartItem(toolId);

const getDraftQty = (toolId) => {
    const tool = getToolById(toolId);
    if (!tool || tool.stok <= 0) {
        return 0;
    }
    return cartDrafts[toolId] ?? 1;
};
const setDraftQty = (toolId, qty) => {
    const tool = getToolById(toolId);
    const max = tool ? tool.stok : qty;
    if (max <= 0) {
        cartDrafts[toolId] = 0;
        return;
    }
    cartDrafts[toolId] = Math.max(1, Math.min(qty, max));
};

const displayQty = (toolId) => {
    const item = findCartItem(toolId);
    return item ? item.qty : getDraftQty(toolId);
};

const setToolQty = (tool, value) => {
    if (!tool || tool.stok <= 0) {
        return;
    }

    const parsed = Number(value);
    const qty = Number.isFinite(parsed) ? Math.floor(parsed) : 1;
    const normalizedQty = Math.max(1, Math.min(qty, tool.stok));
    const item = findCartItem(tool.id);

    if (item) {
        item.qty = normalizedQty;
        return;
    }

    setDraftQty(tool.id, normalizedQty);
};

const toggleCartSelection = (tool, checked) => {
    if (isReadOnlyCatalog.value || !tool || tool.stok <= 0) {
        return;
    }

    if (!checked) {
        removeFromCart(tool.id);
        return;
    }

    if (!isInCart(tool.id)) {
        addToCart(tool);
    }
};

const increaseTool = (tool) => {
    if (tool.stok <= 0) {
        return;
    }
    const item = findCartItem(tool.id);
    if (item) {
        if (item.qty < tool.stok) {
            item.qty += 1;
        }
        return;
    }
    setDraftQty(tool.id, getDraftQty(tool.id) + 1);
};

const decreaseTool = (tool) => {
    if (tool.stok <= 0) {
        return;
    }
    const item = findCartItem(tool.id);
    if (item) {
        if (item.qty > 1) {
            item.qty -= 1;
        }
        return;
    }
    setDraftQty(tool.id, getDraftQty(tool.id) - 1);
};

const addToCart = (tool) => {
    if (isReadOnlyCatalog.value) {
        return;
    }
    if (isInCart(tool.id)) {
        return;
    }
    if (tool.stok <= 0) {
        return;
    }
    cacheTool(tool);
    const qty = Math.min(getDraftQty(tool.id), tool.stok);
    if (qty <= 0) {
        return;
    }
    cart.value.push({ id: tool.id, qty });
};

const applySelectedTemplate = async () => {
    if (isReadOnlyCatalog.value || !selectedTemplateId.value) {
        return;
    }

    const template = templates.value.find((item) => String(item.id) === String(selectedTemplateId.value));
    if (!template) {
        return;
    }

    const requestedItems = (template.items ?? [])
        .map((item) => {
            const id = item?.alat_id ?? item?.id ?? null;
            const qty = Math.max(1, Math.floor(Number(item?.qty ?? 1)));
            if (!id || qty <= 0) {
                return null;
            }

            return { id, qty };
        })
        .filter(Boolean);

    if (!requestedItems.length) {
        showAlert('error', 'Template tidak memiliki daftar alat yang dapat digunakan.');
        return;
    }

    let availableItems = [];
    try {
        const response = await axios.post('/api/alats/availability', {
            area_id: areaId.value,
            items: requestedItems,
        });
        availableItems = Array.isArray(response.data?.data) ? response.data.data : [];
    } catch (error) {
        showAlert('error', 'Gagal memeriksa stok template.');
        return;
    }

    const availabilityMap = new Map(availableItems.map((item) => [Number(item.id), item]));
    const warnings = [];
    const nextCart = requestedItems
        .map((requested) => {
            const item = availabilityMap.get(Number(requested.id));
            if (!item) {
                warnings.push({
                    id: requested.id,
                    nama: 'Alat tidak ditemukan',
                    requested_qty: requested.qty,
                    available_qty: 0,
                    usable_qty: 0,
                });
                return null;
            }

            const availableQty = Math.max(0, Number(item.available_qty ?? item.stok ?? 0));
            const usableQty = Math.min(requested.qty, Math.max(0, Number(item.usable_qty ?? availableQty)));
            if (usableQty < requested.qty) {
                warnings.push({
                    id: item.id,
                    nama: item.nama ?? '-',
                    requested_qty: requested.qty,
                    available_qty: availableQty,
                    usable_qty: usableQty,
                });
            }
            if (usableQty <= 0) {
                return null;
            }

            cacheTool({
                id: item.id,
                kode: item?.kode ?? '-',
                nama: item?.nama ?? item?.name ?? '-',
                stok: availableQty,
                totalAset: Number(item?.total_aset ?? availableQty),
                deskripsi: '',
                lokasi: item?.area_name ?? areaName.value,
            });
            cartDrafts[item.id] = usableQty;

            return {
                id: item.id,
                qty: usableQty,
            };
        })
        .filter(Boolean);

    templateWarnings.value = warnings;
    if (!nextCart.length) {
        drawerOpen.value = true;
        showAlert('error', 'Tidak ada alat dari template yang memiliki stok cukup.');
        return;
    }

    cart.value = nextCart;
    drawerOpen.value = true;
    if (warnings.length) {
        showAlert('error', 'Template dimuat, tetapi sebagian alat disesuaikan dengan stok. Detail ada di keranjang.');
        return;
    }

    showAlert('success', `Template "${template.nama}" dimuat ke keranjang.`);
};

const toggleView = () => {
    viewMode.value = viewMode.value === 'grid' ? 'list' : 'grid';
};

const removeFromCart = (toolId) => {
    cart.value = cart.value.filter((item) => item.id !== toolId);
    templateWarnings.value = templateWarnings.value.filter((item) => Number(item.id) !== Number(toolId));
};

const increaseCart = (toolId) => {
    const cartItem = findCartItem(toolId);
    const tool = getToolById(toolId);
    if (!cartItem || !tool) {
        return;
    }
    if (cartItem.qty < tool.stok) {
        cartItem.qty += 1;
    }
};

const decreaseCart = (toolId) => {
    const cartItem = findCartItem(toolId);
    if (!cartItem) {
        return;
    }
    if (cartItem.qty > 1) {
        cartItem.qty -= 1;
    }
};

const form = ref({
    tanggal_pinjam: '',
    tanggal_kembali: '',
    pekerjaan: '',
});

// Tombol "Tambah Lagi" di keranjang: tutup drawer lalu bawa pengguna kembali ke
// kolom pencarian katalog supaya bisa langsung mencari alat berikutnya. Isi
// keranjang sengaja tidak diubah.
const addMoreTools = async () => {
    drawerOpen.value = false;
    await nextTick();

    const input = searchInput.value;
    if (!input) {
        return;
    }

    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
    input.focus({ preventScroll: true });
};

const openCheckout = () => {
    if (isReadOnlyCatalog.value) {
        checkoutError.value = 'Peminjaman hanya dapat dibuat dari area akun Anda.';
        return;
    }
    if (!cartItems.value.length) {
        return;
    }
    checkoutError.value = '';
    if (checkoutDateLimits.value.minBorrowDate && (!form.value.tanggal_pinjam || form.value.tanggal_pinjam < checkoutDateLimits.value.minBorrowDate)) {
        form.value.tanggal_pinjam = checkoutDateLimits.value.minBorrowDate;
    }
    if (checkoutDateLimits.value.maxReturnDate && (!form.value.tanggal_kembali || form.value.tanggal_kembali > checkoutDateLimits.value.maxReturnDate)) {
        form.value.tanggal_kembali = checkoutDateLimits.value.maxReturnDate;
    }
    checkoutOpen.value = true;
};

const resetCheckout = () => {
    form.value = {
        tanggal_pinjam: '',
        tanggal_kembali: '',
        pekerjaan: '',
    };
    cart.value = [];
    Object.keys(cartDrafts).forEach((key) => {
        delete cartDrafts[key];
    });
    checkoutOpen.value = false;
    drawerOpen.value = false;
    checkoutError.value = '';
    templateWarnings.value = [];
};

const submitCheckout = async () => {
    if (isReadOnlyCatalog.value) {
        checkoutError.value = 'Peminjaman hanya dapat dibuat dari area akun Anda.';
        return;
    }
    if (!form.value.tanggal_pinjam || !form.value.tanggal_kembali || !form.value.pekerjaan) {
        checkoutError.value = 'Lengkapi tanggal pinjam, tanggal kembali, dan pekerjaan.';
        return;
    }
    if (!cartItems.value.length) {
        checkoutError.value = 'Keranjang masih kosong.';
        return;
    }
    if (checkoutDateLimits.value.minBorrowDate && form.value.tanggal_pinjam < checkoutDateLimits.value.minBorrowDate) {
        checkoutError.value = checkoutDateLimits.value.message;
        return;
    }
    if (checkoutDateLimits.value.maxReturnDate && form.value.tanggal_kembali > checkoutDateLimits.value.maxReturnDate) {
        checkoutError.value = checkoutDateLimits.value.message;
        return;
    }
    isSubmitting.value = true;
    checkoutError.value = '';
    try {
        const payload = {
            tanggal_pinjam: form.value.tanggal_pinjam,
            tanggal_kembali: form.value.tanggal_kembali,
            pekerjaan: form.value.pekerjaan,
            ...(roleKey.value === 'super_admin' && areaId.value ? { area_id: areaId.value } : {}),
            items: cartItems.value.map((item) => ({ id: item.id, qty: item.qty })),
        };
        await axios.post('/api/peminjaman', payload);
        resetCheckout();
        loadKatalog(buildFilterParams());
        showAlert('success', 'Peminjaman berhasil dibuat.');
    } catch (error) {
        const message =
            error?.response?.data?.message ||
            error?.response?.data?.errors?.items?.[0] ||
            'Gagal membuat peminjaman.';
        checkoutError.value = message;
        showAlert('error', message);
    } finally {
        isSubmitting.value = false;
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
</script>
