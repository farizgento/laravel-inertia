<template>
    <ToastNotification
        :open="!!alertMessage"
        :type="alertType"
        :title="alertTitle"
        :message="alertMessage"
        @close="closeAlert"
    />

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Laporan Kehilangan</h1>
        <p class="mt-1 text-sm text-slate-500">Pantau laporan kehilangan alat per area</p>
    </div>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xl shadow-slate-200/50">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
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
                        <path d="M3 7h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                        <path d="M3 7l4-4h10l4 4" />
                        <path d="m8 12 8 8" />
                        <path d="m16 12-8 8" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Data Kehilangan</h2>
                    <p class="mt-1 text-sm text-slate-500">Area aktif: {{ areaName }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-if="canManageReports"
                    class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600"
                    type="button"
                    @click="downloadTemplateSurat"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z" />
                        <path d="M14 2v6h6" />
                        <path d="M12 18v-6" />
                        <path d="m9 15 3 3 3-3" />
                    </svg>
                    Template Excel
                </button>
                <LaporanExcelExport kategori="kehilangan" :params="buildExportParams()" :total="pagination.total" />
                <button
                    v-if="canManageReports"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700"
                    type="button"
                    @click="openCreate"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                    Tambah Laporan
                </button>
            </div>
        </div>

        <div class="mt-5 grid gap-3 lg:grid-cols-[1.2fr,0.8fr,0.6fr]">
            <label class="relative">
                <span class="sr-only">Cari laporan</span>
                <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                </span>
                <input
                    v-model="search"
                    type="text"
                    placeholder="Cari alat, pelapor, atau deskripsi..."
                    class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-11 pr-4 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                />
            </label>
            <select
                v-model="statusFilter"
                class="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
            >
                <option v-for="status in statusOptions" :key="status" :value="status">
                    Status - {{ status }}
                </option>
            </select>
        </div>

        <LaporanPeriodFilter v-model="periodFilter" />

        <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200">
            <div class="overflow-x-auto">
                <table class="min-w-[980px] w-full text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs font-semibold uppercase tracking-widest text-slate-500">
                            <th class="px-4 py-3">Tanggal Pelaporan</th>
                            <th class="px-4 py-3">Alat</th>
                            <th class="px-4 py-3">Pelapor</th>
                            <th class="px-4 py-3">Jumlah</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Deskripsi</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-if="isLoading">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                Memuat data kehilangan...
                            </td>
                        </tr>
                        <tr v-else-if="loadError">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-rose-500">
                                {{ loadError }}
                            </td>
                        </tr>
                        <tr v-else v-for="item in reports" :key="item.id">
                            <td class="px-4 py-4 text-slate-600">
                                <p class="font-semibold text-slate-900">{{ item.tanggalLaporan }}</p>
                                <p class="mt-1 text-xs text-slate-400">#{{ item.id }} &middot; input {{ item.createdAt }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ item.alatNama }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ item.alatKode }} - {{ item.areaName }}</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ item.userName }}
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                {{ item.jumlah }} unit
                            </td>
                            <td class="px-4 py-4">
                                <span
                                    class="rounded-full px-3 py-1 text-[11px] font-semibold"
                                    :class="statusBadge(item.status)"
                                >
                                    {{ item.status }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-slate-600">
                                <p class="line-clamp-2 max-w-[240px]">
                                    {{ item.deskripsi }}
                                </p>
                            </td>
                            <td class="px-4 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <button
                                        v-if="canShowStatusAction(item)"
                                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-semibold transition disabled:cursor-not-allowed disabled:opacity-70"
                                        :class="statusActionClass(item)"
                                        type="button"
                                        :disabled="isStatusSubmitting && statusSubmittingId === item.id"
                                        @click="handleStatusAction(item)"
                                    >
                                        {{ statusActionLabel(item) }}
                                    </button>
                                    <button
                                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                        type="button"
                                        @click="openDetail(item)"
                                    >
                                        Detail
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!isLoading && !loadError && !reports.length">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-slate-500">
                                Belum ada laporan kehilangan.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="pagination.lastPage > 1"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            <span>
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} - Total {{ pagination.total }} laporan
            </span>
            <div class="flex flex-wrap items-center gap-2">
                <button
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:text-slate-300"
                    type="button"
                    :disabled="pagination.currentPage === 1"
                    @click="goToPage(pagination.currentPage - 1)"
                >
                    Sebelumnya
                </button>
                <button
                    v-for="page in pageNumbers"
                    :key="page"
                    class="h-9 min-w-[36px] rounded-lg border px-3 text-sm font-semibold transition"
                    :class="page === pagination.currentPage
                        ? 'border-blue-600 bg-blue-600 text-white'
                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                    type="button"
                    @click="goToPage(page)"
                >
                    {{ page }}
                </button>
                <button
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:text-slate-300"
                    type="button"
                    :disabled="pagination.currentPage === pagination.lastPage"
                    @click="goToPage(pagination.currentPage + 1)"
                >
                    Selanjutnya
                </button>
            </div>
        </div>
    </section>

    <teleport to="body">
        <div
            v-if="formOpen"
            class="fixed inset-0 z-50 bg-slate-900/50"
        >
            <div class="flex min-h-full items-center justify-center overflow-y-auto p-4" @click.self="closeForm">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Tambah Laporan Kehilangan</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Centang satu atau beberapa alat yang hilang, atau pilih sekaligus lewat Excel.
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

                <div class="mt-5 space-y-5">
                    <div class="flex items-center gap-2">
                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">1</span>
                        <p class="text-sm font-semibold text-slate-800">Waktu dan alat yang dilaporkan</p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Tanggal Pelaporan *</span>
                            <input
                                v-model="form.tanggal_laporan"
                                type="date"
                                :max="hariIniIso"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                            <p v-if="formErrors.tanggal_laporan" class="text-xs font-semibold text-rose-500">
                                {{ formErrors.tanggal_laporan }}
                            </p>
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Status</span>
                            <input
                                type="text"
                                value="Dilaporkan"
                                disabled
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700"
                            />
                        </label>
                    </div>

                    <div class="rounded-xl border border-blue-100 bg-blue-50/60 p-3">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-slate-800">Banyak alat hilang? Pilih lewat Excel</p>
                                <p class="mt-0.5 text-xs text-slate-600">
                                    Unduh daftar alat area ini, isi kolom <span class="font-semibold">Jumlah</span> pada alat
                                    yang hilang, lalu unggah kembali. Tidak perlu hafal kode alat.
                                </p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    type="button"
                                    :disabled="isDownloadingDaftar"
                                    @click="downloadDaftarAlat"
                                >
                                    {{ isDownloadingDaftar ? 'Mengunduh...' : 'Unduh Daftar Alat' }}
                                </button>
                                <button
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                                    type="button"
                                    :disabled="isMatchingDaftar"
                                    @click="daftarInput?.click()"
                                >
                                    {{ isMatchingDaftar ? 'Membaca...' : 'Unggah Daftar' }}
                                </button>
                                <input
                                    ref="daftarInput"
                                    type="file"
                                    accept=".xlsx,.csv"
                                    class="sr-only"
                                    @change="uploadDaftarAlat"
                                />
                            </div>
                        </div>

                        <div v-if="hasilCocok" class="mt-3 rounded-lg border border-slate-200 bg-white p-3 text-xs">
                            <div class="flex items-start justify-between gap-2">
                                <p class="font-semibold" :class="hasilCocok.items.length ? 'text-emerald-700' : 'text-slate-600'">
                                    {{ hasilCocok.items.length }} alat dimasukkan ke pilihan
                                    <span v-if="hasilCocok.masalah.length" class="text-amber-700">
                                        &middot; {{ hasilCocok.masalah.length }} baris perlu dicek
                                    </span>
                                </p>
                                <button
                                    class="shrink-0 text-slate-400 transition hover:text-slate-600"
                                    type="button"
                                    aria-label="Tutup hasil"
                                    @click="hasilCocok = null"
                                >
                                    &times;
                                </button>
                            </div>
                            <ul v-if="hasilCocok.items.length" class="mt-2 max-h-24 space-y-0.5 overflow-y-auto text-slate-600">
                                <li v-for="item in hasilCocok.items" :key="`cocok-${item.id}`">
                                    <span class="font-mono text-slate-400">{{ item.kode }}</span>
                                    {{ item.nama }} &times; {{ item.qty }}
                                </li>
                            </ul>
                            <ul
                                v-if="hasilCocok.masalah.length"
                                class="mt-2 max-h-24 space-y-0.5 overflow-y-auto rounded-md bg-amber-50 px-2 py-1.5 text-amber-800"
                            >
                                <li v-for="(masalah, index) in hasilCocok.masalah" :key="`masalah-${index}`">
                                    Baris {{ masalah.baris }}: {{ masalah.pesan }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-medium text-slate-700">Alat Hilang *</span>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ selectedItems.length }} alat dipilih
                            </span>
                        </div>
                        <div class="rounded-xl border border-slate-200">
                            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-3">
                                <input
                                    v-model="toolSearch"
                                    type="search"
                                    placeholder="Cari nama atau kode alat"
                                    class="h-9 flex-1 rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                <button
                                    v-if="selectedItems.length"
                                    class="h-9 rounded-lg border border-slate-200 px-3 text-xs font-semibold text-slate-600 transition hover:border-rose-200 hover:text-rose-600"
                                    type="button"
                                    @click="clearSelection"
                                >
                                    Kosongkan Pilihan
                                </button>
                            </div>
                            <div class="max-h-64 divide-y divide-slate-100 overflow-y-auto">
                                <label
                                    v-for="tool in filteredTools"
                                    :key="tool.id"
                                    class="flex items-center gap-3 px-3 py-2 transition hover:bg-slate-50"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-blue-600"
                                        :checked="qtyFor(tool.id) > 0"
                                        @change="toggleTool(tool)"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-slate-800">{{ tool.nama }}</p>
                                        <p class="truncate text-xs text-slate-400">
                                            {{ tool.kode }} &middot; {{ tool.jenis_alat || '-' }}
                                        </p>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="stockBadgeClass(tool)"
                                    >
                                        {{ tool.stok_tersedia }}/{{ tool.total_aset }} tersedia
                                    </span>
                                    <input
                                        v-if="qtyFor(tool.id) > 0"
                                        type="number"
                                        min="1"
                                        :max="maxQty(tool)"
                                        :value="qtyFor(tool.id)"
                                        class="h-8 w-16 shrink-0 rounded-lg border border-slate-200 px-2 text-xs text-slate-700 outline-none focus:border-blue-500"
                                        @input="setQty(tool, $event.target.value)"
                                    />
                                </label>
                                <p v-if="!filteredTools.length" class="px-3 py-6 text-center text-sm text-slate-500">
                                    Tidak ada alat yang cocok dengan pencarian.
                                </p>
                            </div>
                        </div>
                        <p v-if="formErrors.items" class="text-xs font-semibold text-rose-500">{{ formErrors.items }}</p>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                        <div class="flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-600 text-xs font-semibold text-white">2</span>
                            <p class="text-sm font-semibold text-slate-800">Deskripsi dan foto tiap alat</p>
                        </div>
                        <span
                            v-if="selectedItems.length"
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="itemBelumLengkap.length ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                        >
                            {{ itemBelumLengkap.length ? `${itemBelumLengkap.length} alat belum lengkap` : 'Semua alat lengkap' }}
                        </span>
                    </div>

                    <p
                        v-if="!selectedItems.length"
                        class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500"
                    >
                        Pilih alat dulu di langkah 1, lalu isi deskripsi dan foto tiap alat di sini.
                    </p>

                    <div v-else class="max-h-[26rem] space-y-3 overflow-y-auto pr-1">
                        <article
                            v-for="item in selectedItems"
                            :key="`terpilih-${item.id}`"
                            class="rounded-xl border p-3 transition"
                            :class="itemLengkap(item) ? 'border-emerald-200 bg-emerald-50/40' : 'border-slate-200 bg-white'"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-slate-800">{{ item.nama }}</p>
                                    <p class="text-xs text-slate-400">{{ item.kode }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <label class="flex items-center gap-1 text-xs text-slate-500">
                                        Jumlah
                                        <input
                                            type="number"
                                            min="1"
                                            :max="Math.max(1, item.total_aset)"
                                            :value="item.qty"
                                            class="h-8 w-16 rounded-lg border border-slate-200 px-2 text-xs text-slate-700 outline-none focus:border-blue-500"
                                            @input="setQty(item, $event.target.value)"
                                        />
                                    </label>
                                    <button
                                        class="rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold text-slate-500 transition hover:border-rose-200 hover:text-rose-600"
                                        type="button"
                                        @click="removeSelected(item.id)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>

                            <p
                                class="mt-2 inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="itemLengkap(item) ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                            >
                                {{ statusItem(item) }}
                            </p>

                            <textarea
                                :value="item.deskripsi"
                                rows="2"
                                class="mt-2 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Jelaskan kehilangan alat ini"
                                @input="setDeskripsi(item.id, $event.target.value)"
                            ></textarea>

                            <div class="mt-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-slate-600">Foto alat ini (minimal 3)</span>
                                    <label
                                        class="cursor-pointer rounded-lg border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 transition hover:bg-blue-100"
                                    >
                                        Tambah Foto
                                        <input
                                            type="file"
                                            accept="image/*"
                                            multiple
                                            class="sr-only"
                                            @change="handleItemFotoChange(item.id, $event)"
                                        />
                                    </label>
                                </div>

                                <div v-if="item.previews.length" class="mt-2 grid grid-cols-3 gap-2">
                                    <div
                                        v-for="(preview, index) in item.previews"
                                        :key="`foto-${item.id}-${index}`"
                                        class="relative overflow-hidden rounded-lg border border-slate-200"
                                    >
                                        <img :src="preview.url" :alt="`Foto ${index + 1}`" class="h-20 w-full object-cover" />
                                        <button
                                            class="absolute right-1 top-1 rounded-full bg-slate-900/70 px-1.5 text-[11px] font-semibold text-white transition hover:bg-rose-600"
                                            type="button"
                                            aria-label="Hapus foto"
                                            @click="removeItemFoto(item.id, index)"
                                        >
                                            &times;
                                        </button>
                                    </div>
                                </div>
                                <p v-else class="mt-2 text-xs text-slate-400">
                                    Belum ada foto khusus alat ini. Foto umum di langkah 3 akan dipakai bila dibiarkan kosong.
                                </p>
                            </div>
                        </article>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-400 text-xs font-semibold text-white">3</span>
                                <p class="text-sm font-semibold text-slate-800">Deskripsi dan foto umum (opsional)</p>
                            </div>
                            <button
                                v-if="form.deskripsi.trim()"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-blue-300 hover:text-blue-700"
                                type="button"
                                @click="terapkanDeskripsiUmum"
                            >
                                Salin deskripsi ke semua alat
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-slate-500">
                            Dipakai otomatis untuk alat yang deskripsi atau fotonya dibiarkan kosong.
                        </p>

                        <textarea
                            v-model="form.deskripsi"
                            rows="2"
                            class="mt-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Contoh: alat terendam banjir di gudang"
                        ></textarea>
                        <p v-if="formErrors.deskripsi" class="mt-1 text-xs font-semibold text-rose-500">{{ formErrors.deskripsi }}</p>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-600">Foto umum (minimal 3 bila dipakai)</span>
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="form.foto.length"
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="form.foto.length >= 3 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ form.foto.length }} foto
                                </span>
                                <label class="cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 transition hover:border-blue-300 hover:text-blue-700">
                                    Tambah Foto
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        multiple
                                        class="sr-only"
                                        @change="handleFileChange"
                                    />
                                </label>
                            </div>
                        </div>

                        <div v-if="previewUrls.length" class="mt-2 grid grid-cols-3 gap-2 sm:grid-cols-5">
                            <div
                                v-for="(preview, index) in previewUrls"
                                :key="`preview-${index}`"
                                class="relative overflow-hidden rounded-lg border border-slate-200"
                            >
                                <img :src="preview.url" :alt="`Foto umum ${index + 1}`" class="h-20 w-full object-cover" />
                                <button
                                    class="absolute right-1 top-1 rounded-full bg-slate-900/70 px-1.5 text-[11px] font-semibold text-white transition hover:bg-rose-600"
                                    type="button"
                                    aria-label="Hapus foto"
                                    @click="removeFoto(index)"
                                >
                                    &times;
                                </button>
                            </div>
                        </div>
                        <p v-if="formErrors.foto" class="mt-1 text-xs font-semibold text-rose-500">{{ formErrors.foto }}</p>
                        <p class="mt-2 text-xs text-slate-400">Tiap foto maksimal 5 MB, maksimal 10 foto per alat.</p>
                    </div>
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
                        {{ isSubmitting ? 'Mengirim...' : 'Kirim Laporan' }}
                    </button>
                </div>
            </div>
            </div>
        </div>
    </teleport>

    <teleport to="body">
        <div
            v-if="detailItem"
            class="fixed inset-0 z-50 bg-slate-900/50"
        >
            <div class="flex min-h-full items-center justify-center overflow-y-auto p-4" @click.self="detailItem = null">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Detail Kehilangan</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ detailItem?.alatNama }} - {{ detailItem?.alatKode }}</p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="detailItem = null"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="space-y-3 text-sm">
                        <p class="text-slate-500">Pelapor</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.userName }}</p>
                        <p class="text-slate-500">Area</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.areaName }}</p>
                        <p class="text-slate-500">Tanggal Pelaporan</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.tanggalLaporan }}</p>
                        <p class="text-slate-500">Waktu Input</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.createdAt }}</p>
                        <p class="text-slate-500">Jumlah</p>
                        <p class="font-semibold text-slate-900">{{ detailItem?.jumlah }} unit</p>
                        <p class="text-slate-500">Status</p>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                            :class="statusBadge(detailItem?.status)"
                        >
                            {{ detailItem?.status }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500">Deskripsi</p>
                        <p class="mt-2 text-sm font-semibold text-slate-900">{{ detailItem?.deskripsi }}</p>
                        <p v-if="detailPhotos.length" class="mt-4 text-sm text-slate-500">
                            Foto ({{ detailPhotos.length }})
                        </p>
                        <div v-if="detailPhotos.length" class="mt-2 grid grid-cols-2 gap-2">
                            <a
                                v-for="(photo, index) in detailPhotos"
                                :key="`foto-${index}`"
                                :href="photo.url"
                                target="_blank"
                                rel="noopener"
                                class="overflow-hidden rounded-2xl border border-slate-200 transition hover:border-blue-300"
                            >
                                <img :src="photo.url" :alt="`Foto kehilangan ${index + 1}`" class="h-32 w-full object-cover" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </teleport>

    <LaporanStatusModal
        :open="!!selectedStatusItem"
        :report="selectedStatusItem"
        :model-value="statusForm.status"
        report-label="kehilangan"
        :error="statusError"
        :is-submitting="isStatusSubmitting"
        @close="closeStatusModal"
        @submit="submitStatus"
        @update:model-value="statusForm.status = $event"
    />
</template>

<script setup>
import axios from 'axios';
import { computed, inject, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import LaporanStatusModal from '../../Components/LaporanStatusModal.vue';
import LaporanPeriodFilter from '../../Components/LaporanPeriodFilter.vue';
import LaporanExcelExport from '../../Components/LaporanExcelExport.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Laporan Kehilangan',
                subtitle: 'Pantau laporan kehilangan alat per area',
                activeMenu: 'laporan-kehilangan',
            },
            () => page
        ),
});

const page = usePage();
const loadCachedUser = () => {
    return null;
};

const cachedUser = ref(loadCachedUser());
const authUser = computed(() => page.props.auth?.user ?? cachedUser.value);
const roleKey = computed(() => (authUser.value?.role?.key ?? '').toLowerCase());
const userAreaId = computed(() => authUser.value?.area_id ?? authUser.value?.area?.id ?? null);
const canManageReports = computed(() =>
    ['sp_tool', 'pic_tool', 'mgr_tool', 'admin', 'super_admin'].includes(roleKey.value)
);
// Laporan yang sudah final hanya boleh dikoreksi PIC Tool, admin, dan super admin.
const canCorrectStatus = computed(() => ['pic_tool', 'admin', 'super_admin'].includes(roleKey.value));
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const setAreaSwitching = inject('setAreaSwitching', null);
const activeAreaName = inject('activeAreaName', ref('Area tidak diketahui'));
const refreshSidebarNotificationCounts = inject('refreshSidebarNotificationCounts', async () => {});
const currentAreaId = computed(() =>
    isAreaSwitcherRole.value ? activeAreaId.value : userAreaId.value
);
const areaName = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaName.value
        : page.props.auth?.user?.area?.name ?? 'Area tidak diketahui'
);

const reports = ref([]);
const tools = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const search = ref('');
const selectedTool = ref('');
const statusFilter = ref('Semua');
const periodFilter = ref({ period: 'all' });

const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 10,
});

const formOpen = ref(false);
const formError = ref('');
const formErrors = reactive({
    items: '',
    tanggal_laporan: '',
    deskripsi: '',
    foto: '',
});
// Alat yang dicentang pada form: id alat -> { nama, kode, qty, total_aset, deskripsi }.
const selectedQty = reactive({});
const toolSearch = ref('');
const daftarInput = ref(null);
const isDownloadingDaftar = ref(false);
const isMatchingDaftar = ref(false);
const hasilCocok = ref(null);
const isSubmitting = ref(false);
const fileInput = ref(null);
const previewUrls = ref([]);
const detailItem = ref(null);
const selectedStatusItem = ref(null);
const isStatusSubmitting = ref(false);
const statusSubmittingId = ref(null);
const statusError = ref('');
const statusForm = reactive({
    status: '',
});

const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const toDateInput = (date) => {
    const bulan = String(date.getMonth() + 1).padStart(2, '0');
    const tanggal = String(date.getDate()).padStart(2, '0');
    return `${date.getFullYear()}-${bulan}-${tanggal}`;
};

const hariIniIso = computed(() => toDateInput(new Date()));

const form = reactive({
    tanggal_laporan: toDateInput(new Date()),
    deskripsi: '',
    foto: [],
});

const detailPhotos = computed(() => detailItem.value?.photos ?? []);

const filteredTools = computed(() => {
    const keyword = toolSearch.value.trim().toLowerCase();
    if (!keyword) {
        return tools.value;
    }

    return tools.value.filter((tool) =>
        [tool.nama, tool.kode, tool.jenis_alat]
            .filter(Boolean)
            .some((nilai) => String(nilai).toLowerCase().includes(keyword))
    );
});

const selectedItems = computed(() =>
    Object.values(selectedQty)
        .filter((item) => Number(item?.qty) > 0)
        .sort((a, b) => String(a.nama).localeCompare(String(b.nama)))
);

const qtyFor = (toolId) => Number(selectedQty[toolId]?.qty ?? 0);

const maxQty = (tool) => Math.max(1, Number(tool?.total_aset ?? 1));

// Warna badge mengikuti halaman template peminjaman: merah habis, kuning menipis, hijau aman.
const stockBadgeClass = (tool) => {
    const total = Number(tool?.total_aset ?? 0);
    const tersedia = Number(tool?.stok_tersedia ?? 0);

    if (tersedia <= 0) {
        return 'bg-rose-100 text-rose-700';
    }

    if (total > 0 && tersedia / total < 0.35) {
        return 'bg-amber-100 text-amber-700';
    }

    return 'bg-emerald-100 text-emerald-700';
};

const pilihAlat = (tool, qty = 1, deskripsi = '') => {
    const jumlah = Math.max(1, Math.min(Math.floor(Number(qty) || 1), maxQty(tool)));
    const sebelumnya = selectedQty[tool.id];

    selectedQty[tool.id] = {
        id: tool.id,
        nama: tool.nama ?? sebelumnya?.nama ?? '-',
        kode: tool.kode ?? sebelumnya?.kode ?? '-',
        total_aset: Number(tool.total_aset ?? sebelumnya?.total_aset ?? 0),
        qty: jumlah,
        deskripsi: deskripsi || sebelumnya?.deskripsi || '',
        // Foto khusus alat ini; bila kosong, foto umum yang dipakai.
        fotos: sebelumnya?.fotos ?? [],
        previews: sebelumnya?.previews ?? [],
    };
};

const MIN_FOTO = 3;

const punyaDeskripsi = (item) => Boolean(item.deskripsi.trim() || form.deskripsi.trim());

const punyaFoto = (item) =>
    item.fotos.length >= MIN_FOTO || (item.fotos.length === 0 && form.foto.length >= MIN_FOTO);

const itemLengkap = (item) => punyaDeskripsi(item) && punyaFoto(item);

const statusItem = (item) => {
    if (itemLengkap(item)) {
        return item.fotos.length ? `Lengkap · ${item.fotos.length} foto` : 'Lengkap · pakai foto umum';
    }

    const kurang = [];
    if (!punyaDeskripsi(item)) {
        kurang.push('deskripsi');
    }
    if (!punyaFoto(item)) {
        const sisa = MIN_FOTO - item.fotos.length;
        kurang.push(item.fotos.length ? `${sisa} foto lagi` : `${MIN_FOTO} foto`);
    }

    return `Butuh ${kurang.join(' dan ')}`;
};

const itemBelumLengkap = computed(() => selectedItems.value.filter((item) => !itemLengkap(item)));

const handleItemFotoChange = (itemId, event) => {
    const item = selectedQty[itemId];
    const files = Array.from(event.target.files ?? []);
    event.target.value = '';
    if (!item || !files.length) {
        return;
    }

    const sisa = MAKS_FOTO - item.fotos.length;
    if (sisa <= 0) {
        showAlert('error', `Maksimal ${MAKS_FOTO} foto per alat.`);
        return;
    }

    files.slice(0, sisa).forEach((file) => {
        item.fotos.push(file);
        item.previews.push({ url: URL.createObjectURL(file), name: file.name });
    });

    if (files.length > sisa) {
        showAlert('error', `Hanya ${sisa} foto ditambahkan, maksimal ${MAKS_FOTO} foto per alat.`);
    }
};

const removeItemFoto = (itemId, index) => {
    const item = selectedQty[itemId];
    if (!item) {
        return;
    }

    const preview = item.previews[index];
    if (preview) {
        URL.revokeObjectURL(preview.url);
    }
    item.previews.splice(index, 1);
    item.fotos.splice(index, 1);
};

const setQty = (tool, value) => {
    pilihAlat(tool, value);
};

const setDeskripsi = (toolId, value) => {
    if (selectedQty[toolId]) {
        selectedQty[toolId].deskripsi = value;
    }
};

const toggleTool = (tool) => {
    if (qtyFor(tool.id) > 0) {
        delete selectedQty[tool.id];
        return;
    }

    pilihAlat(tool, 1);
};

const removeSelected = (toolId) => {
    (selectedQty[toolId]?.previews ?? []).forEach((preview) => URL.revokeObjectURL(preview.url));
    delete selectedQty[toolId];
};

const clearSelection = () => {
    Object.keys(selectedQty).forEach((key) => {
        removeSelected(key);
    });
    hasilCocok.value = null;
};

const statusOptions = computed(() => {
    const base = ['Dilaporkan', 'Disetujui', 'Ditolak'];
    const dynamic = new Set(base);
    reports.value.forEach((item) => {
        if (item.status) {
            dynamic.add(item.status);
        }
    });
    return ['Semua', ...Array.from(dynamic)];
});

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

const statusBadge = (status) => {
    switch ((status || '').toLowerCase()) {
        case 'dilaporkan':
            return 'bg-amber-100 text-amber-700';
        case 'disetujui':
            return 'bg-blue-100 text-blue-700';
        case 'ditolak':
            return 'bg-rose-100 text-rose-700';
        case 'selesai':
            return 'bg-emerald-100 text-emerald-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
};

const isLaporanFinal = (item) => (item?.status ?? 'Dilaporkan') !== 'Dilaporkan';

const canShowStatusAction = (item) =>
    isLaporanFinal(item) ? canCorrectStatus.value : canManageReports.value;

const statusActionLabel = (item) => {
    if (isStatusSubmitting.value && statusSubmittingId.value === item?.id) {
        return 'Memproses...';
    }

    return isLaporanFinal(item) ? 'Ubah Status' : 'Persetujuan';
};

const statusActionClass = (item) =>
    isLaporanFinal(item)
        ? 'border-slate-200 bg-slate-50 text-slate-600 hover:border-slate-300'
        : 'border-amber-200 bg-amber-50 text-amber-700 hover:border-amber-300';

const normalizeReport = (item) => ({
    id: item?.id ?? '',
    alatId: item?.alat_id ?? '',
    alatNama: item?.alat_nama ?? '-',
    alatKode: item?.alat_kode ?? '-',
    areaName: item?.area_name ?? '-',
    userName: item?.user_name ?? '-',
    jumlah: Number.isFinite(item?.jumlah) ? item.jumlah : 0,
    status: item?.status ?? 'Dilaporkan',
    deskripsi: item?.deskripsi ?? '-',
    url: item?.url ?? '',
    photos: Array.isArray(item?.photos) && item.photos.length
        ? item.photos
        : item?.url
          ? [{ url: item.url, original_name: item?.original_name ?? null }]
          : [],
    createdAt: item?.created_at ?? '-',
    tanggalLaporan: item?.tanggal_laporan ?? item?.created_at ?? '-',
});

const buildParams = () => {
    const params = {
        page: pagination.currentPage,
        per_page: pagination.perPage,
        ...periodFilter.value,
    };
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (selectedTool.value) {
        params.alat_id = selectedTool.value;
    }
    if (statusFilter.value && statusFilter.value !== 'Semua') {
        params.status = statusFilter.value;
    }
    if (currentAreaId.value) {
        params.area_id = currentAreaId.value;
    }
    return params;
};

const buildExportParams = () => {
    const params = { ...periodFilter.value };
    const keyword = search.value.trim();
    if (keyword) {
        params.search = keyword;
    }
    if (selectedTool.value) {
        params.alat_id = selectedTool.value;
    }
    if (statusFilter.value && statusFilter.value !== 'Semua') {
        params.status = statusFilter.value;
    }
    if (currentAreaId.value) {
        params.area_id = currentAreaId.value;
    }
    return params;
};

const downloadTemplateSurat = () => {
    axios.get('/api/laporan-kehilangan/template', {
        responseType: 'blob',
    })
        .then((response) => {
            const blob = new Blob([response.data], {
                type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            const disposition = response.headers['content-disposition'] ?? '';
            const match = disposition.match(/filename=\"?([^\";]+)\"?/i);

            link.href = url;
            link.download = match?.[1] ?? 'template-kehilangan.xlsx';
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        })
        .catch(() => {
            showAlert('error', 'Gagal mengunduh template Excel kehilangan.');
        });
};

let reportRequestId = 0;
const loadReports = async () => {
    const requestId = ++reportRequestId;
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await axios.get('/api/laporan-kehilangan', {
            params: buildParams(),
        });
        if (requestId !== reportRequestId) return;
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        reports.value = data.map((item) => normalizeReport(item));
        if (Array.isArray(payload)) {
            pagination.currentPage = 1;
            pagination.lastPage = 1;
            pagination.total = reports.value.length;
        } else {
            const meta = payload?.meta ?? {};
            pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
            pagination.lastPage = Number(meta.last_page ?? 1);
            pagination.perPage = Number(meta.per_page ?? pagination.perPage);
            pagination.total = Number(meta.total ?? reports.value.length);
        }
    } catch (error) {
        if (requestId !== reportRequestId) return;
        reports.value = [];
        loadError.value = 'Gagal memuat laporan kehilangan.';
        showAlert('error', loadError.value);
    } finally {
        if (requestId === reportRequestId) isLoading.value = false;
    }
};

const loadTools = async () => {
    try {
        // Payload lengkap (bukan mode for_select) dipakai supaya ketersediaan
        // dan jenis alat ikut terbawa untuk pemilih alat.
        const params = {};
        if (currentAreaId.value) {
            params.area_id = currentAreaId.value;
        }
        const response = await axios.get('/api/alats', { params });
        const payload = response.data;
        const data = Array.isArray(payload) ? payload : Array.isArray(payload?.data) ? payload.data : [];
        tools.value = data.map((tool) => {
            const totalAset = Number(tool.total_aset ?? 0);

            return {
                id: tool.id,
                nama: tool.nama ?? '-',
                kode: tool.kode ?? '-',
                jenis_alat: tool.jenis_alat ?? '',
                total_aset: totalAset,
                stok_tersedia: Number(tool.stok_tersedia ?? tool.stok ?? totalAset),
            };
        });
    } catch (error) {
        tools.value = [];
    }
};

const goToPage = (pageNumber) => {
    const next = Math.min(Math.max(1, pageNumber), pagination.lastPage || 1);
    if (next === pagination.currentPage) {
        return;
    }
    pagination.currentPage = next;
    loadReports();
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

const resetFormErrors = () => {
    formError.value = '';
    formErrors.items = '';
    formErrors.tanggal_laporan = '';
    formErrors.deskripsi = '';
    formErrors.foto = '';
};

const bersihkanPreview = () => {
    previewUrls.value.forEach((preview) => URL.revokeObjectURL(preview.url));
    previewUrls.value = [];
};

const resetForm = () => {
    form.tanggal_laporan = toDateInput(new Date());
    form.deskripsi = '';
    form.foto = [];
    toolSearch.value = '';
    clearSelection();
    resetFormErrors();
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    bersihkanPreview();
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
};

const MAKS_FOTO = 10;

// Foto ditambahkan, bukan menimpa, supaya bisa memilih beberapa kali.
const handleFileChange = (event) => {
    const files = Array.from(event.target.files ?? []);
    event.target.value = '';
    if (!files.length) {
        return;
    }

    const sisa = MAKS_FOTO - form.foto.length;
    if (sisa <= 0) {
        showAlert('error', `Maksimal ${MAKS_FOTO} foto per laporan.`);
        return;
    }

    const diterima = files.slice(0, sisa);
    diterima.forEach((file) => {
        form.foto.push(file);
        previewUrls.value.push({ url: URL.createObjectURL(file), name: file.name });
    });

    if (files.length > diterima.length) {
        showAlert('error', `Hanya ${diterima.length} foto ditambahkan, maksimal ${MAKS_FOTO} foto per laporan.`);
    }
};

const removeFoto = (index) => {
    const preview = previewUrls.value[index];
    if (preview) {
        URL.revokeObjectURL(preview.url);
    }
    previewUrls.value.splice(index, 1);
    form.foto.splice(index, 1);
};

const daftarAlatParams = () => (currentAreaId.value ? { area_id: currentAreaId.value } : {});

const downloadDaftarAlat = async () => {
    isDownloadingDaftar.value = true;
    try {
        const response = await axios.get('/api/laporan-alat/daftar-alat', {
            params: daftarAlatParams(),
            responseType: 'blob',
        });
        const disposition = response.headers?.['content-disposition'] ?? '';
        const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
        const filename = match ? decodeURIComponent(match[1]) : 'DAFTAR-ALAT-LAPORAN.xlsx';
        const url = URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    } catch (error) {
        showAlert('error', 'Gagal mengunduh daftar alat.');
    } finally {
        isDownloadingDaftar.value = false;
    }
};

const uploadDaftarAlat = async (event) => {
    const file = event.target.files?.[0];
    event.target.value = '';
    if (!file) {
        return;
    }

    isMatchingDaftar.value = true;
    try {
        const payload = new FormData();
        payload.append('file', file);
        Object.entries(daftarAlatParams()).forEach(([key, value]) => {
            payload.append(key, value);
        });

        const response = await axios.post('/api/laporan-alat/cocokkan', payload);
        const items = Array.isArray(response.data?.items) ? response.data.items : [];
        const masalah = Array.isArray(response.data?.masalah) ? response.data.masalah : [];

        items.forEach((item) => {
            pilihAlat(
                {
                    id: item.id,
                    nama: item.nama,
                    kode: item.kode,
                    total_aset: item.total_aset,
                },
                item.qty
            );
        });
        hasilCocok.value = { items, masalah };

        if (!items.length && !masalah.length) {
            showAlert('error', 'Tidak ada baris dengan kolom Jumlah terisi.');
        }
    } catch (error) {
        const errors = error?.response?.data?.errors;
        const firstError = errors ? Object.values(errors).flat()[0] : null;
        showAlert('error', firstError ?? error?.response?.data?.message ?? 'Gagal membaca file daftar alat.');
    } finally {
        isMatchingDaftar.value = false;
    }
};

const terapkanDeskripsiUmum = () => {
    const umum = form.deskripsi.trim();
    if (!umum) {
        return;
    }

    selectedItems.value.forEach((item) => {
        setDeskripsi(item.id, umum);
    });
};

const submitForm = async () => {
    resetFormErrors();
    if (!selectedItems.value.length) {
        formErrors.items = 'Pilih minimal satu alat.';
        formError.value = 'Lengkapi semua field wajib.';
        return;
    }
    if (!form.tanggal_laporan) {
        formError.value = 'Lengkapi semua field wajib.';
        return;
    }
    if (form.foto.length && form.foto.length < 3) {
        formErrors.foto = 'Foto umum minimal 3, atau kosongkan dan isi foto tiap alat.';
        formError.value = 'Lengkapi semua field wajib.';
        return;
    }
    if (itemBelumLengkap.value.length) {
        const contoh = itemBelumLengkap.value[0];
        formErrors.items = `${itemBelumLengkap.value.length} alat belum lengkap, contoh: ${contoh.nama} (${statusItem(contoh).toLowerCase()}).`;
        formError.value = 'Lengkapi deskripsi dan foto tiap alat.';
        return;
    }
    isSubmitting.value = true;
    try {
        const payload = new FormData();
        payload.append('tanggal_laporan', form.tanggal_laporan);
        payload.append('deskripsi', form.deskripsi);
        form.foto.forEach((file, index) => {
            payload.append(`foto[${index}]`, file);
        });
        selectedItems.value.forEach((item, index) => {
            payload.append(`items[${index}][alat_id]`, item.id);
            payload.append(`items[${index}][jumlah]`, item.qty);
            payload.append(`items[${index}][deskripsi]`, item.deskripsi);
            item.fotos.forEach((file, urutan) => {
                payload.append(`items[${index}][foto][${urutan}]`, file);
            });
        });

        const response = await axios.post('/api/laporan-kehilangan', payload, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
            params: daftarAlatParams(),
        });

        const total = Number(response.data?.total ?? selectedItems.value.length);

        await loadReports();
        await refreshSidebarNotificationCounts();
        closeForm();
        showAlert('success', `${total} laporan kehilangan berhasil dikirim.`);
    } catch (error) {
        if (error?.response?.status === 422) {
            const errors = error.response?.data?.errors ?? {};
            // Galat per baris item (items.3.deskripsi) ikut ditampilkan di bagian pilihan alat.
            const itemErrorKey = Object.keys(errors).find((key) => key.startsWith('items.'));
            formErrors.items = errors.items?.[0] ?? (itemErrorKey ? errors[itemErrorKey][0] : '');
            formErrors.tanggal_laporan = errors.tanggal_laporan?.[0] ?? '';
            formErrors.deskripsi = errors.deskripsi?.[0] ?? '';
            formErrors.foto = errors.foto?.[0]
                ?? Object.keys(errors).filter((key) => key.startsWith('foto.')).map((key) => errors[key][0])[0]
                ?? '';
            formError.value = error.response?.data?.message ?? 'Periksa kembali isian.';
        } else {
            formError.value = error.response?.data?.message ?? 'Gagal mengirim laporan.';
        }
        showAlert('error', formError.value);
    } finally {
        isSubmitting.value = false;
    }
};

const openDetail = (item) => {
    detailItem.value = item;
};

const openStatusModal = (item) => {
    selectedStatusItem.value = item;
    statusForm.status = item?.status && item.status !== 'Dilaporkan' ? item.status : '';
    statusError.value = '';
};

const handleStatusAction = (item) => {
    openStatusModal(item);
};

const closeStatusModal = () => {
    selectedStatusItem.value = null;
    statusForm.status = '';
    statusError.value = '';
};

const submitStatus = async () => {
    if (!selectedStatusItem.value?.id || !statusForm.status) {
        statusError.value = 'Pilih status terlebih dahulu.';
        return;
    }

    await submitStatusFor(selectedStatusItem.value, statusForm.status, true);
};

const submitStatusFor = async (item, status, shouldCloseModal = false) => {
    if (!item?.id || !status) {
        return;
    }

    isStatusSubmitting.value = true;
    statusSubmittingId.value = item.id;
    statusError.value = '';
    try {
        await axios.put(`/api/laporan-kehilangan/${item.id}`, {
            status,
            ...(currentAreaId.value ? { area_id: currentAreaId.value } : {}),
        });
        await loadReports();
        await refreshSidebarNotificationCounts();
        if (shouldCloseModal) {
            closeStatusModal();
        }
        showAlert('success', 'Status laporan kehilangan berhasil diperbarui.');
    } catch (error) {
        statusError.value =
            error?.response?.data?.errors?.status?.[0] ??
            error?.response?.data?.message ??
            'Gagal memperbarui status laporan.';
        showAlert('error', statusError.value);
    } finally {
        isStatusSubmitting.value = false;
        statusSubmittingId.value = null;
    }
};

let filterTimeout = null;
watch([search, selectedTool, statusFilter, periodFilter], () => {
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }
    filterTimeout = setTimeout(() => {
        pagination.currentPage = 1;
        loadReports();
    }, 300);
});

onMounted(() => {
    cachedUser.value = loadCachedUser();
    if (isAreaSwitcherRole.value) {
        setAreaSwitching?.(true);
    }
    Promise.all([loadTools(), loadReports()]).finally(() => {
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
        selectedTool.value = '';
        try {
            await Promise.all([loadTools(), loadReports()]);
        } finally {
            if (shouldShow) {
                setAreaSwitching?.(false);
            }
        }
    }
);
</script>
