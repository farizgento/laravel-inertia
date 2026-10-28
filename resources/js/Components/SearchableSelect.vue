<template>
    <div ref="root" class="relative">
        <button
            ref="trigger"
            type="button"
            :disabled="disabled || loading"
            class="flex w-full items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-left text-sm shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100"
            :class="selectedLabel ? 'text-slate-900' : 'text-slate-400'"
            :aria-expanded="isOpen"
            aria-haspopup="listbox"
            @click="toggle"
            @keydown.down.prevent="open"
        >
            <span class="truncate">{{ loading ? loadingText : selectedLabel || placeholder }}</span>
            <svg
                class="h-4 w-4 shrink-0 text-slate-400 transition"
                :class="isOpen ? 'rotate-180' : ''"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
            >
                <path d="m6 9 6 6 6-6" />
            </svg>
        </button>

        <div
            v-if="isOpen"
            class="absolute z-30 w-full rounded-xl border border-slate-200 bg-white shadow-xl"
            :class="openUpward ? 'bottom-full mb-1' : 'top-full mt-1'"
        >
            <div class="border-b border-slate-100 p-2">
                <input
                    ref="searchInput"
                    v-model="keyword"
                    type="text"
                    :placeholder="searchPlaceholder"
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    @keydown.down.prevent="moveHighlight(1)"
                    @keydown.up.prevent="moveHighlight(-1)"
                    @keydown.enter.prevent="pickHighlighted"
                    @keydown.esc.prevent="close"
                    @keydown.tab="close"
                />
            </div>

            <ul v-if="filteredOptions.length" class="max-h-56 overflow-y-auto py-1" role="listbox">
                <li
                    v-for="(option, index) in filteredOptions"
                    :key="option.value"
                    role="option"
                    :aria-selected="option.value === modelValue"
                    class="cursor-pointer px-3 py-2 text-sm transition"
                    :class="[
                        index === highlightedIndex ? 'bg-blue-50 text-blue-700' : 'text-slate-700',
                        option.value === modelValue ? 'font-semibold' : '',
                    ]"
                    @mouseenter="highlightedIndex = index"
                    @click="pick(option.value)"
                >
                    {{ option.label }}
                </li>
            </ul>
            <p v-else class="px-3 py-3 text-sm text-slate-400">
                {{ options.length ? noResultText : emptyText }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

// Perkiraan tinggi panel, dipakai untuk memutuskan panel dibuka ke atas atau ke
// bawah. Di dalam modal yang bisa di-scroll, panel yang menjorok ke bawah bisa
// terpotong oleh kontainernya.
const PANEL_HEIGHT_ESTIMATE = 280;

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    /** Daftar { value, label }. */
    options: {
        type: Array,
        default: () => [],
    },
    placeholder: {
        type: String,
        default: 'Pilih',
    },
    searchPlaceholder: {
        type: String,
        default: 'Cari...',
    },
    loadingText: {
        type: String,
        default: 'Memuat...',
    },
    emptyText: {
        type: String,
        default: 'Belum ada pilihan',
    },
    noResultText: {
        type: String,
        default: 'Tidak ada yang cocok',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue']);

const root = ref(null);
const trigger = ref(null);
const searchInput = ref(null);
const isOpen = ref(false);
const openUpward = ref(false);
const keyword = ref('');
const highlightedIndex = ref(0);

const selectedLabel = computed(() => {
    const match = props.options.find((option) => option.value === props.modelValue);
    // Nilai yang tidak ada di daftar tetap ditampilkan apa adanya daripada
    // membuat field terlihat kosong.
    return match?.label ?? props.modelValue ?? '';
});

const matchesTerms = (text, terms) => {
    const haystack = String(text ?? '').toLowerCase();

    // Semua kata harus cocok, tanpa peduli urutannya.
    return terms.every((term) => haystack.includes(term));
};

const filteredOptions = computed(() => {
    const terms = keyword.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
    if (!terms.length) {
        return props.options;
    }

    // Nama dicocokkan lebih dulu supaya keterangan seperti periode dan status
    // tidak mengaburkan hasil: "unit 2" tidak boleh memunculkan unit 3 hanya
    // karena periodenya tahun 2027.
    const byName = props.options.filter((option) => matchesTerms(option.search ?? option.label, terms));
    if (byName.length) {
        return byName;
    }

    // Tidak ada nama yang cocok, jadi keterangan lengkap ikut dicoba agar
    // pencarian seperti "berlangsung" atau "2027" tetap berguna.
    return props.options.filter((option) => matchesTerms(option.label, terms));
});

const close = () => {
    isOpen.value = false;
    keyword.value = '';
    if (typeof document !== 'undefined') {
        document.removeEventListener('mousedown', handleOutsideClick);
    }
};

const handleOutsideClick = (event) => {
    if (root.value && !root.value.contains(event.target)) {
        close();
    }
};

const open = async () => {
    if (props.disabled || props.loading || isOpen.value) {
        return;
    }

    const rect = trigger.value?.getBoundingClientRect();
    if (rect && typeof window !== 'undefined') {
        const spaceBelow = window.innerHeight - rect.bottom;
        openUpward.value = spaceBelow < PANEL_HEIGHT_ESTIMATE && rect.top > spaceBelow;
    }

    isOpen.value = true;
    highlightedIndex.value = Math.max(
        0,
        filteredOptions.value.findIndex((option) => option.value === props.modelValue)
    );

    if (typeof document !== 'undefined') {
        document.addEventListener('mousedown', handleOutsideClick);
    }

    await nextTick();
    searchInput.value?.focus();
};

const toggle = () => {
    if (isOpen.value) {
        close();
        return;
    }
    open();
};

const pick = (value) => {
    emit('update:modelValue', value);
    close();
    trigger.value?.focus();
};

const pickHighlighted = () => {
    const option = filteredOptions.value[highlightedIndex.value];
    if (option) {
        pick(option.value);
    }
};

const moveHighlight = (step) => {
    const total = filteredOptions.value.length;
    if (!total) {
        return;
    }
    highlightedIndex.value = (highlightedIndex.value + step + total) % total;
};

// Hasil filter berubah, jadi sorotan dikembalikan ke atas agar Enter tidak
// memilih baris yang sudah tidak terlihat.
watch(keyword, () => {
    highlightedIndex.value = 0;
});

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.removeEventListener('mousedown', handleOutsideClick);
    }
});
</script>
