<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    by_severity: {
        type: Object,
        required: true,
    },
    by_category: {
        type: Array,
        default: () => [],
    },
    store_losses: {
        type: Array,
        default: () => [],
    },
    by_status: {
        type: Object,
        required: true,
    },
    total_loss: {
        type: Number,
        default: 0,
    },
    total_findings: {
        type: Number,
        default: 0,
    },
    completion_rate: {
        type: Number,
        default: 0,
    },
    closed_on_time: {
        type: Number,
        default: 0,
    },
    closed_overdue: {
        type: Number,
        default: 0,
    },
});

const exportDropdownOpen = ref(false);

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

// Robust calculations to ensure counts are always accurate
const totalFindingsCount = computed(() => {
    const fromSev = Object.values(props.by_severity || {}).reduce((a, b) => a + b, 0);
    if (fromSev > 0) return fromSev;
    const fromStatus = Object.values(props.by_status || {}).reduce((a, b) => a + b, 0);
    if (fromStatus > 0) return fromStatus;
    return props.total_findings || 0;
});

const calculatedCompletionRate = computed(() => {
    const total = Object.values(props.by_status || {}).reduce((a, b) => a + b, 0);
    const closed = props.by_status?.CLOSED || 0;
    if (total > 0) {
        return Math.round((closed / total) * 100);
    }
    return props.completion_rate || 0;
});

const highRiskCount = computed(() => {
    return (props.by_severity.CRITICAL || 0) + (props.by_severity.MAJOR || 0);
});

// Color definitions
const severityColors = {
    CRITICAL: '#e11d48',
    MAJOR: '#ea580c',
    MINOR: '#2563eb',
    OBSERVATION: '#64748b',
};

const statusColors = {
    OPEN: '#ef4444',
    IN_PROGRESS: '#f59e0b',
    WAITING_VERIFICATION: '#0284c7',
    VERIFIED: '#6366f1',
    CLOSED: '#10b981',
};

// SVG Donut Calculations for Severity
const severitySegments = computed(() => {
    const total = Object.values(props.by_severity).reduce((a, b) => a + b, 0);
    const C = 2 * Math.PI * 38;
    let accumulated = 0;

    return Object.entries(props.by_severity).map(([sev, count]) => {
        const percent = total > 0 ? count / total : 0;
        const length = percent * C;
        const offset = -accumulated;
        accumulated += length;
        return {
            key: sev,
            count,
            percent: Math.round(percent * 100),
            color: severityColors[sev] || '#94a3b8',
            dasharray: `${length} ${C - length}`,
            dashoffset: offset,
        };
    });
});

// SVG Donut Calculations for Status
const statusSegments = computed(() => {
    const total = Object.values(props.by_status).reduce((a, b) => a + b, 0);
    const C = 2 * Math.PI * 38;
    let accumulated = 0;

    return Object.entries(props.by_status).map(([st, count]) => {
        const percent = total > 0 ? count / total : 0;
        const length = percent * C;
        const offset = -accumulated;
        accumulated += length;
        return {
            key: st,
            count,
            percent: Math.round(percent * 100),
            color: statusColors[st] || '#94a3b8',
            dasharray: `${length} ${C - length}`,
            dashoffset: offset,
        };
    });
});

// Max store loss for proportional bar chart
const maxStoreLoss = computed(() => {
    if (!props.store_losses.length) return 1;
    const max = Math.max(...props.store_losses.map(s => s.total_loss));
    return max > 0 ? max : 1;
});

// Top stores by loss
const topLossStores = computed(() => {
    return props.store_losses.filter(s => s.total_loss > 0).slice(0, 5);
});
</script>

<template>
    <AppLayout title="Laporan & Rekap Audit">
        <Head title="Laporan & Rekap Audit — Administrator" />

        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">Laporan & Rekapitulasi Audit Retail</h1>
                <p class="text-xs text-gray-500 mt-1">Distribusi severity risiko temuan, efektivitas tindak lanjut, dan visualisasi grafik audit CSA</p>
            </div>

            <!-- Single Trigger Button & Smooth Dropdown Menu directly underneath -->
            <div class="relative">
                <button
                    type="button"
                    @click="exportDropdownOpen = !exportDropdownOpen"
                    class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-md bg-emerald-800 hover:bg-emerald-900 text-white shadow-xs transition-colors cursor-pointer"
                >
                    <span>Download Laporan Excel</span>
                    <svg
                        class="w-3.5 h-3.5 text-emerald-200 transition-transform duration-200"
                        :class="{ 'rotate-180': exportDropdownOpen }"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Invisible overlay to close dropdown when clicking outside -->
                <div
                    v-if="exportDropdownOpen"
                    class="fixed inset-0 z-20"
                    @click="exportDropdownOpen = false"
                ></div>

                <!-- Smooth Dropdown menu directly below the button (no icons or emojis) -->
                <Transition
                    enter-active-class="transition ease-out duration-150"
                    enter-from-class="transform opacity-0 scale-95 -translate-y-1"
                    enter-to-class="transform opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition ease-in duration-100"
                    leave-from-class="transform opacity-100 scale-100 translate-y-0"
                    leave-to-class="transform opacity-0 scale-95 -translate-y-1"
                >
                    <div
                        v-if="exportDropdownOpen"
                        class="absolute right-0 mt-1.5 w-56 rounded-lg border border-gray-200 bg-white shadow-lg z-30 py-1 text-xs origin-top-right divide-y divide-gray-100"
                    >
                        <a
                            :href="route('admin.reports.export-findings')"
                            @click="exportDropdownOpen = false"
                            class="block px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors font-medium"
                        >
                            Rekap Seluruh Temuan (.xls)
                        </a>

                        <a
                            :href="route('admin.reports.export-stores')"
                            @click="exportDropdownOpen = false"
                            class="block px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors font-medium"
                        >
                            Rekapitulasi per Toko (.xls)
                        </a>

                        <a
                            :href="route('admin.reports.export-summary')"
                            @click="exportDropdownOpen = false"
                            class="block px-4 py-2.5 text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors font-medium"
                        >
                            Ringkasan Eksekutif (.xls)
                        </a>
                    </div>
                </Transition>
            </div>
        </div>

        <!-- 4 Key Metrics Overview Cards (Matching Screenshot Exactly) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">TOTAL KERUGIAN</div>
                <div class="text-2xl font-bold text-gray-900 font-mono mt-1.5">{{ formatRupiah(total_loss) }}</div>
                <div class="text-xs text-gray-400 mt-1">Akumulasi cabang CSA</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">TOTAL TEMUAN</div>
                <div class="text-2xl font-bold text-gray-900 mt-1.5">{{ totalFindingsCount }} Temuan</div>
                <div class="text-xs text-gray-400 mt-1">Dari hasil pemeriksaan</div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">TINGKAT CLOSED</div>
                <div class="text-2xl font-bold text-emerald-600 mt-1.5">{{ calculatedCompletionRate }}%</div>
                <div class="text-xs text-gray-400 mt-1 flex flex-wrap items-center gap-1.5">
                    <span><b class="text-gray-700">{{ by_status.CLOSED || 0 }}</b> All</span>
                    <span class="text-gray-300">•</span>
                    <span class="text-emerald-700 font-medium"><b>{{ closed_on_time || 0 }}</b> On Time</span>
                    <span class="text-gray-300">•</span>
                    <span class="text-rose-600 font-medium"><b>{{ closed_overdue || 0 }}</b> Overdue</span>
                </div>
            </div>

            <div class="bg-white p-5 rounded-xl border border-gray-200/80 shadow-xs">
                <div class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">RISIKO TINGGI</div>
                <div class="text-2xl font-bold text-rose-600 mt-1.5">{{ highRiskCount }} Temuan</div>
                <div class="text-xs text-rose-500 font-medium mt-1">Major & Critical</div>
            </div>
        </div>

        <!-- Visual Charts Row 1: Donut & Progress Diagrams -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Diagram 1: Distribusi Severity Temuan -->
            <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs text-xs">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                            Distribusi Severity Temuan
                        </h2>
                        <p class="text-[11px] text-gray-400 mt-0.5">Proporsi tingkat keparahan risiko temuan audit</p>
                    </div>
                    <span class="text-[11px] font-mono text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                        {{ totalFindingsCount }} Total
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <!-- SVG Donut Chart -->
                    <div class="relative w-36 h-36 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90 transform">
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="11" />
                            <circle
                                v-for="seg in severitySegments"
                                :key="seg.key"
                                v-show="seg.count > 0"
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="11"
                                :stroke-dasharray="seg.dasharray"
                                :stroke-dashoffset="seg.dashoffset"
                                class="transition-all duration-500"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                            <span class="text-xl font-bold text-gray-900 leading-tight">{{ totalFindingsCount }}</span>
                            <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400">Temuan</span>
                        </div>
                    </div>

                    <!-- Progress Bar Breakdown -->
                    <div class="flex-1 w-full space-y-3">
                        <div v-for="seg in severitySegments" :key="seg.key" class="space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                    <span class="font-medium text-gray-700 capitalize">{{ seg.key.toLowerCase() }}</span>
                                </div>
                                <div class="font-mono text-gray-900">
                                    <span class="font-semibold">{{ seg.count }}</span>
                                    <span class="text-gray-400 text-[10px] ml-1">({{ seg.percent }}%)</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${seg.percent}%`, backgroundColor: seg.color }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Diagram 2: Distribusi Status Penyelesaian -->
            <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs text-xs">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                            Distribusi Status Penyelesaian
                        </h2>
                        <p class="text-[11px] text-gray-400 mt-0.5">Progres alur tindak lanjut dan penutupan temuan</p>
                    </div>
                    <span class="text-[11px] font-mono text-emerald-700 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded font-semibold">
                        {{ calculatedCompletionRate }}% Selesai
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    <!-- SVG Donut Chart -->
                    <div class="relative w-36 h-36 flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 100 100" class="w-full h-full -rotate-90 transform">
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#f1f5f9" stroke-width="11" />
                            <circle
                                v-for="seg in statusSegments"
                                :key="seg.key"
                                v-show="seg.count > 0"
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                :stroke="seg.color"
                                stroke-width="11"
                                :stroke-dasharray="seg.dasharray"
                                :stroke-dashoffset="seg.dashoffset"
                                class="transition-all duration-500"
                            />
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none">
                            <span class="text-xl font-bold text-emerald-700 leading-tight">{{ calculatedCompletionRate }}%</span>
                            <span class="text-[10px] uppercase font-semibold tracking-wider text-gray-400">Ditutup</span>
                        </div>
                    </div>

                    <!-- Progress Bar Breakdown -->
                    <div class="flex-1 w-full space-y-2.5">
                        <div v-for="seg in statusSegments" :key="seg.key" class="space-y-1">
                            <div class="flex items-center justify-between text-[11px]">
                                <div class="flex items-center gap-1.5">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: seg.color }"></span>
                                    <span class="font-medium text-gray-700">{{ seg.key.replace('_', ' ') }}</span>
                                </div>
                                <div class="font-mono text-gray-900">
                                    <span class="font-semibold">{{ seg.count }}</span>
                                    <span class="text-gray-400 text-[10px] ml-1">({{ seg.percent }}%)</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{ width: `${seg.percent}%`, backgroundColor: seg.color }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Visual Charts Row 2: Perbandingan Kerugian per Cabang & Kategori Audit -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-8 text-xs">
            <!-- Diagram 3: Grafik Kerugian Cabang Terbesar (7 cols) -->
            <div class="lg:col-span-7 bg-white rounded-lg border border-gray-200 p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                            Grafik Kerugian Finansial per Cabang
                        </h2>
                        <p class="text-[11px] text-gray-400 mt-0.5">Perbandingan nominal kerugian toko dengan temuan audit tertinggi</p>
                    </div>
                    <span class="text-[11px] font-mono font-bold text-gray-900">
                        {{ formatRupiah(total_loss) }}
                    </span>
                </div>

                <div v-if="topLossStores.length > 0" class="space-y-3.5">
                    <div v-for="store in topLossStores" :key="store.store_code" class="space-y-1.5">
                        <div class="flex items-center justify-between text-[11px]">
                            <div class="font-medium text-gray-900 truncate mr-2">
                                {{ store.store_name }} <span class="text-gray-400 font-mono text-[10px]">({{ store.store_code }})</span>
                            </div>
                            <div class="font-mono font-bold text-gray-900 shrink-0">
                                {{ formatRupiah(store.total_loss) }}
                            </div>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden flex items-center">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-blue-600 to-indigo-600 transition-all duration-500"
                                :style="{ width: `${Math.max(5, (store.total_loss / maxStoreLoss) * 100)}%` }"
                            ></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-gray-400">
                            <span>Area: {{ store.area || 'Retail' }}</span>
                            <span>{{ store.total_findings }} temuan dari {{ store.total_audits }} audit</span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-gray-400 italic">
                    Belum ada data kerugian finansial cabang.
                </div>
            </div>

            <!-- Diagram 4: Distribusi Kategori Audit (5 cols) -->
            <div class="lg:col-span-5 bg-white rounded-lg border border-gray-200 p-5 shadow-xs">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-700">
                            Kategori Audit (CSA)
                        </h2>
                        <p class="text-[11px] text-gray-400 mt-0.5">Sebaran temuan pada 3 fokus bidang audit</p>
                    </div>
                    <span class="text-[11px] font-mono text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                        {{ by_category.length }} Kategori
                    </span>
                </div>

                <div v-if="by_category.length > 0" class="space-y-3">
                    <div
                        v-for="cat in by_category"
                        :key="cat.id"
                        class="p-3 rounded-lg border border-gray-200 bg-gray-50/50 space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-gray-900 text-xs">{{ cat.name }}</span>
                            <span class="font-bold text-blue-700 font-mono text-xs">{{ cat.count }} Temuan</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                            <div
                                class="h-full rounded-full bg-blue-600 transition-all duration-500"
                                :style="{ width: `${totalFindingsCount > 0 ? (cat.count / totalFindingsCount) * 100 : 0}%` }"
                            ></div>
                        </div>
                        <div class="flex items-center justify-between text-[10px] text-gray-500 pt-0.5">
                            <span>Estimasi Kerugian:</span>
                            <span class="font-mono font-medium text-gray-800">{{ formatRupiah(cat.total_loss) }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-gray-400 italic">
                    Belum ada data kategori audit.
                </div>
            </div>
        </div>

        <!-- Store Loss Ranking Table / List -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden shadow-xs text-xs">
            <div class="p-3 sm:p-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5 sm:gap-2 bg-gray-50/50">
                <div>
                    <h2 class="text-xs sm:text-sm font-semibold text-gray-900">Rekapitulasi Temuan & Kerugian per Cabang</h2>
                    <p class="text-[10px] sm:text-[11px] text-gray-500">Peringkat kerugian retail berdasarkan hasil temuan audit</p>
                </div>
                <div class="text-[11px] sm:text-xs font-semibold text-gray-700">
                    Total Loss: <span class="text-gray-900 font-bold text-xs sm:text-sm font-mono">{{ formatRupiah(total_loss) }}</span>
                </div>
            </div>

            <!-- 1. Mobile List View (No horizontal scrolling) -->
            <div class="block md:hidden divide-y divide-gray-100">
                <div v-if="store_losses.length === 0" class="p-5 text-center text-gray-400 text-xs">
                    Belum ada data rekapitulasi cabang.
                </div>
                <div
                    v-for="s in store_losses"
                    :key="'m-sl-' + s.store_code"
                    class="p-3.5 space-y-2 hover:bg-gray-50 transition-colors"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ s.store_name }}</div>
                            <div class="font-mono text-[10px] text-gray-400 mt-0.5">{{ s.store_code }} • Area: {{ s.area || '-' }}</div>
                        </div>
                        <div class="font-mono font-bold text-xs text-gray-900 shrink-0">
                            {{ formatRupiah(s.total_loss) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1.5 border-t border-gray-100">
                        <span>Total Audit: <strong class="text-gray-700 font-medium">{{ s.total_audits }} kali</strong></span>
                        <span class="font-semibold text-slate-800">{{ s.total_findings }} temuan</span>
                    </div>
                </div>
            </div>

            <!-- 2. Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-[10px] tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-5 py-3">Kode & Nama Toko</th>
                            <th class="px-5 py-3">Wilayah / Area</th>
                            <th class="px-5 py-3">Total Audit</th>
                            <th class="px-5 py-3">Total Temuan</th>
                            <th class="px-5 py-3 text-right">Akumulasi Kerugian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="s in store_losses" :key="s.store_code" class="hover:bg-gray-50/70">
                            <td class="px-5 py-3.5 font-medium text-gray-900">
                                {{ s.store_name }} <span class="text-gray-500 font-mono">({{ s.store_code }})</span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-700">{{ s.area || '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-700">{{ s.total_audits }} kali</td>
                            <td class="px-5 py-3.5 font-semibold text-gray-900">{{ s.total_findings }} temuan</td>
                            <td class="px-5 py-3.5 text-right font-bold font-mono text-gray-900">
                                {{ formatRupiah(s.total_loss) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
