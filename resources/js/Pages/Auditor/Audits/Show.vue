<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';

const props = defineProps({
    audit: {
        type: Object,
        required: true,
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

// Delete finding state
const deleteModalOpen = ref(false);
const selectedFindingToDelete = ref(null);

const confirmDeleteFinding = (finding) => {
    selectedFindingToDelete.value = finding;
    deleteModalOpen.value = true;
};

const executeDeleteFinding = () => {
    if (!selectedFindingToDelete.value) return;

    router.delete(route('auditor.findings.destroy', selectedFindingToDelete.value.id), {
        onSuccess: () => {
            deleteModalOpen.value = false;
            selectedFindingToDelete.value = null;
        },
    });
};

// Upload Document State (LHP & BAP Bertanda Tangan)
const uploadDocModalOpen = ref(false);
const docForm = useForm({
    document_type: 'LHP',
    title: '',
    file: null,
    notes: '',
});

const submitDoc = () => {
    docForm.post(route('auditor.audits.documents.store', props.audit.id), {
        onSuccess: () => {
            uploadDocModalOpen.value = false;
            docForm.reset();
        },
    });
};

const deleteDoc = (docId) => {
    if (confirm('Yakin ingin menghapus dokumen ini?')) {
        router.delete(route('auditor.documents.destroy', docId));
    }
};
</script>

<template>
    <AppLayout :title="`Audit ${audit.audit_number}`">
        <Head :title="`Detail Audit ${audit.audit_number}`" />

        <!-- Top Breadcrumb & Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('auditor.audits.index')" class="hover:text-blue-600">My Audits</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">{{ audit.audit_number }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-2.5 flex-wrap">
                    {{ audit.audit_number }}
                    <span
                        v-if="audit.category"
                        class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold tracking-wide border"
                        :class="{
                            'bg-emerald-50 text-emerald-800 border-emerald-200': audit.category.includes('Retail'),
                            'bg-amber-50 text-amber-800 border-amber-200': audit.category.includes('Finance'),
                            'bg-indigo-50 text-indigo-800 border-indigo-200': audit.category.includes('Distribusi'),
                            'bg-slate-100 text-slate-700 border-slate-200': !audit.category
                        }"
                    >
                        {{ audit.category }}
                    </span>
                    <StatusBadge :status="audit.status" />
                </h1>
            </div>

            <div class="flex items-center flex-wrap gap-2.5">
                <!-- Upload LHP/BAP Button -->
                <button
                    @click="uploadDocModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-xs"
                >
                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Upload LHP / BAP Bertanda Tangan
                </button>

                <!-- Tambah Finding Button -->
                <Link
                    :href="route('auditor.findings.create', audit.id)"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-xs"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Tambah Finding Baru
                </Link>
            </div>
        </div>

        <!-- Audit Information Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-6 shadow-xs">
            <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-4 pb-2 border-b border-gray-100 flex items-center justify-between">
                <span>Informasi Surat Tugas Audit</span>
                <span class="text-[11px] font-mono text-gray-400">ID: #{{ audit.id }}</span>
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-xs">
                <div>
                    <div class="text-gray-500 font-medium">Kategori Audit</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.category || 'Audit Umum' }}</div>
                    <div class="text-[11px] text-gray-500 font-mono">{{ audit.title || 'Surat Tugas Audit' }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Unit / Toko / Gudang</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.store.name }}</div>
                    <div class="text-[11px] text-gray-500 font-mono">
                        {{ audit.store.code }} • {{ audit.store.business_entity || 'Unit Ritel' }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Tanggal Pelaksanaan</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.audit_date }}</div>
                    <div class="text-[11px] text-gray-500">Area: {{ audit.store.area || '-' }}</div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Tim Auditor (Maks. 5 Orang)</div>
                    <div class="text-gray-900 font-semibold mt-1 flex items-center gap-1">
                        <span>{{ audit.auditor.name }}</span>
                        <span class="text-[10px] px-1.5 py-0.2 rounded bg-blue-100 text-blue-800 font-normal">Lead</span>
                    </div>
                    <div v-if="audit.auditors && audit.auditors.length > 1" class="text-[11px] text-gray-500 mt-1 flex flex-wrap gap-1">
                        <span v-for="team in audit.auditors.filter(a => a.id !== audit.auditor.id)" :key="team.id" class="px-1.5 py-0.5 bg-gray-100 rounded text-gray-700">
                            + {{ team.name }}
                        </span>
                    </div>
                </div>

                <div>
                    <div class="text-gray-500 font-medium">Statistik Temuan</div>
                    <div class="text-gray-900 font-semibold mt-1">{{ audit.findings.length }} Finding(s)</div>
                    <div class="text-[11px] text-gray-500">{{ audit.documents?.length || 0 }} Dokumen Bukti / BAP</div>
                </div>
            </div>

            <div v-if="audit.notes" class="mt-4 pt-3 border-t border-gray-100 text-xs">
                <span class="text-gray-500 font-medium">Catatan Audit: </span>
                <span class="text-gray-700">{{ audit.notes }}</span>
            </div>
        </div>

        <!-- Dokumen Bukti Lapangan / LHP & BAP Section -->
        <!-- Dokumen LHP & BAP Section (Clean Enterprise Table) -->
        <div class="bg-white rounded-lg border border-gray-200 p-5 mb-8 shadow-xs text-xs">
            <div class="flex items-center justify-between pb-3 border-b border-gray-200 mb-3">
                <div class="flex items-center gap-2">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-900">
                        Dokumen Bukti Lapangan (LHP & BAP Bertanda Tangan)
                    </h2>
                    <span class="px-2 py-0.5 text-[10px] font-semibold rounded bg-slate-100 text-slate-700 border border-slate-200">
                        {{ audit.documents?.length || 0 }} File
                    </span>
                </div>

                <button
                    @click="uploadDocModalOpen = true"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 transition-colors shadow-2xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Unggah LHP / BAP
                </button>
            </div>

            <div v-if="!audit.documents || audit.documents.length === 0" class="text-center py-6 text-gray-400 bg-gray-50/50 rounded border border-dashed border-gray-200">
                Belum ada dokumen LHP / BAP bertanda tangan yang diunggah untuk audit ini.
            </div>

            <div v-else class="border border-gray-200 rounded overflow-hidden">
                <!-- 1. Mobile Card View -->
                <div class="block md:hidden divide-y divide-gray-100">
                    <div
                        v-for="doc in audit.documents"
                        :key="'m-doc-' + doc.id"
                        class="p-3.5 space-y-2 bg-white"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span
                                    class="inline-block px-2 py-0.5 text-[10px] font-bold rounded mb-1"
                                    :class="doc.document_type === 'LHP' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (doc.document_type === 'BAP' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-700 border border-gray-200')"
                                >
                                    {{ doc.document_type }}
                                </span>
                                <div class="font-semibold text-gray-900 text-xs">{{ doc.title }}</div>
                                <div class="font-mono text-[10px] text-gray-500 mt-0.5">{{ doc.file_name }} ({{ doc.file_size }})</div>
                            </div>
                        </div>

                        <div v-if="doc.notes" class="text-[11px] text-gray-600 bg-slate-50 p-2 rounded border border-gray-100">
                            {{ doc.notes }}
                        </div>

                        <div class="flex items-center justify-between pt-1 text-[10px] text-gray-400 border-t border-gray-100">
                            <span>{{ doc.created_at }}</span>
                            <div class="flex items-center gap-1.5">
                                <a
                                    :href="doc.file_url"
                                    target="_blank"
                                    class="px-2.5 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-2xs"
                                >
                                    Lihat File
                                </a>
                                <button
                                    @click="deleteDoc(doc.id)"
                                    class="px-2.5 py-1 text-xs font-medium rounded border border-red-200 bg-white text-red-600 hover:bg-red-50 shadow-2xs cursor-pointer"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                            <tr>
                                <th class="px-3.5 py-2.5 w-20 text-center">Tipe</th>
                                <th class="px-3.5 py-2.5">Judul Dokumen</th>
                                <th class="px-3.5 py-2.5">Nama File & Ukuran</th>
                                <th class="px-3.5 py-2.5">Keterangan</th>
                                <th class="px-3.5 py-2.5 w-32 whitespace-nowrap">Waktu Upload</th>
                                <th class="px-3.5 py-2.5 w-28 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr v-for="doc in audit.documents" :key="doc.id" class="hover:bg-slate-50/70 transition-colors">
                                <td class="px-3.5 py-2.5 text-center align-middle">
                                    <span
                                        class="inline-block px-2 py-0.5 text-[10px] font-bold rounded"
                                        :class="doc.document_type === 'LHP' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (doc.document_type === 'BAP' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-700 border border-gray-200')"
                                    >
                                        {{ doc.document_type }}
                                    </span>
                                </td>
                                <td class="px-3.5 py-2.5 font-medium text-gray-900 align-middle">
                                    {{ doc.title }}
                                </td>
                                <td class="px-3.5 py-2.5 text-gray-600 font-mono text-[11px] align-middle">
                                    {{ doc.file_name }} <span class="text-gray-400">({{ doc.file_size }})</span>
                                </td>
                                <td class="px-3.5 py-2.5 text-gray-600 align-middle">
                                    {{ doc.notes || '—' }}
                                </td>
                                <td class="px-3.5 py-2.5 text-gray-500 font-mono text-[11px] align-middle whitespace-nowrap">
                                    {{ doc.created_at }}
                                </td>
                                <td class="px-3.5 py-2.5 text-right align-middle whitespace-nowrap space-x-1.5">
                                    <a
                                        :href="doc.file_url"
                                        target="_blank"
                                        class="inline-flex items-center px-2 py-1 text-[11px] font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 shadow-2xs"
                                    >
                                        Lihat File
                                    </a>
                                    <button
                                        @click="deleteDoc(doc.id)"
                                        class="inline-flex items-center px-2 py-1 text-[11px] font-medium rounded border border-red-200 bg-white text-red-600 hover:bg-red-50 shadow-2xs cursor-pointer"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Findings List Section -->
        <div class="mb-4 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wider">
                Daftar Finding / Temuan Audit ({{ audit.findings.length }})
            </h2>

            <Link
                :href="route('auditor.finding-qualities.create', { audit_id: audit.id })"
                class="text-xs text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-300 px-3 py-1.5 rounded font-medium inline-flex items-center gap-1.5 transition-colors"
            >
                <svg class="w-3.5 h-3.5 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Lapor Finding Quality</span>
            </Link>
        </div>

        <div v-if="audit.findings.length === 0" class="bg-white rounded border border-gray-200 p-8 text-center text-xs text-gray-500">
            Belum ada finding untuk audit ini. Klik tombol "Tambah Finding Baru" di atas untuk mencatat temuan.
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="(finding, index) in audit.findings"
                :key="finding.id"
                class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs transition-colors hover:border-gray-300"
            >
                <!-- Finding Header -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 mb-3 border-b border-gray-100">
                    <div class="flex items-center flex-wrap gap-2.5">
                        <span class="text-xs font-mono font-bold text-gray-400">#{{ String(index + 1).padStart(3, '0') }}</span>
                        <span class="text-xs font-semibold text-gray-900">{{ finding.category }}</span>
                        <SeverityBadge :severity="finding.severity" :show-timeline="true" />
                        <StatusBadge :status="finding.status" />
                    </div>

                    <!-- Action Buttons: Detail & Verifikasi + Hapus Finding Red Box -->
                    <div class="flex items-center gap-2">
                        <!-- Hapus Finding Red Box Button -->
                        <button
                            @click="confirmDeleteFinding(finding)"
                            title="Hapus Finding"
                            type="button"
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded border border-red-300 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white hover:border-red-600 transition-colors shadow-2xs"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Hapus</span>
                        </button>

                        <!-- Detail Finding -->
                        <Link
                            :href="route('auditor.findings.show', finding.id)"
                            class="inline-flex items-center px-3 py-1 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs"
                        >
                            Detail Finding →
                        </Link>
                    </div>
                </div>

                <!-- Finding Content Details -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-xs">
                    <div class="md:col-span-2 space-y-3">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Temuan (Finding):</div>
                            <p class="text-gray-900 leading-relaxed font-normal bg-gray-50/70 p-3 rounded border border-gray-100">{{ finding.finding }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div v-if="finding.opinion">
                                <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                                <p class="text-gray-700 leading-relaxed">{{ finding.opinion }}</p>
                            </div>
                            <div v-if="finding.recommendation">
                                <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                                <p class="text-gray-700 leading-relaxed">{{ finding.recommendation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Meta, Loss, SOP, Action Plan Status -->
                    <div class="border-t md:border-t-0 md:border-l md:border-gray-100 md:pl-6 space-y-3">
                        <div>
                            <div class="text-gray-500 font-medium">Nominal Kerugian</div>
                            <div class="text-sm font-bold text-gray-900 mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium">SOP / SE Terkait</div>
                            <div class="text-gray-800 mt-0.5">{{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}</div>
                        </div>

                        <div class="pt-2 border-t border-gray-100">
                            <div class="text-gray-500 font-medium mb-1">Tindak Lanjut Unit / PIC</div>
                            <div v-if="finding.action_plan?.action_plan" class="space-y-1">
                                <div class="text-gray-900 font-medium">{{ finding.action_plan.action_plan }}</div>
                                <div class="text-[11px] text-gray-500">PIC: <span class="font-medium text-gray-700">{{ finding.action_plan.pic || '-' }}</span> | Deadline: <span class="font-medium text-gray-700">{{ finding.action_plan.deadline || '-' }}</span></div>
                            </div>
                            <div v-else class="text-gray-400 italic">Belum diisi</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: KONFIRMASI HAPUS FINDING ================= -->
        <div v-if="deleteModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl border border-gray-200 animate-in fade-in zoom-in-95 text-xs">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-gray-900">Hapus Temuan (Finding)?</h3>
                        <p class="text-gray-500 text-xs mt-0.5">Tindakan ini akan menghapus data finding beserta action plan dan bukti terkait.</p>
                    </div>
                </div>

                <div v-if="selectedFindingToDelete" class="bg-gray-50 p-3 rounded border border-gray-200 text-gray-800 mb-5 line-clamp-3">
                    "{{ selectedFindingToDelete.finding }}"
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button
                        @click="deleteModalOpen = false"
                        type="button"
                        class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </button>
                    <button
                        @click="executeDeleteFinding"
                        type="button"
                        class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700 font-medium shadow-xs"
                    >
                        Ya, Hapus Finding
                    </button>
                </div>
            </div>
        </div>

        <!-- ================= MODAL: UPLOAD LHP / BAP ================= -->
        <div v-if="uploadDocModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-lg max-w-lg w-full p-6 shadow-xl border border-gray-200 animate-in fade-in zoom-in-95 text-xs">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-900">Unggah Dokumen LHP / BAP Bertanda Tangan</h3>
                    <button @click="uploadDocModalOpen = false" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
                </div>

                <form @submit.prevent="submitDoc" class="space-y-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Jenis Dokumen <span class="text-red-500">*</span></label>
                        <select
                            v-model="docForm.document_type"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="LHP">LHP (Laporan Hasil Pemeriksaan)</option>
                            <option value="BAP">BAP (Berita Acara Pemeriksaan) Bertanda Tangan Auditee</option>
                            <option value="OTHER">Dokumen Berita Acara Lainnya</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Judul / Keterangan Dokumen <span class="text-red-500">*</span></label>
                        <input
                            v-model="docForm.title"
                            type="text"
                            required
                            placeholder="Contoh: BAP Temuan Stock Opname Bertanda Tangan Ka. Toko"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">File Dokumen (PDF, Word, Excel, PNG, JPEG) <span class="text-red-500">*</span></label>
                        <input
                            type="file"
                            required
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp"
                            @change="e => docForm.file = e.target.files[0]"
                            class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                        />
                        <p class="text-[10px] text-gray-400 mt-1">Format: PDF, Word (.doc, .docx), Excel (.xls, .xlsx), PNG, JPG/JPEG. Maksimal 10 MB.</p>
                        <div v-if="docForm.errors.file" class="text-red-600 text-[11px] mt-1">{{ docForm.errors.file }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                        <textarea
                            v-model="docForm.notes"
                            rows="2"
                            placeholder="Catatan klarifikasi atau nama penandatangan..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100">
                        <button
                            @click="uploadDocModalOpen = false"
                            type="button"
                            class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="docForm.processing"
                            class="px-4 py-2 rounded bg-emerald-600 text-white hover:bg-emerald-700 font-medium disabled:opacity-50 shadow-xs"
                        >
                            {{ docForm.processing ? 'Mengunggah...' : 'Unggah Dokumen' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
