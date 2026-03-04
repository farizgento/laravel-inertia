<template>
    <teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-40 bg-slate-900/50"
            @click.self="emit('update:modelValue', false)"
        >
            <aside class="absolute right-0 top-0 flex h-full w-full max-w-sm flex-col bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Keranjang Peminjaman</h3>
                        <p class="text-sm text-slate-500">
                            {{ uniqueItems }} jenis alat - {{ totalItems }} total item
                        </p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="emit('update:modelValue', false)"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="flex flex-1 flex-col overflow-hidden">
                    <div class="flex-1 space-y-4 overflow-y-auto px-5 py-4">
                        <p v-if="!cartItems.length" class="text-sm text-slate-500">
                            Keranjang masih kosong.
                        </p>
                        <div
                            v-for="item in cartItems"
                            :key="item.id"
                            class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">
                                        {{ item.kode }}
                                    </p>
                                    <p class="text-sm font-semibold capitalize text-slate-900">{{ item.nama }}</p>
                                </div>
                                <button
                                    class="text-red-500 transition hover:text-red-600"
                                    type="button"
                                    @click="emit('remove', item.id)"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4h8v2" />
                                        <path d="m6 6 1 14h10l1-14" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center rounded-full bg-slate-100 px-2 py-1">
                                    <button
                                        class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700"
                                        type="button"
                                        @click="emit('decrease', item.id)"
                                    >
                                        -
                                    </button>
                                    <span class="w-8 text-center text-sm font-semibold text-slate-700">
                                        {{ item.qty }}
                                    </span>
                                    <button
                                        class="flex h-7 w-7 items-center justify-center rounded-full text-base font-semibold text-slate-500 transition hover:text-slate-700"
                                        type="button"
                                        @click="emit('increase', item.id)"
                                    >
                                        +
                                    </button>
                                </div>
                                <span class="text-xs text-slate-400">Stok: {{ item.stok }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 px-5 py-4">
                        <button
                            class="w-full rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400"
                            type="button"
                            :disabled="!cartItems.length"
                        @click="emit('checkout')"
                    >
                            Checkout ({{ totalItems }} item)
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </teleport>
</template>

<script setup>
defineProps({
    modelValue: {
        type: Boolean,
        default: false,
    },
    cartItems: {
        type: Array,
        default: () => [],
    },
    uniqueItems: {
        type: Number,
        default: 0,
    },
    totalItems: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits([
    'update:modelValue',
    'remove',
    'increase',
    'decrease',
    'checkout',
]);
</script>
