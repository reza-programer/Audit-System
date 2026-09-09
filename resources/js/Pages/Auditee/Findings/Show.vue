<script setup>
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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

const formatDate = (dateString) => {
    if (!dateString) return '—';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return dateString;
    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    }).format(date);
};

const getInitialDeadline = () => {
    if (!props.finding.action_plan?.deadline) return '';
    return typeof props.finding.action_plan.deadline === 'string'
        ? props.finding.action_plan.deadline.substring(0, 10)
        : '';
};

// Check if action plan has already been filled with content
const hasActionPlan = computed(() => Boolean(props.finding.action_plan && props.finding.action_plan.action_plan));

// Action Plan State & Form
const isEditing = ref(!hasActionPlan.value);

const actionPlanForm = useForm({
    action_plan: props.finding.action_plan?.action_plan || '',
    response: props.finding.action_plan?.response || '',
    pic: props.finding.action_plan?.pic || '',
    deadline: getInitialDeadline(),
});

const startEditing = () => {
    actionPlanForm.action_plan = props.finding.action_plan?.action_plan || '';
    actionPlanForm.response = props.finding.action_plan?.response || '';
    actionPlanForm.pic = props.finding.action_plan?.pic || '';
    actionPlanForm.deadline = getInitialDeadline();
    actionPlanForm.clearErrors();
    isEditing.value = true;
};

const cancelEditing = () => {
    actionPlanForm.action_plan = props.finding.action_plan?.action_plan || '';
    actionPlanForm.response = props.finding.action_plan?.response || '';
    actionPlanForm.pic = props.finding.action_plan?.pic || '';
    actionPlanForm.deadline = getInitialDeadline();
    actionPlanForm.clearErrors();
    if (hasActionPlan.value) {
        isEditing.value = false;
    }
};

const submitActionPlan = () => {
    if (props.finding.action_plan) {
        actionPlanForm.patch(route('auditee.action-plans.update', props.finding.id), {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
            },
        });
    } else {
        actionPlanForm.post(route('auditee.action-plans.store', props.finding.id), {
            preserveScroll: true,
            onSuccess: () => {
                isEditing.value = false;
            },
        });
    }
};

// Evidence Form
const evidenceForm = useForm({
    file: null,
    description: '',
});

const fileInputRef = ref(null);

const handleFileChange = (e) => {
    evidenceForm.file = e.target.files[0] || null;
};

const handleFileUpload = (e) => {
    evidenceForm.file = e.target.files[0] || null;
};

const submitEvidence = () => {
    evidenceForm.post(route('auditee.evidences.store', props.finding.id), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            evidenceForm.reset();
            if (fileInputRef.value) fileInputRef.value.value = '';
        },
    });
};

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

const deleteEvidence = (evidenceId) => {
    openConfirm({
        title: 'Hapus Bukti Perbaikan (Evidence)',
        message: 'Apakah Anda yakin ingin menghapus file foto bukti perbaikan ini?',
        confirmText: 'Ya, Hapus Bukti',
        type: 'danger',
        action: () => router.delete(route('auditee.evidences.destroy', evidenceId), { preserveScroll: true }),
    });
};
</script>

<template>
    <AppLayout :title="`Tindak Lanjut Finding #${finding.id}`">
        <Head :title="`Tindak Lanjut Finding #${finding.id}`" />

        <!-- Breadcrumb & Header -->
        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('auditee.audits.index')" class="hover:text-blue-600">Audit Toko</Link>
                <span>/</span>
                <Link :href="route('auditee.audits.show', finding.audit.id)" class="hover:text-blue-600">{{ finding.audit.audit_number }}</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Finding Detail & Tindak Lanjut</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight flex items-center gap-3">
                Finding #{{ String(finding.id).padStart(4, '0') }}
                <SeverityBadge :severity="finding.severity" />
                <StatusBadge :status="finding.status" />
            </h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left 2 Cols: Finding Info (Read-Only) + Action Plan Form + Evidence Form -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 1. Detail Temuan Auditor (Read Only) -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span>1. Informasi Temuan Auditor (Read-Only)</span>
                        <span class="text-[11px] text-gray-400 font-normal">Ditetapkan oleh Auditor</span>
                    </h2>

                    <div class="grid grid-cols-2 gap-4 bg-gray-50/70 p-3 rounded border border-gray-100">
                        <div>
                            <span class="text-gray-500 font-medium">Kategori:</span>
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
                        <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200/70 font-medium">{{ finding.finding }}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="finding.auditor_opinion">
                            <div class="text-gray-500 font-medium mb-1">Opini Auditor:</div>
                            <p class="text-gray-800 leading-relaxed bg-gray-50/50 p-2.5 rounded border border-gray-100">{{ finding.auditor_opinion }}</p>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rekomendasi Perbaikan:</div>
                            <p class="text-blue-900 font-medium leading-relaxed bg-blue-50/40 p-2.5 rounded border border-blue-100">{{ finding.recommendation }}</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Formulir Action Plan & Response Toko -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100 flex items-center justify-between">
                        <span>2. Rencana Tindak Lanjut (Action Plan)</span>
                        <div class="flex items-center gap-2">
                            <StatusBadge v-if="finding.action_plan" :status="finding.action_plan.status" />
                            <button
                                v-if="hasActionPlan && !isEditing && finding.can_edit_action_plan"
                                type="button"
                                @click="startEditing"
                                class="px-2.5 py-1 text-xs font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded border border-blue-200 transition-colors"
                            >
                                Edit Action Plan
                            </button>
                        </div>
                    </h2>

                    <!-- View Mode (Read-Only setelah disimpan) -->
                    <div v-if="hasActionPlan && !isEditing" class="space-y-4">
                        <div>
                            <div class="text-gray-500 font-medium mb-1">Rencana Perbaikan (Action Plan):</div>
                            <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200/70 font-medium whitespace-pre-line">{{ finding.action_plan.action_plan }}</p>
                        </div>

                        <div>
                            <div class="text-gray-500 font-medium mb-1">Tanggapan / Response Toko:</div>
                            <p class="text-gray-900 leading-relaxed bg-gray-50 p-3 rounded border border-gray-200/70 font-medium whitespace-pre-line">{{ finding.action_plan.response }}</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 bg-gray-50/70 p-3 rounded border border-gray-100">
                            <div>
                                <span class="text-gray-500 font-medium">PIC Perbaikan:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ finding.action_plan.pic }}</div>
                            </div>

                            <div>
                                <span class="text-gray-500 font-medium">Target Deadline:</span>
                                <div class="font-semibold text-gray-900 mt-0.5">{{ formatDate(finding.action_plan.deadline) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Edit / Input Form Mode -->
                    <form v-else-if="finding.can_edit_action_plan" @submit.prevent="submitActionPlan" class="space-y-4">
                        <div>
                            <label class="block font-medium text-gray-700 mb-1.5">
                                Rencana Perbaikan (Action Plan) <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="actionPlanForm.action_plan"
                                rows="3"
                                required
                                :disabled="!finding.can_edit_action_plan"
                                placeholder="Jelaskan langkah konkret perbaikan yang akan/sedang dilakukan toko..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                            ></textarea>
                            <div v-if="actionPlanForm.errors.action_plan" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.action_plan }}</div>
                        </div>

                        <div>
                            <label class="block font-medium text-gray-700 mb-1.5">
                                Tanggapan / Response Toko <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="actionPlanForm.response"
                                rows="3"
                                required
                                :disabled="!finding.can_edit_action_plan"
                                placeholder="Tanggapan toko mengenai temuan ini..."
                                class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                            ></textarea>
                            <div v-if="actionPlanForm.errors.response" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.response }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-medium text-gray-700 mb-1.5">
                                    PIC Perbaikan <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="actionPlanForm.pic"
                                    type="text"
                                    required
                                    :disabled="!finding.can_edit_action_plan"
                                    placeholder="Nama penanggung jawab toko..."
                                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                                />
                                <div v-if="actionPlanForm.errors.pic" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.pic }}</div>
                            </div>

                            <div>
                                <label class="block font-medium text-gray-700 mb-1.5">
                                    Target Deadline <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="actionPlanForm.deadline"
                                    type="date"
                                    required
                                    :disabled="!finding.can_edit_action_plan"
                                    class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 disabled:bg-gray-100"
                                />
                                <div v-if="actionPlanForm.errors.deadline" class="text-red-600 text-[11px] mt-1">{{ actionPlanForm.errors.deadline }}</div>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-end gap-2">
                            <button
                                v-if="hasActionPlan"
                                type="button"
                                @click="cancelEditing"
                                class="px-3 py-1.5 text-xs font-medium rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="actionPlanForm.processing"
                                class="px-4 py-1.5 text-xs font-medium rounded bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-xs"
                            >
                                {{ actionPlanForm.processing ? 'Menyimpan...' : (hasActionPlan ? 'Simpan Perubahan' : 'Simpan Action Plan') }}
                            </button>
                        </div>
                    </form>

                    <!-- Empty state jika tidak bisa edit dan belum pernah diisi -->
                    <div v-else class="p-4 bg-gray-50 rounded border border-dashed border-gray-200 text-center text-gray-500 text-xs">
                        Belum ada rencana tindak lanjut yang diisi.
                    </div>
                </div>

                <!-- 3. Upload & Bukti Perbaikan (Evidences) -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        3. Bukti Perbaikan (Evidence)
                    </h2>

                    <!-- Upload Form (if open/in progress) -->
                    <div v-if="finding.can_upload_evidence" class="bg-gray-50/70 p-4 rounded-lg border border-gray-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="font-semibold text-gray-900">Unggah Bukti Perbaikan (Foto / Dokumen)</div>
                            <span class="text-[11px] text-gray-500">Maks. 10 MB (JPG, PNG, PDF, XLSX, DOCX)</span>
                        </div>

                        <!-- Rejected Notice Banner if any evidence rejected -->
                        <div
                            v-if="finding.evidences.some(e => e.verification_status === 'REJECTED')"
                            class="p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2.5 text-amber-900"
                        >
                            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div class="text-xs">
                                <strong class="font-bold">Bukti Sebelumnya Ditolak Auditor:</strong> Silakan tinjau catatan penolakan pada daftar bukti di bawah, lalu unggah file bukti perbaikan baru yang telah disesuaikan.
                            </div>
                        </div>

                        <form @submit.prevent="submitEvidence" class="space-y-3">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label class="block font-medium text-gray-700 mb-1">Pilih File Bukti <span class="text-red-500">*</span></label>
                                    <input
                                        ref="fileInputRef"
                                        type="file"
                                        required
                                        accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"
                                        @change="handleFileChange"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border file:border-gray-300 file:text-xs file:font-medium file:bg-white file:text-gray-700 hover:file:bg-gray-50 cursor-pointer"
                                    />
                                    <div v-if="evidenceForm.errors.file" class="text-red-600 text-[11px] mt-1">{{ evidenceForm.errors.file }}</div>
                                </div>

                                <div>
                                    <label class="block font-medium text-gray-700 mb-1">Keterangan Bukti</label>
                                    <input
                                        v-model="evidenceForm.description"
                                        type="text"
                                        placeholder="Contoh: Foto fisik stok ulang revisi, Berita Acara..."
                                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                    />
                                    <div v-if="evidenceForm.errors.description" class="text-red-600 text-[11px] mt-1">{{ evidenceForm.errors.description }}</div>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="evidenceForm.processing"
                                    class="px-4 py-1.5 text-xs font-semibold rounded bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-50 transition-colors shadow-xs inline-flex items-center gap-1.5"
                                >
                                    <svg v-if="evidenceForm.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>{{ evidenceForm.processing ? 'Mengunggah...' : 'Unggah Bukti Baru' }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Evidences List -->
                    <div v-if="finding.evidences.length === 0" class="text-center py-6 text-gray-400 italic bg-gray-50 rounded border border-dashed border-gray-200">
                        Belum ada bukti perbaikan yang diunggah.
                    </div>

                    <div v-else class="space-y-3">
                        <div
                            v-for="e in finding.evidences"
                            :key="e.id"
                            class="p-3.5 rounded border border-gray-200 bg-gray-50/40 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-900">{{ e.description || 'Bukti Perbaikan' }}</span>
                                    <StatusBadge :status="e.verification_status" />
                                </div>
                                <div class="text-[11px] text-gray-500">
                                    Diupload pada: {{ e.uploaded_at }}
                                </div>
                                <div v-if="e.rejection_reason" class="text-[11px] text-red-600 font-medium">
                                    Catatan Penolakan Auditor: {{ e.rejection_reason }}
                                </div>
                                <div v-if="e.verified_by" class="text-[11px] text-gray-600">
                                    Diverifikasi oleh: {{ e.verified_by }}
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a
                                    :href="e.file_url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 text-xs rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Lihat File
                                </a>
                                <button
                                    v-if="e.can_delete"
                                    @click="deleteEvidence(e.id)"
                                    class="px-2.5 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium transition-colors"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right 1 Col: Metadata & Workflow Status -->
            <div class="space-y-6">
                <!-- Meta -->
                <div class="bg-white rounded border border-gray-200 p-5 shadow-xs text-xs space-y-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 pb-2 border-b border-gray-100">
                        Informasi Toko & Audit
                    </h3>

                    <div>
                        <div class="text-gray-500">Nomor Audit</div>
                        <div class="font-mono font-semibold text-gray-900 mt-0.5">{{ finding.audit.audit_number }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Toko</div>
                        <div class="font-semibold text-gray-900 mt-0.5">{{ finding.store.name }} ({{ finding.store.code }})</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Nominal Kerugian</div>
                        <div class="text-base font-bold text-gray-900 mt-0.5">{{ formatRupiah(finding.loss_amount) }}</div>
                    </div>

                    <div>
                        <div class="text-gray-500">Status Finding Saat Ini</div>
                        <div class="mt-1"><StatusBadge :status="finding.status" /></div>
                    </div>
                </div>

                <!-- Workflow Step Tracker (Live Animated) -->
                <WorkflowTracker
                    :status="finding.status"
                    :has-documents="finding.has_documents || (finding.audit && finding.audit.documents_count > 0)"
                    :documents-count="finding.documents_count || (finding.audit ? finding.audit.documents_count : 0)"
                    :has-action-plan="finding.has_action_plan || !!finding.action_plan?.action_plan"
                />
            </div>
        </div>

        <!-- Modern Action Confirmation Modal -->
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
