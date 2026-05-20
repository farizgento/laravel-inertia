<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Riwayat Peminjaman - {{ areaName }}</h1>
        <p class="mt-1 text-sm text-slate-500">Daftar riwayat peminjaman yang tersedia</p>
    </div>

    <section class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Total</p>
            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ totalCount }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Perlu Review</p>
            <p class="mt-2 text-2xl font-semibold text-blue-600">{{ reviewCount }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Disetujui</p>
            <p class="mt-2 text-2xl font-semibold text-amber-500">{{ processCount }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-xl shadow-slate-200/50">
            <p class="text-sm text-slate-500">Dikirim</p>
            <p class="mt-2 text-2xl font-semibold text-emerald-600">{{ deliveredCount }}</p>
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

        <div class="mt-4">
            <p v-if="isLoading" class="text-sm text-slate-500">Memuat data peminjaman...</p>
            <p v-else-if="loadError" class="text-sm text-rose-500">{{ loadError }}</p>
            <p v-else-if="!filteredItems.length" class="text-sm text-slate-500">
                Belum ada peminjaman.
            </p>
            <div v-else class="overflow-hidden rounded-2xl border border-slate-200">
                <div class="overflow-x-auto">
                    <table class="min-w-[1320px] w-full text-sm">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">
                                <th class="px-4 py-3">Dibuat</th>
                                <th class="px-4 py-3">Pekerjaan</th>
                                <th class="px-4 py-3">Peminjam</th>
                                <th class="px-4 py-3">Direview/Disetujui</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Kategori</th>
                                <th class="px-4 py-3">Periode</th>
                                <th class="px-4 py-3">Item</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr
                            v-for="item in filteredItems"
                            :key="item.id"
                            class="align-top transition hover:bg-slate-50"
                            >
                            <td class="px-4 py-4 text-slate-600">
                                {{ item.createdAt }}
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold text-slate-900">{{ item.title }}</p>
                                <p class="mt-1 text-xs text-slate-500">ID #{{ item.id }}</p>
                            </td>
                            <td class="px-4 py-4">
                                    <p class="font-semibold text-slate-900">{{ item.userName }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="reviewApprovalLabel(item) === '-' || reviewApprovalLabel(item).endsWith('/-') ? 'bg-slate-100 text-slate-500' : 'bg-blue-50 text-blue-700'"
                                    >
                                        {{ reviewApprovalLabel(item) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="statusClass(item.status)"
                                    >
                                        {{ item.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                        :class="kategoriClass(item.kategori)"
                                    >
                                        {{ item.kategori }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-slate-600">
                                    <p>{{ item.borrowDate }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Kembali: {{ item.returnDate }}</p>
                                </td>
                                <td class="px-4 py-4 text-center font-semibold text-slate-700">
                                    {{ item.itemCount }}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <button
                                            v-if="hasSuratJalan(item)"
                                            class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:border-emerald-300"
                                            type="button"
                                            @click="openSuratJalan(item)"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                                <path d="M14 2v6h6" />
                                                <path d="M16 13H8" />
                                                <path d="M16 17H8" />
                                                <path d="M10 9H8" />
                                            </svg>
                                            Surat Jalan
                                        </button>
                                        <button
                                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-blue-200 hover:text-blue-700"
                                            type="button"
                                            @click="openDetail(item)"
                                        >
                                            <svg
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z" />
                                                <circle cx="12" cy="12" r="3" />
                                            </svg>
                                            Detail
                                        </button>
                                        <button
                                            v-if="canManagePeminjaman"
                                            class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:border-blue-300"
                                            type="button"
                                            @click="openEdit(item)"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button
                                            v-if="canManagePeminjaman"
                                            class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:border-rose-300 disabled:cursor-not-allowed disabled:opacity-60"
                                            type="button"
                                            :disabled="deletingId === item.id"
                                            @click="deletePeminjaman(item)"
                                        >
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="M19 6l-1 14H6L5 6" />
                                            </svg>
                                            {{ deletingId === item.id ? 'Menghapus...' : 'Hapus' }}
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div
            v-if="pagination.lastPage > 1"
            class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
        >
            <span>
                Halaman {{ pagination.currentPage }} dari {{ pagination.lastPage }} - Total {{ pagination.total }} peminjaman
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
                        :disabled="isDeleteSubmitting"
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
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option v-for="status in editableStatuses" :key="status" :value="status">
                                {{ status }}
                            </option>
                        </select>
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
import { usePage } from '@inertiajs/vue3';
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
const pagination = reactive({
    currentPage: 1,
    lastPage: 1,
    total: 0,
    perPage: 8,
});

const search = ref('');
const statusFilter = ref('Semua');
const kategoriFilter = ref('Semua');
const selectedItem = ref(null);
const suratJalanItem = ref(null);
const editItem = ref(null);
const isSavingEdit = ref(false);
const deletingId = ref(null);
const deleteError = ref('');
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
    tanggal_pinjam: '',
    tanggal_kembali: '',
    status: '',
});
const editableStatuses = [
    'Perlu Disetujui',
    'Perlu Direview',
    'Disetujui',
    'Dikirim',
    'Diterima',
    'Dikembalikan Partials',
    'Dikembalikan Semuanya',
    'Selesai',
    'Ditolak',
];

const filteredItems = computed(() => items.value);

const totalCount = computed(() => (pagination.total ? pagination.total : items.value.length));
const reviewCount = computed(() =>
    items.value.filter((item) => ['Perlu Direview', 'Perlu Disetujui'].includes(item.status)).length
);
const processCount = computed(() => items.value.filter((item) => item.status === 'Disetujui').length);
const deliveredCount = computed(() => items.value.filter((item) => item.status === 'Dikirim').length);

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

    return [
        item.suratJalanUrl || item.suratJalanPath
            ? {
                  label: 'Surat Jalan',
                  url: item.suratJalanUrl,
                  path: item.suratJalanPath,
                  pengirimName: item.pengirimNama,
              }
            : null,
        item.returnSuratJalanUrl || item.returnSuratJalanPath
            ? {
                  label: 'Surat Jalan Kembali',
                  url: item.returnSuratJalanUrl,
                  path: item.returnSuratJalanPath,
                  pengirimName: item.pengembaliNama,
              }
            : null,
    ].filter(Boolean);
};

const hasSuratJalan = (item) => suratJalanDocuments(item).length > 0;

const closeDetail = () => {
    selectedItem.value = null;
};

const openEdit = (item) => {
    editItem.value = item;
    editError.value = '';
    editForm.pekerjaan = item?.title ?? '';
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

    isBulkDeleting.value = true;
    try {
        await axios.delete('/api/peminjaman/area', {
            data: { area_id: currentAreaId.value },
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
        pengembaliNama: item?.pengembali_nama ?? '',
        returnSuratJalanUrl: item?.surat_jalan_pengembalian_url ?? '',
        returnSuratJalanPath: item?.surat_jalan_pengembalian_path ?? '',
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
            return;
        }
        const data = Array.isArray(payload?.data) ? payload.data : [];
        const meta = payload?.meta ?? payload ?? {};
        items.value = data.map((item) => normalizeHistory(item));
        pagination.currentPage = Number(meta.current_page ?? pagination.currentPage) || 1;
        pagination.lastPage = Number(meta.last_page ?? pagination.lastPage) || 1;
        pagination.perPage = Number(meta.per_page ?? pagination.perPage) || pagination.perPage;
        pagination.total = Number(meta.total ?? pagination.total) || items.value.length;
    } catch (error) {
        items.value = [];
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

