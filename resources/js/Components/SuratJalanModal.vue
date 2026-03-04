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
                    <div v-if="pengirimName" class="mb-4 flex items-center gap-2 text-sm text-slate-600">
                        <svg class="h-4 w-4 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 7a4 4 0 1 0-8 0" />
                            <path d="M12 11v4" />
                            <path d="M5.5 21a6.5 6.5 0 0 1 13 0" />
                        </svg>
                        <span>Pengirim: <span class="font-semibold text-slate-800">{{ pengirimName }}</span></span>
                    </div>
                    <div v-if="!url" class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-500">
                        Surat jalan belum tersedia.
                    </div>
                    <div v-else-if="isImage" class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <img :src="url" :alt="titleText" class="max-h-[70vh] w-full object-contain" />
                    </div>
                    <div v-else-if="isPdf" class="overflow-hidden rounded-xl border border-slate-200">
                        <iframe :src="url" class="h-[70vh] w-full" title="Surat Jalan"></iframe>
                    </div>
                    <div v-else class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
                        Format file tidak didukung untuk preview. Silakan buka di tab baru.
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 px-6 py-4">
                    <a
                        v-if="url"
                        :href="url"
                        target="_blank"
                        rel="noopener"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Buka di Tab Baru
                    </a>
                    <button
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
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
import { computed } from 'vue';

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
});

const emit = defineEmits(['close']);

const extension = computed(() => {
    const source = props.url || props.path || '';
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
