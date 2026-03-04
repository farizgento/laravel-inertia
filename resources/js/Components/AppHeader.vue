<template>
    <header
        class="flex flex-col gap-4 border-b border-slate-200 bg-white/80 px-6 py-5 backdrop-blur md:flex-row md:items-center md:justify-between"
    >
        <div class="flex items-center gap-4">
            <button
                class="flex shadow-sm h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                type="button"
                aria-label="Toggle sidebar"
                aria-controls="app-sidebar"
                :aria-expanded="sidebarOpen"
                @click="emit('toggle-sidebar')"
            >
                <svg
                    class="h-6 w-6"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M4 4h16v16H4z" />
                    <path d="M8 4v16" />
                </svg>
            </button>
            <div>
                <h2 class="text-base font-semibold text-slate-900">
                    {{ title || 'Dashboard' }}
                </h2>
                <p v-if="subtitle" class="text-xs text-slate-500">
                    {{ subtitle }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2">
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 21s-6-5.33-6-11a6 6 0 1 1 12 0c0 5.67-6 11-6 11z" />
                    <circle cx="12" cy="10" r="2" />
                </svg>
                {{ displayArea }}
            </div>
            <div class="rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600">
                Hai, {{ displayName }} ({{ displayRole }})
            </div>
        </div>
    </header>
</template>

<script setup>
const emit = defineEmits(['toggle-sidebar']);

defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
    displayArea: {
        type: String,
        default: 'Area',
    },
    displayName: {
        type: String,
        default: 'Pengguna',
    },
    displayRole: {
        type: String,
        default: 'User',
    },
    sidebarOpen: {
        type: Boolean,
        default: false,
    },
});
</script>
