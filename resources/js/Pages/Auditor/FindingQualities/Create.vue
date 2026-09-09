<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    findings: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Object,
        default: () => ({}),
    },
    preselectedFinding: {
        type: [String, Number],
        default: null,
    },
    preselectedAudit: {
        type: [String, Number],
        default: null,
    },
});

const form = useForm({
    finding_id: props.preselectedFinding || '',
    quality_categories: ['impact_50m'],
    title: '',
    impact_amount: '',
    root_cause: '',
    systemic_issue: '',
    recommendation: '',
    auditor_notes: '',
});

const toggleCategory = (key) => {
    const index = form.quality_categories.indexOf(key);
    if (index > -1) {
        form.quality_categories.splice(index, 1);
    } else {
        form.quality_categories.push(key);
    }
};

const selectAllCategories = () => {
    const allKeys = Object.keys(props.categories);
    if (form.quality_categories.length === allKeys.length) {
        form.quality_categories = [];
    } else {
        form.quality_categories = [...allKeys];
    }
};

// Auto fill loss amount when finding is selected
watch(
    () => form.finding_id,
    (selectedId) => {
        const found = props.findings.find(f => f.id === Number(selectedId));
        if (found) {
            if (!form.title) {
                form.title = `Temuan Kritis: ${found.finding.substring(0, 70)}...`;
            }
            if (found.loss_amount && !form.impact_amount) {
                form.impact_amount = found.loss_amount;
            }
        }
    }
);

const getCardTheme = (key, isChecked) => {
    const themes = {
        impact_50m: {
            container: isChecked
                ? 'border-emerald-600 bg-emerald-50/80 ring-2 ring-emerald-500 shadow-xs border-t-4 border-t-emerald-600'
                : 'border-emerald-200/90 bg-emerald-50/30 hover:border-emerald-400 hover:bg-emerald-50/60 border-t-4 border-t-emerald-500/70',
            checkbox: 'text-emerald-600 focus:ring-emerald-500',
            badge: isChecked ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-800 border border-emerald-300',
            title: 'text-emerald-950',
            desc: 'text-emerald-800/90',
        },
        fraud_risk: {
            container: isChecked
                ? 'border-rose-600 bg-rose-50/80 ring-2 ring-rose-500 shadow-xs border-t-4 border-t-rose-600'
                : 'border-rose-200/90 bg-rose-50/30 hover:border-rose-400 hover:bg-rose-50/60 border-t-4 border-t-rose-500/70',
            checkbox: 'text-rose-600 focus:ring-rose-500',
            badge: isChecked ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-800 border border-rose-300',
            title: 'text-rose-950',
            desc: 'text-rose-800/90',
        },
        system_control: {
            container: isChecked
                ? 'border-indigo-600 bg-indigo-50/80 ring-2 ring-indigo-500 shadow-xs border-t-4 border-t-indigo-600'
                : 'border-indigo-200/90 bg-indigo-50/30 hover:border-indigo-400 hover:bg-indigo-50/60 border-t-4 border-t-indigo-500/70',
            checkbox: 'text-indigo-600 focus:ring-indigo-500',
            badge: isChecked ? 'bg-indigo-700 text-white' : 'bg-indigo-100 text-indigo-800 border border-indigo-300',
            title: 'text-indigo-950',
            desc: 'text-indigo-800/90',
        },
        org_structure: {
            container: isChecked
                ? 'border-amber-600 bg-amber-50/80 ring-2 ring-amber-500 shadow-xs border-t-4 border-t-amber-600'
                : 'border-amber-200/90 bg-amber-50/30 hover:border-amber-400 hover:bg-amber-50/60 border-t-4 border-t-amber-500/70',
            checkbox: 'text-amber-600 focus:ring-amber-500',
            badge: isChecked ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-800 border border-amber-300',
            title: 'text-amber-950',
            desc: 'text-amber-800/90',
        },
    };

    return themes[key] || {
        container: isChecked ? 'border-blue-600 bg-blue-50/40 ring-1 ring-blue-600' : 'border-gray-200 hover:border-gray-300 bg-white',
        checkbox: 'text-blue-600 focus:ring-blue-500',
        badge: 'bg-slate-100 text-slate-800',
        title: 'text-gray-900',
        desc: 'text-gray-500',
    };
};

const selectedFindingObj = computed(() => {
    return props.findings.find(f => f.id === Number(form.finding_id));
});

const submit = () => {
    form.post(route('auditor.finding-qualities.store'));
};
</script>

<template>
    <AppLayout title="Buat Laporan Finding Quality">
        <Head title="Buat Laporan Finding Quality" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('auditor.finding-qualities.index')" class="hover:text-blue-600">Finding Quality</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Buat Laporan Baru</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Formulir Laporan Finding Quality</h1>
            <p class="text-xs text-gray-500 mt-1">
                Pilih kriteria temuan berkualitas tinggi untuk dilaporkan ke manajemen (dapat memilih 1 hingga 4 kategori sekaligus)
            </p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-xs max-w-4xl text-xs">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- STEP 1: Pilih Finding Referensi -->
                <div>
                    <label class="block font-semibold text-gray-800 text-xs mb-1.5">
                        1. Referensi Temuan Audit (Nomor Audit & Uraian) <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="form.finding_id"
                        required
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                    >
                        <option value="" disabled>-- Pilih Nomor Temuan Audit --</option>
                        <option v-for="f in findings" :key="f.id" :value="f.id">
                            {{ f.display_label }}
                        </option>
                    </select>
                    <div v-if="form.errors.finding_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.finding_id }}</div>

                    <!-- Highlight box for selected finding -->
                    <div v-if="selectedFindingObj" class="mt-2.5 p-3 rounded bg-slate-50 border border-slate-200">
                        <div class="font-semibold text-gray-900 flex items-center gap-2">
                            <span>{{ selectedFindingObj.audit_number }}</span>
                            <span>• {{ selectedFindingObj.store_name }}</span>
                            <span class="text-[10px] px-1.5 py-0.5 bg-slate-200 rounded font-normal">{{ selectedFindingObj.category }}</span>
                        </div>
                        <p class="text-gray-700 mt-1 font-normal">"{{ selectedFindingObj.finding }}"</p>
                    </div>
                </div>

                <!-- STEP 2: Pilih Kategori Target Temuan High Quality (Bisa multi-pilih hingga 4 kategori) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block font-semibold text-gray-800 text-xs">
                            2. Kategori Target Finding Quality <span class="text-red-500">*</span>
                            <span class="font-normal text-gray-500 ml-1">(Dapat dipilih lebih dari satu atau sekaligus ke-4 nya)</span>
                        </label>
                        <button
                            type="button"
                            @click="selectAllCategories"
                            class="text-[11px] text-blue-600 hover:text-blue-800 font-semibold cursor-pointer underline"
                        >
                            {{ form.quality_categories.length === Object.keys(categories).length ? 'Batal Pilih Semua' : 'Pilih Semua (4 Kategori)' }}
                        </button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div
                            v-for="(cat, key) in categories"
                            :key="key"
                            @click="toggleCategory(key)"
                            class="p-4 rounded-xl border cursor-pointer transition-all flex items-start gap-3.5 select-none"
                            :class="getCardTheme(key, form.quality_categories.includes(key)).container"
                        >
                            <div class="flex items-center h-5 mt-0.5">
                                <input
                                    type="checkbox"
                                    :value="key"
                                    :checked="form.quality_categories.includes(key)"
                                    @click.stop="toggleCategory(key)"
                                    class="h-4 w-4 rounded border-gray-300 cursor-pointer"
                                    :class="getCardTheme(key, form.quality_categories.includes(key)).checkbox"
                                />
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <div class="font-bold text-xs" :class="getCardTheme(key, form.quality_categories.includes(key)).title">
                                        {{ cat.label }}
                                    </div>
                                    <span class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded shadow-2xs" :class="getCardTheme(key, form.quality_categories.includes(key)).badge">
                                        {{ cat.code }}
                                    </span>
                                </div>
                                <p class="text-[11px] leading-relaxed" :class="getCardTheme(key, form.quality_categories.includes(key)).desc">
                                    {{ cat.description }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.errors.quality_categories" class="text-red-600 text-[11px] mt-1">{{ form.errors.quality_categories }}</div>
                </div>

                <!-- STEP 3: Judul & Dampak Finansial -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block font-medium text-gray-700 mb-1">
                            Judul Laporan Finding Quality <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Contoh: Selisih Fisik Barang Dagangan Senilai Rp 65 Juta di DC Cikarang"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.title" class="text-red-600 text-[11px] mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Nilai Dampak / Kerugian (Rp)
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 text-xs">Rp</span>
                            <input
                                v-model="form.impact_amount"
                                type="number"
                                min="0"
                                placeholder="0"
                                class="w-full pl-9 text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                            />
                        </div>
                        <div v-if="form.errors.impact_amount" class="text-red-600 text-[11px] mt-1">{{ form.errors.impact_amount }}</div>
                    </div>
                </div>

                <!-- STEP 4: Root Cause & Isu Sistemik -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Root Cause / Analisis Penyebab Utama <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.root_cause"
                            rows="4"
                            required
                            placeholder="Jelaskan akar penyebab terjadinya temuan, mengapa kontrol tidak berfungsi..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.root_cause" class="text-red-600 text-[11px] mt-1">{{ form.errors.root_cause }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Kelemahan Sistemik / Celah Prosedur
                        </label>
                        <textarea
                            v-model="form.systemic_issue"
                            rows="4"
                            placeholder="Potensi keterulangan di unit/cabang lain atau kelemahan validasi sistem..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.systemic_issue" class="text-red-600 text-[11px] mt-1">{{ form.errors.systemic_issue }}</div>
                    </div>
                </div>

                <!-- STEP 5: Rekomendasi Strategis & Catatan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Rekomendasi Perbaikan Strategis <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="form.recommendation"
                            rows="3"
                            required
                            placeholder="Langkah perbaikan dan mitigasi yang direkomendasikan..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                        <div v-if="form.errors.recommendation" class="text-red-600 text-[11px] mt-1">{{ form.errors.recommendation }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">
                            Catatan Khusus Auditor
                        </label>
                        <textarea
                            v-model="form.auditor_notes"
                            rows="3"
                            placeholder="Catatan tambahan atau temuan pendukung..."
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('auditor.finding-qualities.index')"
                        class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Laporan' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
