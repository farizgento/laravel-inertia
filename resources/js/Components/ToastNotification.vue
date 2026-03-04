<template>
    <div
        v-if="open"
        class="fixed right-6 top-6 z-50 w-full max-w-sm rounded-2xl border bg-white px-4 py-3 shadow-xl"
        :class="type === 'success' ? 'border-emerald-100 shadow-emerald-100' : 'border-rose-100 shadow-rose-100'"
    >
        <div class="flex items-start gap-3">
            <span
                class="flex h-9 w-9 items-center justify-center rounded-full border"
                :class="type === 'success'
                    ? 'border-emerald-200 bg-emerald-50 text-emerald-600'
                    : 'border-rose-200 bg-rose-50 text-rose-600'"
            >
                <svg
                    v-if="type === 'success'"
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M20 6 9 17l-5-5" />
                </svg>
                <svg
                    v-else
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M18 6 6 18" />
                    <path d="M6 6 18 18" />
                </svg>
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900">{{ title }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ message }}</p>
            </div>
            <button
                class="flex h-7 w-7 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                type="button"
                @click="$emit('close')"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6 6 18" />
                    <path d="M6 6 18 18" />
                </svg>
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'success',
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
});

defineEmits(['close']);
</script>
