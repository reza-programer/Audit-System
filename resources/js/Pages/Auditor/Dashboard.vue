<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    recent_audits: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <AppLayout title="Auditor Dashboard">
        <Head title="Auditor Dashboard" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6">
            <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Auditor Dashboard</h1>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Ringkasan penugasan audit dan temuan yang menjadi tanggung jawab Anda</p>
        </div>

        <!-- Metrics Overview (Clean Corporate Grid) -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 sm:gap-3 mb-6 sm:mb-8">
            <div class="bg-white p-3.5 sm:p-4 rounded border border-gray-200">
                <div class="text-[10px] sm:text-[11px] font-medium text-gray-500 uppercase tracking-wider">Assigned Audits</div>
                <div class="text-xl sm:text-2xl font-semibold text-gray-900 mt-1">{{ stats.assigned_audits }}</div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded border border-gray-200">
                <div class="text-[10px] sm:text-[11px] font-medium text-red-600 uppercase tracking-wider">Open Findings</div>
                <div class="text-xl sm:text-2xl font-semibold text-red-600 mt-1">{{ stats.open_findings }}</div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded border border-gray-200">
                <div class="text-[10px] sm:text-[11px] font-medium text-amber-600 uppercase tracking-wider">In Progress</div>
                <div class="text-xl sm:text-2xl font-semibold text-amber-600 mt-1">{{ stats.in_progress_findings }}</div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded border border-blue-200 bg-blue-50/20">
                <div class="text-[10px] sm:text-[11px] font-medium text-blue-700 uppercase tracking-wider">Waiting Verify</div>
                <div class="text-xl sm:text-2xl font-semibold text-blue-700 mt-1">{{ stats.waiting_verification }}</div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded border border-emerald-200 bg-emerald-50/20">
                <div class="text-[10px] sm:text-[11px] font-medium text-emerald-700 uppercase tracking-wider">Closed (All)</div>
                <div class="flex items-center justify-between gap-1.5 mt-1">
                    <div class="text-xl sm:text-2xl font-bold text-emerald-700 leading-none shrink-0">{{ stats.closed_findings }}</div>
                    <div class="flex items-center gap-1 shrink-0">
                        <div class="bg-emerald-100/80 rounded px-1.5 py-0.5 text-center border border-emerald-200/80" title="Closed tepat waktu">
                            <div class="text-[8px] sm:text-[8.5px] font-semibold text-emerald-700 uppercase tracking-tight leading-none">On Time</div>
                            <div class="text-[11px] sm:text-xs font-bold text-emerald-800 mt-0.5 leading-none">{{ stats.closed_on_time ?? 0 }}</div>
                        </div>
                        <div class="bg-rose-100/80 rounded px-1.5 py-0.5 text-center border border-rose-200/80" title="Closed melewati deadline">
                            <div class="text-[8px] sm:text-[8.5px] font-semibold text-rose-600 uppercase tracking-tight leading-none">Overdue</div>
                            <div class="text-[11px] sm:text-xs font-bold text-rose-700 mt-0.5 leading-none">{{ stats.closed_overdue ?? 0 }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-3.5 sm:p-4 rounded border border-red-200 bg-red-50/30">
                <div class="text-[10px] sm:text-[11px] font-medium text-red-700 uppercase tracking-wider">Overdue Actions</div>
                <div class="text-xl sm:text-2xl font-semibold text-red-700 mt-1">{{ stats.overdue_findings }}</div>
            </div>
        </div>

        <!-- Recent Audits Table / List -->
        <div class="bg-white rounded border border-gray-200 overflow-hidden shadow-2xs">
            <div class="px-4 py-3 sm:px-5 sm:py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-xs sm:text-sm font-semibold text-gray-900">Recent Assigned Audits</h2>
                    <p class="text-[10px] sm:text-xs text-gray-500">Daftar penugasan audit terkini</p>
                </div>
                <Link
                    :href="route('auditor.audits.index')"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800"
                >
                    Lihat Semua Audit →
                </Link>
            </div>

            <!-- 1. Mobile List View (No horizontal scrolling) -->
            <div class="block sm:hidden divide-y divide-gray-100">
                <div v-if="recent_audits.length === 0" class="p-5 text-center text-gray-400 text-xs">
                    Belum ada penugasan audit untuk akun Anda.
                </div>
                <div
                    v-for="audit in recent_audits"
                    :key="'m-auditor-audit-' + audit.id"
                    class="p-3.5 space-y-2 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ audit.store }}</div>
                            <div class="font-mono text-[11px] text-gray-500 mt-0.5">{{ audit.audit_number }}</div>
                        </div>
                        <StatusBadge :status="audit.status" />
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1.5 border-t border-gray-100">
                        <span>Tanggal: <strong class="text-gray-700 font-medium">{{ audit.audit_date }}</strong></span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-800">
                            {{ audit.findings_count }} temuan
                        </span>
                    </div>
                    <div class="pt-1.5 flex justify-end">
                        <Link
                            :href="route('auditor.audits.show', audit.id)"
                            class="inline-flex items-center px-3 py-1 text-xs font-medium rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                        >
                            Detail & Temuan →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- 2. Desktop Table View -->
            <div class="hidden sm:block overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3">No. Audit</th>
                            <th class="px-5 py-3">Toko</th>
                            <th class="px-5 py-3">Tanggal Audit</th>
                            <th class="px-5 py-3">Findings</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-if="recent_audits.length === 0">
                            <td colspan="6" class="px-5 py-8 text-center text-gray-500">
                                Belum ada penugasan audit untuk akun Anda.
                            </td>
                        </tr>
                        <tr
                            v-for="audit in recent_audits"
                            :key="audit.id"
                            class="hover:bg-gray-50/70 transition-colors"
                        >
                            <td class="px-5 py-3 font-mono font-medium text-gray-900">
                                {{ audit.audit_number }}
                            </td>
                            <td class="px-5 py-3 font-medium text-gray-900">
                                {{ audit.store }}
                            </td>
                            <td class="px-5 py-3 text-gray-600">
                                {{ audit.audit_date }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ audit.findings_count }} temuan
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <StatusBadge :status="audit.status" />
                            </td>
                            <td class="px-5 py-3 text-right">
                                <Link
                                    :href="route('auditor.audits.show', audit.id)"
                                    class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded border border-gray-300 text-gray-700 bg-white hover:bg-gray-50 transition-colors"
                                >
                                    Detail & Temuan
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
