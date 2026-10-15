<template>
    <button type="button" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-600 focus-visible:outline-2 focus-visible:outline-blue-600" @click="open">
        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12" /><path d="m7 10 5 5 5-5" /><path d="M5 21h14" /></svg>
        Export Excel
    </button>
    <Teleport to="body">
        <dialog ref="dialog" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-900/50" :aria-labelledby="titleId" @cancel="onCancel">
            <form class="p-6" @submit.prevent="download">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 :id="titleId" class="text-lg font-semibold">Export laporan {{ kategori }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Seluruh {{ total }} laporan sesuai filter akan diekspor beserta fotonya, termasuk data di halaman lain.</p>
                    </div>
                    <button type="button" aria-label="Tutup pengaturan ekspor" :disabled="busy" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl hover:bg-slate-100 disabled:opacity-50" @click="dialog.close()">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" /></svg>
                    </button>
                </div>
                <p class="mt-4 text-sm text-slate-600">Lengkapi bagian surat bila diperlukan. Isian ini hanya digunakan untuk file yang diunduh.</p>
                <fieldset :disabled="busy" class="mt-4 grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-1.5 text-sm font-medium sm:col-span-2"><span>Nomor laporan (opsional)</span><input v-model="form.nomor_laporan" autofocus maxlength="150" :class="inputClass" /></label>
                    <label class="grid gap-1.5 text-sm font-medium sm:col-span-2"><span>Hasil pemeriksaan (opsional)</span><textarea v-model="form.hasil_pemeriksaan" rows="3" maxlength="2000" :class="textareaClass" /></label>
                    <label class="grid gap-1.5 text-sm font-medium sm:col-span-2"><span>Usulan / saran (opsional)</span><textarea v-model="form.usulan" rows="3" maxlength="2000" :class="textareaClass" /></label>
                    <label class="grid gap-1.5 text-sm font-medium"><span>Tempat (opsional)</span><input v-model="form.tempat" maxlength="100" :class="inputClass" /></label>
                    <label class="grid gap-1.5 text-sm font-medium"><span>Tanggal surat</span><input v-model="form.tanggal_cetak" type="date" required :class="inputClass" /></label>
                    <label class="grid gap-1.5 text-sm font-medium"><span>Nama penandatangan (opsional)</span><input v-model="form.penandatangan" maxlength="150" :class="inputClass" /></label>
                    <label class="grid gap-1.5 text-sm font-medium"><span>Jabatan (opsional)</span><input v-model="form.jabatan" maxlength="150" :class="inputClass" /></label>
                </fieldset>
                <p v-if="error" class="mt-4 text-sm text-rose-600" role="alert">{{ error }}</p>
                <div class="mt-6 flex flex-wrap justify-end gap-3">
                    <button type="button" :disabled="busy" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="dialog.close()">Batal</button>
                    <button type="submit" :disabled="busy" class="min-h-11 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-wait disabled:opacity-60">{{ busy ? 'Menyiapkan Excel dan foto...' : 'Unduh Excel' }}</button>
                </div>
            </form>
        </dialog>
    </Teleport>
</template>

<script setup>
import axios from 'axios';
import { onBeforeUnmount, reactive, ref } from 'vue';

const props = defineProps({ kategori: { type: String, required: true }, params: { type: Object, required: true }, total: { type: Number, default: 0 } });
const dialog = ref(null);
const busy = ref(false);
const error = ref('');
const titleId = `export-${props.kategori}-title`;
const today = new Date();
const form = reactive({ nomor_laporan: '', hasil_pemeriksaan: '', usulan: '', tempat: '', tanggal_cetak: `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`, penandatangan: '', jabatan: '' });
const inputClass = 'h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-normal outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
const textareaClass = 'w-full rounded-xl border border-slate-300 bg-white p-3 text-sm font-normal outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
const open = () => { error.value = ''; dialog.value.showModal(); dialog.value.scrollTop = 0; };
const onCancel = (event) => { if (busy.value) event.preventDefault(); };
let controller;
onBeforeUnmount(() => controller?.abort());
const download = async () => {
    if (busy.value) return;
    busy.value = true;
    error.value = '';
    controller = new AbortController();
    try {
        const response = await axios.post(`/api/laporan-${props.kategori}/export`, { ...props.params, ...form }, { responseType: 'blob', signal: controller.signal });
        const url = window.URL.createObjectURL(response.data);
        const link = document.createElement('a');
        link.href = url;
        link.download = response.headers['content-disposition']?.match(/filename="?([^";]+)"?/i)?.[1] ?? `laporan-${props.kategori}.xlsx`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => window.URL.revokeObjectURL(url), 1000);
        dialog.value?.close();
    } catch (exception) {
        if (axios.isCancel(exception)) return;
        let payload = exception.response?.data;
        if (payload instanceof Blob) {
            try { payload = JSON.parse(await payload.text()); } catch { payload = null; }
        }
        error.value = Object.values(payload?.errors ?? {}).flat()[0] ?? payload?.message ?? 'Gagal mengunduh Excel. Silakan coba kembali.';
    } finally {
        busy.value = false;
    }
};
</script>
