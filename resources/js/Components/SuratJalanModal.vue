<template>
    <teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
            @click.self="emit('close')"
        >
            <div class="w-full max-w-4xl overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Surat Jalan
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-slate-900">
                            {{ titleText }}
                        </h3>
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

                <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
                    <div v-if="documentItems.length > 1" class="mb-4 flex flex-wrap gap-2">
                        <button
                            v-for="(document, index) in documentItems"
                            :key="`${document.label}-${index}`"
                            class="rounded-xl border px-3 py-2 text-xs font-semibold transition"
                            :class="activeDocumentIndex === index
                                ? 'border-blue-200 bg-blue-50 text-blue-700'
                                : 'border-slate-200 bg-white text-slate-600 hover:border-blue-200'"
                            type="button"
                            @click="activeDocumentIndex = index"
                        >
                            {{ document.label }}
                        </button>
                    </div>
                    <div v-if="currentPengirimName" class="mb-4 flex items-center gap-2 text-sm text-slate-600">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 7a4 4 0 1 0-8 0" />
                            <path d="M12 11v4" />
                            <path d="M5.5 21a6.5 6.5 0 0 1 13 0" />
                        </svg>
                        <span>Pengirim: <span class="font-semibold text-slate-800">{{ currentPengirimName }}</span></span>
                    </div>
                    <div v-if="!currentUrl" class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                        Surat jalan belum tersedia.
                    </div>
                    <div v-else-if="isImage" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <img :src="currentUrl" :alt="titleText" class="max-h-[70vh] w-full object-contain" />
                    </div>
                    <div v-else-if="isPdf" class="overflow-hidden rounded-xl border border-slate-200">
                        <iframe :src="currentUrl" class="h-[70vh] w-full" title="Surat Jalan"></iframe>
                    </div>
                    <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                        Format file tidak didukung untuk preview.
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <p v-if="submitError" class="mr-auto text-xs text-rose-500">
                        {{ submitError }}
                    </p>
                    <button
                        v-if="acceptEnabled && canAccept"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                        type="button"
                        :disabled="isSubmitting"
                        @click="acceptPeminjaman"
                    >
                        {{ isSubmitting ? 'Memproses...' : 'Terima' }}
                    </button>
                    <button
                        class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700"
                        type="button"
                        @click="emit('close')"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    url: {
        type: String,
        default: '',
    },
    path: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        default: '',
    },
    pengirimName: {
        type: String,
        default: '',
    },
    peminjamanId: {
        type: [String, Number],
        default: '',
    },
    peminjamanStatus: {
        type: String,
        default: '',
    },
    canAcceptOverride: {
        type: Boolean,
        default: false,
    },
    acceptEnabled: {
        type: Boolean,
        default: true,
    },
    documents: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['close', 'accepted']);
const page = usePage();

const isSubmitting = ref(false);
const submitError = ref('');
const activeDocumentIndex = ref(0);
const roleKey = computed(() => String(page.props.auth?.user?.role?.key ?? '').toLowerCase());

const canAccept = computed(
    () => (roleKey.value === 'user' || props.canAcceptOverride) && props.peminjamanStatus === 'Dikirim' && !!props.peminjamanId
);

const extension = computed(() => {
    const source = currentUrl.value || currentPath.value || '';
    const cleaned = source.split('?')[0].split('#')[0];
    const parts = cleaned.split('.');
    if (parts.length < 2) {
        return '';
    }
    return String(parts.pop()).toLowerCase();
});

const isPdf = computed(() => extension.value === 'pdf');
const isImage = computed(() =>
    ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension.value)
);

const documentItems = computed(() => {
    const mapped = Array.isArray(props.documents)
        ? props.documents
            .filter((document) => document?.url || document?.path)
            .map((document, index) => ({
                label: document?.label ?? `Surat Jalan ${index + 1}`,
                url: document?.url ?? '',
                path: document?.path ?? '',
                pengirimName: document?.pengirimName ?? '',
            }))
        : [];

    if (mapped.length) {
        return mapped;
    }

    return [{
        label: 'Surat Jalan',
        url: props.url,
        path: props.path,
        pengirimName: props.pengirimName,
    }];
});

const currentDocument = computed(() =>
    documentItems.value[Math.min(activeDocumentIndex.value, Math.max(documentItems.value.length - 1, 0))] ?? {}
);
const currentUrl = computed(() => currentDocument.value.url ?? '');
const currentPath = computed(() => currentDocument.value.path ?? '');
const currentPengirimName = computed(() => currentDocument.value.pengirimName ?? '');

const acceptPeminjaman = async () => {
    if (!canAccept.value || isSubmitting.value) {
        return;
    }
    isSubmitting.value = true;
    submitError.value = '';
    try {
        await axios.post(`/api/pengiriman/${props.peminjamanId}/terima`);
        emit('accepted');
        emit('close');
    } catch (error) {
        submitError.value =
            error.response?.data?.message ?? 'Gagal menerima peminjaman.';
    } finally {
        isSubmitting.value = false;
    }
};

watch(
    () => props.open,
    (value) => {
        if (value) {
            submitError.value = '';
            isSubmitting.value = false;
            activeDocumentIndex.value = 0;
        }
    }
);

const titleText = computed(() => {
    if (props.title) {
        return `#${props.peminjamanId || '-'} - ${props.title}`;
    }
    if (props.peminjamanId) {
        return `#${props.peminjamanId}`;
    }
    return 'Detail Surat Jalan';
});
</script>
