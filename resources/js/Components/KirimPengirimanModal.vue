<template>
    <teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="emit('close')"
        >
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Pengiriman Peminjaman
                        </p>
                        <h3 class="mt-2 text-xl font-semibold text-slate-900">
                            {{ item?.title || '-' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            ID #{{ item?.id ?? '-' }} - Dibuat {{ item?.createdAt || '-' }}
                        </p>
                        <p v-if="item?.userName" class="mt-1 text-sm text-slate-500">
                            Peminjam: {{ item.userName }}
                        </p>
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

                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Status</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.status || '-' }}
                        </p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-xs text-slate-400">Jumlah Item</p>
                        <p class="mt-2 text-sm font-semibold text-slate-800">
                            {{ item?.itemCount ?? 0 }}
                        </p>
                    </div>
                </div>

                <div class="mt-6 space-y-4">
                    <div>
                        <label class="text-sm font-semibold text-slate-900" for="pengirim-nama">
                            Nama Pengirim
                        </label>
                        <input
                            id="pengirim-nama"
                            v-model="senderName"
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            type="text"
                            placeholder="Masukkan nama pengirim"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-900">Surat Jalan</label>
                        <p class="mt-1 text-xs text-slate-500">
                            Unggah file surat jalan (PDF atau gambar).
                        </p>
                        <input
                            type="file"
                            accept=".pdf,image/*"
                            class="mt-3 block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-600 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-emerald-700"
                            @change="handleFileChange"
                        />

                        <div v-if="suratJalanFile" class="mt-3">
                            <div
                                class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs"
                            >
                                <span class="truncate text-slate-600">
                                    {{ suratJalanFile.name }} · {{ formatSize(suratJalanFile.size) }}
                                </span>
                                <button
                                    class="text-rose-600 hover:text-rose-700"
                                    type="button"
                                    @click="removeFile"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="validationError"
                    class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700"
                >
                    {{ validationError }}
                </div>

                <div class="mt-6 flex flex-wrap items-center justify-end gap-3">
                    <button
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                        type="button"
                        @click="emit('close')"
                    >
                        Batal
                    </button>
                    <button
                        class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-emerald-300"
                        type="button"
                        :disabled="isSubmitting"
                        @click="submitShipping"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : 'Kirim' }}
                    </button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    item: {
        type: Object,
        default: null,
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'submit']);

const senderName = ref('');
const suratJalanFile = ref(null);
const validationError = ref('');

const handleFileChange = (event) => {
    const [file] = Array.from(event.target?.files ?? []);
    if (file) {
        suratJalanFile.value = file;
        validationError.value = '';
    }
    event.target.value = '';
};

const removeFile = () => {
    suratJalanFile.value = null;
};

const formatSize = (size) => {
    if (!Number.isFinite(size)) {
        return '-';
    }
    if (size < 1024) {
        return `${size} B`;
    }
    if (size < 1024 * 1024) {
        return `${(size / 1024).toFixed(1)} KB`;
    }
    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
};

const submitShipping = () => {
    const name = senderName.value.trim();
    if (!name) {
        validationError.value = 'Nama pengirim wajib diisi.';
        return;
    }
    if (!suratJalanFile.value) {
        validationError.value = 'Surat jalan wajib diunggah.';
        return;
    }
    emit('submit', {
        peminjamanId: props.item?.id ?? null,
        pengirimNama: name,
        suratJalan: suratJalanFile.value,
    });
};

watch(
    () => props.item,
    (next) => {
        senderName.value = next?.pengirimNama ?? '';
        suratJalanFile.value = null;
        validationError.value = '';
    },
    { immediate: true }
);
</script>
