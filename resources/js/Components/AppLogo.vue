<template>
    <!-- Logo gagal dimuat: lencana petir lama dipakai agar tidak muncul ikon
         gambar rusak, misalnya saat diakses dari luar jaringan kantor. -->
    <span
        v-if="gagalMuat"
        class="inline-flex items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-sky-400 text-white shadow-lg shadow-blue-200"
        :class="ukuranCadangan"
        :title="alt"
    >
        <svg
            class="h-1/2 w-1/2"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" />
        </svg>
    </span>

    <img
        v-else
        :src="sumber"
        :alt="alt"
        class="w-auto object-contain"
        :class="tinggi"
        loading="lazy"
        decoding="async"
        @error="gagalMuat = true"
    />
</template>

<script setup>
import { ref } from 'vue';

/**
 * Logo PLN Indonesia Power.
 *
 * Berkasnya disimpan di public/images supaya aplikasi tidak bergantung pada
 * server lain yang bisa mati atau tidak terjangkau dari luar jaringan kantor.
 * Ukurannya sudah dikecilkan dari 4845x1063 menjadi 600x132 (165 KB -> 21 KB),
 * karena logo ini paling lebar hanya ditampilkan sekitar 255 piksel.
 */
const SUMBER_LOGO = '/images/logo-pln.png';

defineProps({
    /** Kelas tinggi Tailwind, misalnya "h-10". Lebarnya mengikuti rasio asli. */
    tinggi: {
        type: String,
        default: 'h-10',
    },
    /** Ukuran lencana pengganti saat gambar gagal dimuat. */
    ukuranCadangan: {
        type: String,
        default: 'h-10 w-10',
    },
    alt: {
        type: String,
        default: 'PLN Indonesia Power',
    },
});

const sumber = SUMBER_LOGO;
const gagalMuat = ref(false);
</script>
