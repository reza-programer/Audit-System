<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import SeverityBadge from '@/Components/SeverityBadge.vue';
import WorkflowTracker from '@/Components/WorkflowTracker.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    finding: {
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

// State for editing action plan
const editingActionPlan = ref(!props.finding.action_plan?.action_plan);
const actionPlanForm = useForm({
    action_plan: props.finding.action_plan?.action_plan || '',
    pic: props.finding.action_plan?.pic || '',
    deadline: props.finding.action_plan?.deadline || '',
    notes: props.finding.action_plan?.response || '',
});

const submitActionPlan = () => {
    actionPlanForm.patch(route('auditor.findings.action-plan.update', props.finding.id), {
        onSuccess: () => {
            editingActionPlan.value = false;
        },
    });
};

// Confirm Modal state
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

const deleteFinding = () => {
    openConfirm({
        title: 'Hapus Temuan Audit (Finding)',
        message: 'Apakah Anda yakin ingin menghapus temuan audit ini beserta data terkait?',
        confirmText: 'Ya, Hapus Finding',
        type: 'danger',
        action: () => router.delete(route('auditor.findings.destroy', props.finding.id)),
    });
};
</script>

<template>
    <AppLayout :title="`Finding Detail - ${finding.audit.audit_number}`">
        <Head :title="`Finding Detail - ${finding.audit.audit_number}`" />

        <!-- Top Breadcrumb & Actions -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                    <Link :href="route('auditor.audits.index')" class="hover:text-blue-600">My Audits</Link>
                    <span>/</span>
                    <Link :href="route('auditor.audits.show', finding.audit.id)" class="hover:text-blue-600 font-mono">{{ finding.audit.audit_number }}</Link>
                    <span>/</span>
                    <span class="text-gray-900 font-medium">Finding #{{ String(finding.id).padStart(4, '0') }}</span>
                </div>
                <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                    Finding #{{ String(finding.id).padStart(4, '0') }}
                    <SeverityBadge :severity="finding.severity" :show-timeline="true" />
                    <StatusBadge :status="finding.status" />
                </h1>
            </div>

            <div class="flex items-center flex-wrap gap-2">
                <Link
                    :href="route('auditor.finding-qualities.create', { finding_id: finding.id, audit_id: finding.audit.id })"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs"
                >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>Lapor Finding Quality</span>
                </Link>

                <button
                    @click="deleteFinding"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded border border-red-300 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white transition-colors shadow-2xs"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Hapus Finding</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Main Sections -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Section 1: Finding Details -->
                <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        1. Temuan Audit (Finding Information)
                    </h2>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50/70 p-3 rounded border border-gray-200/60">
                        <div>
                            <span class="text-gray-500 font-medium">Kategori Audit:</span>
                            <div class="font-semibold text-gray-900 mt-0.5">{{ finding.category }}</div>
                        </div>
                        <div>
                            <span class="text-gray-500 font-medium">SOP / SE Acuan:</span>
                            <div class="font-semibold text-gray-900 mt-0.5">
                                {{ finding.sop ? `${finding.sop.code} - ${finding.sop.title}` : '—' }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="text-gray-500 font-medium mb-1">Uraian Temuan:</div>
                        <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200 whitespace-pre-line">{{ finding.finding }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="finding.auditor_opinion">
                            <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200">{{ finding.auditor_opinion }}</p>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200">{{ finding.recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Komitmen Tindak Lanjut Toko (Auditor Input) -->
                <div class="bg-white rounded-lg border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                        <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500">
                            2. Komitmen Tindak Lanjut Toko (Hasil Klarifikasi Lapangan)
                        </h2>
                        <button
                            v-if="!editingActionPlan && finding.action_plan?.action_plan"
                            @click="editingActionPlan = true"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium"
                        >
                            Edit Tindak Lanjut
                        </button>
                    </div>

                    <!-- Mode Edit / Input Tindak Lanjut oleh Auditor -->
                    <form v-if="editingActionPlan" @submit.prevent="submitActionPlan" class="space-y-4">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1">
                                Rencana Tindakan / Komitmen Perbaikan Toko <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="actionPlanForm.action_plan"
                                rows="3"
                                required
                                placeholder="Tuliskan komitmen tindakan perbaikan yang disepakati dengan kepala toko / auditee..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            ></textarea>
                            <div v-if="actionPlanForm.errors.action_plan" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.action_plan }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-gray-700 mb-1">PIC / Penanggung Jawab Toko</label>
                                <input
                                    v-model="actionPlanForm.pic"
                                    type="text"
                                    placeholder="Contoh: Kepala Toko / Supervisor"
                                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                />
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 mb-1">Target Tanggal Selesai (Deadline)</label>
                                <input
                                    v-model="actionPlanForm.deadline"
                                    type="date"
                                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1">Catatan Tambahan / Kesepakatan LHP</label>
                            <input
                                v-model="actionPlanForm.notes"
                                type="text"
                                placeholder="Catatan klarifikasi lapangan atau komitmen tambahan..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                v-if="finding.action_plan?.action_plan"
                                type="button"
                                @click="editingActionPlan = false"
                                class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="actionPlanForm.processing"
                                class="px-4 py-1.5 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs"
                            >
                                {{ actionPlanForm.processing ? 'Menyimpan...' : 'Simpan Tindak Lanjut' }}
                            </button>
                        </div>
                    </form>

                    <!-- Mode Tampilan (Jika sudah diinput) -->
                    <div v-else class="space-y-3">
                        <div class="bg-gray-50 p-3.5 rounded border border-gray-200">
                            <div class="text-gray-500 font-medium mb-1">Komitmen Tindakan Perbaikan:</div>
                            <p class="text-gray-900 leading-relaxed font-medium whitespace-pre-line">{{ finding.action_plan.action_plan }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 bg-gray-50/70 p-3 rounded border border-gray-200/60">
                            <div>
                                <span class="text-gray-500 font-medium">PIC Penanggung Jawab:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.pic || '—' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-500 font-medium">Target Deadline:</span>
                                <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.deadline || '—' }}</div>
                            </div>
                        </div>

                        <div v-if="finding.action_plan.response" class="text-gray-600 bg-gray-50 p-2.5 rounded border border-gray-200">
                            <span class="font-medium text-gray-700">Catatan: </span> {{ finding.action_plan.response }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Metadata Card & Workflow Tracker -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg border border-gray-200 p-4 shadow-xs text-xs space-y-3">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-700 pb-2 border-b border-gray-100">
                        Informasi Audit
                    </h3>

                    <div>
                        <span class="text-gray-400 font-medium">Nomor Surat Tugas:</span>
                        <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ finding.audit.audit_number }}</div>
                    </div>

                    <div>
                        <span class="text-gray-400 font-medium">Unit / Toko:</span>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ finding.store.name }} ({{ finding.store.code }})</div>
                    </div>

                    <div>
                        <span class="text-gray-400 font-medium">Nominal Kerugian:</span>
                        <div class="font-mono font-bold text-gray-900 text-sm mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                    </div>

                    <div>
                        <span class="text-gray-400 font-medium">Status Finding:</span>
                        <div class="mt-1">
                            <StatusBadge :status="finding.status" />
                        </div>
                    </div>

                    <div>
                        <span class="text-gray-400 font-medium">Dokumen LHP / BAP:</span>
                        <div class="mt-1">
                            <span v-if="finding.audit?.documents_count > 0" class="inline-flex items-center gap-1.5 font-medium text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ finding.audit.documents_count }} Dokumen terlampir
                            </span>
                            <span v-else class="text-gray-400 italic">Belum ada dokumen</span>
                        </div>
                    </div>
                </div>

                <!-- 3-Step Enterprise Workflow Status -->
                <WorkflowTracker
                    :status="finding.status"
                    :has-documents="finding.has_documents || (finding.audit && finding.audit.documents_count > 0)"
                    :documents-count="finding.documents_count || (finding.audit ? finding.audit.documents_count : 0)"
                    :has-action-plan="finding.has_action_plan || !!finding.action_plan?.action_plan"
                />
            </div>
        </div>

        <!-- Confirm Modal -->
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
