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
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Transaksi Peminjaman</h2>
                <p class="mt-1 text-sm text-slate-500">{{ pageSubtitle }}</p>
            </div>
            <Link
                v-if="showCreateButton"
                :href="createPeminjamanHref"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 5v14" />
                    <path d="M5 12h14" />
                </svg>
                Buat Peminjaman
            </Link>
        </div>

        <div class="mt-5 rounded-2xl border border-slate-200 bg-slate-50 p-2">
            <div class="grid gap-2 md:grid-cols-2">
                <button
                    v-for="tab in shippingTabs"
                    :key="tab.key"
                    class="flex items-center border-1 justify-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === tab.key
                        ? 'bg-white text-blue-700 shadow-sm'
                        : 'text-slate-600 hover:bg-white/70'"
                    type="button"
                    @click="activeTab = tab.key"
                >
                    <span class="text-slate-400">
                        <svg v-if="tab.key === 'siap-dikirim'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h11v10H3z" />
                            <path d="M14 10h4l3 3v4h-7z" />
                            <circle cx="7.5" cy="19" r="1.5" />
                            <circle cx="17.5" cy="19" r="1.5" />
                        </svg>
                        <svg v-else-if="tab.key === 'dikirim'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 7h13l3 5v6H3z" />
                            <circle cx="7.5" cy="18" r="1.5" />
                            <circle cx="17.5" cy="18" r="1.5" />
                        </svg>
                        <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6 9 17l-5-5" />
                            <path d="M21 12a9 9 0 1 1-2.64-6.36" />
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

            <div class="mt-2 grid gap-2 md:grid-cols-2">
                <button
                    v-for="tab in returnTabs"
                    :key="tab.key"
                    class="flex items-center justify-center border-1 gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition"
                    :class="activeTab === tab.key
                        ? 'bg-white text-blue-700 shadow-sm'
                        : 'text-slate-600 hover:bg-white/70'"
                    type="button"
                    @click="activeTab = tab.key"
                >
                    <span class="text-slate-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 6 9 17l-5-5" />
                            <path d="M21 12a9 9 0 1 1-2.64-6.36" />
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
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat data peminjaman...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Tidak ada peminjaman pada kategori ini.
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
                            <span>{{ item.areaName }}</span>
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
                    </div>

                    <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-700">
                        {{ item.title }}
                    </div>

                    <div v-if="isReturnStatus(item.status)" class="mt-3 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                        <div
                            v-for="tool in item.tools"
                            :key="tool.item_id"
                            class="flex items-center justify-between gap-3 py-1"
                        >
                            <span class="truncate">{{ tool.name }}</span>
                            <span class="shrink-0">
                                {{ tool.returnedQty }}/{{ tool.approvedQty }} kembali
                            </span>
                        </div>
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
                            v-if="(item.status === 'Dikirim' || isReturnStatus(item.status)) && item.suratJalanUrl"
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
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="button"
                            :disabled="acceptingId === item.id"
                            @click="openAcceptModal(item)"
                        >
                            <svg v-if="acceptingId === item.id" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-90" fill="currentColor" d="M12 2a10 10 0 0 1 10 10h-4a6 6 0 0 0-6-6V2Z" />
                            </svg>
                            <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            {{ acceptingId === item.id ? 'Memproses...' : 'Terima' }}
                        </button>
                        <button
                            v-if="item.status === 'Dipesan'"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-700 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                            type="button"
                            @click="openShipping(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7h11v10H3z" />
                                <path d="M14 10h4l3 3v4h-7z" />
                                <circle cx="7.5" cy="19" r="1.5" />
                                <circle cx="17.5" cy="19" r="1.5" />
                            </svg>
                            Pengiriman
                        </button>
                        <button
                            v-if="canCompleteItem(item)"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="button"
                            :disabled="finishingId === item.id"
                            @click="openCompleteModal(item)"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                            {{ finishingId === item.id ? 'Memproses...' : 'Selesai' }}
                        </button>
                        <button
                            v-if="canReturnItem(item)"
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700 transition hover:border-amber-300"
                            type="button"
                            @click="openReturnModal(item)"
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
    <KirimPengirimanModal
        :open="!!shippingItem"
        :item="shippingItem"
        :is-submitting="isShipping"
        @close="shippingItem = null"
        @submit="submitShipping"
    />
    <SuratJalanModal
        :open="!!suratJalanItem"
        :url="suratJalanItem?.suratJalanUrl"
        :path="suratJalanItem?.suratJalanPath"
        :title="suratJalanItem?.title"
        :pengirim-name="suratJalanItem?.pengirimNama"
        :peminjaman-id="suratJalanItem?.id"
        :peminjaman-status="suratJalanItem?.status"
        @close="suratJalanItem = null"
    />

    <teleport to="body">
        <div
            v-if="acceptItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeAcceptModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Konfirmasi
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">
                            Terima peminjaman
                        </h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeAcceptModal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Tandai peminjaman ini sudah diterima?
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ acceptItem?.title || '-' }} (ID: {{ acceptItem?.id || '-' }})
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        Aksi ini akan mengubah status menjadi <span class="font-semibold">Diterima</span> dan memasukkan stok antar area ke katalog area Anda.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeAcceptModal"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="acceptingId === acceptItem?.id"
                        @click="confirmAccept"
                    >
                        {{ acceptingId === acceptItem?.id ? 'Memproses...' : 'Ya, Terima' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="completeItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeCompleteModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Konfirmasi
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">
                            Selesaikan peminjaman
                        </h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeCompleteModal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 p-4">
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">
                                Tandai peminjaman ini sebagai selesai?
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ completeItem?.title || '-' }} (ID: {{ completeItem?.id || '-' }})
                            </p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        Aksi ini akan mengubah status peminjaman menjadi <span class="font-semibold">Selesai</span>.
                    </p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeCompleteModal"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="finishingId === completeItem?.id"
                        @click="confirmComplete"
                    >
                        {{ finishingId === completeItem?.id ? 'Memproses...' : 'Ya, Selesai' }}
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="returnItem"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="closeReturnModal"
        >
            <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Konfirmasi</p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">Kembalikan alat</h3>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="closeReturnModal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 text-sm text-slate-600">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-slate-900">
                            {{ returnItem?.title || '-' }} (ID: {{ returnItem?.id || '-' }})
                        </p>
                        <p class="mt-1 text-xs text-slate-500">
                            Isi jumlah alat yang dikembalikan. Bisa sebagian atau seluruh sisa alat.
                        </p>
                    </div>

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
                                <label class="w-full max-w-[150px] space-y-1 text-xs font-semibold text-slate-600">
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
                    <p v-if="returnError" class="mt-3 text-sm font-semibold text-rose-500">{{ returnError }}</p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <button
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="closeReturnModal"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700 disabled:cursor-not-allowed disabled:opacity-70"
                        type="button"
                        :disabled="isReturning"
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
import { computed, inject, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import PeminjamanDetailModal from '../../Components/PeminjamanDetailModal.vue';
import KirimPengirimanModal from '../../Components/KirimPengirimanModal.vue';
import SuratJalanModal from '../../Components/SuratJalanModal.vue';
import ToastNotification from '../../Components/ToastNotification.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: page.props?.shippingPageTitle ?? 'Pengiriman',
                subtitle: page.props?.shippingPageSubtitle ?? 'Kelola pengiriman peminjaman alat',
                activeMenu: page.props?.shippingActiveMenu ?? 'pengiriman',
            },
            () => page
        ),
});

const page = usePage();
const refreshPengirimanNotificationCounts = inject('refreshPengirimanNotificationCounts', async () => {});
const isAreaSwitcherRole = inject('isAreaSwitcherRole', ref(false));
const activeAreaId = inject('activeAreaId', ref(null));
const fallbackAreaName = computed(() => page.props.auth?.user?.area?.name ?? 'Area tidak diketahui');
const roleKey = computed(() => String(page.props.auth?.user?.role?.key ?? '').toLowerCase());
const userAreaId = computed(() => page.props.auth?.user?.area_id ?? page.props.auth?.user?.area?.id ?? null);
const currentAreaId = computed(() =>
    isAreaSwitcherRole.value
        ? activeAreaId.value
        : userAreaId.value
);
const shippingCategory = computed(() => page.props.shippingCategory ?? 'Intra Area');
const isInterAreaPage = computed(() => shippingCategory.value === 'Antar Area');
const isAdminRole = computed(() => ['admin', 'super_admin'].includes(roleKey.value));
const createPeminjamanHref = computed(() => isInterAreaPage.value ? '/peminjaman-antar-area' : '/peminjaman');
const showCreateButton = computed(() => isInterAreaPage.value || isAdminRole.value);
const pageTitle = computed(() => page.props.shippingPageTitle ?? 'Pengiriman');
const pageSubtitle = computed(() => page.props.shippingPageSubtitle ?? 'Kelola pengiriman peminjaman alat');

const items = ref([]);
const isLoading = ref(false);
const isShipping = ref(false);
const loadError = ref('');
const alertMessage = ref('');
const alertType = ref('success');
const alertTitle = ref('');
let alertTimeout = null;

const detailItem = ref(null);
const shippingItem = ref(null);
const suratJalanItem = ref(null);
const completeItem = ref(null);
const acceptItem = ref(null);
const returnItem = ref(null);
const finishingId = ref(null);
const acceptingId = ref(null);
const isReturning = ref(false);
const returnError = ref('');
const returnRows = ref([]);

const tabConfig = [
    { key: 'siap-dikirim', label: 'Siap Dikirim', status: 'Dipesan' },
    { key: 'dikirim', label: 'Dikirim', status: 'Dikirim' },
    { key: 'diterima', label: 'Diterima', status: 'Diterima' },
    { key: 'dikembalikan-parsial', label: 'Dikembalikan Parsial', status: 'Dikembalikan Partials' },
    { key: 'dikembalikan-semua', label: 'Dikembalikan Semua', status: 'Dikembalikan Semuanya' },
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
        case 'Dipesan':
            return 'Siap Dikirim';
        case 'Dikirim':
            return 'Dikirim';
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
        case 'Dipesan':
            return 'bg-amber-100 text-amber-600';
        case 'Dikirim':
            return 'bg-emerald-100 text-emerald-600';
        case 'Diterima':
            return 'bg-blue-100 text-blue-600';
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
        Dipesan: 0,
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

const shippingTabs = computed(() =>
    tabs.value.filter((tab) => ['siap-dikirim', 'dikirim'].includes(tab.key))
);

const returnTabs = computed(() =>
    tabs.value.filter((tab) =>
        [
            ...(isInterAreaPage.value ? ['diterima'] : []),
            'dikembalikan-parsial',
            'dikembalikan-semua',
        ].includes(tab.key)
    )
);

const activeStatus = computed(() => {
    const match = tabConfig.find((tab) => tab.key === activeTab.value);
    return match?.status ?? 'Dipesan';
});

const filteredItems = computed(() =>
    items.value.filter((item) => item.status === activeStatus.value)
);

const approvedLabel = (item) => {
    const total = Number.isFinite(item?.itemCount) ? item.itemCount : 0;
    return `${total} alat disetujui`;
};

const isReturnStatus = (status) => ['Diterima', 'Dikembalikan Partials', 'Dikembalikan Semuanya'].includes(status);

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
            userName: item?.user_name ?? '-',
            areaName: item?.area_name ?? fallbackAreaName.value,
            areaId: item?.area_id ?? null,
            requesterAreaName: item?.requester_area_name ?? '',
            requesterAreaId: item?.requester_area_id ?? null,
            isInterArea: Boolean(item?.is_inter_area),
            reviewerName: item?.reviewed_by_name ?? '-',
            requesterReviewerName: item?.requester_reviewed_by_name ?? '-',
            createdAt: item?.created_at ?? '-',
            borrowDate: item?.borrow_date ?? '-',
            returnDate: item?.return_date ?? '-',
            itemCount: Number.isFinite(item?.item_count) ? item.item_count : 0,
            status: item?.status ?? 'Dipesan',
            pengirimNama: item?.pengirim_nama ?? '',
            suratJalanUrl: item?.surat_jalan_url ?? '',
            suratJalanPath: item?.surat_jalan_path ?? '',
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

const loadHistory = async () => {
    isLoading.value = true;
    loadError.value = '';
    try {
        const [pengirimanResponse, pengembalianResponse] = await Promise.all([
            axios.get('/api/pengiriman', {
                params: {
                    kategori: shippingCategory.value,
                    ...(isAreaSwitcherRole.value && currentAreaId.value ? { area_id: currentAreaId.value } : {}),
                },
            }),
            axios.get('/api/pengembalian', {
                params: {
                    kategori: shippingCategory.value,
                    ...(isAreaSwitcherRole.value && currentAreaId.value ? { area_id: currentAreaId.value } : {}),
                },
            }),
        ]);
        const pengirimanData = Array.isArray(pengirimanResponse.data) ? pengirimanResponse.data : [];
        const pengembalianData = Array.isArray(pengembalianResponse.data) ? pengembalianResponse.data : [];
        items.value = [...pengirimanData, ...pengembalianData].map((item) => normalizeHistory(item));
        await refreshPengirimanNotificationCounts({ silent: true });
    } catch (error) {
        items.value = [];
        loadError.value = 'Gagal memuat data peminjaman.';
        showAlert('error', loadError.value);
    } finally {
        isLoading.value = false;
    }
};

const openDetail = (item) => {
    detailItem.value = item;
};

const openShipping = (item) => {
    shippingItem.value = item;
};

const openSuratJalan = (item) => {
    suratJalanItem.value = item;
};

const canAcceptSuratJalan = (item) =>
    Boolean(item?.isInterArea)
    && item?.status === 'Dikirim'
    && Number(item?.requesterAreaId) === Number(currentAreaId.value);

const canReturnItem = (item) =>
    Boolean(item?.isInterArea)
    && ['Diterima', 'Dikembalikan Partials'].includes(item?.status)
    && Number(item?.requesterAreaId) === Number(currentAreaId.value);

const canCompleteItem = (item) =>
    item?.status === 'Dikembalikan Semuanya'
    && (
        ['admin', 'super_admin'].includes(roleKey.value)
        || !item?.isInterArea
        || Number(item?.areaId) === Number(currentAreaId.value)
    );

const openAcceptModal = (item) => {
    acceptItem.value = item;
};

const closeAcceptModal = () => {
    if (acceptingId.value) {
        return;
    }
    acceptItem.value = null;
};

const openReturnModal = (item) => {
    returnItem.value = item;
    returnError.value = '';
    returnRows.value = (Array.isArray(item?.tools) ? item.tools : [])
        .filter((tool) => Number(tool?.remainingQty ?? 0) > 0)
        .map((tool) => ({
            itemId: tool.item_id,
            name: tool.name,
            code: tool.code,
            approvedQty: Number(tool.approvedQty ?? 0),
            returnedQty: Number(tool.returnedQty ?? 0),
            remainingQty: Number(tool.remainingQty ?? 0),
            returnQty: Number(tool.remainingQty ?? 0),
        }));
};

const closeReturnModal = () => {
    if (isReturning.value) {
        return;
    }
    returnItem.value = null;
    returnRows.value = [];
    returnError.value = '';
};

const validateReturnRows = () => {
    const submittedRows = returnRows.value.filter((row) => Number(row.returnQty) > 0);
    if (!submittedRows.length) {
        returnError.value = 'Isi minimal satu jumlah pengembalian alat.';
        return false;
    }

    const invalidRow = submittedRows.find(
        (row) => Number(row.returnQty) < 1 || Number(row.returnQty) > Number(row.remainingQty)
    );
    if (invalidRow) {
        returnError.value = `Jumlah kembali ${invalidRow.name} tidak valid.`;
        return false;
    }

    returnError.value = '';
    return true;
};

const confirmReturn = async () => {
    if (!returnItem.value?.id || isReturning.value || !validateReturnRows()) {
        return;
    }

    isReturning.value = true;
    try {
        const formData = new FormData();
        returnRows.value
            .filter((row) => Number(row.returnQty) > 0)
            .forEach((row, index) => {
                formData.append(`items[${index}][item_id]`, String(row.itemId));
                formData.append(`items[${index}][returned_qty]`, String(row.returnQty));
            });

        const response = await axios.post(`/api/pengiriman/${returnItem.value.id}/kembalikan`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await loadHistory();
        const nextStatus = response?.data?.status ?? '';
        returnItem.value = null;
        returnRows.value = [];
        returnError.value = '';
        showAlert(
            'success',
            nextStatus === 'Dikembalikan Semuanya'
                ? 'Semua alat berhasil dikembalikan.'
                : 'Pengembalian parsial berhasil disimpan.'
        );
    } catch (error) {
        returnError.value = error?.response?.data?.message ?? 'Gagal mengembalikan peminjaman.';
        showAlert('error', returnError.value);
    } finally {
        isReturning.value = false;
    }
};

const acceptPeminjaman = async (item) => {
    if (!item?.id || acceptingId.value) {
        return;
    }

    acceptingId.value = item.id;
    try {
        await axios.post(`/api/pengiriman/${item.id}/terima`);
        await loadHistory();
        acceptItem.value = null;
        showAlert('success', 'Peminjaman berhasil diterima.');
    } catch (error) {
        const message = error.response?.data?.message ?? 'Gagal menerima peminjaman.';
        showAlert('error', message);
    } finally {
        acceptingId.value = null;
    }
};

const confirmAccept = async () => {
    if (!acceptItem.value) {
        return;
    }

    await acceptPeminjaman(acceptItem.value);
};

const openCompleteModal = (item) => {
    completeItem.value = item;
};

const closeCompleteModal = () => {
    if (finishingId.value) {
        return;
    }
    completeItem.value = null;
};

const markAsCompleted = async (item) => {
    if (!item?.id || finishingId.value) {
        return;
    }

    finishingId.value = item.id;
    try {
        await axios.post(`/api/pengembalian/${item.id}/selesai`);
        await loadHistory();
        showAlert('success', 'Peminjaman berhasil diselesaikan.');
        completeItem.value = null;
    } catch (error) {
        const message = error?.response?.data?.message ?? 'Gagal menyelesaikan peminjaman.';
        showAlert('error', message);
    } finally {
        finishingId.value = null;
    }
};

const confirmComplete = async () => {
    if (!completeItem.value) {
        return;
    }

    await markAsCompleted(completeItem.value);
};

const submitShipping = async (payload) => {
    if (!payload?.peminjamanId) {
        return;
    }
    isShipping.value = true;
    loadError.value = '';
    try {
        const formData = new FormData();
        formData.append('pengirim_nama', payload.pengirimNama ?? '');
        formData.append('surat_jalan', payload.suratJalan ?? null);
        await axios.post(`/api/pengiriman/${payload.peminjamanId}/kirim`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        await loadHistory();
        shippingItem.value = null;
        showAlert('success', 'Pengiriman berhasil dikirim.');
    } catch (error) {
        loadError.value = error.response?.data?.message ?? 'Gagal mengirim peminjaman.';
        showAlert('error', loadError.value);
    } finally {
        isShipping.value = false;
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
    loadHistory();
});
</script>
