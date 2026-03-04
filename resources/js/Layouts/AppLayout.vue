<template>
    <div class="relative h-screen overflow-hidden bg-slate-100">
        <Head :title="pageTitle" />
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
                    :sidebar-open="isSidebarOpen"
                    @toggle-sidebar="toggleSidebar"
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
import { computed, onMounted, ref } from 'vue';
import AppHeader from '../Components/AppHeader.vue';
import AppSidebar from '../Components/AppSidebar.vue';

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

const displayName = computed(() => authUser.value?.name ?? 'Memuat...');
const displayArea = computed(() => authUser.value?.area?.name ?? 'Memuat...');
const displayRole = computed(() => authUser.value?.role?.name ?? 'Memuat...');
const roleKey = computed(() => authUser.value?.role?.key ?? '');
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

const redirectToLogin = () => {
    window.localStorage.removeItem('auth_token');
    window.localStorage.removeItem('auth_user');
    delete axios.defaults.headers.common.Authorization;
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
        window.localStorage.removeItem('auth_token');
        window.localStorage.removeItem('auth_user');
        delete axios.defaults.headers.common.Authorization;
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

onMounted(() => {
    const token = window.localStorage.getItem('auth_token');
    if (!token) {
        redirectToLogin();
        return;
    }
    axios.defaults.headers.common.Authorization = `Bearer ${token}`;
    if (!page.props.auth?.user && !fetchedUser.value) {
        fetchedUser.value = loadCachedUser();
    }
    if (!page.props.auth?.user) {
        loadUser();
    }
});
</script>
