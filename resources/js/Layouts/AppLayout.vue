<template>
    <div class="relative h-screen overflow-hidden bg-slate-100">
        <Head :title="pageTitle" />
        <ToastNotification
            :open="!!flashMessage"
            :type="flashType"
            :title="flashTitle"
            :message="flashMessage"
            @close="closeFlash"
        />
        <div
            v-if="isAreaSwitching || isGlobalLoading"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40"
        >
            <div class="rounded-2xl bg-white px-5 py-4 shadow-xl">
                <div class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-50 text-blue-600">
                        <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9" class="opacity-25" />
                            <path d="M21 12a9 9 0 0 1-9 9" class="opacity-75" />
                        </svg>
                    </span>
                    <div class="text-sm font-semibold text-slate-700">{{ loadingMessage }}</div>
                </div>
                <div class="mt-3 h-2 w-56 overflow-hidden rounded-full bg-slate-200">
                    <div class="h-full w-1/2 animate-pulse rounded-full bg-blue-500/70"></div>
                </div>
            </div>
        </div>
        <div
            class="fixed inset-0 z-20 bg-slate-900/40 transition-opacity duration-200 lg:hidden"
            :class="isSidebarOpen ? 'opacity-100' : 'pointer-events-none opacity-0'"
            @click="closeSidebar"
        ></div>

        <div class="relative h-screen w-full lg:flex">
            <div
                id="app-sidebar"
                class="fixed inset-y-0 left-0 z-30 w-64 transform transition duration-200 ease-out lg:static lg:z-auto lg:overflow-hidden"
                :class="
                    isSidebarOpen
                        ? 'translate-x-0 opacity-100 lg:w-60'
                        : '-translate-x-full opacity-0 pointer-events-none lg:w-0'
                "
            >
                <AppSidebar
                    :active-menu="activeMenu"
                    :display-name="displayName"
                    :display-area="displayArea"
                    :display-role="displayRole"
                    :initials="initials"
                    :role-key="roleKey"
                    :review-pending-count="reviewPendingCount"
                    :kerusakan-pending-count="kerusakanPendingCount"
                    :kehilangan-pending-count="kehilanganPendingCount"
                    @logout="logout"
                    @navigate="closeSidebarOnMobile"
                />
            </div>

            <div class="min-w-0 flex-1 flex flex-col h-screen">
                <AppHeader
                    :title="title"
                    :subtitle="subtitle"
                    :display-area="displayArea"
                    :display-name="displayName"
                    :display-role="displayRole"
                    :areas="areas"
                    :active-area-id="activeAreaId"
                    :is-area-switcher="isAreaSwitcherRole"
                    :sidebar-open="isSidebarOpen"
                    @toggle-sidebar="toggleSidebar"
                    @change-area="handleAreaChange"
                />

                <main class="flex-1 overflow-y-auto px-6 py-6 md:px-8 md:py-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onMounted, provide, ref, watch } from 'vue';
import AppHeader from '../Components/AppHeader.vue';
import AppSidebar from '../Components/AppSidebar.vue';
import ToastNotification from '../Components/ToastNotification.vue';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
    activeMenu: {
        type: String,
        default: 'peminjaman',
    },
});

const page = usePage();
const isSidebarOpen = ref(
    typeof window !== 'undefined' &&
        window.matchMedia('(min-width: 1024px)').matches
);
const AREA_STORAGE_KEY = 'active_area_id_v1';

const areas = ref([]);
const activeAreaId = ref(null);
const isAreaSwitching = ref(false);
const globalLoadingCount = ref(0);
const requestInterceptorId = ref(null);
const responseInterceptorId = ref(null);
const reviewPendingCount = ref(0);
const kerusakanPendingCount = ref(0);
const kehilanganPendingCount = ref(0);
const isApiReady = ref(false);

const loadCachedUser = () => {
    if (typeof window === 'undefined') {
        return null;
    }
    try {
        const cached = window.localStorage.getItem('auth_user');
        return cached ? JSON.parse(cached) : null;
    } catch (err) {
        return null;
    }
};

const fetchedUser = ref(loadCachedUser());
const authUser = computed(() => page.props.auth?.user ?? fetchedUser.value);
const dismissedFlash = ref('');

const displayName = computed(() => authUser.value?.name ?? 'Memuat...');
const displayRole = computed(() => authUser.value?.role?.name ?? 'Memuat...');
const roleKey = computed(() => authUser.value?.role?.key ?? '');
const isMgrTool = computed(() => (roleKey.value ?? '').toLowerCase() === 'mgr_tool');
const isSuperAdmin = computed(() => (roleKey.value ?? '').toLowerCase() === 'super_admin');
const isAreaSwitcherRole = computed(() => isMgrTool.value || isSuperAdmin.value);
const canSeeReviewBadge = computed(() =>
    ['sp_tool', 'mgr_tool', 'super_admin'].includes((roleKey.value ?? '').toLowerCase())
);
const canSeeLaporanBadges = computed(() =>
    ['sp_tool', 'pic_tools', 'pic_tool', 'mgr_tool', 'admin', 'super_admin'].includes(
        (roleKey.value ?? '').toLowerCase()
    )
);
const flashMessage = computed(() => {
    const message = page.props.flash?.error ?? page.props.flash?.success ?? '';
    return message && message !== dismissedFlash.value ? message : '';
});
const flashType = computed(() => (page.props.flash?.error ? 'error' : 'success'));
const flashTitle = computed(() => (flashType.value === 'error' ? 'Gagal' : 'Berhasil'));

const normalizeAreaId = (value) => {
    if (value === null || value === undefined || value === '') {
        return null;
    }
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
};

const activeAreaName = computed(() => {
    if (!isAreaSwitcherRole.value) {
        return authUser.value?.area?.name ?? 'Memuat...';
    }
    const selectedId = activeAreaId.value;
    const match = areas.value.find((area) => Number(area?.id) === Number(selectedId));
    return match?.name ?? authUser.value?.area?.name ?? 'Area tidak diketahui';
});

const displayArea = computed(() =>
    isAreaSwitcherRole.value ? activeAreaName.value : authUser.value?.area?.name ?? 'Memuat...'
);
const isGlobalLoading = computed(() => globalLoadingCount.value > 0);
const loadingMessage = computed(() =>
    isAreaSwitching.value ? 'Memuat data area...' : 'Memuat data...'
);
const initials = computed(() => {
    const base = displayName.value.trim();
    if (!base) {
        return 'U';
    }
    return base
        .split(' ')
        .filter(Boolean)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
});

const pageTitle = computed(() => {
    const base = props.title?.trim();
    return base ? `${base} | ToolArea` : 'ToolArea';
});

const closeFlash = () => {
    dismissedFlash.value = flashMessage.value;
};

const setActiveArea = (nextId, shouldPersist = true) => {
    const normalized = normalizeAreaId(nextId);
    activeAreaId.value = normalized;
    if (typeof window !== 'undefined' && shouldPersist) {
        if (normalized) {
            window.localStorage.setItem(AREA_STORAGE_KEY, String(normalized));
        } else {
            window.localStorage.removeItem(AREA_STORAGE_KEY);
        }
    }
};

const setAreaSwitching = (value) => {
    isAreaSwitching.value = !!value;
};

const startGlobalLoading = () => {
    globalLoadingCount.value += 1;
};

const stopGlobalLoading = () => {
    globalLoadingCount.value = Math.max(0, globalLoadingCount.value - 1);
};

const attachLoadingInterceptors = () => {
    if (requestInterceptorId.value !== null || responseInterceptorId.value !== null) {
        return;
    }
    requestInterceptorId.value = axios.interceptors.request.use(
        (config) => {
            startGlobalLoading();
            return config;
        },
        (error) => {
            stopGlobalLoading();
            return Promise.reject(error);
        }
    );
    responseInterceptorId.value = axios.interceptors.response.use(
        (response) => {
            stopGlobalLoading();
            return response;
        },
        (error) => {
            stopGlobalLoading();
            return Promise.reject(error);
        }
    );
};

const handleAreaChange = (nextId) => {
    if (!isAreaSwitcherRole.value) {
        return;
    }
    const normalized = normalizeAreaId(nextId);
    if (normalized === activeAreaId.value) {
        setAreaSwitching(false);
        return;
    }
    setAreaSwitching(true);
    setActiveArea(normalized);
    setTimeout(() => {
        if (!isGlobalLoading.value) {
            setAreaSwitching(false);
        }
    }, 400);
};

const syncActiveArea = (preferredId = null) => {
    if (!isAreaSwitcherRole.value) {
        setActiveArea(null, false);
        return;
    }
    const availableIds = new Set(areas.value.map((area) => Number(area?.id)).filter((id) => Number.isFinite(id)));
    let next = normalizeAreaId(preferredId);
    if (!next || !availableIds.has(next)) {
        const userAreaId = normalizeAreaId(authUser.value?.area_id ?? authUser.value?.area?.id ?? null);
        if (userAreaId && availableIds.has(userAreaId)) {
            next = userAreaId;
        } else {
            next = availableIds.size ? Array.from(availableIds)[0] : null;
        }
    }
    setActiveArea(next);
};

const loadAreas = async () => {
    if (!isAreaSwitcherRole.value) {
        areas.value = [];
        return;
    }
    try {
        const response = await axios.get('/api/areas');
        areas.value = Array.isArray(response.data) ? response.data : [];
    } catch (err) {
        areas.value = [];
    } finally {
        if (typeof window !== 'undefined') {
            const stored = window.localStorage.getItem(AREA_STORAGE_KEY);
            syncActiveArea(stored);
        } else {
            syncActiveArea(null);
        }
    }
};

const refreshReviewPendingCount = async () => {
    if (!isApiReady.value) {
        return;
    }

    if (!canSeeReviewBadge.value) {
        reviewPendingCount.value = 0;
        return;
    }

    const params = {};
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }

    try {
        const response = await axios.get('/api/review-peminjaman/pending-count', { params });
        reviewPendingCount.value = Number(response.data?.count ?? 0);
    } catch (err) {
        reviewPendingCount.value = 0;
    }
};

const refreshLaporanPendingCounts = async () => {
    if (!isApiReady.value) {
        return;
    }

    if (!canSeeLaporanBadges.value) {
        kerusakanPendingCount.value = 0;
        kehilanganPendingCount.value = 0;
        return;
    }

    const params = {};
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }

    try {
        const response = await axios.get('/api/laporan-pending-counts', { params });
        kerusakanPendingCount.value = Number(response.data?.kerusakan ?? 0);
        kehilanganPendingCount.value = Number(response.data?.kehilangan ?? 0);
    } catch (err) {
        kerusakanPendingCount.value = 0;
        kehilanganPendingCount.value = 0;
    }
};

const refreshSidebarNotificationCounts = async () => {
    await Promise.all([
        refreshReviewPendingCount(),
        refreshLaporanPendingCounts(),
    ]);
};

const redirectToLogin = () => {
    window.clearAuthToken();
    router.visit('/login');
};

const cacheUser = (user) => {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        if (user) {
            window.localStorage.setItem('auth_user', JSON.stringify(user));
        } else {
            window.localStorage.removeItem('auth_user');
        }
    } catch (err) {
        // Ignore cache failures.
    }
};

const loadUser = async () => {
    try {
        const response = await axios.get('/api/user');
        fetchedUser.value = response.data;
        cacheUser(response.data);
        if (isMgrTool.value) {
            await loadAreas();
        }
    } catch (err) {
        if (err?.response?.status === 401) {
            redirectToLogin();
        }
    }
};

const logout = async () => {
    try {
        await axios.post('/api/auth/logout');
    } catch (err) {
        // Ignore logout failures and continue to clear local state.
    } finally {
        window.clearAuthToken();
        router.visit('/login');
    }
};

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const closeSidebar = () => {
    isSidebarOpen.value = false;
};

const closeSidebarOnMobile = () => {
    if (typeof window === 'undefined') {
        return;
    }
    if (window.matchMedia('(max-width: 1023px)').matches) {
        isSidebarOpen.value = false;
    }
};

provide('activeAreaId', activeAreaId);
provide('activeAreaName', activeAreaName);
provide('isMgrTool', isMgrTool);
provide('isAreaSwitcherRole', isAreaSwitcherRole);
provide('setAreaSwitching', setAreaSwitching);
provide('refreshReviewPendingCount', refreshReviewPendingCount);
provide('refreshLaporanPendingCounts', refreshLaporanPendingCounts);
provide('refreshSidebarNotificationCounts', refreshSidebarNotificationCounts);

watch(
    () => isAreaSwitcherRole.value,
    (next) => {
        if (next) {
            setAreaSwitching(true);
            loadAreas();
            setTimeout(() => {
                if (!isGlobalLoading.value) {
                    setAreaSwitching(false);
                }
            }, 600);
        } else {
            areas.value = [];
            setActiveArea(null, false);
            setAreaSwitching(false);
        }
    },
    { immediate: true }
);

watch(
    () => authUser.value?.area_id ?? authUser.value?.area?.id ?? null,
    (next) => {
        if (!isAreaSwitcherRole.value) {
            return;
        }
        if (!areas.value.length) {
            return;
        }
        syncActiveArea(next);
    }
);

watch(
    () => roleKey.value,
    () => {
        refreshSidebarNotificationCounts();
    },
    { immediate: true }
);

watch(
    () => activeAreaId.value,
    () => {
        refreshSidebarNotificationCounts();
    }
);

watch(
    () => isGlobalLoading.value,
    (next) => {
        if (!next && isAreaSwitching.value) {
            setAreaSwitching(false);
        }
    }
);

watch(
    () => [page.props.flash?.error ?? '', page.props.flash?.success ?? ''],
    () => {
        dismissedFlash.value = '';
    }
);

onMounted(() => {
    attachLoadingInterceptors();
    const token = window.localStorage.getItem('auth_token');
    if (!token) {
        redirectToLogin();
        return;
    }
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    isApiReady.value = true;
    if (!page.props.auth?.user && !fetchedUser.value) {
        fetchedUser.value = loadCachedUser();
    }
    if (!page.props.auth?.user) {
        loadUser();
    } else {
        refreshSidebarNotificationCounts();
    }
});
</script>
