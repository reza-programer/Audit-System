<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    currentStatus: {
        type: String,
        default: 'all',
    },
    stats: {
        type: Object,
        default: () => ({
            all: 0,
            active: 0,
            pending: 0,
            inactive: 0,
        }),
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const roleFilter = ref('');
const currentPage = ref(1);

const roleLabels = {
    admin: 'Admin',
    chief: 'Chief Auditor',
    asmen: 'Asisten Manager',
    coordinator: 'Koordinator',
    auditor: 'Auditor',
    auditee: 'Auditee Toko',
};

const getRoleLabel = (role) => roleLabels[role] || role;

const filteredUsers = computed(() => {
    return props.users.filter((u) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (u.name && u.name.toLowerCase().includes(query)) ||
            (u.email && u.email.toLowerCase().includes(query)) ||
            (u.phone && u.phone.toLowerCase().includes(query));

        const matchesRole = !roleFilter.value || u.role === roleFilter.value;

        return matchesSearch && matchesRole;
    });
});

watch([searchQuery, roleFilter], () => {
    currentPage.value = 1;
});

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredUsers.value.slice(start, start + 10);
});

const setStatusFilter = (status) => {
    router.get(route('admin.users.index'), { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const toggleActive = (user) => {
    router.patch(route('admin.users.toggle-active', user.id), {}, {
        preserveScroll: true,
    });
};

// --- Approval & Rejection Logic ---
const approveModal = ref({
    show: false,
    user: null,
    selectedRole: 'auditor',
});

const openApproveModal = (user) => {
    approveModal.value = {
        show: true,
        user,
        selectedRole: user.requested_role || 'auditor',
    };
};

const submitApprove = () => {
    if (!approveModal.value.user) return;
    router.patch(route('admin.users.approve', approveModal.value.user.id), {
        role: approveModal.value.selectedRole,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            approveModal.value.show = false;
        },
    });
};

const rejectModal = ref({
    show: false,
    user: null,
    reason: '',
});

const openRejectModal = (user) => {
    rejectModal.value = {
        show: true,
        user,
        reason: '',
    };
};

const submitReject = () => {
    if (!rejectModal.value.user) return;
    router.patch(route('admin.users.reject', rejectModal.value.user.id), {
        reason: rejectModal.value.reason,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            rejectModal.value.show = false;
        },
    });
};

// --- Delete Modal ---
const confirmModal = ref({
    show: false,
    title: '',
    message: '',
    confirmText: '',
    type: 'primary',
    action: null,
});

const openConfirm = (config) => {
    confirmModal.value = {
        show: true,
        title: config.title || 'Konfirmasi Tindakan',
        message: config.message || 'Apakah Anda yakin ingin melanjutkan?',
        confirmText: config.confirmText || 'Ya, Lanjutkan',
        type: config.type || 'primary',
        action: config.action,
    };
};

const handleConfirm = () => {
    if (confirmModal.value.action) {
        confirmModal.value.action();
    }
    confirmModal.value.show = false;
};

const deleteUser = (user) => {
    openConfirm({
        title: 'Hapus Akun Pengguna',
        message: `Apakah Anda yakin ingin menghapus user ${user.name} (${user.email})?`,
        confirmText: 'Ya, Hapus Pengguna',
        type: 'danger',
        action: () => router.delete(route('admin.users.destroy', user.id)),
    });
};
</script>

<template>
    <AppLayout title="Manajemen Pengguna">
        <Head title="Manajemen Pengguna — Admin" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Manajemen Pengguna</h1>
                <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Kelola akun, persetujuan pendaftar baru, dan hak akses pengguna sistem</p>
            </div>

            <div class="flex items-center">
                <Link
                    :href="route('admin.users.create')"
                    class="inline-flex items-center justify-center w-full sm:w-auto gap-1.5 px-3.5 py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs cursor-pointer"
                >
                    + Tambah Pengguna
                </Link>
            </div>
        </div>

        <!-- Filter Status Tabs (Scrollable on Mobile) -->
        <div class="mb-4 border-b border-gray-200 overflow-x-auto no-scrollbar">
            <nav class="flex space-x-3 sm:space-x-6 text-xs whitespace-nowrap min-w-max pb-2" aria-label="Tabs">
                <button
                    type="button"
                    @click="setStatusFilter('all')"
                    class="pb-2 px-1 border-b-2 font-medium cursor-pointer transition-colors"
                    :class="currentStatus === 'all' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Semua Pengguna
                    <span class="ml-1 py-0.5 px-1.5 rounded-full text-[10px]" :class="currentStatus === 'all' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'">
                        {{ stats.all }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('active')"
                    class="pb-2 px-1 border-b-2 font-medium cursor-pointer transition-colors"
                    :class="currentStatus === 'active' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Aktif
                    <span class="ml-1 py-0.5 px-1.5 rounded-full text-[10px]" :class="currentStatus === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600'">
                        {{ stats.active }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('pending')"
                    class="pb-2 px-1 border-b-2 font-medium cursor-pointer transition-colors flex items-center gap-1"
                    :class="currentStatus === 'pending' ? 'border-amber-600 text-amber-700 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Menunggu Persetujuan
                    <span
                        class="py-0.5 px-1.5 rounded-full text-[10px] font-bold"
                        :class="stats.pending > 0 ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-300 animate-pulse' : 'bg-gray-100 text-gray-600'"
                    >
                        {{ stats.pending }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="setStatusFilter('inactive')"
                    class="pb-2 px-1 border-b-2 font-medium cursor-pointer transition-colors"
                    :class="currentStatus === 'inactive' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                >
                    Nonaktif / Ditolak
                    <span class="ml-1 py-0.5 px-1.5 rounded-full text-[10px]" :class="currentStatus === 'inactive' ? 'bg-gray-200 text-gray-800' : 'bg-gray-100 text-gray-600'">
                        {{ stats.inactive }}
                    </span>
                </button>
            </nav>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white p-3 sm:p-3.5 rounded-lg border border-gray-200 shadow-2xs mb-4 sm:mb-5 text-xs">
            <div class="flex flex-col sm:flex-row items-center gap-2.5 sm:gap-3">
                <div class="relative flex-1 w-full">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari nama, email, atau no. WhatsApp..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    />
                </div>
                <div class="w-full sm:w-56 shrink-0">
                    <select
                        v-model="roleFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white font-medium"
                    >
                        <option value="">Semua Jabatan</option>
                        <option value="admin">Admin</option>
                        <option value="chief">Chief Auditor</option>
                        <option value="asmen">Asisten Manager</option>
                        <option value="coordinator">Koordinator</option>
                        <option value="auditor">Auditor</option>
                        <option value="auditee">Auditee Toko</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Content: Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="filteredUsers.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada data pengguna pada filter ini.
                </div>
                <div
                    v-for="u in paginatedUsers"
                    :key="'mobile-' + u.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ u.name }}</div>
                            <div class="text-[11px] text-gray-500 font-mono mt-0.5">{{ u.email }}</div>
                        </div>

                        <!-- Status Badge -->
                        <div>
                            <template v-if="u.approval_status === 'pending'">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                    Pending Review
                                </span>
                            </template>
                            <template v-else-if="u.approval_status === 'rejected'">
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-rose-50 text-rose-800 border border-rose-200">
                                    Ditolak
                                </span>
                            </template>
                            <template v-else>
                                <button
                                    @click="toggleActive(u)"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium border"
                                    :class="u.is_active ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="u.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Role & Phone Details -->
                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1 border-t border-gray-100">
                        <div>
                            <span class="text-gray-400">Jabatan: </span>
                            <span class="font-medium text-slate-800">
                                {{ u.approval_status === 'pending' ? getRoleLabel(u.requested_role) : getRoleLabel(u.role) }}
                            </span>
                        </div>
                        <div class="font-mono text-[11px] text-gray-700">
                            {{ u.phone || '—' }}
                        </div>
                    </div>

                    <!-- Mobile Action Buttons -->
                    <div class="pt-2 flex items-center justify-end gap-2 border-t border-gray-100">
                        <template v-if="u.approval_status === 'pending'">
                            <button
                                @click="openApproveModal(u)"
                                class="px-3 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-[11px] shadow-2xs transition cursor-pointer"
                            >
                                Setujui
                            </button>
                            <button
                                @click="openRejectModal(u)"
                                class="px-3 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-medium text-[11px] transition cursor-pointer"
                            >
                                Tolak
                            </button>
                        </template>
                        <template v-else>
                            <Link
                                :href="route('admin.users.edit', u.id)"
                                class="px-3 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium text-[11px] transition cursor-pointer"
                            >
                                Edit
                            </Link>
                            <button
                                @click="deleteUser(u)"
                                class="px-3 py-1 rounded bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium text-[11px] transition cursor-pointer"
                            >
                                Hapus
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">Nama Lengkap</th>
                            <th class="px-4 py-3 whitespace-nowrap">Email</th>
                            <th class="px-4 py-3 whitespace-nowrap">No. WhatsApp</th>
                            <th class="px-4 py-3 whitespace-nowrap">Jabatan / Role</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredUsers.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data pengguna pada filter ini.
                            </td>
                        </tr>
                        <tr
                            v-for="u in paginatedUsers"
                            :key="u.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle font-medium text-gray-900">
                                <div>{{ u.name }}</div>
                                <div v-if="u.created_at" class="text-[10px] text-gray-400 font-normal">
                                    Daftar: {{ u.created_at }}
                                </div>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-gray-600 font-mono text-[11px]">
                                {{ u.email }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-gray-700 font-mono text-[11px]">
                                {{ u.phone || '—' }}
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">
                                <template v-if="u.approval_status === 'pending'">
                                    <div class="text-[11px] text-amber-800 font-medium">
                                        Pengajuan: <span class="font-semibold underline">{{ getRoleLabel(u.requested_role) }}</span>
                                    </div>
                                    <div v-if="u.stores !== '—'" class="text-[10px] text-gray-500">
                                        Toko: {{ u.stores }}
                                    </div>
                                </template>
                                <template v-else>
                                    <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ getRoleLabel(u.role) }}
                                    </span>
                                </template>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <!-- PENDING APPROVAL BADGE -->
                                <template v-if="u.approval_status === 'pending'">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        Pending Review
                                    </span>
                                </template>
                                <!-- REJECTED BADGE -->
                                <template v-else-if="u.approval_status === 'rejected'">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-rose-50 text-rose-800 border border-rose-200">
                                        Ditolak
                                    </span>
                                </template>
                                <!-- STANDARD ACTIVE / INACTIVE TOGGLE -->
                                <template v-else>
                                    <button
                                        @click="toggleActive(u)"
                                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium transition-colors border cursor-pointer"
                                        :class="u.is_active ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200 hover:bg-emerald-100' : 'text-gray-500 bg-gray-50 border-gray-200 hover:bg-gray-100'"
                                        title="Klik untuk mengubah status aktif"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="u.is_active ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                        {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </button>
                                </template>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-right">
                                <!-- ACTION FOR PENDING USERS -->
                                <div v-if="u.approval_status === 'pending'" class="inline-flex items-center gap-2">
                                    <button
                                        @click="openApproveModal(u)"
                                        class="px-2.5 py-1 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-[11px] shadow-2xs transition cursor-pointer"
                                    >
                                        Setujui
                                    </button>
                                    <button
                                        @click="openRejectModal(u)"
                                        class="px-2.5 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-medium text-[11px] transition cursor-pointer"
                                    >
                                        Tolak
                                    </button>
                                </div>

                                <!-- ACTION FOR APPROVED USERS -->
                                <div v-else class="inline-flex items-center gap-2">
                                    <Link
                                        :href="route('admin.users.edit', u.id)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs cursor-pointer"
                                    >
                                        Edit
                                    </Link>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteUser(u)"
                                        class="text-red-600 hover:text-red-800 font-medium hover:underline text-xs cursor-pointer"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <Pagination
                :current-page="currentPage"
                :per-page="10"
                :total-items="filteredUsers.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- Approve Modal -->
        <div v-if="approveModal.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-sm w-full p-6 shadow-xl text-xs space-y-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Setujui Pendaftaran Akun</h3>
                        <p class="text-gray-500 text-[11px]">Konfirmasi peran dan hak akses akun</p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded text-slate-700 space-y-1">
                    <div>Nama: <strong class="text-slate-900">{{ approveModal.user?.name }}</strong></div>
                    <div>WhatsApp: <span class="font-mono">{{ approveModal.user?.phone }}</span></div>
                    <div>Pengajuan: <span class="text-blue-700 font-medium">{{ getRoleLabel(approveModal.user?.requested_role) }}</span></div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Pilih Jabatan yang Disetujui:</label>
                    <select
                        v-model="approveModal.selectedRole"
                        class="w-full text-xs rounded border-gray-300 focus:border-emerald-500 focus:ring-emerald-500 font-medium"
                    >
                        <option value="auditor">Auditor</option>
                        <option value="coordinator">Koordinator</option>
                        <option value="auditee">Auditee Toko</option>
                        <option value="asmen">Asisten Manager</option>
                        <option value="chief">Chief Auditor</option>
                        <option value="admin">Admin (Administrator)</option>
                    </select>
                    <p class="text-[10px] text-gray-400 mt-1">
                        Notifikasi WhatsApp persetujuan akan otomatis dikirim ke nomor pengguna.
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button
                        type="button"
                        @click="approveModal.show = false"
                        class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitApprove"
                        class="px-3.5 py-1.5 rounded bg-emerald-600 hover:bg-emerald-700 text-white font-medium cursor-pointer"
                    >
                        Ya, Setujui Akun
                    </button>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div v-if="rejectModal.show" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-lg max-w-sm w-full p-6 shadow-xl text-xs space-y-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Tolak Pendaftaran Akun</h3>
                        <p class="text-gray-500 text-[11px]">Tolak permohonan pendaftar</p>
                    </div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200 rounded text-slate-700">
                    <div>Tolak pendaftaran akun untuk <strong class="text-slate-900">{{ rejectModal.user?.name }}</strong>?</div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Alasan Penolakan (Opsional):</label>
                    <textarea
                        v-model="rejectModal.reason"
                        rows="2"
                        placeholder="Contoh: Nomor telepon atau identitas tidak valid..."
                        class="w-full text-xs rounded border-gray-300 focus:border-rose-500 focus:ring-rose-500"
                    ></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button
                        type="button"
                        @click="rejectModal.show = false"
                        class="px-3 py-1.5 rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 font-medium cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="submitReject"
                        class="px-3.5 py-1.5 rounded bg-rose-600 hover:bg-rose-700 text-white font-medium cursor-pointer"
                    >
                        Tolak Pendaftaran
                    </button>
                </div>
            </div>
        </div>

        <!-- Action Confirmation Modal -->
        <ConfirmModal
            :show="confirmModal.show"
            :title="confirmModal.title"
            :message="confirmModal.message"
            :confirm-text="confirmModal.confirmText"
            :type="confirmModal.type"
            @confirm="handleConfirm"
            @close="confirmModal.show = false"
        />
    </AppLayout>
</template>
