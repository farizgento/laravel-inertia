<template>
    <Head title="Pilih Peminjaman Tools | IPTOOLS" />

    <main class="landing-shell min-h-screen overflow-hidden bg-[#eef3f8] text-slate-950">
        <div class="ambient-grid"></div>
        <div class="relative mx-auto flex min-h-screen w-full max-w-6xl flex-col px-5 py-6 sm:px-8 lg:px-10">
            <header class="flex items-center justify-between gap-4 py-2">
                <a href="/" class="group inline-flex items-center gap-3" aria-label="IPTOOLS Lite">
                    <img src="http://192.168.90.68:1708/LOGO/logomsu.png" alt="logo" width="200">
                </a>
            </header>

            <section class="grid flex-1 items-center gap-10 py-10 lg:grid-cols-[0.92fr_1.08fr] lg:py-14">
                <div class="max-w-xl">
                    <div
                        class="inline-flex items-center gap-2 rounded-md border border-blue-200 bg-white/70 px-3 py-1.5 text-xs font-semibold text-blue-700 shadow-sm"
                    >
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Portal peminjaman alat
                    </div>

                    <h1 class="mt-6 text-4xl font-bold leading-tight text-slate-950 sm:text-5xl">
                        Pilih layanan peminjaman tools
                    </h1>
                    <p class="mt-5 max-w-lg text-base leading-7 text-slate-600">
                        Akses Special Tools untuk peminjaman alat khusus, atau masuk ke General Tools untuk
                         kebutuhan operasional area.
                    </p>
                </div>

                <div class="relative">
                    <div class="selector-panel rounded-lg border border-white/80 bg-white/80 p-4 shadow-2xl shadow-slate-300/50 backdrop-blur sm:p-5">
                        <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-4">
                            <div>
                                <p class="text-xs font-semibold uppercase text-slate-500">Pilih tujuan</p>
                                <h2 class="mt-1 text-lg font-bold text-slate-950">Peminjaman Tools</h2>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            </div>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <a
                                v-for="tool in toolOptions"
                                :key="tool.title"
                                :href="tool.href"
                                :target="tool.external ? '_blank' : undefined"
                                :rel="tool.external ? 'noopener noreferrer' : undefined"
                                class="tool-card group relative overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-blue-300 hover:shadow-xl hover:shadow-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                <span class="card-sheen"></span>
                                <span
                                    class="flex h-12 w-12 items-center justify-center rounded-lg transition duration-300"
                                    :class="tool.iconClass"
                                >
                                    <svg
                                        v-if="tool.type === 'general'"
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.4 2.4-2.8-2.8 2.2-2.6Z" />
                                    </svg>
                                    <svg
                                        v-else
                                        class="h-6 w-6"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="M4 14a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v3a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3v-3Z" />
                                        <path d="M8 10V8a4 4 0 0 1 8 0v2" />
                                        <path d="M12 14v2" />
                                    </svg>
                                </span>

                                <div class="mt-5">
                                    <p class="text-xs font-semibold uppercase" :class="tool.eyebrowClass">
                                        {{ tool.eyebrow }}
                                    </p>
                                    <h3 class="mt-2 text-xl font-bold text-slate-950">{{ tool.title }}</h3>
                                    <p class="mt-3 min-h-16 text-sm leading-6 text-slate-600">{{ tool.description }}</p>
                                </div>

                                <div class="mt-6 flex items-center justify-between gap-3">
                                    <span class="text-sm font-semibold text-slate-900">{{ tool.action }}</span>
                                    <span
                                        class="flex h-9 w-9 items-center justify-center rounded-md bg-slate-950 text-white transition group-hover:translate-x-1"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M5 12h14" />
                                            <path d="m13 6 6 6-6 6" />
                                        </svg>
                                    </span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </section>

            <footer class="flex flex-col gap-2 border-t border-slate-200 py-5 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                <span>2026 Tool Area Lending. Semua hak cipta dilindungi.</span>
                <span>General Tools dan Special Tools dalam satu pintu akses.</span>
            </footer>
        </div>
    </main>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';

const toolOptions = [
    {
        type: 'general',
        eyebrow: 'General Tools',
        title: 'Peminjaman General Tools',
        description: 'Buka portal untuk kebutuhan peminjaman General Tools.',
        action: 'Buka General Tools',
        href: '/login',
        external: false,
        iconClass: 'bg-blue-50 text-blue-700 group-hover:bg-blue-600 group-hover:text-white',
        eyebrowClass: 'text-blue-700',
    },
    {
        type: 'special',
        eyebrow: 'Special Tools',
        title: 'Peminjaman Special Tools',
        description: 'Buka portal untuk kebutuhan peminjaman Special Tools.',
        action: 'Buka Special Tools',
        href: 'http://192.168.90.68:1004/',
        external: false,
        iconClass: 'bg-emerald-50 text-emerald-700 group-hover:bg-emerald-600 group-hover:text-white',
        eyebrowClass: 'text-emerald-700',
    },
];

const trustPoints = ['Akses cepat', 'Alur peminjaman jelas', 'Dua portal terhubung'];

const stats = [
    { value: '01', label: 'Portal General Tools' },
    { value: '02', label: 'Portal Special Tools' },
    { value: '24/7', label: 'Akses jaringan internal' },
];
</script>

<style scoped>
.landing-shell {
    position: relative;
    isolation: isolate;
}

.ambient-grid {
    position: absolute;
    inset: 0;
    z-index: -1;
    background:
        linear-gradient(rgba(15, 23, 42, 0.055) 1px, transparent 1px),
        linear-gradient(90deg, rgba(15, 23, 42, 0.055) 1px, transparent 1px),
        radial-gradient(circle at 12% 18%, rgba(37, 99, 235, 0.2), transparent 30%),
        radial-gradient(circle at 88% 72%, rgba(16, 185, 129, 0.16), transparent 32%);
    background-size: 42px 42px, 42px 42px, auto, auto;
}

.selector-panel {
    animation: panel-rise 650ms ease-out both;
}

.tool-card {
    animation: card-in 520ms ease-out both;
}

.tool-card:nth-child(2) {
    animation-delay: 90ms;
}

.card-sheen {
    position: absolute;
    inset: 0;
    pointer-events: none;
    transform: translateX(-120%);
    background: linear-gradient(110deg, transparent 35%, rgba(255, 255, 255, 0.72) 50%, transparent 65%);
    transition: transform 700ms ease;
}

.tool-card:hover .card-sheen {
    transform: translateX(120%);
}

@keyframes panel-rise {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes card-in {
    from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@media (prefers-reduced-motion: reduce) {
    .selector-panel,
    .tool-card,
    .card-sheen {
        animation: none;
        transition: none;
    }
}
</style>
