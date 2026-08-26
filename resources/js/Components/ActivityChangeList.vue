<template>
    <div>
        <p class="mb-2 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.16em]" :class="toneClass">
            <span class="inline-block h-1.5 w-1.5 rounded-full" :class="dotClass" aria-hidden="true"></span>
            {{ title }}
        </p>

        <dl v-if="changes?.length" class="space-y-1.5">
            <div
                v-for="change in changes"
                :key="change.field"
                class="flex flex-col gap-0.5 sm:flex-row sm:gap-2"
            >
                <dt class="shrink-0 text-xs font-semibold text-slate-600 sm:w-40">{{ change.label }}</dt>
                <dd class="min-w-0 text-xs text-slate-700">
                    <a
                        v-if="isUrl(formatValue(change.value))"
                        :href="formatValue(change.value)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-1 rounded text-blue-600 underline underline-offset-2 transition hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-200"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <path d="M7 10l5 5 5-5" />
                            <path d="M12 15V3" />
                        </svg>
                        Download File
                    </a>
                    <span
                        v-else
                        class="block break-words"
                        :class="isEmptyValue(change.value) ? 'italic text-slate-400' : ''"
                        :title="formatValue(change.value)"
                    >
                        {{ formatValue(change.value) }}
                    </span>
                </dd>
            </div>
        </dl>

        <p v-else class="text-xs italic text-slate-400">{{ emptyText }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    changes: {
        type: Array,
        default: () => [],
    },
    emptyText: {
        type: String,
        default: 'Tidak ada data.',
    },
    tone: {
        type: String,
        default: 'slate',
    },
});

const toneClass = computed(() => (props.tone === 'rose' ? 'text-rose-600' : 'text-emerald-600'));
const dotClass = computed(() => (props.tone === 'rose' ? 'bg-rose-500' : 'bg-emerald-500'));

// Dipertahankan sama persis dengan perilaku sebelumnya agar nilai log tampil identik.
const formatValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return 'null';
    }

    return typeof value === 'object' ? JSON.stringify(value) : String(value);
};

const isEmptyValue = (value) => value === null || value === undefined || value === '';

const isUrl = (value) => /^https?:\/\//i.test(String(value ?? '').trim());
</script>
