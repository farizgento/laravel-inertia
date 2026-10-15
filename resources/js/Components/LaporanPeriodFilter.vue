<template>
    <form class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4" @submit.prevent="apply">
        <div class="flex flex-wrap items-end gap-3">
            <label class="grid w-full gap-1.5 text-sm font-medium text-slate-700 sm:w-48">
                <span>Periode pelaporan</span>
                <select v-model="draft.period" :class="inputClass">
                    <option value="all">Semua tanggal</option>
                    <option value="monthly">Bulanan</option>
                    <option value="yearly">Tahunan</option>
                    <option value="custom">Rentang khusus</option>
                </select>
            </label>
            <label v-if="draft.period === 'monthly'" class="grid w-full gap-1.5 text-sm font-medium text-slate-700 sm:w-44">
                <span>Bulan</span>
                <select v-model.number="draft.month" :class="inputClass">
                    <option v-for="(month, index) in months" :key="month" :value="index + 1">{{ month }}</option>
                </select>
            </label>
            <label v-if="['monthly', 'yearly'].includes(draft.period)" class="grid w-full gap-1.5 text-sm font-medium text-slate-700 sm:w-32">
                <span>Tahun</span>
                <input v-model.number="draft.year" type="number" min="1900" max="2100" required :class="inputClass" />
            </label>
            <template v-if="draft.period === 'custom'">
                <label class="grid w-full gap-1.5 text-sm font-medium text-slate-700 sm:w-44">
                    <span>Dari tanggal</span>
                    <input v-model="draft.date_from" type="date" required :max="draft.date_to || undefined" :class="inputClass" />
                </label>
                <label class="grid w-full gap-1.5 text-sm font-medium text-slate-700 sm:w-44">
                    <span>Sampai tanggal</span>
                    <input v-model="draft.date_to" type="date" required :min="draft.date_from || undefined" :class="inputClass" />
                </label>
            </template>
            <button type="submit" class="h-11 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white transition hover:bg-blue-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">Terapkan</button>
            <button type="button" class="h-11 rounded-xl border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-blue-600" @click="reset">Reset periode</button>
        </div>
        <p class="mt-3 text-xs text-slate-500" aria-live="polite">Filter aktif: {{ activeLabel }}. Berlaku untuk tabel dan ekspor Excel berdasarkan tanggal pelaporan.</p>
    </form>
</template>

<script setup>
import { computed, reactive } from 'vue';

const props = defineProps({ modelValue: { type: Object, required: true } });
const emit = defineEmits(['update:modelValue']);
const now = new Date();
const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
const draft = reactive({ period: 'all', year: now.getFullYear(), month: now.getMonth() + 1, date_from: today, date_to: today, ...props.modelValue });
const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const inputClass = 'h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100';
const formatDate = (value) => new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
const activeLabel = computed(() => {
    const filter = props.modelValue;
    if (filter.period === 'monthly') return `${months[filter.month - 1]} ${filter.year}`;
    if (filter.period === 'yearly') return `Tahun ${filter.year}`;
    if (filter.period === 'custom') return `${formatDate(filter.date_from)} – ${formatDate(filter.date_to)}`;
    return 'Semua tanggal';
});
const apply = () => {
    const filter = { period: draft.period };
    if (['monthly', 'yearly'].includes(draft.period)) filter.year = draft.year;
    if (draft.period === 'monthly') filter.month = draft.month;
    if (draft.period === 'custom') {
        if (!draft.date_from || !draft.date_to || draft.date_from > draft.date_to) return;
        filter.date_from = draft.date_from;
        filter.date_to = draft.date_to;
    }
    emit('update:modelValue', filter);
};
const reset = () => {
    draft.period = 'all';
    emit('update:modelValue', { period: 'all' });
};
</script>
