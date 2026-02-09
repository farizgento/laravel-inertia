<!-- <template>
    <div class="page">
        <header class="page-header">
            <div>
                <h1>CRUD Alat</h1>
                <p class="subtitle">Contoh sederhana dengan Inertia + API.</p>
            </div>
            <button v-if="isEdit" class="btn ghost" type="button" @click="cancelEdit">
                Batal Edit
            </button>
        </header>

        <section class="card">
            <h2 class="section-title">{{ isEdit ? 'Edit Alat' : 'Tambah Alat' }}</h2>
            <form class="form-grid" @submit.prevent="submit">
                <label class="field">
                    <span>Nama</span>
                    <input v-model="form.nama" type="text" placeholder="Nama alat" required />
                </label>
                <label class="field">
                    <span>Merk</span>
                    <input v-model="form.merk" type="text" placeholder="Merk" />
                </label>
                <label class="field">
                    <span>Jumlah</span>
                    <input v-model.number="form.jumlah" type="number" min="0" required />
                </label>
                <label class="field full">
                    <span>Keterangan</span>
                    <textarea v-model="form.keterangan" rows="3" placeholder="Catatan singkat" />
                </label>
                <div class="actions">
                    <button class="btn primary" type="submit">
                        {{ isEdit ? 'Simpan Perubahan' : 'Tambah' }}
                    </button>
                </div>
            </form>
            <p v-if="error" class="error">{{ error }}</p>
        </section>

        <section class="card">
            <h2 class="section-title">Daftar Alat</h2>
            <div v-if="loading" class="status">Memuat data...</div>
            <div v-else-if="items.length === 0" class="status">Belum ada data.</div>
            <div v-else class="table">
                <div class="table-header">
                    <span>Nama</span>
                    <span>Merk</span>
                    <span>Jumlah</span>
                    <span>Keterangan</span>
                    <span>Aksi</span>
                </div>
                <div v-for="item in items" :key="item.id" class="table-row">
                    <span>{{ item.nama }}</span>
                    <span>{{ item.merk || '-' }}</span>
                    <span>{{ item.jumlah }}</span>
                    <span>{{ item.keterangan || '-' }}</span>
                    <span class="row-actions">
                        <button class="btn small" type="button" @click="edit(item)">Edit</button>
                        <button class="btn small danger" type="button" @click="removeItem(item)">
                            Hapus
                        </button>
                    </span>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const items = ref([]);
const loading = ref(false);
const error = ref('');
const form = ref({
    id: null,
    nama: '',
    merk: '',
    jumlah: 0,
    keterangan: '',
});

const isEdit = computed(() => form.value.id !== null);

const redirectIfUnauthorized = (err) => {
    if (err?.response?.status === 401) {
        router.visit('/login');
        return true;
    }
    return false;
};

const load = async () => {
    loading.value = true;
    error.value = '';
    try {
        const response = await axios.get('/api/alats');
        items.value = response.data;
    } catch (err) {
        if (redirectIfUnauthorized(err)) {
            return;
        }
        error.value = err.response?.data?.message ?? 'Gagal memuat data.';
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.value = {
        id: null,
        nama: '',
        merk: '',
        jumlah: 0,
        keterangan: '',
    };
};

const submit = async () => {
    error.value = '';
    try {
        if (isEdit.value) {
            await axios.put(`/api/alats/${form.value.id}`, form.value);
        } else {
            await axios.post('/api/alats', form.value);
        }
        resetForm();
        await load();
    } catch (err) {
        if (redirectIfUnauthorized(err)) {
            return;
        }
        error.value = err.response?.data?.message ?? 'Gagal menyimpan data.';
    }
};

const edit = (item) => {
    form.value = {
        id: item.id,
        nama: item.nama,
        merk: item.merk,
        jumlah: item.jumlah,
        keterangan: item.keterangan,
    };
};

const cancelEdit = () => {
    resetForm();
};

const removeItem = async (item) => {
    if (!confirm(`Hapus alat "${item.nama}"?`)) {
        return;
    }
    error.value = '';
    try {
        await axios.delete(`/api/alats/${item.id}`);
        await load();
    } catch (err) {
        if (redirectIfUnauthorized(err)) {
            return;
        }
        error.value = err.response?.data?.message ?? 'Gagal menghapus data.';
    }
};

onMounted(() => {
    load();
});
</script> -->
