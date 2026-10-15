<template>
    <div class="flex items-center gap-4" :class="tataLetak === 'bawah' ? 'flex-col' : 'flex-col sm:flex-row'">
        <div class="relative shrink-0" :class="kelasUkuran">
            <svg viewBox="0 0 120 120" class="h-full w-full -rotate-90" role="img" :aria-label="ariaLabel">
                <circle cx="60" cy="60" r="52" fill="none" stroke="#e2e8f0" :stroke-width="tebal" />
                <circle
                    v-for="segmen in segmenTerhitung"
                    :key="segmen.key"
                    cx="60"
                    cy="60"
                    r="52"
                    fill="none"
                    :stroke="segmen.warna"
                    :stroke-width="aktif === segmen.key ? tebal + 5 : tebal"
                    :stroke-dasharray="`${segmen.panjang} ${keliling - segmen.panjang}`"
                    :stroke-dashoffset="-segmen.offset"
                    class="cursor-pointer transition-all duration-300"
                    :class="aktif && aktif !== segmen.key ? 'opacity-25' : 'opacity-100'"
                    @mouseenter="aktif = segmen.key"
                    @mouseleave="aktif = null"
                >
                    <title>{{ segmen.label }}: {{ formatAngka(segmen.nilai) }} ({{ segmen.persen }}%)</title>
                </circle>
            </svg>

            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-4 text-center">
                <p class="font-semibold text-slate-900" :class="ringkas ? 'text-lg' : 'text-2xl'">
                    {{ aktif ? formatAngka(segmenAktif?.nilai ?? 0) : formatAngka(nilaiPusat) }}
                </p>
                <p class="mt-0.5 font-semibold uppercase tracking-[0.18em] text-slate-400" :class="ringkas ? 'text-[9px]' : 'text-[11px]'">
                    {{ aktif ? segmenAktif?.label : labelPusat }}
                </p>
            </div>
        </div>

        <ul class="w-full" :class="ringkas ? 'space-y-1' : 'space-y-1.5'">
            <li
                v-for="segmen in segmenTerhitung"
                :key="`legenda-${segmen.key}`"
                class="flex cursor-pointer items-center justify-between gap-3 rounded-lg transition"
                :class="[
                    ringkas ? 'px-2 py-1' : 'rounded-xl px-3 py-2',
                    aktif === segmen.key ? 'bg-slate-100' : 'hover:bg-slate-50',
                ]"
                @mouseenter="aktif = segmen.key"
                @mouseleave="aktif = null"
            >
                <span class="flex min-w-0 items-center gap-2">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: segmen.warna }"></span>
                    <span class="truncate text-slate-600" :class="ringkas ? 'text-xs' : 'text-sm'">{{ segmen.label }}</span>
                </span>
                <span class="shrink-0 font-semibold text-slate-900" :class="ringkas ? 'text-xs' : 'text-sm'">
                    {{ formatAngka(segmen.nilai) }}
                    <span class="ml-1 font-medium text-slate-400" :class="ringkas ? 'text-[10px]' : 'text-xs'">
                        {{ segmen.persen }}%
                    </span>
                </span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    /**
     * Daftar bagian donat: { key, label, nilai, warna }.
     */
    segmen: {
        type: Array,
        default: () => [],
    },
    labelPusat: {
        type: String,
        default: 'Total',
    },
    // Angka di tengah donat; bila kosong memakai jumlah seluruh segmen.
    nilaiPusatProp: {
        type: Number,
        default: null,
    },
    tebal: {
        type: Number,
        default: 16,
    },
    ariaLabel: {
        type: String,
        default: 'Grafik donat',
    },
    // Kelas ukuran lingkaran, mis. 'h-28 w-28' untuk versi kecil di kartu.
    kelasUkuran: {
        type: String,
        default: 'h-44 w-44',
    },
    // Versi ringkas dipakai di dalam kartu ringkasan yang ruangnya sempit.
    ringkas: {
        type: Boolean,
        default: false,
    },
    tataLetak: {
        type: String,
        default: 'samping',
    },
});

const aktif = ref(null);
const keliling = 2 * Math.PI * 52;

const total = computed(() =>
    props.segmen.reduce((carry, item) => carry + Math.max(0, Number(item?.nilai ?? 0)), 0)
);

const nilaiPusat = computed(() => (props.nilaiPusatProp === null ? total.value : props.nilaiPusatProp));

const segmenTerhitung = computed(() => {
    const jumlah = total.value;
    let offset = 0;

    return props.segmen.map((item) => {
        const nilai = Math.max(0, Number(item?.nilai ?? 0));
        const bagian = jumlah > 0 ? nilai / jumlah : 0;
        const panjang = bagian * keliling;
        const hasil = {
            key: item.key,
            label: item.label,
            nilai,
            warna: item.warna,
            panjang,
            offset,
            persen: jumlah > 0 ? Math.round(bagian * 1000) / 10 : 0,
        };

        offset += panjang;

        return hasil;
    });
});

const segmenAktif = computed(() => segmenTerhitung.value.find((item) => item.key === aktif.value) ?? null);

const formatAngka = (nilai) => new Intl.NumberFormat('id-ID').format(nilai ?? 0);
</script>
