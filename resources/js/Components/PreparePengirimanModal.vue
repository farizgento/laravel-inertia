<template>
    <teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"
            @click.self="emit('close')"
        >
            <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                            Siapkan Peminjaman
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
                            {{ approvedItemTotal }}
                        </p>
                    </div>
                </div>

                <div class="mt-6">
                    <div>
                        <h4 class="text-base font-semibold text-slate-900">Unggah Foto Bukti</h4>
                        <p class="mt-1 text-xs text-slate-500">
                            Setiap alat wajib memiliki minimal 1 foto. Foto akan dikompres otomatis.
                        </p>
                    </div>

                    <div class="mt-4 space-y-4">
                        <div
                            v-for="row in rows"
                            :key="row.itemId"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ row.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ row.code }}</p>
                                </div>
                                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    {{ row.qty }} unit
                                </span>
                            </div>

                            <div class="mt-3">
                                <input
                                    type="file"
                                    accept="image/*"
                                    multiple
                                    class="block w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm text-slate-700 shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-white hover:file:bg-blue-700"
                                    @change="(event) => handleFileChange(row, event)"
                                />
                            </div>

                            <div v-if="row.files.length" class="mt-3 space-y-2">
                                <div
                                    v-for="(file, index) in row.files"
                                    :key="file.name + index"
                                    class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs"
                                >
                                    <span class="truncate text-slate-600">
                                        {{ file.name }} · {{ formatSize(file.size) }}
                                    </span>
                                    <button
                                        class="text-rose-600 hover:text-rose-700"
                                        type="button"
                                        @click="removeFile(row, index)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-if="!rows.length" class="text-sm text-slate-500">
                            Tidak ada alat yang disetujui untuk disiapkan.
                        </p>
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
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                        type="button"
                        :disabled="!rows.length || isSubmitting"
                        @click="submitPrepare"
                    >
                        {{ isSubmitting ? 'Menyimpan...' : 'Siapkan' }}
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

const rows = ref([]);
const validationError = ref('');
const approvedItemTotal = ref(0);

const approvedQty = (tool) => {
    if (Number.isFinite(tool?.approvedQty)) {
        return tool.approvedQty;
    }
    if (Number.isFinite(tool?.approved_qty)) {
        return tool.approved_qty;
    }
    return 0;
};

const buildRows = (tools) =>
    (Array.isArray(tools) ? tools : [])
        .filter((tool) => approvedQty(tool) > 0)
        .map((tool) => ({
            itemId: tool?.item_id ?? null,
            name: tool?.name ?? '-',
            code: tool?.code ?? '-',
            qty: approvedQty(tool),
            files: [],
        }));

const computeApprovedTotal = (tools) =>
    (Array.isArray(tools) ? tools : []).reduce((total, tool) => total + approvedQty(tool), 0);

const compressImage = (file) =>
    new Promise((resolve) => {
        if (!file?.type?.startsWith('image/')) {
            resolve(file);
            return;
        }

        const url = URL.createObjectURL(file);
        const img = new Image();
        img.onload = () => {
            URL.revokeObjectURL(url);
            const maxDim = 1600;
            const scale = Math.min(maxDim / img.width, maxDim / img.height, 1);
            const width = Math.round(img.width * scale);
            const height = Math.round(img.height * scale);
            const canvas = document.createElement('canvas');
            canvas.width = width;
            canvas.height = height;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, width, height);
            ctx.drawImage(img, 0, 0, width, height);
            canvas.toBlob(
                (blob) => {
                    if (!blob) {
                        resolve(file);
                        return;
                    }
                    const baseName = file.name.replace(/\.[^.]+$/, '');
                    const compressedFile = new File([blob], `${baseName}.jpg`, { type: 'image/jpeg' });
                    resolve(compressedFile);
                },
                'image/jpeg',
                0.7
            );
        };
        img.onerror = () => {
            URL.revokeObjectURL(url);
            resolve(file);
        };
        img.src = url;
    });

const handleFileChange = async (row, event) => {
    const files = Array.from(event.target?.files ?? []);
    if (!files.length) {
        return;
    }
    const compressed = [];
    for (const file of files) {
        compressed.push(await compressImage(file));
    }
    row.files.push(...compressed);
    event.target.value = '';
    validationError.value = '';
};

const removeFile = (row, index) => {
    row.files.splice(index, 1);
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

const validateRows = () => {
    for (const row of rows.value) {
        if (!row.files.length) {
            return `Upload minimal 1 foto untuk ${row.name}.`;
        }
    }
    return '';
};

const submitPrepare = () => {
    const error = validateRows();
    if (error) {
        validationError.value = error;
        return;
    }
    emit('submit', {
        peminjamanId: props.item?.id ?? null,
        items: rows.value.map((row) => ({
            item_id: row.itemId,
            files: row.files,
        })),
    });
};

watch(
    () => props.item,
    (next) => {
        rows.value = buildRows(next?.tools);
        approvedItemTotal.value = computeApprovedTotal(next?.tools);
        validationError.value = '';
    },
    { immediate: true }
);
</script>
