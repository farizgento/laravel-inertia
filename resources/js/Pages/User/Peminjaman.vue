<template>
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
