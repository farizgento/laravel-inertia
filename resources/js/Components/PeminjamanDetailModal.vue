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
                            ID #{{ item?.id ?? '-' }} - Dibuat {{ item?.createdAt || '-' }}
                        </p>
                        <p v-if="item?.userName" class="mt-1 text-sm text-slate-500">
                            Peminjam: {{ item.userName }}
                        </p>
                        <p v-if="pengirimName" class="mt-1 text-sm text-slate-500">
                            Pengirim: {{ pengirimName }}
                        </p>
                        <template v-if="isInterArea">
                            <p class="mt-1 text-sm text-slate-500">
                                Area alat: {{ sourceAreaName }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Direview: {{ requesterReviewerName }}
                            </p>
                            <p class="mt-1 text-sm text-slate-500">
                                Disetujui: {{ approverName }}
                            </p>
                        </template>
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

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Status</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.status || '-' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Jumlah Item disetujui</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ approvedItemCount }}
                        </p>
                    </div>
                    <div v-if="isInterArea" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Area Alat</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ sourceAreaName }}
                        </p>
                    </div>
                    <div v-if="isInterArea" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Area Peminjam</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ requesterAreaName }}
                        </p>
                    </div>
                    <div v-if="isInterArea" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Direview</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ requesterReviewerName }}
                        </p>
                    </div>
                    <div v-if="isInterArea" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Disetujui</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ approverName }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Tanggal Peminjam</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.borrowDate || '-' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Tanggal Kembali</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.returnDate || '-' }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Daftar Alat</p>
                    </div>
                    <div class="divide-y divide-slate-100">
                        <div
                            v-for="tool in item?.tools || []"
                            :key="tool.name + tool.code"
                            class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm"
                        >
                            <div>
                                <p class="font-semibold capitalize text-slate-900">{{ tool.name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ tool.code }}</p>
                                <p v-if="isPicTools" class="mt-2 text-xs text-slate-500">
                                    Disetujui:
                                    <span class="font-semibold text-emerald-700">
                                        {{ approvedQty(tool) }}
                                    </span>
                                </p>
                                <p v-else-if="hasReviewResult(tool)" class="mt-2 text-xs text-slate-500">
                                    <span class="text-slate-600">
                                        Diminta: <span class="font-semibold text-slate-900">{{ tool.qty }}</span>
                                    </span>
                                    <span class="mx-1 text-slate-300">|</span>
                                    <span class="text-emerald-600">
                                        Disetujui:
                                        <span class="font-semibold text-emerald-700">
                                            {{ approvedQty(tool) }}
                                        </span>
                                    </span>
                                    <span class="mx-1 text-slate-300">|</span>
                                    <span class="text-rose-600">
                                        Ditolak:
                                        <span class="font-semibold text-rose-700">
                                            {{ rejectedQty(tool) }}
                                        </span>
                                    </span>
                                </p>
                                <p v-if="approvedQty(tool) > 0" class="mt-2 text-xs text-slate-500">
                                    <span class="text-indigo-600">
                                        Dikembalikan:
                                        <span class="font-semibold text-indigo-700">
                                            {{ returnedQty(tool) }}
                                        </span>
                                    </span>
                                    <span class="mx-1 text-slate-300">|</span>
                                    <span class="text-amber-600">
                                        Sisa:
                                        <span class="font-semibold text-amber-700">
                                            {{ remainingQty(tool) }}
                                        </span>
                                    </span>
                                </p>
                                <p v-else-if="!hasReviewResult(tool)" class="mt-2 text-xs text-slate-500">
                                    Status: Menunggu Review
                                </p>
                            </div>
                            <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                {{ displayQty(tool) }} unit
                            </span>
                        </div>
                        <p v-if="!(item?.tools || []).length" class="px-4 py-4 text-sm text-slate-500">
                            Tidak ada detail alat.
                        </p>
                    </div>
                </div>

                <div v-if="itemReports.length" class="mt-6 rounded-xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Laporan Kerusakan / Kehilangan</p>
                    </div>
                    <div class="space-y-4 px-4 py-4">
                        <div
                            v-for="report in itemReports"
                            :key="`${report.id}-${report.kategori}-${report.alatId}`"
                            class="rounded-xl border border-slate-100 bg-slate-50 p-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold uppercase"
                                            :class="reportCategoryClass(report.kategori)"
                                        >
                                            {{ reportCategoryLabel(report.kategori) }}
                                        </span>
                                        <span
                                            class="inline-flex rounded-full px-3 py-1 text-[11px] font-semibold"
                                            :class="reportStatusClass(report.status)"
                                        >
                                            {{ report.status || '-' }}
                                        </span>
                                    </div>
                                    <p class="mt-3 text-sm font-semibold text-slate-900">
                                        {{ report.alatName || '-' }}
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500">
                                        {{ report.alatCode || '-' }} | {{ report.createdAt || '-' }}
                                    </p>
                                </div>
                                <div class="rounded-lg bg-white px-3 py-2 text-right shadow-sm">
                                    <p class="text-[11px] uppercase tracking-[0.18em] text-slate-400">Jumlah</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-800">{{ report.jumlah ?? 0 }}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                {{ report.deskripsi || '-' }}
                            </p>
                            <div
                                v-if="reportPhotoSrc(report)"
                                class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-white"
                            >
                                <img
                                    :src="reportPhotoSrc(report)"
                                    :alt="report.originalName || report.alatName || 'Laporan alat'"
                                    class="h-40 w-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(['close']);
const page = usePage();
const roleKey = computed(() => page.props.auth?.user?.role?.key ?? '');
const isPicTools = computed(() => {
    const key = roleKey.value?.toLowerCase?.() ?? '';
    return key === 'pic_tools' || key === 'pic_tool';
});
const pengirimName = computed(
    () => props.item?.pengirimNama ?? props.item?.pengirim_nama ?? ''
);
const isInterArea = computed(() =>
    Boolean(props.item?.isInterArea ?? props.item?.is_inter_area)
    || props.item?.kategori === 'Antar Area'
);
const sourceAreaName = computed(
    () => props.item?.areaName ?? props.item?.area_name ?? '-'
);
const requesterAreaName = computed(
    () => props.item?.requesterAreaName ?? props.item?.requester_area_name ?? '-'
);
const requesterReviewerName = computed(
    () => props.item?.requesterReviewerName ?? props.item?.requester_reviewed_by_name ?? '-'
);
const approverName = computed(
    () => props.item?.reviewerName ?? props.item?.reviewed_by_name ?? '-'
);
const itemReports = computed(() =>
    Array.isArray(props.item?.reports) ? props.item.reports : []
);
const approvedQty = (tool) =>
    Number.isFinite(tool?.approvedQty) ? tool.approvedQty : 0;
const approvedItemCount = computed(() => {
    const tools = Array.isArray(props.item?.tools) ? props.item.tools : [];
    if (tools.length) {
        return tools.reduce((total, tool) => total + approvedQty(tool), 0);
    }

    return props.item?.itemCount ?? 0;
});

const returnedQty = (tool) =>
    Number.isFinite(tool?.returnedQty) ? tool.returnedQty : 0;

const remainingQty = (tool) =>
    Math.max(approvedQty(tool) - returnedQty(tool), 0);

const rejectedQty = (tool) => {
    const requested = Number.isFinite(tool?.qty) ? tool.qty : 0;
    const approved = approvedQty(tool);
    return Math.max(requested - approved, 0);
};

const hasReviewResult = (tool) =>
    tool?.reviewStatus && tool.reviewStatus !== 'Menunggu Review';

const displayQty = (tool) => (isPicTools.value ? approvedQty(tool) : Number.isFinite(tool?.qty) ? tool.qty : 0);

const reportCategoryLabel = (kategori) => {
    if ((kategori || '').toLowerCase() === 'kerusakan') {
        return 'Kerusakan';
    }
    if ((kategori || '').toLowerCase() === 'kehilangan') {
        return 'Kehilangan';
    }
    return kategori || '-';
};

const reportCategoryClass = (kategori) => {
    if ((kategori || '').toLowerCase() === 'kerusakan') {
        return 'bg-amber-100 text-amber-700';
    }
    if ((kategori || '').toLowerCase() === 'kehilangan') {
        return 'bg-rose-100 text-rose-700';
    }
    return 'bg-slate-100 text-slate-700';
};

const reportStatusClass = (status) => {
    switch ((status || '').toLowerCase()) {
        case 'dilaporkan':
            return 'bg-blue-100 text-blue-700';
        case 'disetujui':
            return 'bg-emerald-100 text-emerald-700';
        case 'ditolak':
            return 'bg-rose-100 text-rose-700';
        case 'selesai':
            return 'bg-slate-200 text-slate-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
};

const reportPhotoSrc = (report) => {
    if (!report) {
        return '';
    }
    if (report.url) {
        return report.url;
    }
    if (!report.path) {
        return '';
    }
    return report.path.startsWith('/storage/') ? report.path : `/storage/${report.path}`;
};
</script>
