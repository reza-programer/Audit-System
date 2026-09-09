<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import FlashMessage from '@/Components/FlashMessage.vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const props = defineProps({
    title: {
        type: String,
        default: 'Sistem Audit (IAMS)',
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const roles = computed(() => user.value?.roles || []);
const isAuditor = computed(() => roles.value.includes('auditor'));
const isCoordinator = computed(() => roles.value.includes('coordinator'));
const isAdmin = computed(() => roles.value.includes('admin'));
const isChief = computed(() => roles.value.includes('chief'));
const isAsmen = computed(() => roles.value.includes('asmen'));
const isManagement = computed(() => isCoordinator.value || isChief.value || isAsmen.value || isAdmin.value);

// Mobile drawer state
const mobileMenuOpen = ref(false);

// Page Loading state for smooth transitions
const isPageLoading = ref(false);

// Desktop Collapsible state (stored in localStorage)
const isCollapsed = ref(false);
const userDropdownOpen = ref(false);
const notificationsOpen = ref(false);
const notifications = computed(() => page.props.notifications || { count: 0, items: [] });

// Close dropdown when clicking outside
const toggleUserDropdown = () => {
    userDropdownOpen.value = !userDropdownOpen.value;
    if (userDropdownOpen.value) {
        notificationsOpen.value = false;
    }
};

const toggleNotifications = () => {
    notificationsOpen.value = !notificationsOpen.value;
    if (notificationsOpen.value) {
        userDropdownOpen.value = false;
    }
};

const closeUserDropdown = () => {
    userDropdownOpen.value = false;
};

// Compute user initials for avatar
const userInitials = computed(() => {
    if (!user.value?.name) return 'U';
    const names = user.value.name.split(' ');
    if (names.length >= 2) {
        return (names[0][0] + names[1][0]).toUpperCase();
    }
    return user.value.name.substring(0, 2).toUpperCase();
});

const roleDisplayLabel = computed(() => {
    if (isAdmin.value) return 'Administrator';
    if (isChief.value) return 'Chief Auditor';
    if (isAsmen.value) return 'Asisten Manager';
    if (isCoordinator.value) return 'Koordinator Audit';
    if (isAuditor.value) return 'Auditor';
    return 'User';
});

let removeStartListener = null;
let removeFinishListener = null;

onMounted(() => {
    const saved = localStorage.getItem('sidebar_collapsed');
    if (saved !== null) {
        isCollapsed.value = saved === 'true';
    }

    window.addEventListener('click', (e) => {
        const dropdown = document.getElementById('user-profile-dropdown');
        if (dropdown && !dropdown.contains(e.target)) {
            userDropdownOpen.value = false;
        }
        const notifDropdown = document.getElementById('notification-dropdown');
        if (notifDropdown && !notifDropdown.contains(e.target)) {
            notificationsOpen.value = false;
        }
    });

    // Listen to Inertia router events for lazy transition shimmer
    removeStartListener = router.on('start', () => {
        isPageLoading.value = true;
    });

    removeFinishListener = router.on('finish', () => {
        isPageLoading.value = false;
    });
});

onUnmounted(() => {
    if (removeStartListener) removeStartListener();
    if (removeFinishListener) removeFinishListener();
});

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
    localStorage.setItem('sidebar_collapsed', isCollapsed.value);
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="h-screen bg-[#F9FAFB] text-gray-900 flex font-sans antialiased overflow-hidden">
        <!-- ================= DESKTOP SIDEBAR ================= -->
        <aside
            class="hidden md:flex flex-col h-screen bg-[#111827] text-gray-300 border-r border-gray-800 shrink-0 transition-all duration-300 ease-in-out z-30"
            :class="isCollapsed ? 'w-[68px]' : 'w-64'"
        >
            <!-- Brand & Toggle Header -->
            <div class="h-16 flex items-center justify-between px-3.5 border-b border-gray-800 bg-[#0B0F17] shrink-0">
                <div v-if="!isCollapsed" class="flex items-center gap-2.5 overflow-hidden whitespace-nowrap pl-1">
                    <ApplicationLogo size="w-7 h-7" />
                    <div>
                        <div class="text-sm font-bold text-white tracking-tight leading-tight">IAMS</div>
                        <div class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Sistem Audit</div>
                    </div>
                </div>
                <div v-else class="mx-auto">
                    <ApplicationLogo size="w-7 h-7" />
                </div>

                <button
                    v-if="!isCollapsed"
                    @click="toggleSidebar"
                    title="Ciutkan Menu (Hanya Icon)"
                    class="p-1.5 rounded text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                    </svg>
                </button>
            </div>

            <!-- Expand button if collapsed -->
            <div v-if="isCollapsed" class="py-2 flex justify-center border-b border-gray-800/60">
                <button
                    @click="toggleSidebar"
                    title="Perluas Menu Sidebar"
                    class="p-1.5 rounded text-gray-400 hover:text-white hover:bg-gray-800 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Navigation Links (Scrollable) -->
            <nav class="flex-1 px-2.5 py-3 space-y-3 overflow-y-auto overflow-x-hidden">
                <!-- ================= ADMIN NAV ================= -->
                <template v-if="isAdmin">
                    <div class="space-y-1">
                        <Link
                            :href="route('admin.dashboard')"
                            :title="isCollapsed ? 'Dashboard' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.dashboard') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Dashboard</span>
                        </Link>
                    </div>

                    <!-- Audit Management -->
                    <div class="space-y-1 pt-1">
                        <div v-if="!isCollapsed" class="px-3 pb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                            Audit Management
                        </div>
                        <div v-else class="border-t border-gray-800 my-2"></div>

                        <Link
                            :href="route('admin.audits.index')"
                            :title="isCollapsed ? 'Audits' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.audits.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Audits</span>
                        </Link>

                        <Link
                            :href="route('admin.findings.index')"
                            :title="isCollapsed ? 'Findings' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.findings.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Findings</span>
                        </Link>

                        <Link
                            :href="route('coordinator.finding-qualities.index')"
                            :title="isCollapsed ? 'Finding Quality' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.finding-qualities.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Finding Quality</span>
                        </Link>

                        <Link
                            :href="route('admin.action-plans.index')"
                            :title="isCollapsed ? 'Action Plans' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.action-plans.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Action Plans</span>
                        </Link>

                        <Link
                            :href="route('admin.reports.index')"
                            :title="isCollapsed ? 'Reports' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.reports.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Reports</span>
                        </Link>
                    </div>

                    <!-- Master Data -->
                    <div class="space-y-1 pt-1">
                        <div v-if="!isCollapsed" class="px-3 pb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                            Master Data & CSA
                        </div>
                        <div v-else class="border-t border-gray-800 my-2"></div>

                        <Link
                            :href="route('admin.users.index')"
                            :title="isCollapsed ? 'Users Management' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.users.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Users</span>
                        </Link>

                        <Link
                            :href="route('admin.stores.index')"
                            :title="isCollapsed ? 'Stores & CSA' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.stores.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Toko & Gudang (CSA)</span>
                        </Link>

                        <Link
                            :href="route('admin.audit-categories.index')"
                            :title="isCollapsed ? 'Audit Categories' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.audit-categories.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Audit Categories</span>
                        </Link>

                        <Link
                            :href="route('admin.sops.index')"
                            :title="isCollapsed ? 'SOP / SE Acuan' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.sops.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">SOP / SE</span>
                        </Link>

                        <Link
                            :href="route('admin.notification-rules.index')"
                            :title="isCollapsed ? 'Jadwal Notifikasi' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.notification-rules.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Jadwal Notifikasi</span>
                        </Link>

                        <Link
                            :href="route('admin.settings.index')"
                            :title="isCollapsed ? 'Pengaturan Sistem' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('admin.settings.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Pengaturan Sistem</span>
                        </Link>
                    </div>
                </template>

                <!-- ================= COORDINATOR / CHIEF / ASMEN NAV ================= -->
                <template v-else-if="isManagement">
                    <div class="space-y-1">
                        <Link
                            :href="route('coordinator.dashboard')"
                            :title="isCollapsed ? 'Dashboard' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.dashboard') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Dashboard</span>
                        </Link>
                    </div>

                    <!-- Audit Management -->
                    <div class="space-y-1 pt-1">
                        <div v-if="!isCollapsed" class="px-3 pb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                            Audit Management
                        </div>
                        <div v-else class="border-t border-gray-800 my-2"></div>

                        <Link
                            :href="route('coordinator.audits.index')"
                            :title="isCollapsed ? 'Audits' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.audits.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Audits</span>
                        </Link>

                        <Link
                            :href="route('coordinator.findings.index')"
                            :title="isCollapsed ? 'Findings' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.findings.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Findings</span>
                        </Link>

                        <Link
                            :href="route('coordinator.finding-qualities.index')"
                            :title="isCollapsed ? 'Finding Quality' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.finding-qualities.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Finding Quality</span>
                        </Link>

                        <Link
                            :href="route('coordinator.action-plans.index')"
                            :title="isCollapsed ? 'Action Plans' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.action-plans.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Action Plans</span>
                        </Link>

                        <Link
                            :href="route('coordinator.reports.index')"
                            :title="isCollapsed ? 'Reports' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.reports.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Reports</span>
                        </Link>
                    </div>

                    <!-- Master Data CSA -->
                    <div class="space-y-1 pt-1">
                        <div v-if="!isCollapsed" class="px-3 pb-1 text-[10px] font-semibold text-gray-500 uppercase tracking-wider">
                            Master Data CSA
                        </div>
                        <div v-else class="border-t border-gray-800 my-2"></div>

                        <Link
                            :href="route('coordinator.stores.index')"
                            :title="isCollapsed ? 'Toko & Gudang (CSA)' : ''"
                            class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                            :class="[
                                route().current('coordinator.stores.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                                isCollapsed ? 'justify-center px-2' : ''
                            ]"
                        >
                            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span v-if="!isCollapsed" class="truncate">Toko & Gudang (CSA)</span>
                        </Link>
                    </div>
                </template>

                <!-- ================= AUDITOR NAV ================= -->
                <template v-if="isAuditor">
                    <div v-if="!isCollapsed" class="px-3 pb-1 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                        Auditor Menu
                    </div>
                    <div v-else class="border-t border-gray-800 my-2"></div>

                    <Link
                        :href="route('auditor.dashboard')"
                        :title="isCollapsed ? 'Dashboard Auditor' : ''"
                        class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                        :class="[
                            route().current('auditor.dashboard') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                            isCollapsed ? 'justify-center px-2' : ''
                        ]"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        <span v-if="!isCollapsed" class="truncate">Dashboard</span>
                    </Link>

                    <Link
                        :href="route('auditor.audits.index')"
                        :title="isCollapsed ? 'My Audits' : ''"
                        class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                        :class="[
                            route().current('auditor.audits.*') || route().current('auditor.findings.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                            isCollapsed ? 'justify-center px-2' : ''
                        ]"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <span v-if="!isCollapsed" class="truncate">My Audits</span>
                    </Link>

                    <Link
                        :href="route('auditor.finding-qualities.index')"
                        :title="isCollapsed ? 'Report Finding Quality' : ''"
                        class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors"
                        :class="[
                            route().current('auditor.finding-qualities.*') ? 'bg-slate-800 text-white font-medium border border-slate-700/60 shadow-2xs' : 'text-gray-300 hover:bg-gray-800 hover:text-white',
                            isCollapsed ? 'justify-center px-2' : ''
                        ]"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span v-if="!isCollapsed" class="truncate">Finding Quality</span>
                    </Link>
                </template>

                <!-- Logout Menu Item (Dekat Master Data) -->
                <div class="space-y-1 pt-1">
                    <div class="border-t border-gray-800 my-2"></div>

                    <button
                        @click="logout"
                        :title="isCollapsed ? 'Logout' : ''"
                        type="button"
                        class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded transition-colors text-gray-300 hover:bg-gray-800 hover:text-white cursor-pointer"
                        :class="isCollapsed ? 'justify-center px-2' : ''"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span v-if="!isCollapsed" class="truncate">Logout</span>
                    </button>
                </div>
            </nav>
        </aside>

        <!-- ================= MOBILE SLIDE-OVER DRAWER ================= -->
        <div
            v-if="mobileMenuOpen"
            class="fixed inset-0 z-50 md:hidden flex"
        >
            <!-- Backdrop Overlay -->
            <div
                @click="mobileMenuOpen = false"
                class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
            ></div>

            <!-- Mobile Drawer Menu -->
            <aside class="relative flex flex-col w-72 max-w-[80vw] h-full bg-[#111827] text-gray-300 border-r border-gray-800 shadow-2xl z-10">
                <div class="h-16 flex items-center justify-between px-4 border-b border-gray-800 bg-[#0B0F17]">
                    <div class="flex items-center gap-2.5">
                        <ApplicationLogo size="w-7 h-7" />
                        <div>
                            <div class="text-sm font-bold text-white tracking-tight leading-tight">IAMS</div>
                            <div class="text-[9px] text-gray-400 font-medium uppercase tracking-wider">Sistem Audit</div>
                        </div>
                    </div>
                    <button
                        @click="mobileMenuOpen = false"
                        class="p-2 text-gray-400 hover:text-white rounded"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav class="flex-1 px-3 py-4 space-y-3 overflow-y-auto" @click="mobileMenuOpen = false">
                    <!-- Admin Mobile -->
                    <template v-if="isAdmin">
                        <Link :href="route('admin.dashboard')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Dashboard</Link>
                        <div class="px-3 pt-2 text-[10px] font-semibold text-gray-500 uppercase">Audit Management</div>
                        <Link :href="route('admin.audits.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Audits</Link>
                        <Link :href="route('admin.findings.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Findings</Link>
                        <Link :href="route('coordinator.finding-qualities.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Finding Quality</Link>
                        <Link :href="route('admin.action-plans.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Action Plans</Link>
                        <Link :href="route('admin.reports.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Reports</Link>
                        <div class="px-3 pt-2 text-[10px] font-semibold text-gray-500 uppercase">Master Data & CSA</div>
                        <Link :href="route('admin.users.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Users</Link>
                        <Link :href="route('admin.stores.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Toko & Gudang (CSA)</Link>
                        <Link :href="route('admin.audit-categories.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Audit Categories</Link>
                        <Link :href="route('admin.sops.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">SOP / SE</Link>
                        <Link :href="route('admin.notification-rules.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Jadwal Notifikasi</Link>
                        <Link :href="route('admin.settings.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Pengaturan Sistem</Link>
                    </template>

                    <!-- Coordinator / Chief / Asmen Mobile -->
                    <template v-else-if="isManagement">
                        <Link :href="route('coordinator.dashboard')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Dashboard</Link>
                        <div class="px-3 pt-2 text-[10px] font-semibold text-gray-500 uppercase">Audit Management</div>
                        <Link :href="route('coordinator.audits.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Audits</Link>
                        <Link :href="route('coordinator.findings.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Findings</Link>
                        <Link :href="route('coordinator.finding-qualities.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Finding Quality</Link>
                        <Link :href="route('coordinator.action-plans.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Action Plans</Link>
                        <Link :href="route('coordinator.reports.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Reports</Link>
                        <div class="px-3 pt-2 text-[10px] font-semibold text-gray-500 uppercase">Master Data CSA</div>
                        <Link :href="route('coordinator.stores.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Toko & Gudang (CSA)</Link>
                    </template>

                    <!-- Auditor Mobile -->
                    <template v-if="isAuditor">
                        <Link :href="route('auditor.dashboard')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Dashboard</Link>
                        <Link :href="route('auditor.audits.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">My Audits</Link>
                        <Link :href="route('auditor.finding-qualities.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Finding Quality</Link>
                        <Link :href="route('auditor.verification.index')" class="flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white">Verification Queue</Link>
                    </template>

                    <!-- Mobile Logout Item -->
                    <div class="border-t border-gray-800 my-2"></div>
                    <button
                        @click="logout"
                        type="button"
                        class="w-full flex items-center gap-3 px-3 py-2 text-xs font-medium rounded text-gray-300 hover:bg-gray-800 hover:text-white cursor-pointer"
                    >
                        <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </nav>
            </aside>
        </div>

        <!-- ================= MAIN CONTENT AREA ================= -->
        <div class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto">
            <!-- Topbar Header -->
            <header class="h-14 sm:h-16 bg-white border-b border-gray-200 flex items-center justify-between px-3 sm:px-6 shrink-0 sticky top-0 z-20">
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Mobile Hamburger -->
                    <button
                        @click="mobileMenuOpen = true"
                        class="md:hidden p-1.5 -ml-1 text-gray-600 hover:text-gray-900 rounded focus:outline-none"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <!-- Desktop Collapse Toggle Button in Topbar -->
                    <button
                        @click="toggleSidebar"
                        :title="isCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
                        class="hidden md:flex items-center justify-center p-2 rounded text-gray-500 hover:text-gray-800 hover:bg-gray-100 transition-colors"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h8m-8 6h16" />
                        </svg>
                    </button>

                    <slot name="header">
                        <h1 class="text-sm sm:text-base font-semibold text-gray-900 truncate max-w-[170px] sm:max-w-none">{{ title }}</h1>
                    </slot>
                </div>

                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Notification Bell -->
                    <div id="notification-dropdown" class="relative">
                        <button
                            @click.stop="toggleNotifications"
                            type="button"
                            title="Notifikasi"
                            class="w-9 h-9 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 flex items-center justify-center transition relative cursor-pointer"
                            :class="notificationsOpen ? 'bg-gray-100 text-gray-900' : ''"
                        >
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>

                            <!-- Badge Indicator -->
                            <span
                                v-if="notifications.count > 0"
                                class="absolute top-1.5 right-1.5 min-w-[16px] h-4 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white leading-none"
                            >
                                {{ notifications.count > 9 ? '9+' : notifications.count }}
                            </span>
                        </button>

                        <!-- Notification Dropdown Menu -->
                        <div
                            v-if="notificationsOpen"
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50 text-xs"
                        >
                            <div class="px-3.5 py-2.5 border-b border-gray-100 flex items-center justify-between">
                                <div class="font-semibold text-gray-900">Notifikasi</div>
                                <span
                                    v-if="notifications.count > 0"
                                    class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800"
                                >
                                    {{ notifications.count }} Pendaftar Baru
                                </span>
                            </div>

                            <div class="max-h-72 overflow-y-auto divide-y divide-gray-100">
                                <template v-if="notifications.items && notifications.items.length > 0">
                                    <Link
                                        v-for="item in notifications.items"
                                        :key="item.id"
                                        :href="item.url"
                                        @click="notificationsOpen = false"
                                        class="block px-3.5 py-2.5 hover:bg-slate-50 transition-colors"
                                    >
                                        <div class="flex items-start gap-2.5">
                                            <span class="w-2 h-2 rounded-full bg-blue-600 mt-1.5 shrink-0"></span>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-medium text-gray-900 truncate">{{ item.title }}</div>
                                                <div class="text-[11px] text-gray-600 mt-0.5 leading-snug">{{ item.message }}</div>
                                                <div class="text-[10px] text-gray-400 mt-1 font-mono">{{ item.time }}</div>
                                            </div>
                                        </div>
                                    </Link>
                                </template>
                                <div v-else class="px-4 py-6 text-center text-gray-400 text-xs">
                                    Tidak ada notifikasi baru
                                </div>
                            </div>

                            <div v-if="notifications.count > 0" class="p-2 border-t border-gray-100 bg-slate-50 rounded-b-lg">
                                <Link
                                    :href="route('admin.users.index', { status: 'pending' })"
                                    @click="notificationsOpen = false"
                                    class="block text-center text-xs text-blue-600 hover:text-blue-800 font-medium py-1"
                                >
                                    Tinjau Semua Pendaftar &rarr;
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- User Profile Icon Button -->
                    <div id="user-profile-dropdown" class="relative">
                        <button
                            @click.stop="toggleUserDropdown"
                            type="button"
                            title="Akun Pengguna"
                            class="w-9 h-9 rounded-full bg-slate-800 text-white flex items-center justify-center font-semibold text-xs transition hover:ring-2 hover:ring-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer shadow-xs"
                            :class="userDropdownOpen ? 'ring-2 ring-blue-500' : ''"
                        >
                            {{ userInitials }}
                        </button>

                        <!-- Dropdown Menu Popup -->
                        <div
                            v-if="userDropdownOpen"
                            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-1.5 z-50 text-xs"
                        >
                            <!-- User Info Header -->
                            <div class="px-3.5 py-2.5 border-b border-gray-100">
                                <p class="font-semibold text-gray-900 truncate">{{ user.name }}</p>
                                <p class="text-[11px] text-gray-500 truncate mt-0.5">{{ user.email }}</p>
                                <div class="mt-1.5">
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ roleDisplayLabel }}
                                    </span>
                                </div>
                            </div>

                            <!-- Logout Action -->
                            <div class="p-1">
                                <button
                                    @click="logout"
                                    type="button"
                                    class="w-full flex items-center gap-2.5 px-3 py-2 text-left text-red-600 hover:bg-red-50 rounded-md transition-colors font-medium text-xs cursor-pointer"
                                >
                                    <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    <span>Logout (Keluar)</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Subtle Top Progress Indicator on Page Navigation -->
            <div v-if="isPageLoading" class="h-0.5 w-full bg-blue-100 sticky top-16 z-30 overflow-hidden">
                <div class="h-full bg-blue-600 animate-shimmer w-full"></div>
            </div>

            <!-- Main Content Container with Smooth Transition -->
            <main class="flex-1 p-3 sm:p-6 md:p-8 max-w-7xl w-full mx-auto animate-in fade-in duration-200">
                <FlashMessage />
                <slot />
            </main>
        </div>
    </div>
</template>
