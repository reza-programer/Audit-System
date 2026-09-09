<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    qualityFinding: {
        type: Object,
        required: true,
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
});

const formatRupiah = (number) => {
    if (!number) return 'Rp 0';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const printReport = () => {
    window.print();
};

const deleteQualityFinding = () => {
    if (confirm('Yakin ingin menghapus laporan Finding Quality ini?')) {
        router.delete(route('auditor.finding-qualities.destroy', props.qualityFinding.id));
    }
};
</script>

<template>
    <AppLayout title="Detail Laporan Finding Quality">
        <Head :title="`Finding Quality - ${qualityFinding.title}`" />

        <!-- Breadcrumb & Top Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 print:hidden">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('auditor.finding-qualities.index')" class="hover:text-blue-600">Finding Quality</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium font-mono">#FQ-{{ String(qualityFinding.id).padStart(4, '0') }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight">
                    Lembar Laporan Finding Quality
                </h1>
            </div>

            <div class="flex items-center gap-2.5">
                <button
                    @click="printReport"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs"
                >
                    <svg class="w-4 h-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Cetak / Print PDF
                </button>

                <button
                    @click="deleteQualityFinding"
                    class="inline-flex items-center gap-1 px-3 py-2 text-xs font-medium rounded border border-red-200 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors"
                >
                    Hapus
                </button>
            </div>
        </div>

        <!-- Main Corporate Audit Sheet -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-xs overflow-hidden text-xs max-w-4xl mx-auto">
            <!-- Sheet Header -->
            <div class="p-6 border-b border-gray-200 bg-slate-50/70">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                            <template v-if="qualityFinding.quality_categories_info && qualityFinding.quality_categories_info.length">
                                <span
                                    v-for="cat in qualityFinding.quality_categories_info"
                                    :key="cat.id"
                                    class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-200 text-slate-800 border border-slate-300"
                                >
                                    {{ cat.label }}
                                </span>
                            </template>
                            <span v-else class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-200 text-slate-800 border border-slate-300">
                                {{ qualityFinding.categories_info?.label || qualityFinding.quality_category }}
                            </span>
                            <span class="font-mono text-gray-500">#FQ-{{ String(qualityFinding.id).padStart(4, '0') }}</span>
                        </div>
                        <h2 class="text-base sm:text-lg font-bold text-gray-900 leading-snug">
                            {{ qualityFinding.title }}
                        </h2>
                    </div>

                    <div class="text-left sm:text-right shrink-0 text-[11px] text-gray-500 font-mono space-y-0.5">
                        <div>Tanggal: {{ qualityFinding.created_at }}</div>
                        <div>Pelapor: <span class="font-medium text-gray-800 font-sans">{{ qualityFinding.reported_by }}</span></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-5 pt-4 border-t border-gray-200 text-xs">
                    <div>
                        <div class="text-gray-400 font-medium text-[11px]">Nomor Surat Tugas</div>
                        <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ qualityFinding.audit.audit_number }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 font-medium text-[11px]">Unit / Toko</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ qualityFinding.audit.store_name }}</div>
                    </div>
                    <div>
                        <div class="text-gray-400 font-medium text-[11px]">Nilai Dampak Finansial</div>
                        <div class="font-mono font-bold text-gray-900 mt-0.5">
                            {{ formatRupiah(qualityFinding.impact_amount || qualityFinding.finding.loss_amount) }}
                        </div>
                    </div>
                    <div>
                        <div class="text-gray-400 font-medium text-[11px]">Severity & Status</div>
                        <div class="mt-1 flex items-center gap-1.5">
                            <SeverityBadge :severity="qualityFinding.finding.severity" />
                            <StatusBadge :status="qualityFinding.finding.status" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Report Body -->
            <div class="p-6 space-y-5">
                <!-- 1. Uraian Temuan -->
                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                        1. Uraian Temuan Pemeriksaan
                    </h3>
                    <div class="bg-gray-50 p-3.5 rounded border border-gray-200 text-gray-800 leading-relaxed">
                        {{ qualityFinding.finding.finding }}
                    </div>
                </div>

                <!-- 2. Root Cause & Kelemahan Sistemik -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                            2. Analisis Akar Masalah (Root Cause)
                        </h3>
                        <div class="bg-white p-3.5 rounded border border-gray-200 text-gray-800 leading-relaxed whitespace-pre-line">
                            {{ qualityFinding.root_cause }}
                        </div>
                    </div>

                    <div>
                        <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                            3. Kelemahan Sistemik / Pengendalian Internal
                        </h3>
                        <div class="bg-white p-3.5 rounded border border-gray-200 text-gray-800 leading-relaxed whitespace-pre-line">
                            {{ qualityFinding.systemic_issue || 'Kelemahan kontrol internal di tingkat operasional unit yang memerlukan revisi SOP atau supervisi terpusat.' }}
                        </div>
                    </div>
                </div>

                <!-- 4. Rekomendasi Solusi -->
                <div>
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                        4. Rekomendasi & Mitigasi Strategis
                    </h3>
                    <div class="bg-white p-3.5 rounded border border-gray-200 text-gray-800 leading-relaxed whitespace-pre-line">
                        {{ qualityFinding.recommendation }}
                    </div>
                </div>

                <!-- 5. Catatan Khusus Auditor -->
                <div v-if="qualityFinding.auditor_notes">
                    <h3 class="text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-1.5">
                        5. Catatan Tambahan Auditor
                    </h3>
                    <div class="bg-white p-3 rounded border border-gray-200 text-gray-700 leading-relaxed">
                        {{ qualityFinding.auditor_notes }}
                    </div>
                </div>

                <!-- Signatures Section for Printing -->
                <div class="pt-8 border-t border-gray-200 grid grid-cols-3 gap-6 text-center text-xs">
                    <div>
                        <div class="text-gray-500 mb-12">Auditor Pelapor:</div>
                        <div class="font-semibold text-gray-900 border-t border-gray-300 pt-1.5">{{ qualityFinding.reported_by }}</div>
                        <div class="text-[10px] text-gray-400">Auditor Lapangan</div>
                    </div>
                    <div>
                        <div class="text-gray-500 mb-12">Koordinator / Asmen:</div>
                        <div class="font-semibold text-gray-900 border-t border-gray-300 pt-1.5">( ........................................ )</div>
                        <div class="text-[10px] text-gray-400">Koordinator Audit / Asmen</div>
                    </div>
                    <div>
                        <div class="text-gray-500 mb-12">Chief Auditor:</div>
                        <div class="font-semibold text-gray-900 border-t border-gray-300 pt-1.5">( ........................................ )</div>
                        <div class="text-[10px] text-gray-400">Chief Internal Audit</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
