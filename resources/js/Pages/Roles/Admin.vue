<template>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-slate-900">Buat Peminjaman</h1>
        <p class="mt-1 text-sm text-slate-500">
            Lengkapi formulir berikut untuk memulai pengajuan.
        </p>
    </div>
    <PeminjamanForm />
</template>

<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import PeminjamanForm from '../../Components/PeminjamanForm.vue';

defineOptions({
    layout: (h, page) =>
        h(
            AppLayout,
            {
                title: 'Buat Peminjaman',
                subtitle: 'Ajukan peminjaman alat untuk area Anda',
                activeMenu: 'peminjaman',
            },
            () => page
        ),
});
</script>
