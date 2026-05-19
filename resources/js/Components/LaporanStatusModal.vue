<template>
    <teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 bg-slate-900/50"
        >
            <div class="flex min-h-full items-center justify-center overflow-y-auto p-4" @click.self="emit('close')">
            <div class="max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Persetujuan Laporan</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            Ubah status laporan {{ reportLabel }} untuk {{ report?.alatNama ?? '-' }}.
                        </p>
                    </div>
                    <button
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        @click="emit('close')"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <div class="mt-5 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        <p class="font-semibold text-slate-900">{{ report?.alatNama ?? '-' }}</p>
                        <p class="mt-1">{{ report?.alatKode ?? '-' }} - {{ report?.areaName ?? '-' }}</p>
                        <p class="mt-1">Pelapor: {{ report?.userName ?? '-' }}</p>
                        <p class="mt-1">Status saat ini: {{ report?.status ?? '-' }}</p>
                    </div>

                    <div class="space-y-3">
                        <label
                            v-for="option in statusOptions"
                            :key="option.value"
                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 transition hover:border-blue-300"
                            :class="modelValue === option.value ? 'border-blue-500 bg-blue-50' : 'bg-white'"
                        >
                            <input
                                :checked="modelValue === option.value"
                                class="mt-1"
                                type="radio"
                                name="laporan-status"
                                :value="option.value"
                                @change="emit('update:modelValue', option.value)"
                            />
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ option.label }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ option.description }}</p>
                            </div>
                        </label>
                    </div>

                    <p v-if="error" class="text-sm font-semibold text-rose-500">
                        {{ error }}
                    </p>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300"
                        type="button"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="isSubmitting || !modelValue"
                        @click="emit('submit')"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : 'Simpan Status' }}
                    </button>
                </div>
            </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { computed } from 'vue';

const approvalOptions = [
    {
        value: 'Disetujui',
        label: 'Disetujui',
        description: 'Total aset alat akan berkurang sesuai jumlah laporan.',
    },
    {
        value: 'Ditolak',
        label: 'Ditolak',
        description: 'Laporan ditolak dan stok tidak berkurang.',
    },
];

const completionOptions = [
    {
        value: 'Selesai',
        label: 'Selesai',
        description: 'Jika sebelumnya disetujui, total aset alat akan dikembalikan.',
    },
];

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    report: {
        type: Object,
        default: null,
    },
    modelValue: {
        type: String,
        default: '',
    },
    reportLabel: {
        type: String,
        default: 'alat',
    },
    error: {
        type: String,
        default: '',
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
});

const statusOptions = computed(() => {
    if (props.report?.status === 'Disetujui') {
        return completionOptions;
    }

    return approvalOptions;
});

const emit = defineEmits(['close', 'submit', 'update:modelValue']);
</script>
