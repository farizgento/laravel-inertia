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
                        <p class="text-xs text-slate-400">Jumlah Item</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.itemCount ?? 0 }}
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
                                    <span class="mx-1 text-slate-300">•</span>
                                    <span class="text-emerald-600">
                                        Disetujui:
                                        <span class="font-semibold text-emerald-700">
                                            {{ approvedQty(tool) }}
                                        </span>
                                    </span>
                                    <span class="mx-1 text-slate-300">•</span>
                                    <span class="text-rose-600">
                                        Ditolak:
                                        <span class="font-semibold text-rose-700">
                                            {{ rejectedQty(tool) }}
                                        </span>
                                    </span>
                                </p>
                                <p v-else class="mt-2 text-xs text-slate-500">
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

                <div v-if="hasAnyPhotos(item)" class="mt-6 rounded-xl border border-slate-200 bg-white">
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Bukti Foto</p>
                    </div>
                    <div class="space-y-4 px-4 py-4">
                        <div
                            v-for="tool in item?.tools || []"
                            :key="tool.name + tool.code + '-photos'"
                            class="rounded-xl border border-slate-100 bg-slate-50 px-4 py-3"
                        >
                            <p class="text-sm font-semibold text-slate-900">{{ tool.name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ tool.code }}</p>
                            <div v-if="toolPhotos(tool).length" class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <div
                                    v-for="photo in toolPhotos(tool)"
                                    :key="photo.id ?? photo.url ?? photo.originalName"
                                    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
                                >
                                    <img
                                        :src="photoSrc(photo)"
                                        :alt="photo.originalName || tool.name"
                                        class="h-32 w-full object-cover"
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                            <p v-else class="mt-3 text-xs text-slate-500">Belum ada foto.</p>
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
const approvedQty = (tool) =>
    Number.isFinite(tool?.approvedQty) ? tool.approvedQty : 0;

const rejectedQty = (tool) => {
    const requested = Number.isFinite(tool?.qty) ? tool.qty : 0;
    const approved = approvedQty(tool);
    return Math.max(requested - approved, 0);
};

const hasReviewResult = (tool) =>
    tool?.reviewStatus && tool.reviewStatus !== 'Menunggu Review';

const toolPhotos = (tool) =>
    Array.isArray(tool?.photos)
        ? tool.photos.filter((photo) => photo?.url || photo?.path)
        : [];

const hasAnyPhotos = (item) =>
    Array.isArray(item?.tools) && item.tools.some((tool) => toolPhotos(tool).length);

const photoSrc = (photo) => {
    if (!photo) {
        return '';
    }
    if (photo.url) {
        return photo.url;
    }
    if (!photo.path) {
        return '';
    }
    return photo.path.startsWith('/storage/') ? photo.path : `/storage/${photo.path}`;
};

const displayQty = (tool) => (isPicTools.value ? approvedQty(tool) : Number.isFinite(tool?.qty) ? tool.qty : 0);
</script>
