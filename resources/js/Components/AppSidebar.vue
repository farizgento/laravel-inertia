<template>
    <aside class="flex h-screen w-full flex-col overflow-hidden border-r border-slate-200 bg-white/95">
        <div class="border-b border-slate-200 px-4 py-4">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-sky-400 text-white shadow-lg shadow-blue-200"
                >
                    <svg
                        class="h-5 w-5"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="M13 2L3 14h7l-1 8 10-12h-7l1-8z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-sm font-semibold text-slate-900">ToolArea</h1>
                    <p class="text-xs text-slate-500">Lending System</p>
                </div>
            </div>
        </div>

        <div class="flex min-h-0 flex-1 flex-col">
            <div class="sidebar-scroll min-h-0 flex-1 overflow-y-auto px-3 py-4 pr-2">
                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-400">Menu</p>
                <nav class="mt-3 space-y-1.5 pb-4">
                    <template v-for="item in menuItems" :key="item.key">
                        <div v-if="item.children?.length" class="space-y-1">
                            <button
                                :class="navClass(item)"
                                type="button"
                                @click="toggleGroup(item.key)"
                            >
                                <span
                                    :class="[
                                        'flex h-9 w-9 items-center justify-center rounded-xl',
                                        iconWrapperClass(item),
                                    ]"
                                >
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect x="3" y="4" width="18" height="16" rx="2" />
                                        <path d="M7 8h10M7 12h6M7 16h4" />
                                    </svg>
                                </span>
                                <span class="min-w-0 flex-1 text-left text-sm font-semibold leading-5 text-slate-700">
                                    {{ item.label }}
                                </span>
                                <span class="ml-2 shrink-0 text-slate-400 transition" :class="isGroupOpen(item) ? 'rotate-90' : ''">
                                    <svg
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <path d="m9 6 6 6-6 6" />
                                    </svg>
                                </span>
                            </button>
                            <div v-show="isGroupOpen(item)" class="space-y-1 pl-2">
                                <Link
                                    v-for="child in item.children"
                                    :key="child.key"
                                    :href="child.href"
                                    :class="childNavClass(child)"
                                    @click="emit('navigate')"
                                >
                                    <span
                                        class="ml-3 h-1.5 w-1.5 rounded-full"
                                        :class="isItemActive(child) ? 'bg-blue-600' : 'bg-slate-300'"
                                    ></span>
                                    <span class="min-w-0 flex-1 text-sm font-semibold leading-5 text-slate-700">{{ child.label }}</span>
                                    <span
                                        v-if="shouldShowReviewBadge(child)"
                                        class="ml-2 inline-flex min-w-[1.5rem] shrink-0 items-center justify-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold leading-none text-rose-700"
                                    >
                                        {{ formatBadgeCount(badgeCountForItem(child)) }}
                                    </span>
                                </Link>
                            </div>
                        </div>
                        <Link
                            v-else
                            :href="item.href"
                            :class="navClass(item)"
                            @click="emit('navigate')"
                        >
                            <span
                                :class="[
                                    'flex h-9 w-9 items-center justify-center rounded-xl',
                                    iconWrapperClass(item),
                                ]"
                            >
                                <svg
                                    v-if="item.icon === 'dashboard'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect x="3" y="3" width="8" height="8" rx="2" />
                                    <rect x="13" y="3" width="8" height="8" rx="2" />
                                    <rect x="13" y="13" width="8" height="8" rx="2" />
                                    <rect x="3" y="13" width="8" height="8" rx="2" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'master-alat'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M14.7 6.3a4 4 0 0 0-5.4 5.4l-6.6 6.6a2 2 0 0 0 2.8 2.8l6.6-6.6a4 4 0 0 0 5.4-5.4l-3 3-2.8-2.8 3-3Z" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'peminjaman'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <rect x="3" y="4" width="18" height="16" rx="2" />
                                    <path d="M7 8h10M7 12h6M7 16h4" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'pengiriman'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M3 7h11v10H3z" />
                                    <path d="M14 10h4l3 3v4h-7z" />
                                    <circle cx="7.5" cy="19" r="1.5" />
                                    <circle cx="17.5" cy="19" r="1.5" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'mutasi-alat'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M16 3l4 4-4 4" />
                                    <path d="M20 7H4" />
                                    <path d="M8 21l-4-4 4-4" />
                                    <path d="M4 17h16" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'riwayat'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v5l3 3" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'kerusakan'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M10.3 4.3 2.9 17.1a2 2 0 0 0 1.7 3h14.8a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z" />
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'kehilangan'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M3 7h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Z" />
                                    <path d="M3 7l4-4h10l4 4" />
                                    <path d="m8 12 8 8" />
                                    <path d="m16 12-8 8" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'area'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M3 6 9 3l6 3 6-3v15l-6 3-6-3-6 3z" />
                                    <path d="M9 3v15" />
                                    <path d="M15 6v15" />
                                </svg>
                                <svg
                                    v-else-if="item.icon === 'users'"
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                <svg
                                    v-else
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M12 2v20M2 12h20" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1 text-sm font-semibold leading-5 text-slate-700">{{ item.label }}</span>
                            <span
                                v-if="shouldShowReviewBadge(item)"
                                class="ml-2 inline-flex min-w-[1.5rem] shrink-0 items-center justify-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold leading-none text-rose-700"
                            >
                                {{ formatBadgeCount(badgeCountForItem(item)) }}
                            </span>
                            <span :class="shouldShowReviewBadge(item) ? 'ml-2 shrink-0 text-slate-400' : 'ml-2 shrink-0 text-slate-400'">
                                <svg
                                    class="h-4 w-4"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="m9 6 6 6-6 6" />
                                </svg>
                            </span>
                        </Link>
                    </template>
                </nav>
            </div>

            <div class="mt-auto border-t border-slate-200 px-4 py-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-sm font-semibold text-blue-600"
                    >
                        {{ initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">{{ displayName }}</p>
                        <p class="truncate text-xs text-slate-500">
                            {{ displayArea }} | {{ displayRole }}
                        </p>
                    </div>
                </div>
                <button
                    class="mt-4 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    type="button"
                    @click="emit('logout')"
                >
                    <span class="flex items-center justify-start gap-2">
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4" />
                            <path d="M10 17l5-5-5-5" />
                            <path d="M15 12H3" />
                        </svg>
                        Keluar
                    </span>
                </button>
            </div>
        </div>
    </aside>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    activeMenu: {
        type: String,
        default: 'dashboard',
    },
    displayName: {
        type: String,
        default: 'Pengguna',
    },
    displayArea: {
        type: String,
        default: 'Area',
    },
    displayRole: {
        type: String,
        default: 'User',
    },
    initials: {
        type: String,
        default: 'U',
    },
    roleKey: {
        type: String,
        default: '',
    },
    reviewPendingCount: {
        type: Number,
        default: 0,
    },
    kerusakanPendingCount: {
        type: Number,
        default: 0,
    },
    kehilanganPendingCount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(['logout', 'navigate']);
const page = usePage();

const menuItems = computed(() => {
    const roleKey = props.roleKey?.toLowerCase() ?? 'user';
    const isSpTool = roleKey === 'sp_tool';
    const isPicTools = roleKey === 'pic_tools' || roleKey === 'pic_tool';
    const isMgrTool = roleKey === 'mgr_tool';
    const isSuperAdmin = roleKey === 'super_admin';
    const isAdmin = roleKey === 'admin';

    const items = [
        ...(isSuperAdmin
            ? [
                  {
                      key: 'dashboard',
                      label: 'Dashboard',
                      href: '/dashboard',
                      icon: 'dashboard',
                  },
                  {
                      key: 'tambah-pengguna',
                      label: 'Pengguna',
                      href: '/tambah-pengguna',
                      icon: 'users',
                  },
                  {
                      key: 'area',
                      label: 'Area',
                      href: '/area',
                      icon: 'area',
                  },
                  {
                      key: 'peminjaman',
                      label: 'Peminjaman',
                      href: '/peminjaman',
                      icon: 'peminjaman',
                  },
                  {
                      key: 'review',
                      label: 'Review Peminjaman',
                      href: '/review-peminjaman',
                      icon: 'riwayat',
                  },
                  {
                      key: 'master-alat',
                      label: 'Master Alat',
                      href: '/master-alat',
                      icon: 'master-alat',
                  },
                  {
                      key: 'mutasi-alat',
                      label: 'Mutasi Alat',
                      href: '/mutasi-alat',
                      icon: 'mutasi-alat',
                  },
                  {
                      key: 'riwayat',
                      label: 'Riwayat Peminjaman',
                      href: '/riwayat-peminjaman',
                      icon: 'riwayat',
                  },
                  {
                      key: 'laporan-kerusakan',
                      label: 'Laporan Kerusakan',
                      href: '/laporan-kerusakan',
                      icon: 'kerusakan',
                  },
                  {
                      key: 'laporan-kehilangan',
                      label: 'Laporan Kehilangan',
                      href: '/laporan-kehilangan',
                      icon: 'kehilangan',
                  },
              ]
            : isAdmin
            ? [
                  {
                      key: 'dashboard',
                      label: 'Dashboard',
                      href: '/dashboard',
                      icon: 'dashboard',
                  },
                  {
                      key: 'tambah-pengguna',
                      label: 'Tambah Pengguna',
                      href: '/tambah-pengguna',
                      icon: 'users',
                  },
                  {
                      key: 'pengiriman',
                      label: 'Pengiriman',
                      href: '/pengiriman-alat',
                      icon: 'pengiriman',
                  },
                  {
                      key: 'riwayat-pengiriman',
                      label: 'Riwayat Pengiriman',
                      href: '/riwayat-pengiriman',
                      icon: 'riwayat',
                  },
                  {
                      key: 'riwayat',
                      label: 'Riwayat Peminjaman',
                      href: '/riwayat-peminjaman',
                      icon: 'riwayat',
                  },
                  {
                      key: 'laporan-kehilangan',
                      label: 'Laporan Kehilangan',
                      href: '/laporan-kehilangan',
                      icon: 'kehilangan',
                  },
                  {
                      key: 'laporan-kerusakan',
                      label: 'Laporan Kerusakan',
                      href: '/laporan-kerusakan',
                      icon: 'kerusakan',
                  },
              ]
            : isPicTools
            ? [
                  {
                      key: 'dashboard',
                      label: 'Dashboard',
                      href: '/dashboard',
                      icon: 'dashboard',
                  },
                  {
                      key: 'master-alat',
                      label: 'Master Alat',
                      href: '/master-alat',
                      icon: 'master-alat',
                  },
                  {
                      key: 'pengiriman',
                      label: 'Pengiriman',
                      href: '/pengiriman-alat',
                      icon: 'pengiriman',
                  },
                  {
                      key: 'riwayat-pengiriman',
                      label: 'Riwayat Pengiriman',
                      href: '/riwayat-pengiriman',
                      icon: 'riwayat',
                  },
                  {
                      key: 'riwayat',
                      label: 'Riwayat Peminjaman',
                      href: '/riwayat-peminjaman',
                      icon: 'riwayat',
                  },
                  {
                      key: 'laporan-kerusakan',
                      label: 'Laporan Kerusakan',
                      href: '/laporan-kerusakan',
                      icon: 'kerusakan',
                  },
                  {
                      key: 'laporan-kehilangan',
                      label: 'Laporan Kehilangan',
                      href: '/laporan-kehilangan',
                      icon: 'kehilangan',
                  },
              ]
            : isMgrTool
              ? [
                    {
                        key: 'dashboard',
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: 'dashboard',
                    },
                    {
                        key: 'review',
                        label: 'Review Peminjaman',
                        href: '/review-peminjaman',
                        icon: 'riwayat',
                    },
                    {
                        key: 'master-alat',
                        label: 'Master Alat',
                        href: '/master-alat',
                        icon: 'master-alat',
                    },
                    {
                        key: 'mutasi-alat',
                        label: 'Mutasi Alat',
                        href: '/mutasi-alat',
                        icon: 'mutasi-alat',
                    },
                    {
                        key: 'riwayat',
                        label: 'Riwayat Peminjaman',
                        href: '/riwayat-peminjaman',
                        icon: 'riwayat',
                    },
                    {
                        key: 'laporan-kerusakan',
                        label: 'Daftar Kerusakan',
                        href: '/laporan-kerusakan',
                        icon: 'kerusakan',
                    },
                    {
                        key: 'laporan-kehilangan',
                        label: 'Daftar Kehilangan',
                        href: '/laporan-kehilangan',
                        icon: 'kehilangan',
                    },
                ]
              : [
                    {
                        key: 'dashboard',
                        label: 'Dashboard',
                        href: '/dashboard',
                        icon: 'dashboard',
                    },
                    ...(isSpTool
                        ? [
                              {
                                  key: 'review',
                                  label: 'Review Peminjaman',
                                  href: '/review-peminjaman',
                                  icon: 'riwayat',
                              },
                              {
                                  key: 'master-alat',
                                  label: 'Master Alat',
                                  href: '/master-alat',
                                  icon: 'master-alat',
                              },
                              {
                                  key: 'riwayat-pengiriman',
                                  label: 'Riwayat Pengiriman',
                                  href: '/riwayat-pengiriman',
                                  icon: 'riwayat',
                              },
                              {
                                  key: 'riwayat',
                                  label: 'Riwayat Peminjaman',
                                  href: '/riwayat-peminjaman',
                                  icon: 'riwayat',
                              },
                              {
                                  key: 'laporan-kerusakan',
                                  label: 'Laporan Kerusakan',
                                  href: '/laporan-kerusakan',
                                  icon: 'kerusakan',
                              },
                              {
                                  key: 'laporan-kehilangan',
                                  label: 'Laporan Kehilangan',
                                  href: '/laporan-kehilangan',
                                  icon: 'kehilangan',
                              },
                          ]
                        : [
                              {
                                  key: 'peminjaman',
                                  label: 'Buat Peminjaman',
                                  href: '/peminjaman',
                                  icon: 'peminjaman',
                              },
                              
                              {
                                  key: 'mutasi-alat',
                                  label: 'Mutasi Alat',
                                  href: '/mutasi-alat',
                                  icon: 'mutasi-alat',
                              },
                              {
                                  key: 'riwayat',
                                  label: 'Riwayat Peminjaman',
                                  href: '/riwayat-peminjaman',
                                  icon: 'riwayat',
                              },
                          ]),
                ]),
    ];

    return items;
});

const openGroups = ref({});

const normalizePath = (value) => {
    const raw = String(value ?? '').split('?')[0].split('#')[0].trim();
    if (!raw) {
        return '/';
    }

    if (raw === '/') {
        return raw;
    }

    return raw.endsWith('/') ? raw.slice(0, -1) : raw;
};

const currentPath = computed(() => normalizePath(page.url));

const isHrefActive = (item) => {
    const itemPath = normalizePath(item?.href);
    if (!itemPath) {
        return false;
    }

    if (currentPath.value === itemPath) {
        return true;
    }

    return itemPath !== '/' && currentPath.value.startsWith(`${itemPath}/`);
};

const isItemActive = (item) => {
    if (!item) {
        return false;
    }
    if (item.key === props.activeMenu) {
        return true;
    }
    if (isHrefActive(item)) {
        return true;
    }
    if (Array.isArray(item.children)) {
        return item.children.some((child) => child.key === props.activeMenu || isHrefActive(child));
    }
    return false;
};

const isGroupOpen = (item) => {
    const key = item?.key ?? '';
    if (!key) {
        return false;
    }
    if (openGroups.value[key] === undefined) {
        return isItemActive(item);
    }
    return openGroups.value[key];
};

const toggleGroup = (key) => {
    const item = menuItems.value.find((entry) => entry.key === key);
    const next = !isGroupOpen(item);
    openGroups.value = {
        ...openGroups.value,
        [key]: next,
    };
};

const badgeCountForItem = (item) => {
    switch (item?.key) {
        case 'review':
            return Number(props.reviewPendingCount ?? 0);
        case 'laporan-kerusakan':
            return Number(props.kerusakanPendingCount ?? 0);
        case 'laporan-kehilangan':
            return Number(props.kehilanganPendingCount ?? 0);
        default:
            return 0;
    }
};

const shouldShowReviewBadge = (item) => badgeCountForItem(item) > 0;

const formatBadgeCount = (count) => {
    const normalized = Number(count ?? 0);
    if (!Number.isFinite(normalized) || normalized <= 0) {
        return '0';
    }

    return normalized > 99 ? '99+' : String(normalized);
};

const navClass = (item) => {
    const base =
        'flex min-h-[56px] items-center gap-3 rounded-2xl px-3 py-2.5 text-sm font-semibold transition';
    return `${base} ${
        isItemActive(item) ? 'bg-blue-50 text-blue-700 shadow-sm shadow-blue-100/70' : 'text-slate-600 hover:bg-slate-100'
    }`;
};

const childNavClass = (item) => {
    const base =
        'flex min-h-[48px] items-center gap-3 rounded-xl px-3 py-2 text-sm font-semibold transition';
    return `${base} ${
        isItemActive(item) ? 'bg-blue-50 text-blue-700' : 'text-slate-600 hover:bg-slate-100'
    }`;
};

const iconWrapperClass = (item) =>
    isItemActive(item) ? 'bg-blue-600 text-white shadow-lg shadow-blue-200/80' : 'bg-slate-100 text-blue-600';
</script>

<style scoped>
.sidebar-scroll {
    scrollbar-gutter: stable;
}

.sidebar-scroll::-webkit-scrollbar {
    width: 8px;
}

.sidebar-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.sidebar-scroll::-webkit-scrollbar-thumb {
    border-radius: 9999px;
    background-color: rgba(148, 163, 184, 0.55);
}

.sidebar-scroll::-webkit-scrollbar-thumb:hover {
    background-color: rgba(100, 116, 139, 0.7);
}
</style>
