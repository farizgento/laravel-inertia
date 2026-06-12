<template>
    <header
        class="relative z-40 flex flex-col gap-4 border-b border-slate-200 bg-white/80 px-6 py-5 backdrop-blur md:flex-row md:items-center md:justify-between"
    >
        <div class="flex items-center gap-4">
            <button
                class="flex shadow-sm h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                type="button"
                aria-label="Toggle sidebar"
                aria-controls="app-sidebar"
                :aria-expanded="sidebarOpen"
                @click="emit('toggle-sidebar')"
            >
                <svg
                    class="h-6 w-6"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M4 4h16v16H4z" />
                    <path d="M8 4v16" />
                </svg>
            </button>
            <div>
                <h2 class="text-base font-semibold text-slate-900">
                    {{ title || 'Dashboard' }}
                </h2>
                <p v-if="subtitle" class="text-xs text-slate-500">
                    {{ subtitle }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
            <div v-if="showMailbox" class="relative">
                <button
                    class="relative flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:bg-slate-50"
                    type="button"
                    aria-label="Mailbox"
                    :aria-expanded="isMailboxOpen"
                    @click="isMailboxOpen = !isMailboxOpen"
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
                        <rect x="3" y="5" width="18" height="14" rx="2" />
                        <path d="m3 7 9 6 9-6" />
                    </svg>
                    <span
                        v-if="mailboxCount > 0"
                        class="absolute -right-1 -top-1 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-rose-600 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white"
                    >
                        {{ formatMailboxCount(mailboxCount) }}
                    </span>
                </button>

                <div
                    v-if="isMailboxOpen"
                    class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
                >
                    <div class="border-b border-slate-200 px-4 py-3">
                        <p class="text-sm font-semibold text-slate-900">Mailbox</p>
                    </div>
                    <div v-if="!mailboxItems.length" class="px-4 py-6 text-center text-sm text-slate-500">
                        Tidak ada aksi saat ini.
                    </div>
                    <div v-else class="max-h-96 overflow-y-auto py-1">
                        <Link
                            v-for="item in visibleMailboxItems"
                            :key="item.key"
                            :href="item.href"
                            class="flex items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50"
                            @click="isMailboxOpen = false"
                        >
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 7h11v10H3z" />
                                    <path d="M14 10h4l3 3v4h-7z" />
                                    <circle cx="7.5" cy="19" r="1.5" />
                                    <circle cx="17.5" cy="19" r="1.5" />
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold leading-5 text-slate-800">{{ item.title }}</span>
                                <span class="mt-0.5 block text-xs leading-4 text-slate-500">{{ item.description }}</span>
                            </span>
                            <span class="mt-1 inline-flex min-w-[1.5rem] shrink-0 items-center justify-center rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-semibold leading-none text-rose-700">
                                {{ formatMailboxCount(item.count) }}
                            </span>
                        </Link>
                        <button
                            v-if="mailboxItems.length > mailboxPreviewLimit"
                            class="w-full border-t border-slate-100 px-4 py-3 text-center text-sm font-semibold text-blue-600 transition hover:bg-slate-50 hover:text-blue-700"
                            type="button"
                            @click="showAllMailbox = !showAllMailbox"
                        >
                            {{ showAllMailbox ? 'Tampilkan 5 pesan saja' : 'Lihat semua pesan' }}
                        </button>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2">
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M12 21s-6-5.33-6-11a6 6 0 1 1 12 0c0 5.67-6 11-6 11z" />
                    <circle cx="12" cy="10" r="2" />
                </svg>
                <template v-if="isAreaSwitcher">
                    <select
                        class="bg-transparent text-xs font-semibold text-slate-600 outline-none"
                        :value="activeAreaId ?? ''"
                        @change="emit('change-area', $event.target.value)"
                    >
                        <option value="" disabled>Pilih area</option>
                        <option v-for="area in areas" :key="area.id" :value="area.id">
                            {{ area.name }}
                        </option>
                    </select>
                </template>
                <template v-else>
                    {{ displayArea }}
                </template>
            </div>
            <div class="relative">
                <button
                    class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 shadow-sm transition hover:border-blue-300 hover:text-blue-600"
                    type="button"
                    aria-label="Menu akun"
                    :aria-expanded="isAccountOpen"
                    @click="toggleAccountMenu"
                >
                    <span>Hai, {{ displayName }} ({{ displayRole }})</span>
                    <svg
                        class="h-4 w-4 transition"
                        :class="isAccountOpen ? 'rotate-180' : ''"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </button>

                <div
                    v-if="isAccountOpen"
                    class="absolute right-0 z-50 mt-2 w-43 overflow-hidden rounded-2xl border border-slate-200 bg-white py-1 shadow-xl shadow-slate-200/70"
                >
                    <button
                        class="flex w-full items-center gap-3 px-3 py-1 text-left text-xs text-slate-600 transition hover:bg-slate-50 hover:text-blue-600"
                        type="button"
                        @click="openPasswordModal"
                    >
                        <svg
                            class="h-4 w-4 shrink-0"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <rect x="4" y="11" width="16" height="9" rx="2" />
                            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                        </svg>
                        Ubah Password
                    </button>
                </div>
            </div>
        </div>
    </header>

    <teleport to="body">
        <div
            v-if="isPasswordModalOpen"
            class="fixed inset-0 z-[80] flex items-center justify-center bg-slate-900/40 px-4 py-6"
            @click.self="closePasswordModal"
        >
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl shadow-slate-900/20">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">Ubah Password</h3>
                        <p class="mt-1 text-sm text-slate-500">Gunakan password baru minimal 8 karakter, 1 kapital, dan 1 angka.</p>
                    </div>
                    <button
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-slate-100 text-slate-500 transition hover:text-slate-700"
                        type="button"
                        :disabled="isChangingPassword"
                        @click="closePasswordModal"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6 6 18" />
                            <path d="M6 6 18 18" />
                        </svg>
                    </button>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submitChangePassword">
                    <label class="block space-y-2 text-sm font-medium text-slate-700">
                        <span>Password Saat Ini</span>
                        <div class="relative">
                            <input
                                v-model="passwordForm.current_password"
                                :type="showCurrentPassword ? 'text' : 'password'"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Masukkan password saat ini"
                            />
                            <button
                                class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-blue-600"
                                type="button"
                                :aria-label="showCurrentPassword ? 'Sembunyikan password saat ini' : 'Tampilkan password saat ini'"
                                @click="showCurrentPassword = !showCurrentPassword"
                            >
                                <svg v-if="showCurrentPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                    <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5 0 8.5 4 10 8a13.2 13.2 0 0 1-3.1 4.6" />
                                    <path d="M6.6 6.6A13.2 13.2 0 0 0 2 12c1.5 4 5 8 10 8 1.4 0 2.7-.3 3.9-.9" />
                                </svg>
                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 12s3.5-8 10-8 10 8 10 8-3.5 8-10 8S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        <span v-if="passwordErrors.current_password" class="block text-xs text-rose-500">
                            {{ passwordErrors.current_password }}
                        </span>
                    </label>

                    <label class="block space-y-2 text-sm font-medium text-slate-700">
                        <span>Password Baru</span>
                        <div class="relative">
                            <input
                                v-model="passwordForm.password"
                                :type="showNewPassword ? 'text' : 'password'"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Minimal 8 karakter"
                            />
                            <button
                                class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-blue-600"
                                type="button"
                                :aria-label="showNewPassword ? 'Sembunyikan password baru' : 'Tampilkan password baru'"
                                @click="showNewPassword = !showNewPassword"
                            >
                                <svg v-if="showNewPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                    <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5 0 8.5 4 10 8a13.2 13.2 0 0 1-3.1 4.6" />
                                    <path d="M6.6 6.6A13.2 13.2 0 0 0 2 12c1.5 4 5 8 10 8 1.4 0 2.7-.3 3.9-.9" />
                                </svg>
                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 12s3.5-8 10-8 10 8 10 8-3.5 8-10 8S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                        <span v-if="passwordErrors.password" class="block text-xs text-rose-500">
                            {{ passwordErrors.password }}
                        </span>
                    </label>

                    <label class="block space-y-2 text-sm font-medium text-slate-700">
                        <span>Konfirmasi Password Baru</span>
                        <div class="relative">
                            <input
                                v-model="passwordForm.password_confirmation"
                                :type="showNewPasswordConfirmation ? 'text' : 'password'"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 pr-10 text-sm text-slate-900 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Ulangi password baru"
                            />
                            <button
                                class="absolute inset-y-0 right-3 flex items-center text-slate-400 transition hover:text-blue-600"
                                type="button"
                                :aria-label="showNewPasswordConfirmation ? 'Sembunyikan konfirmasi password baru' : 'Tampilkan konfirmasi password baru'"
                                @click="showNewPasswordConfirmation = !showNewPasswordConfirmation"
                            >
                                <svg v-if="showNewPasswordConfirmation" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 3l18 18" />
                                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8" />
                                    <path d="M9.9 4.2A10.8 10.8 0 0 1 12 4c5 0 8.5 4 10 8a13.2 13.2 0 0 1-3.1 4.6" />
                                    <path d="M6.6 6.6A13.2 13.2 0 0 0 2 12c1.5 4 5 8 10 8 1.4 0 2.7-.3 3.9-.9" />
                                </svg>
                                <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M2 12s3.5-8 10-8 10 8 10 8-3.5 8-10 8S2 12 2 12Z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </label>

                    <p v-if="passwordMessage" class="rounded-xl px-3 py-2 text-sm font-semibold" :class="passwordMessageClass">
                        {{ passwordMessage }}
                    </p>

                    <div class="flex justify-end gap-2 pt-2">
                        <button
                            class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 disabled:cursor-not-allowed disabled:opacity-60"
                            type="button"
                            :disabled="isChangingPassword"
                            @click="closePasswordModal"
                        >
                            Batal
                        </button>
                        <button
                            class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-blue-300"
                            type="submit"
                            :disabled="isChangingPassword"
                        >
                            {{ isChangingPassword ? 'Menyimpan...' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </teleport>
</template>

<script setup>
import axios from 'axios';
import { Link } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';

const emit = defineEmits(['toggle-sidebar', 'change-area']);

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    subtitle: {
        type: String,
        default: '',
    },
    displayArea: {
        type: String,
        default: 'Area',
    },
    displayName: {
        type: String,
        default: 'Pengguna',
    },
    displayRole: {
        type: String,
        default: 'User',
    },
    sidebarOpen: {
        type: Boolean,
        default: false,
    },
    areas: {
        type: Array,
        default: () => [],
    },
    activeAreaId: {
        type: [String, Number],
        default: null,
    },
    isAreaSwitcher: {
        type: Boolean,
        default: false,
    },
    mailboxItems: {
        type: Array,
        default: () => [],
    },
    mailboxCount: {
        type: Number,
        default: 0,
    },
    showMailbox: {
        type: Boolean,
        default: true,
    },
});

const isMailboxOpen = ref(false);
const isAccountOpen = ref(false);
const isPasswordModalOpen = ref(false);
const isChangingPassword = ref(false);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showNewPasswordConfirmation = ref(false);
const passwordMessage = ref('');
const passwordMessageType = ref('');
const showAllMailbox = ref(false);
const mailboxPreviewLimit = 5;
const passwordForm = reactive({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const passwordErrors = reactive({
    current_password: '',
    password: '',
});

const visibleMailboxItems = computed(() =>
    showAllMailbox.value ? props.mailboxItems : props.mailboxItems.slice(0, mailboxPreviewLimit)
);

const passwordMessageClass = computed(() =>
    passwordMessageType.value === 'success'
        ? 'bg-emerald-50 text-emerald-700'
        : 'bg-rose-50 text-rose-700'
);

const resetPasswordForm = () => {
    passwordForm.current_password = '';
    passwordForm.password = '';
    passwordForm.password_confirmation = '';
    passwordErrors.current_password = '';
    passwordErrors.password = '';
    passwordMessage.value = '';
    passwordMessageType.value = '';
    showCurrentPassword.value = false;
    showNewPassword.value = false;
    showNewPasswordConfirmation.value = false;
};

const toggleAccountMenu = () => {
    isAccountOpen.value = !isAccountOpen.value;
    if (isAccountOpen.value) {
        isMailboxOpen.value = false;
    }
};

const openPasswordModal = () => {
    isAccountOpen.value = false;
    resetPasswordForm();
    isPasswordModalOpen.value = true;
};

const closePasswordModal = () => {
    if (isChangingPassword.value) {
        return;
    }
    isPasswordModalOpen.value = false;
    resetPasswordForm();
};

const submitChangePassword = async () => {
    if (isChangingPassword.value) {
        return;
    }

    passwordErrors.current_password = '';
    passwordErrors.password = '';
    passwordMessage.value = '';
    passwordMessageType.value = '';
    isChangingPassword.value = true;

    try {
        const response = await axios.post('/api/auth/change-password', {
            current_password: passwordForm.current_password,
            password: passwordForm.password,
            password_confirmation: passwordForm.password_confirmation,
        });
        passwordMessageType.value = 'success';
        passwordMessage.value = response.data?.message ?? 'Password akun berhasil diubah.';
        passwordForm.current_password = '';
        passwordForm.password = '';
        passwordForm.password_confirmation = '';
    } catch (error) {
        const errors = error.response?.data?.errors ?? {};
        passwordErrors.current_password = Array.isArray(errors.current_password)
            ? errors.current_password[0]
            : '';
        passwordErrors.password = Array.isArray(errors.password)
            ? errors.password[0]
            : '';
        passwordMessageType.value = 'error';
        passwordMessage.value = error.response?.data?.message ?? 'Gagal mengubah password.';
    } finally {
        isChangingPassword.value = false;
    }
};

const formatMailboxCount = (count) => {
    const normalized = Number(count ?? 0);
    if (!Number.isFinite(normalized) || normalized <= 0) {
        return '0';
    }

    return normalized > 99 ? '99+' : String(normalized);
};

watch(
    () => props.mailboxItems,
    () => {
        if (!props.mailboxItems.length) {
            isMailboxOpen.value = false;
        }
        showAllMailbox.value = false;
    }
);

watch(isMailboxOpen, (next) => {
    if (next) {
        isAccountOpen.value = false;
    }
});
</script>
