<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Template Peminjaman</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola daftar alat siap pakai untuk peminjaman intra area dan antar area.</p>
            </div>
            <button
                class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300 focus-visible:ring-offset-2"
                type="button"
                @click="startCreate"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 5v14M5 12h14" />
                </svg>
                Template Baru
            </button>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50">
            <div class="grid gap-3 md:grid-cols-3">
                <label class="space-y-2">
                    <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Kategori</span>
                    <select
                        v-model="filters.kategori"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Semua kategori</option>
                        <option value="Intra Area">Intra Area</option>
                        <option value="Antar Area">Antar Area</option>
                    </select>
                </label>
                <label v-if="isSuperAdmin" class="space-y-2">
                    <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Area</span>
                    <select
                        v-model="filters.area_id"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Semua area</option>
                        <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                            {{ area.name }}
                        </option>
                    </select>
                </label>
                <label class="space-y-2">
                    <span class="block text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Cari</span>
                    <span class="relative block">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </span>
                        <input
                            v-model="filters.search"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-9 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Nama template"
                            type="text"
                        />
                        <button
                            v-if="filters.search"
                            class="absolute inset-y-0 right-2 my-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                            type="button"
                            title="Kosongkan pencarian"
                            aria-label="Kosongkan pencarian"
                            @click="filters.search = ''"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18M6 6l12 12" />
                            </svg>
                        </button>
                    </span>
                </label>
            </div>

            <div v-if="hasActiveFilters" class="mt-3 flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Filter aktif</span>
                <span
                    v-if="filters.kategori"
                    class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
                >
                    Kategori: {{ filters.kategori }}
                    <button
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                        type="button"
                        title="Hapus filter kategori"
                        aria-label="Hapus filter kategori"
                        @click="filters.kategori = ''"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </span>
                <span
                    v-if="isSuperAdmin && filters.area_id"
                    class="inline-flex items-center gap-1.5 rounded-full border border-blue-200 bg-blue-50 py-1 pl-3 pr-1.5 text-xs font-semibold text-blue-700"
                >
                    Area: {{ filterAreaName }}
                    <button
                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full transition hover:bg-blue-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-300"
                        type="button"
                        title="Hapus filter area"
                        aria-label="Hapus filter area"
                        @click="filters.area_id = ''"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true">
                            <path d="M18 6 6 18M6 6l12 12" />
                        </svg>
                    </button>
                </span>
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
                        Memuat template...
                    </div>
                    <div class="divide-y divide-slate-100 bg-white">
                        <div v-for="row in 4" :key="`skeleton-${row}`" class="flex items-center gap-4 px-4 py-4">
                            <div class="h-9 flex-1 animate-pulse rounded-lg bg-slate-100"></div>
                            <div class="h-6 w-24 shrink-0 animate-pulse rounded-full bg-slate-100"></div>
                            <div class="h-9 w-36 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                            <div class="h-9 w-20 shrink-0 animate-pulse rounded-lg bg-slate-100"></div>
                        </div>
                    </div>
                </div>

                <!-- Empty -->
                <div v-else-if="!filteredTemplates.length" class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/60 px-6 py-12 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 shadow-sm">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 3H5a2 2 0 0 0-2 2v4M15 3h4a2 2 0 0 1 2 2v4M9 21H5a2 2 0 0 1-2-2v-4M15 21h4a2 2 0 0 0 2-2v-4" />
                            <path d="M8 12h8M12 8v8" />
                        </svg>
                    </span>
                    <p class="mt-3 text-sm font-semibold text-slate-700">
                        {{ hasActiveFilters ? 'Tidak ada template yang cocok' : 'Belum ada template' }}
                    </p>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                        {{
                            hasActiveFilters
                                ? 'Coba ubah kata kunci, kategori, atau area untuk memperluas hasil pencarian.'
                                : 'Buat template berisi alat yang sering dipinjam bersama supaya pengajuan berikutnya tinggal sekali klik.'
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
                    <button
                        v-else
                        class="mt-4 inline-flex h-10 items-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700"
                        type="button"
                        @click="startCreate"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 5v14M5 12h14" />
                        </svg>
                        Buat template pertama
                    </button>
                </div>

                <!-- Tabel (md ke atas) -->
                <div v-else>
                    <div class="hidden overflow-hidden rounded-2xl border border-slate-200 md:block">
                        <div class="max-h-[68vh] overflow-auto">
                            <table class="w-full min-w-[720px] text-sm">
                                <caption class="sr-only">
                                    Daftar template peminjaman beserta area dan jumlah alatnya
                                </caption>
                                <thead class="sticky top-0 z-10 bg-slate-50">
                                    <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                        <th scope="col" class="border-b border-slate-200 px-4 py-3">Template</th>
                                        <th scope="col" class="border-b border-slate-200 px-4 py-3">Kategori</th>
                                        <th scope="col" class="border-b border-slate-200 px-4 py-3">Area</th>
                                        <th scope="col" class="border-b border-slate-200 px-4 py-3">Alat</th>
                                        <th scope="col" class="border-b border-slate-200 px-4 py-3 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr
                                        v-for="template in filteredTemplates"
                                        :key="template.id"
                                        class="align-top transition hover:bg-slate-50/70"
                                    >
                                        <td class="max-w-[22rem] px-4 py-4">
                                            <p class="font-semibold text-slate-900" :title="template.nama">{{ template.nama }}</p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span
                                                class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-[11px] font-semibold"
                                                :class="kategoriClass(template.kategori)"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full" :class="kategoriDotClass(template.kategori)" aria-hidden="true"></span>
                                                {{ template.kategori }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-slate-600">
                                            <p>{{ template.area_name }}</p>
                                            <p v-if="template.source_area_name" class="mt-0.5 text-xs text-slate-400">
                                                Sumber: {{ template.source_area_name }}
                                            </p>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold tabular-nums text-slate-700">
                                                <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                                                    <path d="m3.3 7 8.7 5 8.7-5" />
                                                </svg>
                                                {{ template.items_count }} alat
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <div class="flex items-center justify-end gap-1">
                                                <button
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                                                    type="button"
                                                    title="Edit template"
                                                    aria-label="Edit template"
                                                    @click="editTemplate(template)"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M12 20h9" />
                                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    class="flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-200"
                                                    type="button"
                                                    title="Hapus template"
                                                    aria-label="Hapus template"
                                                    @click="askDeleteTemplate(template)"
                                                >
                                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M3 6h18" />
                                                        <path d="M8 6V4h8v2" />
                                                        <path d="m6 6 1 14h10l1-14" />
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
                            v-for="template in filteredTemplates"
                            :key="`card-${template.id}`"
                            class="rounded-2xl border border-slate-200 bg-white p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 font-semibold text-slate-900">{{ template.nama }}</p>
                                <span
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-3 py-1 text-[11px] font-semibold"
                                    :class="kategoriClass(template.kategori)"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="kategoriDotClass(template.kategori)" aria-hidden="true"></span>
                                    {{ template.kategori }}
                                </span>
                            </div>

                            <dl class="mt-3 space-y-1.5 text-sm">
                                <div class="flex gap-2">
                                    <dt class="w-20 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Area</dt>
                                    <dd class="min-w-0 text-slate-600">
                                        {{ template.area_name }}
                                        <span v-if="template.source_area_name" class="block text-xs text-slate-400">
                                            Sumber: {{ template.source_area_name }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="flex gap-2">
                                    <dt class="w-20 shrink-0 text-xs font-semibold uppercase tracking-[0.12em] text-slate-400">Alat</dt>
                                    <dd class="min-w-0 text-slate-600">{{ template.items_count }} alat</dd>
                                </div>
                            </dl>

                            <div class="mt-3 flex gap-2">
                                <button
                                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                    type="button"
                                    @click="editTemplate(template)"
                                >
                                    Edit
                                </button>
                                <button
                                    class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-300"
                                    type="button"
                                    @click="askDeleteTemplate(template)"
                                >
                                    Hapus
                                </button>
                            </div>
                        </li>
                    </ul>

                    <p class="mt-4 border-t border-slate-200 pt-4 text-sm text-slate-500">
                        Total <span class="font-semibold text-slate-700">{{ filteredTemplates.length }}</span> template
                        <span v-if="hasActiveFilters"> sesuai filter</span>
                    </p>
                </div>
            </div>
        </section>

        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/40 px-4 py-6"
            @click.self="closeModal"
        >
            <form
                class="flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/20"
                @submit.prevent="submit"
            >
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-semibold text-slate-900">{{ editingId ? 'Edit Template' : 'Template Baru' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ selectedItems.length }} alat dipilih</p>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-slate-300 hover:bg-slate-50"
                        type="button"
                        @click="closeModal"
                    >
                        <span class="sr-only">Tutup</span>
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                    <label class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Nama Template</span>
                        <input
                            v-model="form.nama"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Contoh: SI Unit 1 PLTU Suralaya"
                            type="text"
                        />
                    </label>

                    <label class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Jenis Peminjaman</span>
                        <select
                            v-model="form.kategori"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="Intra Area">Intra Area</option>
                            <option value="Antar Area">Antar Area</option>
                        </select>
                    </label>

                    <label v-if="isSuperAdmin" class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Area Peminjam</span>
                        <select
                            v-model="form.area_id"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Pilih area</option>
                            <option v-for="area in areas" :key="area.id" :value="String(area.id)">
                                {{ area.name }}
                            </option>
                        </select>
                    </label>

                    <label v-if="form.kategori === 'Antar Area'" class="space-y-1.5 text-sm font-medium text-slate-700">
                        <span>Area Sumber</span>
                        <select
                            v-model="form.source_area_id"
                            class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Pilih area sumber</option>
                            <option v-for="area in sourceAreas" :key="area.id" :value="String(area.id)">
                                {{ area.name }}
                            </option>
                        </select>
                    </label>

                    <div class="rounded-xl border border-slate-200">
                        <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 p-3">
                            <label class="relative min-w-0 flex-1">
                                <span class="sr-only">Cari alat</span>
                                <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="11" cy="11" r="7" />
                                        <path d="m20 20-3.5-3.5" />
                                    </svg>
                                </span>
                                <input
                                    v-model="toolSearch"
                                    class="h-10 w-full rounded-lg border border-slate-200 bg-white pl-9 pr-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    placeholder="Cari alat area template..."
                                    type="text"
                                />
                            </label>
                            <span
                                class="shrink-0 rounded-full px-3 py-1 text-[11px] font-semibold tabular-nums"
                                :class="selectedItems.length ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-500'"
                            >
                                {{ selectedItems.length }} alat dipilih
                            </span>
                        </div>
                        <div class="max-h-[360px] divide-y divide-slate-100 overflow-y-auto">
                            <div v-if="toolLoading" class="flex items-center justify-center gap-2 px-3 py-6 text-sm text-slate-500">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" class="opacity-25" />
                                    <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                                </svg>
                                Memuat alat...
                            </div>
                            <p v-else-if="!toolAreaId" class="px-3 py-6 text-center text-sm text-slate-500">Pilih area terlebih dahulu.</p>
                            <p v-else-if="!tools.length" class="px-3 py-6 text-center text-sm text-slate-500">Alat tidak ditemukan.</p>
                            <template v-else>
                                <label
                                    v-for="tool in tools"
                                    :key="tool.id"
                                    class="flex cursor-pointer items-center gap-3 px-3 py-3 transition"
                                    :class="qtyFor(tool.id) > 0 ? 'bg-blue-50/60' : 'hover:bg-slate-50'"
                                >
                                    <input
                                        class="h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        type="checkbox"
                                        :checked="qtyFor(tool.id) > 0"
                                        @change="toggleTool(tool)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-semibold text-slate-900">{{ tool.nama }}</p>
                                        <p class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-500">
                                            <span class="font-mono">{{ tool.kode }}</span>
                                            <span
                                                class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold tabular-nums"
                                                :class="stockBadgeClass(tool)"
                                            >
                                                {{ tool.stok_tersedia }} / {{ tool.total_aset }} tersedia
                                            </span>
                                        </p>
                                    </div>
                                    <input
                                        class="h-9 w-20 shrink-0 rounded-lg border border-slate-200 px-2 text-center text-sm font-semibold text-slate-700 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        :max="tool.total_aset"
                                        min="0"
                                        type="number"
                                        :value="qtyFor(tool.id) || 0"
                                        :aria-label="`Jumlah ${tool.nama}`"
                                        @click.stop
                                        @input="setQty(tool, $event.target.value)"
                                    />
                                </label>
                            </template>
                        </div>
                    </div>

                    <p v-if="formError" class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-600">
                        {{ formError }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 px-5 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        type="button"
                        @click="closeModal"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="submit"
                        :disabled="isSubmitting"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : 'Simpan Template' }}
                    </button>
                </div>
            </form>
        </div>

        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="cancelDelete"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Konfirmasi</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Hapus template</h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        aria-label="Tutup"
                        @click="cancelDelete"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4">
                        <div class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 9v4" />
                                <path d="M12 17h.01" />
                                <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-slate-900">Hapus "{{ deleteTarget.nama }}"?</p>
                            <p class="mt-1 text-xs text-slate-500">
                                Template berisi {{ deleteTarget.items_count }} alat ini akan dihapus permanen.
                                Peminjaman yang sudah dibuat dari template ini tidak terpengaruh.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        :disabled="isDeleting"
                        @click="cancelDelete"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-rose-300"
                        type="button"
                        :disabled="isDeleting"
                        @click="confirmDelete"
                    >
                        {{ isDeleting ? 'Menghapus...' : 'Ya, hapus' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import ToastNotification from '../../Components/ToastNotification.vue';
import { loadAreas as loadSharedAreas } from '../../lib/areas';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Template Peminjaman',
                subtitle: 'Daftar alat siap pakai untuk peminjaman',
                activeMenu: 'template-peminjaman',
            },
            () => page
        ),
});

const page = usePage();
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const areas = ref([]);
const templates = ref([]);
const tools = ref([]);
const selectedQty = reactive({});
const isLoading = ref(false);
const toolLoading = ref(false);
const isSubmitting = ref(false);
const isDeleting = ref(false);
const deleteTarget = ref(null);
const isHydratingForm = ref(false);
const modalOpen = ref(false);
const editingId = ref(null);
const formError = ref('');
const toolSearch = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;
let toolSearchTimeout = null;

const filters = reactive({
    kategori: '',
    area_id: '',
    search: '',
});

const form = reactive({
    nama: '',
    kategori: 'Intra Area',
    area_id: '',
    source_area_id: '',
});

const authUser = computed(() => page.props.auth?.user ?? null);
const roleKey = computed(() => (authUser.value?.role?.key ?? '').toLowerCase());
const isSuperAdmin = computed(() => roleKey.value === 'super_admin');
const userAreaId = computed(() => authUser.value?.area_id ?? authUser.value?.area?.id ?? '');
const activeManagedAreaId = computed(() =>
    isSuperAdmin.value
        ? form.area_id
        : isAreaSwitcherRole.value
          ? activeAreaId.value ?? userAreaId.value
          : userAreaId.value
);
const toolAreaId = computed(() => (form.kategori === 'Antar Area' ? form.source_area_id : activeManagedAreaId.value));
const sourceAreas = computed(() => areas.value.filter((area) => Number(area.id) !== Number(activeManagedAreaId.value)));
const selectedItems = computed(() =>
    Object.entries(selectedQty)
        .filter(([, qty]) => Number(qty) > 0)
        .map(([id, qty]) => ({ id: Number(id), qty: Number(qty) }))
);
const filteredTemplates = computed(() => {
    const keyword = filters.search.trim().toLowerCase();
    return templates.value.filter((template) => {
        if (keyword && !String(template.nama ?? '').toLowerCase().includes(keyword)) {
            return false;
        }
        return true;
    });
});

const hasActiveFilters = computed(
    () => filters.search.trim() !== '' || filters.kategori !== '' || (isSuperAdmin.value && filters.area_id !== '')
);

const filterAreaName = computed(() => {
    const area = areas.value.find((row) => String(row.id) === String(filters.area_id));

    return area?.name ?? 'Area terpilih';
});

const resetFilters = () => {
    filters.search = '';
    filters.kategori = '';
    if (isSuperAdmin.value) {
        filters.area_id = '';
    }
};

const kategoriClass = (kategori) =>
    kategori === 'Antar Area' ? 'bg-purple-50 text-purple-700' : 'bg-sky-50 text-sky-700';

const kategoriDotClass = (kategori) => (kategori === 'Antar Area' ? 'bg-purple-500' : 'bg-sky-500');

// Warna mengikuti pola halaman master alat: merah habis, kuning menipis, hijau aman.
const stockBadgeClass = (tool) => {
    const total = Number(tool?.total_aset ?? 0);
    const available = Number(tool?.stok_tersedia ?? 0);

    if (available <= 0) {
        return 'bg-rose-100 text-rose-700';
    }

    if (total > 0 && available / total < 0.35) {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-emerald-100 text-emerald-700';
};

const resetSelection = () => {
    Object.keys(selectedQty).forEach((key) => {
        delete selectedQty[key];
    });
};

const resetForm = () => {
    editingId.value = null;
    form.nama = '';
    form.kategori = 'Intra Area';
    form.area_id = isSuperAdmin.value ? (filters.area_id || '') : String(userAreaId.value || '');
    form.source_area_id = '';
    formError.value = '';
    toolSearch.value = '';
    resetSelection();
};

const normalizeTool = (item) => ({
    id: Number(item?.id ?? 0),
    kode: item?.kode ?? '-',
    nama: item?.nama ?? '-',
    total_aset: Number(item?.total_aset ?? 0),
    stok_tersedia: Number(item?.stok_tersedia ?? item?.stok ?? item?.total_aset ?? 0),
});

const loadAreas = async () => {
    try {
        areas.value = await loadSharedAreas();
    } catch (error) {
        areas.value = [];
    }
};

const loadTemplates = async () => {
    isLoading.value = true;
    try {
        const params = {};
        if (filters.kategori) {
            params.kategori = filters.kategori;
        }
        if (isSuperAdmin.value && filters.area_id) {
            params.area_id = filters.area_id;
        }
        const response = await axios.get('/api/peminjaman-templates', { params });
        templates.value = Array.isArray(response.data) ? response.data : [];
    } catch (error) {
        templates.value = [];
        showAlert('error', 'Gagal memuat template peminjaman.');
    } finally {
        isLoading.value = false;
    }
};

const loadTools = async () => {
    if (!toolAreaId.value) {
        tools.value = [];
        return;
    }

    toolLoading.value = true;
    try {
        const response = await axios.get('/api/alats', {
            params: {
                area_id: toolAreaId.value,
                search: toolSearch.value.trim() || undefined,
                inter_area_source: form.kategori === 'Antar Area' ? 1 : undefined,
                per_page: 100,
            },
        });
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        tools.value = data.map((item) => normalizeTool(item));
    } catch (error) {
        tools.value = [];
        showAlert('error', 'Gagal memuat alat template.');
    } finally {
        toolLoading.value = false;
    }
};

const qtyFor = (toolId) => Number(selectedQty[toolId] ?? 0);

const setQty = (tool, value) => {
    const max = Math.max(1, Number(tool?.total_aset ?? tool?.stok_tersedia ?? 1));
    const qty = Math.max(1, Math.min(Math.floor(Number(value) || 1), max));
    selectedQty[tool.id] = qty;
};

const toggleTool = (tool) => {
    if (qtyFor(tool.id) > 0) {
        delete selectedQty[tool.id];
        return;
    }
    setQty(tool, 1);
};

const startCreate = () => {
    resetForm();
    modalOpen.value = true;
    loadTools();
};

const editTemplate = (template) => {
    isHydratingForm.value = true;
    editingId.value = template.id;
    form.nama = template.nama ?? '';
    form.kategori = template.kategori ?? 'Intra Area';
    form.area_id = template.area_id ? String(template.area_id) : '';
    form.source_area_id = template.source_area_id ? String(template.source_area_id) : '';
    resetSelection();
    (template.items ?? []).forEach((item) => {
        selectedQty[item.alat_id ?? item.id] = Number(item.qty ?? 1);
    });
    formError.value = '';
    modalOpen.value = true;
    loadTools().finally(() => {
        isHydratingForm.value = false;
    });
};

const closeModal = () => {
    if (isSubmitting.value) {
        return;
    }
    modalOpen.value = false;
    resetForm();
};

const submit = async () => {
    if (!form.nama || !form.kategori || !selectedItems.value.length) {
        formError.value = 'Lengkapi nama template, jenis peminjaman, dan minimal satu alat.';
        return;
    }
    if (isSuperAdmin.value && !form.area_id) {
        formError.value = 'Area peminjam wajib dipilih.';
        return;
    }
    if (form.kategori === 'Antar Area' && !form.source_area_id) {
        formError.value = 'Area sumber wajib dipilih.';
        return;
    }

    isSubmitting.value = true;
    formError.value = '';
    try {
        const payload = {
            nama: form.nama,
            kategori: form.kategori,
            area_id: activeManagedAreaId.value,
            source_area_id: form.kategori === 'Antar Area' ? form.source_area_id : null,
            items: selectedItems.value,
        };
        if (editingId.value) {
            await axios.put(`/api/peminjaman-templates/${editingId.value}`, payload);
        } else {
            await axios.post('/api/peminjaman-templates', payload);
        }
        showAlert('success', 'Template peminjaman berhasil disimpan.');
        modalOpen.value = false;
        resetForm();
        await loadTemplates();
        await loadTools();
    } catch (error) {
        const errors = error.response?.data?.errors ?? {};
        formError.value =
            error.response?.data?.message ||
            errors.items?.[0] ||
            errors.area_id?.[0] ||
            errors.source_area_id?.[0] ||
            'Gagal menyimpan template.';
    } finally {
        isSubmitting.value = false;
    }
};

const askDeleteTemplate = (template) => {
    deleteTarget.value = template;
};

const cancelDelete = () => {
    if (isDeleting.value) {
        return;
    }
    deleteTarget.value = null;
};

const confirmDelete = async () => {
    const template = deleteTarget.value;
    if (!template || isDeleting.value) {
        return;
    }

    isDeleting.value = true;
    try {
        await axios.delete(`/api/peminjaman-templates/${template.id}`);
        showAlert('success', 'Template peminjaman berhasil dihapus.');
        if (editingId.value === template.id) {
            resetForm();
        }
        deleteTarget.value = null;
        await loadTemplates();
    } catch (error) {
        showAlert('error', error.response?.data?.message || 'Gagal menghapus template.');
    } finally {
        isDeleting.value = false;
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

watch(
    () => [filters.kategori, filters.area_id],
    () => loadTemplates(),
);

watch(
    () => [form.kategori, form.area_id, form.source_area_id, activeAreaId.value],
    () => {
        if (isHydratingForm.value) {
            return;
        }
        resetSelection();
        loadTools();
    },
);

watch(toolSearch, () => {
    if (toolSearchTimeout) {
        clearTimeout(toolSearchTimeout);
    }
    toolSearchTimeout = setTimeout(() => {
        loadTools();
    }, 300);
});

onMounted(async () => {
    await loadAreas();
    resetForm();
    await Promise.all([loadTemplates(), loadTools()]);
});
</script>
