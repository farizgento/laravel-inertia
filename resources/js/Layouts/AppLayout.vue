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
            v-if="isAreaSwitching || isGlobalLoadingVisible"
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
                    :pengiriman-intra-area-count="pengirimanIntraAreaCount"
                    :pengiriman-antar-area-count="pengirimanAntarAreaCount"
                    :mutasi-alat-count="mutasiAlatCount"
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
                    :mailbox-items="mailboxItems"
                    :mailbox-count="mailboxCount"
                    :show-mailbox="!isGuest"
                    @toggle-sidebar="toggleSidebar"
                    @change-area="handleAreaChange"
                />

                <main
                    :key="areaContentKey"
                    class="flex-1 overflow-y-auto px-6 py-6 md:px-8 md:py-8"
                >
                    <slot />
                </main>
            </div>
        </div>

        <div
            v-if="isSessionWarningOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/50 px-4"
        >
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-50 text-amber-600">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                            <circle cx="12" cy="12" r="9" />
                            <path d="M12 7v5l3 2" />
                        </svg>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Sesi akan berakhir</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            <template v-if="isAbsoluteSessionWarning">
                                Sesi Anda sudah mendekati batas waktu maksimal. Anda perlu masuk
                                kembali untuk melanjutkan.
                            </template>
                            <template v-else>
                                Tidak ada aktivitas beberapa saat. Sesi akan berakhir otomatis dan
                                data yang belum disimpan bisa hilang.
                            </template>
                        </p>
                    </div>
                </div>

                <div class="mt-4 rounded-xl bg-slate-50 py-3 text-center">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-400">Berakhir dalam</div>
                    <div class="mt-1 font-mono text-2xl font-semibold text-slate-900">
                        {{ sessionCountdownLabel }}
                    </div>
                </div>

                <div class="mt-5 flex gap-2">
                    <button
                        v-if="!isAbsoluteSessionWarning"
                        type="button"
                        :disabled="isExtendingSession"
                        class="flex-1 rounded-xl bg-blue-600 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                        @click="extendSession"
                    >
                        {{ isExtendingSession ? 'Memproses...' : 'Tetap masuk' }}
                    </button>
                    <button
                        type="button"
                        class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50"
                        :class="isAbsoluteSessionWarning ? 'flex-1' : ''"
                        @click="logout"
                    >
                        Keluar sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue';
import AppHeader from '../Components/AppHeader.vue';
import AppSidebar from '../Components/AppSidebar.vue';
import ToastNotification from '../Components/ToastNotification.vue';
import { loadAreas as loadSharedAreas } from '../lib/areas';

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
const SIDEBAR_BADGE_POLL_INTERVAL_MS = 30000;
const GLOBAL_LOADING_DELAY_MS = 180;
// Pemeriksaan sesi dijalankan tiap detik supaya hitungan di modal peringatan
// akurat. Isinya hanya beberapa operasi aritmetika dan tidak menulis state
// reaktif kecuali modal sedang terbuka, jadi tidak memicu render ulang.
const SESSION_CHECK_INTERVAL_MS = 1000;
// Jarak minimal antar heartbeat. Pengguna yang sedang mengisi form panjang
// tanpa mengirim request apa pun tetap dianggap aktif oleh server.
const SESSION_HEARTBEAT_MIN_GAP_MS = 5 * 60 * 1000;
// Setelah pengguna menganggur sekian lama, polling badge dihentikan agar
// server tidak melayani permintaan untuk tampilan yang tidak sedang dilihat.
const IDLE_POLLING_PAUSE_AFTER_MS = 5 * 60 * 1000;
const SESSION_ACTIVITY_EVENTS = ['pointerdown', 'pointermove', 'keydown', 'wheel', 'touchstart'];
const isSidebarOpen = ref(
    typeof window !== 'undefined' &&
        window.matchMedia('(min-width: 1024px)').matches
);
const AREA_STORAGE_KEY = 'active_area_id_v1';

const areas = ref([]);
const activeAreaId = ref(null);
const isAreaSwitching = ref(false);
const globalLoadingCount = ref(0);
const isGlobalLoadingVisible = ref(false);
const globalLoadingTimerId = ref(null);
const requestInterceptorId = ref(null);
const responseInterceptorId = ref(null);
const reviewPendingCount = ref(0);
const kerusakanPendingCount = ref(0);
const kehilanganPendingCount = ref(0);
const pengirimanIntraAreaCount = ref(0);
const pengirimanAntarAreaCount = ref(0);
const mutasiAlatCount = ref(0);
const mailboxItems = ref([]);
const mailboxCount = ref(0);
const isApiReady = ref(false);
const sidebarBadgePollingId = ref(null);
const isRefreshingSidebarCounts = ref(false);
let notificationRequestId = 0;

const isSessionWarningOpen = ref(false);
const isExtendingSession = ref(false);
const sessionWarningReason = ref('idle');
const sessionRemainingSeconds = ref(0);
const sessionCheckTimerId = ref(null);
// Disengaja bukan ref: nilai ini diperbarui pada setiap gerakan mouse, dan
// menulisnya ke state reaktif akan memicu render ulang puluhan kali per detik.
let lastActivityAt = Date.now();
let lastHeartbeatAt = Date.now();
let absoluteDeadlineAt = null;
let isSessionTerminating = false;

const fetchedUser = ref(null);
const authUser = computed(() => page.props.auth?.user ?? fetchedUser.value);
const dismissedFlash = ref('');

const displayName = computed(() => authUser.value?.name ?? 'Memuat...');
const displayRole = computed(() => authUser.value?.role?.name ?? 'Memuat...');
const roleKey = computed(() => authUser.value?.role?.key ?? '');
const isSuperAdmin = computed(() => (roleKey.value ?? '').toLowerCase() === 'super_admin');
const isGuest = computed(() => (roleKey.value ?? '').toLowerCase() === 'guest');
const isAreaSwitcherRole = computed(() => isSuperAdmin.value || isGuest.value);
const flashMessage = computed(() => {
    const message = page.props.flash?.error ?? page.props.flash?.success ?? '';
    return message && message !== dismissedFlash.value ? message : '';
});
const flashType = computed(() => (page.props.flash?.error ? 'error' : 'success'));
const flashTitle = computed(() => (flashType.value === 'error' ? 'Gagal' : 'Berhasil'));

// Angka batas waktu diambil dari server (config/session.php) agar timer di
// browser dan penegakan di server tidak pernah berbeda.
const sessionTimeoutConfig = computed(() => page.props.sessionTimeout ?? {});
const idleLimitMs = computed(() => {
    const seconds = Number(sessionTimeoutConfig.value.idle ?? -1);
    return Number.isFinite(seconds) && seconds > 0 ? seconds * 1000 : 0;
});
const sessionWarningLeadMs = computed(() => {
    const seconds = Number(sessionTimeoutConfig.value.warningLeadTime ?? 0);
    return Number.isFinite(seconds) && seconds > 0 ? seconds * 1000 : 0;
});
const isAbsoluteSessionWarning = computed(() => sessionWarningReason.value === 'absolute');
const sessionCountdownLabel = computed(() => {
    const total = Math.max(0, sessionRemainingSeconds.value);
    const minutes = Math.floor(total / 60);
    const seconds = total % 60;
    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

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
const areaContentKey = computed(() =>
    isAreaSwitcherRole.value ? `area-${activeAreaId.value ?? 'none'}` : 'default-area'
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
    if (globalLoadingCount.value === 1 && globalLoadingTimerId.value === null) {
        globalLoadingTimerId.value = window.setTimeout(() => {
            if (globalLoadingCount.value > 0) {
                isGlobalLoadingVisible.value = true;
            }
            globalLoadingTimerId.value = null;
        }, GLOBAL_LOADING_DELAY_MS);
    }
};

const stopGlobalLoading = () => {
    globalLoadingCount.value = Math.max(0, globalLoadingCount.value - 1);
    if (globalLoadingCount.value === 0) {
        if (globalLoadingTimerId.value !== null) {
            window.clearTimeout(globalLoadingTimerId.value);
            globalLoadingTimerId.value = null;
        }
        isGlobalLoadingVisible.value = false;
    }
};

const shouldAttachAreaContext = (config) => {
    if (!isAreaSwitcherRole.value || !activeAreaId.value || config?.__skipAreaContext) {
        return false;
    }

    const url = String(config?.url ?? '');
    if (!url.includes('/api/')) {
        return false;
    }

    return ![
        '/api/auth/',
        '/api/user',
        '/api/areas',
        '/api/admin/areas',
    ].some((path) => url.includes(path));
};

const attachAreaContext = (config) => {
    if (!shouldAttachAreaContext(config)) {
        return config;
    }

    config.params = {
        ...(config.params ?? {}),
        area_id: config.params?.area_id ?? activeAreaId.value,
    };

    return config;
};

const shouldShowGlobalLoading = (config) => {
    if (config?.__skipGlobalLoading) {
        return false;
    }

    if (config?.__showGlobalLoading === true) {
        return true;
    }

    const method = String(config?.method ?? 'get').toLowerCase();

    return ['post', 'put', 'patch', 'delete'].includes(method);
};

const attachLoadingInterceptors = () => {
    if (requestInterceptorId.value !== null || responseInterceptorId.value !== null) {
        return;
    }
    requestInterceptorId.value = axios.interceptors.request.use(
        (config) => {
            config = attachAreaContext(config);
            if (shouldShowGlobalLoading(config)) {
                config.__usesGlobalLoading = true;
                startGlobalLoading();
            }
            return config;
        },
        (error) => {
            if (error?.config?.__usesGlobalLoading) {
                stopGlobalLoading();
            }
            return Promise.reject(error);
        }
    );
    responseInterceptorId.value = axios.interceptors.response.use(
        (response) => {
            if (response?.config?.__usesGlobalLoading) {
                stopGlobalLoading();
            }
            captureSessionHeaders(response);
            return response;
        },
        (error) => {
            if (error?.config?.__usesGlobalLoading) {
                stopGlobalLoading();
            }
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
        areas.value = await loadSharedAreas();
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

const applyNotificationPayload = (payload) => {
    const sidebar = payload?.sidebar ?? {};
    reviewPendingCount.value = Number(sidebar.review ?? 0);
    kerusakanPendingCount.value = Number(sidebar.kerusakan ?? 0);
    kehilanganPendingCount.value = Number(sidebar.kehilangan ?? 0);
    pengirimanIntraAreaCount.value = Number(sidebar.pengiriman_intra_area ?? 0);
    pengirimanAntarAreaCount.value = Number(sidebar.pengiriman_antar_area ?? 0);
    mutasiAlatCount.value = Number(sidebar.mutasi_alat ?? 0);
    mailboxItems.value = Array.isArray(payload?.mailbox?.items) ? payload.mailbox.items : [];
    mailboxCount.value = Number(payload?.mailbox?.total ?? 0);
};

const clearNotificationPayload = () => {
    applyNotificationPayload({
        sidebar: {},
        mailbox: {
            items: [],
            total: 0,
        },
    });
};

let pendingNotificationRequest = null;
let pendingNotificationKey = '';

const refreshNotifications = async ({ silent = false, background = false } = {}) => {
    if (!isApiReady.value) {
        return;
    }

    const params = {};
    if (isAreaSwitcherRole.value && activeAreaId.value) {
        params.area_id = activeAreaId.value;
    }

    // Saat mount, onMounted dan watcher activeAreaId/roleKey bisa memicu refresh
    // hampir bersamaan. Permintaan identik yang masih berjalan cukup ditunggu
    // agar tidak mengirim request duplikat ke endpoint yang sama.
    const requestKey = String(params.area_id ?? 'all');
    if (pendingNotificationRequest && pendingNotificationKey === requestKey) {
        return pendingNotificationRequest;
    }

    const requestId = notificationRequestId + 1;
    notificationRequestId = requestId;
    isRefreshingSidebarCounts.value = true;

    pendingNotificationKey = requestKey;
    pendingNotificationRequest = (async () => {
        try {
            const response = await axios.get('/api/notifications', {
                params,
                __skipGlobalLoading: silent,
                // Polling otomatis ditandai agar server tidak menghitungnya
                // sebagai aktivitas pengguna saat mengukur idle timeout.
                headers: background ? { 'X-Silent-Poll': '1' } : {},
            });
            if (requestId !== notificationRequestId) {
                return;
            }
            applyNotificationPayload(response.data);
        } catch (err) {
            if (requestId !== notificationRequestId) {
                return;
            }
            clearNotificationPayload();
        } finally {
            if (pendingNotificationKey === requestKey) {
                pendingNotificationRequest = null;
                pendingNotificationKey = '';
            }
            if (requestId === notificationRequestId) {
                isRefreshingSidebarCounts.value = false;
            }
        }
    })();

    return pendingNotificationRequest;
};

const refreshReviewPendingCount = refreshNotifications;
const refreshLaporanPendingCounts = refreshNotifications;
const refreshPengirimanNotificationCounts = refreshNotifications;
const refreshMailboxActions = refreshNotifications;
const refreshSidebarNotificationCounts = refreshNotifications;

const pollSidebarNotificationCounts = async () => {
    if (typeof document !== 'undefined' && document.hidden) {
        return;
    }

    await refreshSidebarNotificationCounts({ silent: true, background: true });
};

const startSidebarBadgePolling = () => {
    if (typeof window === 'undefined' || sidebarBadgePollingId.value !== null) {
        return;
    }

    sidebarBadgePollingId.value = window.setInterval(() => {
        pollSidebarNotificationCounts();
    }, SIDEBAR_BADGE_POLL_INTERVAL_MS);
};

const stopSidebarBadgePolling = () => {
    if (typeof window === 'undefined' || sidebarBadgePollingId.value === null) {
        return;
    }

    window.clearInterval(sidebarBadgePollingId.value);
    sidebarBadgePollingId.value = null;
};

const handleVisibilityChange = () => {
    if (typeof document === 'undefined' || document.hidden) {
        return;
    }
    // Kembali ke tab aplikasi termasuk aktivitas pengguna.
    handleUserActivity();
    pollSidebarNotificationCounts();
};

const redirectToLogin = () => {
    router.visit('/login');
};

const loadUser = async () => {
    try {
        const response = await axios.get('/api/user');
        fetchedUser.value = response.data;
        return true;
    } catch (err) {
        if (err?.response?.status === 401) {
            redirectToLogin();
        }
        return false;
    }
};

const logout = async () => {
    try {
        await axios.post('/api/auth/logout');
    } catch (err) {
        // Ignore logout failures and continue to clear local state.
    } finally {
        router.visit('/login');
    }
};

const applyAbsoluteDeadline = (secondsRaw) => {
    const seconds = Number(secondsRaw);
    absoluteDeadlineAt = Number.isFinite(seconds) && seconds >= 0
        ? Date.now() + seconds * 1000
        : null;
};

// Setiap response dari server membawa sisa waktu sesi, jadi batas absolut
// selalu tersinkron tanpa perlu request khusus.
const captureSessionHeaders = (response) => {
    const raw = response?.headers?.['x-session-absolute-expires-in'];
    if (raw === undefined || raw === null) {
        return;
    }
    applyAbsoluteDeadline(raw);
};

const sendSessionHeartbeat = async () => {
    try {
        const response = await axios.get('/api/session/heartbeat', {
            __skipGlobalLoading: true,
        });
        applyAbsoluteDeadline(response.data?.absolute_expires_in);
    } catch (err) {
        // Sesi yang sudah berakhir ditangani interceptor global (401).
    }
};

const handleUserActivity = () => {
    // Saat modal peringatan terbuka, sesi hanya boleh diperpanjang lewat tombol.
    // Kalau gerakan mouse ikut dihitung, komputer yang ditinggal dengan mouse
    // tersenggol akan memperpanjang sesi tanpa ada orangnya.
    if (isSessionWarningOpen.value || isSessionTerminating) {
        return;
    }

    const now = Date.now();
    lastActivityAt = now;

    if (sidebarBadgePollingId.value === null) {
        startSidebarBadgePolling();
        pollSidebarNotificationCounts();
    }

    if (now - lastHeartbeatAt >= SESSION_HEARTBEAT_MIN_GAP_MS) {
        lastHeartbeatAt = now;
        sendSessionHeartbeat();
    }
};

const remainingIdleMs = () =>
    idleLimitMs.value ? idleLimitMs.value - (Date.now() - lastActivityAt) : Infinity;

const remainingAbsoluteMs = () =>
    absoluteDeadlineAt === null ? Infinity : absoluteDeadlineAt - Date.now();

const endSessionLocally = async (reason) => {
    if (isSessionTerminating) {
        return;
    }
    isSessionTerminating = true;
    isSessionWarningOpen.value = false;
    stopSidebarBadgePolling();
    stopSessionWatcher();

    try {
        await axios.post('/api/auth/logout', null, { __skipGlobalLoading: true });
    } catch (err) {
        // Sesi mungkin sudah dibersihkan server; lanjut ke halaman login.
    } finally {
        // Memakai location, bukan router.visit, supaya seluruh state halaman
        // ikut dibuang dan tidak ada data sesi lama yang tertinggal di memori.
        window.location.href = `/login?expired=${reason}`;
    }
};

const evaluateSessionState = () => {
    if (isSessionTerminating) {
        return;
    }

    const idleLeft = remainingIdleMs();
    const absoluteLeft = remainingAbsoluteMs();
    const soonest = Math.min(idleLeft, absoluteLeft);

    if (soonest === Infinity) {
        return;
    }

    if (soonest <= 0) {
        endSessionLocally(absoluteLeft <= 0 ? 'absolute' : 'idle');
        return;
    }

    if (idleLimitMs.value && Date.now() - lastActivityAt >= IDLE_POLLING_PAUSE_AFTER_MS) {
        stopSidebarBadgePolling();
    }

    if (sessionWarningLeadMs.value > 0 && soonest <= sessionWarningLeadMs.value) {
        if (!isSessionWarningOpen.value) {
            sessionWarningReason.value = absoluteLeft <= idleLeft ? 'absolute' : 'idle';
            isSessionWarningOpen.value = true;
        }
        sessionRemainingSeconds.value = Math.ceil(soonest / 1000);
    }
};

const extendSession = async () => {
    if (isExtendingSession.value) {
        return;
    }
    isExtendingSession.value = true;

    try {
        await sendSessionHeartbeat();
        const now = Date.now();
        lastActivityAt = now;
        lastHeartbeatAt = now;
        isSessionWarningOpen.value = false;
        startSidebarBadgePolling();
        pollSidebarNotificationCounts();
    } finally {
        isExtendingSession.value = false;
    }
};

const startSessionWatcher = () => {
    if (typeof window === 'undefined' || sessionCheckTimerId.value !== null) {
        return;
    }
    sessionCheckTimerId.value = window.setInterval(evaluateSessionState, SESSION_CHECK_INTERVAL_MS);
};

const stopSessionWatcher = () => {
    if (typeof window === 'undefined' || sessionCheckTimerId.value === null) {
        return;
    }
    window.clearInterval(sessionCheckTimerId.value);
    sessionCheckTimerId.value = null;
};

const attachActivityListeners = () => {
    if (typeof window === 'undefined') {
        return;
    }
    SESSION_ACTIVITY_EVENTS.forEach((eventName) => {
        window.addEventListener(eventName, handleUserActivity, { passive: true, capture: true });
    });
};

const detachActivityListeners = () => {
    if (typeof window === 'undefined') {
        return;
    }
    SESSION_ACTIVITY_EVENTS.forEach((eventName) => {
        window.removeEventListener(eventName, handleUserActivity, { capture: true });
    });
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
provide('isAreaSwitcherRole', isAreaSwitcherRole);
provide('setAreaSwitching', setAreaSwitching);
provide('refreshReviewPendingCount', refreshReviewPendingCount);
provide('refreshLaporanPendingCounts', refreshLaporanPendingCounts);
provide('refreshPengirimanNotificationCounts', refreshPengirimanNotificationCounts);
provide('refreshMailboxActions', refreshMailboxActions);
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

onMounted(async () => {
    attachLoadingInterceptors();
    isApiReady.value = true;
    if (!page.props.auth?.user) {
        await loadUser();
    }
    await refreshSidebarNotificationCounts({ silent: true });

    startSidebarBadgePolling();
    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', handleVisibilityChange);
    }

    lastActivityAt = Date.now();
    lastHeartbeatAt = lastActivityAt;
    attachActivityListeners();
    startSessionWatcher();
});

onBeforeUnmount(() => {
    if (requestInterceptorId.value !== null) {
        axios.interceptors.request.eject(requestInterceptorId.value);
        requestInterceptorId.value = null;
    }
    if (responseInterceptorId.value !== null) {
        axios.interceptors.response.eject(responseInterceptorId.value);
        responseInterceptorId.value = null;
    }
    if (globalLoadingTimerId.value !== null) {
        window.clearTimeout(globalLoadingTimerId.value);
        globalLoadingTimerId.value = null;
    }
    stopSidebarBadgePolling();
    if (typeof document !== 'undefined') {
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    }

    stopSessionWatcher();
    detachActivityListeners();
});
</script>
