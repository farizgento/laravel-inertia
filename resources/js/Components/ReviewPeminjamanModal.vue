<template>
    <teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="emit('close')"
        >
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Detail Peminjaman
                        </p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-900">
                            {{ item?.title || '-' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            ID #{{ item?.id ?? '-' }} - {{ item?.createdAt || '-' }}
                        </p>
                        <p v-if="item?.userName" class="mt-1 text-sm text-slate-500">
                            Peminjam: {{ item.userName }}
                        </p>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="emit('close')"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Status</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.status || '-' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Jumlah Item</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.itemCount ?? 0 }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Tanggal Kembali</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.returnDate || '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <div>
                        <h4 class="text-base font-semibold text-slate-900">Daftar Alat</h4>
                        <p class="mt-1 text-xs text-slate-500">
                            {{ reviewRows.length }} alat dalam peminjaman ini
                        </p>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[820px] text-sm">
                            <thead class="bg-white">
                                <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500">
                                    <th class="px-4 py-3 text-left">Kode</th>
                                    <th class="px-4 py-3 text-left">Nama Alat</th>
                                    <th class="px-4 py-3 text-left">Diminta</th>
                                    <th class="px-4 py-3 text-left">Keputusan</th>
                                    <th class="px-4 py-3 text-left">Qty Disetujui</th>
                                    <th class="px-4 py-3 text-left">Alasan Penolakan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="(row, index) in reviewRows" :key="row.code + index">
                                    <td class="px-4 py-4 text-xs font-semibold text-slate-900 whitespace-nowrap">
                                        {{ row.code }}
                                    </td>
                                    <td class="px-4 py-4 font-semibold text-slate-900">
                                        {{ row.name }}
                                    </td>
                                    <td class="px-4 py-4 text-slate-700">
                                        {{ row.requestedQty }}
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="relative inline-flex items-center">
                                            <select
                                                v-model="row.decision"
                                                @change="handleDecisionChange(row)"
                                                class="h-9 w-28 appearance-none rounded-lg border border-slate-200 bg-white px-3 pr-8 text-xs font-semibold transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                                :class="
                                                    row.decision === 'setujui'
                                                        ? 'text-emerald-600'
                                                        : 'text-rose-600'
                                                "
                                            >
                                                <option value="setujui">Setujui</option>
                                                <option value="tolak">Tolak</option>
                                            </select>
                                            <svg
                                                class="pointer-events-none absolute right-2 h-4 w-4 text-slate-400"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <input
                                            v-model.number="row.approvedQty"
                                            type="number"
                                            min="0"
                                            :max="row.requestedQty"
                                            :disabled="row.decision === 'tolak'"
                                            class="h-9 w-20 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-slate-50 disabled:text-slate-400"
                                        />
                                    </td>
                                    <td class="px-4 py-4">
                                        <input
                                            v-model="row.rejectionReason"
                                            type="text"
                                            placeholder="Opsional"
                                            :disabled="row.decision !== 'tolak'"
                                            class="h-9 w-44 rounded-lg border border-slate-200 bg-white px-3 text-xs text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:bg-slate-50 disabled:text-slate-400"
                                        />
                                    </td>
                                </tr>
                                <tr v-if="!reviewRows.length">
                                    <td colspan="6" class="px-4 py-6 text-sm text-slate-500">
                                        Tidak ada detail alat.
                                    </td>
                                </tr>
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <p class="text-sm font-semibold text-slate-900">Catatan Review (Opsional)</p>
                    <textarea
                        v-model="reviewNote"
                        class="mt-3 min-h-[110px] w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        placeholder="Catatan tambahan untuk peminjam..."
                    ></textarea>
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        type="button"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="!reviewRows.length || isSubmitting"
                        @click="submitReview"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : 'Simpan Review' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        default: null,
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'submit']);

const reviewRows = ref([]);
const reviewNote = ref('');

const buildRows = (tools) =>
    (Array.isArray(tools) ? tools : []).map((tool) => {
        const requestedQty = Number.isFinite(tool?.qty) ? tool.qty : 0;
        const reviewStatus = tool?.review_status ?? 'Menunggu Review';
        const isRejected = reviewStatus === 'Ditolak';
        const isApproved = reviewStatus === 'Disetujui';

        return {
        itemId: tool?.item_id ?? null,
        alatId: tool?.alat_id ?? null,
        code: tool?.code ?? '-',
        name: tool?.name ?? '-',
        requestedQty,
        decision: isRejected ? 'tolak' : 'setujui',
        approvedQty: isRejected ? 0 : isApproved && Number.isFinite(tool?.approved_qty)
            ? tool.approved_qty
            : requestedQty,
        rejectionReason: tool?.rejection_reason ?? '',
        };
    });

const handleDecisionChange = (row) => {
    if (row.decision === 'tolak') {
        row.approvedQty = 0;
        return;
    }

    row.rejectionReason = '';
    if (!Number.isFinite(row.approvedQty) || row.approvedQty === 0) {
        row.approvedQty = row.requestedQty;
    }
};

const submitReview = () => {
    emit('submit', {
        peminjamanId: props.item?.id ?? null,
        review_note: reviewNote.value,
        items: reviewRows.value.map((row) => ({
            item_id: row.itemId,
            decision: row.decision,
            approved_qty: Number.isFinite(row.approvedQty) ? row.approvedQty : 0,
            rejection_reason: row.rejectionReason,
        })),
    });
};

watch(
    () => props.item,
    (next) => {
        reviewRows.value = buildRows(next?.tools);
        reviewNote.value = next?.reviewNote ?? next?.review_note ?? '';
    },
    { immediate: true }
);
</script>
