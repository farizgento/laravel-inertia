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
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <label class="text-sm font-semibold text-slate-900" for="foto-pengiriman">
                                    Foto Pengiriman
                                </label>
                                <p class="mt-1 text-xs text-slate-500">
                                    Unggah 1-{{ MAX_PHOTO_COUNT }} foto JPG, PNG, atau WebP. Maksimal {{ MAX_PHOTO_SIZE_LABEL }} dan 6000×6000 piksel per foto.
                                </p>
                            </div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                {{ photoItems.length }}/{{ MAX_PHOTO_COUNT }} foto
                            </span>
                        </div>

                        <label
                            for="foto-pengiriman"
                            class="mt-3 flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-dashed border-blue-300 bg-blue-50 px-4 py-4 text-sm font-semibold text-blue-700 transition hover:border-blue-400 hover:bg-blue-100"
                            :class="photoItems.length >= MAX_PHOTO_COUNT ? 'cursor-not-allowed opacity-60' : ''"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 16l4-4 4 4 3-3 5 5" />
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <circle cx="8.5" cy="8.5" r="1.5" />
                            </svg>
                            {{ photoItems.length >= MAX_PHOTO_COUNT ? 'Batas foto tercapai' : 'Pilih Foto Pengiriman' }}
                        </label>
                        <input
                            id="foto-pengiriman"
                            ref="photoInput"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            class="sr-only"
                            multiple
                            :disabled="photoItems.length >= MAX_PHOTO_COUNT"
                            @change="handlePhotosChange"
                        />

                        <div v-if="photoItems.length" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div
                                v-for="(photo, index) in photoItems"
                                :key="photo.key"
                                class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-100"
                            >
                                <div class="aspect-[4/3] overflow-hidden">
                                    <img
                                        :src="photo.previewUrl"
                                        :alt="`Pratinjau foto pengiriman ${index + 1}`"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <span class="absolute left-2 top-2 rounded-full bg-slate-900/70 px-2 py-0.5 text-[10px] font-semibold text-white">
                                    {{ index + 1 }}
                                </span>
                                <button
                                    class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-white/95 text-rose-600 shadow-sm transition hover:bg-rose-50"
                                    type="button"
                                    :aria-label="`Hapus foto ${index + 1}`"
                                    @click="removePhoto(photo.key)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M18 6 6 18" />
                                        <path d="M6 6l12 12" />
                                    </svg>
                                </button>
                                <div class="border-t border-slate-200 bg-white px-2.5 py-2">
                                    <p class="truncate text-[11px] font-medium text-slate-700" :title="photo.file.name">
                                        {{ photo.file.name }}
                                    </p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ formatSize(photo.file.size) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5 text-xs leading-5 text-emerald-800">
                            Foto akan dikompresi otomatis saat dikirim. Surat jalan Excel dibuat dari data peminjaman; foto setelah urutan keempat ditempatkan pada lampiran lanjutan.
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
import { onBeforeUnmount, ref, watch } from 'vue';

const MAX_PHOTO_COUNT = 8;
const MAX_PHOTO_SIZE = 5 * 1024 * 1024;
const MAX_PHOTO_SIZE_LABEL = '5 MB';
const ACCEPTED_PHOTO_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);

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
const photoInput = ref(null);
const photoItems = ref([]);
const validationError = ref('');
let photoSequence = 0;

const photoKey = (file) => `${file.name}-${file.size}-${file.lastModified}`;

const clearPhotos = () => {
    photoItems.value.forEach((photo) => window.URL.revokeObjectURL(photo.previewUrl));
    photoItems.value = [];
    if (photoInput.value) {
        photoInput.value.value = '';
    }
};

const handlePhotosChange = (event) => {
    const selectedFiles = Array.from(event.target?.files ?? []);
    event.target.value = '';

    if (!selectedFiles.length) {
        return;
    }

    const errors = [];
    const existingKeys = new Set(photoItems.value.map((photo) => photo.sourceKey));

    selectedFiles.forEach((file) => {
        if (photoItems.value.length >= MAX_PHOTO_COUNT) {
            errors.push(`Maksimal ${MAX_PHOTO_COUNT} foto dapat diunggah.`);
            return;
        }

        if (!ACCEPTED_PHOTO_TYPES.has(file.type)) {
            errors.push(`${file.name} bukan foto JPG, PNG, atau WebP.`);
            return;
        }

        if (file.size > MAX_PHOTO_SIZE) {
            errors.push(`${file.name} melebihi batas ${MAX_PHOTO_SIZE_LABEL}.`);
            return;
        }

        const sourceKey = photoKey(file);
        if (existingKeys.has(sourceKey)) {
            errors.push(`${file.name} sudah dipilih.`);
            return;
        }

        existingKeys.add(sourceKey);
        photoSequence += 1;
        photoItems.value.push({
            key: `${sourceKey}-${photoSequence}`,
            sourceKey,
            file,
            previewUrl: window.URL.createObjectURL(file),
        });
    });

    validationError.value = errors[0] ?? '';
};

const removePhoto = (key) => {
    const photoIndex = photoItems.value.findIndex((photo) => photo.key === key);
    if (photoIndex < 0) {
        return;
    }

    window.URL.revokeObjectURL(photoItems.value[photoIndex].previewUrl);
    photoItems.value.splice(photoIndex, 1);
    validationError.value = '';
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
    if (!photoItems.value.length) {
        validationError.value = 'Minimal satu foto pengiriman wajib diunggah.';
        return;
    }
    emit('submit', {
        peminjamanId: props.item?.id ?? null,
        pengirimNama: name,
        photos: photoItems.value.map((photo) => photo.file),
    });
};

watch(
    () => props.item,
    (next) => {
        senderName.value = next?.pengirimNama ?? '';
        clearPhotos();
        validationError.value = '';
    },
    { immediate: true }
);

onBeforeUnmount(clearPhotos);
</script>
