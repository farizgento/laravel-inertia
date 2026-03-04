<template>
    <teleport to="body">
        <div
            v-if="modelValue"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="emit('update:modelValue', false)"
        >
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-xl font-semibold text-slate-900">Checkout Peminjaman</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Lengkapi informasi peminjaman untuk {{ uniqueItems }} jenis alat ({{ totalItems }}
                            item)
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

                <div class="mt-6 space-y-6">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.73Z" />
                                <path d="m3.3 7 8.7 5 8.7-5" />
                                <path d="M12 12v9" />
                            </svg>
                            Daftar Alat
                        </div>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="item in cartItems"
                                :key="item.id"
                                class="flex items-center justify-between rounded-lg bg-white px-3 py-2 text-sm text-slate-700"
                            >
                                <span class="capitalize">{{ item.nama }}</span>
                                <span class="font-semibold text-slate-500">x{{ item.qty }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Tanggal Pinjam *</span>
                            <input
                                v-model="form.tanggal_pinjam"
                                type="date"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label class="space-y-2 text-sm font-medium text-slate-700">
                            <span>Tanggal Kembali *</span>
                            <input
                                v-model="form.tanggal_kembali"
                                type="date"
                                :min="form.tanggal_pinjam"
                                required
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                    </div>

                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Keperluan *</span>
                        <textarea
                            v-model="form.keperluan"
                            rows="3"
                            placeholder="Jelaskan keperluan peminjaman..."
                            required
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                    <label class="space-y-2 text-sm font-medium text-slate-700">
                        <span>Catatan (Opsional)</span>
                        <textarea
                            v-model="form.catatan"
                            rows="3"
                            placeholder="Catatan tambahan..."
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </label>
                </div>

                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="emit('update:modelValue', false)"
                    >
                        Batal
                    </button>
                    <div class="flex flex-1 flex-col items-end gap-2">
                        <p v-if="checkoutError" class="text-right text-xs font-semibold text-rose-500">
                            {{ checkoutError }}
                        </p>
                        <button
                            class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="button"
                            :disabled="isSubmitting"
                            @click="emit('submit')"
                        >
                            {{ isSubmitting ? 'Memproses...' : 'Buat Peminjaman' }}
                        </button>
                    </div>
                </div>
            </div>
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
    form: {
        type: Object,
        required: true,
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
    checkoutError: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'submit']);
</script>
