<template>
    <header
        class="relative z-40 flex flex-col gap-4 border-b border-slate-200 bg-white/80 px-6 py-5 backdrop-blur md:flex-row md:items-center md:justify-between"
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
            <div class="relative">
                <button
                    class="relative flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
                    type="button"
                    aria-label="Mailbox"
                    :aria-expanded="isMailboxOpen"
                    @click="isMailboxOpen = !isMailboxOpen"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="m3 7 9 6 9-6" />
                    </svg>
                    <span
                        v-if="mailboxCount > 0"
                        class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                    >
                        {{ formatMailboxCount(mailboxCount) }}
                    </span>
                </button>

                <div
                    v-if="isMailboxOpen"
                    class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
                >
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">Mailbox</p>
                    </div>
                    <div v-if="!mailboxItems.length" class="px-4 py-6 text-center text-sm text-slate-500">
                        Tidak ada aksi saat ini.
                    </div>
                    <div v-else class="max-h-96 overflow-y-auto py-1">
                        <Link
                            v-for="item in visibleMailboxItems"
                            :key="item.key"
                            :href="item.href"
                            class="flex items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50"
                            @click="isMailboxOpen = false"
                        >
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 7h11v10H3z" />
                                    <path d="M14 10h4l3 3v4h-7z" />
                                    <circle cx="7.5" cy="19" r="1.5" />
                                    <circle cx="17.5" cy="19" r="1.5" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold leading-5 text-slate-800">{{ item.title }}</span>
                                <span class="mt-0.5 block text-xs leading-4 text-slate-500">{{ item.description }}</span>
                            </span>
                            <span class="mt-1 inline-flex min-w-[1.5rem] shrink-0 items-center justify-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold leading-none text-rose-700">
                                {{ formatMailboxCount(item.count) }}
                            </span>
                        </Link>
                        <button
                            v-if="mailboxItems.length > mailboxPreviewLimit"
                            class="w-full border-t border-slate-100 px-4 py-3 text-center text-sm font-semibold text-blue-600 transition hover:bg-slate-50 hover:text-blue-700"
                            type="button"
                            @click="showAllMailbox = !showAllMailbox"
                        >
                            {{ showAllMailbox ? 'Tampilkan 5 pesan saja' : 'Lihat semua pesan' }}
                        </button>
                    </div>
                </div>
            </div>
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
                <template v-if="isAreaSwitcher">
                    <select
                        class="bg-transparent text-xs font-semibold text-slate-600 outline-none"
                        :value="activeAreaId ?? ''"
                        @change="emit('change-area', $event.target.value)"
                    >
                        <option value="" disabled>Pilih area</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">
                            {{ area.name }}
                        </option>
                    </select>
                </template>
                <template v-else>
                    {{ displayArea }}
                </template>
            </div>
            <div class="rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600">
                Hai, {{ displayName }} ({{ displayRole }})
            </div>
        </div>
    </header>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const emit = defineEmits(['toggle-sidebar', 'change-area']);

const props = defineProps({
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
    areas: {
        type: Array,
        default: () => [],
    },
    activeAreaId: {
        type: [String, Number],
        default: null,
    },
    isAreaSwitcher: {
        type: Boolean,
        default: false,
    },
    mailboxItems: {
        type: Array,
        default: () => [],
    },
    mailboxCount: {
        type: Number,
        default: 0,
    },
});

const isMailboxOpen = ref(false);
const showAllMailbox = ref(false);
const mailboxPreviewLimit = 5;

const visibleMailboxItems = computed(() =>
    showAllMailbox.value ? props.mailboxItems : props.mailboxItems.slice(0, mailboxPreviewLimit)
);

const formatMailboxCount = (count) => {
    const normalized = Number(count ?? 0);
    if (!Number.isFinite(normalized) || normalized <= 0) {
        return '0';
    }

    return normalized > 99 ? '99+' : String(normalized);
};

watch(
    () => props.mailboxItems,
    () => {
        if (!props.mailboxItems.length) {
            isMailboxOpen.value = false;
        }
        showAllMailbox.value = false;
    }
);
</script>
