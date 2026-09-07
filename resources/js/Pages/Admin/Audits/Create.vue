<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    auditors: {
        type: Array,
        default: () => [],
    },
    suggested_number: {
        type: String,
        default: '',
    },
    upcoming_audits: {
        type: Array,
        default: () => [],
    },
});

const today = new Date().toISOString().split('T')[0];

const useCustomStore = ref(false);

const form = useForm({
    audit_number: props.suggested_number,
    title: '',
    category_id: props.categories.length > 0 ? props.categories[0].id : '',
    store_id: '',
    custom_store_name: '',
    auditor_ids: [],
    audit_date: today,
    audit_time: '09:00',
    location: '',
    status: 'PLANNED',
    notes: '',
});

const selectedCategoryName = computed(() => {
    return props.categories.find(c => c.id === form.category_id)?.name || 'Belum dipilih';
});

const selectedStore = computed(() => {
    if (useCustomStore.value || !form.store_id) return null;
    return props.stores.find(s => s.id === form.store_id);
});

const selectedStoreName = computed(() => {
    if (useCustomStore.value) {
        return form.custom_store_name ? `${form.custom_store_name} (Manual)` : 'Ketik nama toko/unit baru';
    }
    const store = selectedStore.value;
    return store ? `[${store.code}] ${store.name}` : 'Belum dipilih';
});

const selectedAuditorsList = computed(() => {
    return props.auditors.filter(a => form.auditor_ids.includes(a.id));
});

const sameDateAudits = computed(() => {
    if (!form.audit_date) return [];
    return props.upcoming_audits.filter(a => a.audit_date === form.audit_date);
});

const sameStoreAudits = computed(() => {
    if (useCustomStore.value || !form.store_id) return [];
    return props.upcoming_audits.filter(a => a.store_id === form.store_id);
});

const availableAuditors = computed(() => {
    return props.auditors.filter(a => !form.auditor_ids.includes(a.id));
});

const addAuditor = (event) => {
    const val = Number(event.target.value);
    if (!val) return;
    if (form.auditor_ids.length >= 5) {
        alert('Maksimal penugasan 5 orang auditor per surat tugas audit.');
        event.target.value = '';
        return;
    }
    if (!form.auditor_ids.includes(val)) {
        form.auditor_ids.push(val);
    }
    event.target.value = '';
};

const removeAuditor = (auditorId) => {
    const index = form.auditor_ids.indexOf(auditorId);
    if (index > -1) {
        form.auditor_ids.splice(index, 1);
    }
};

const getAuditor = (auditorId) => {
    return props.auditors.find(a => a.id === auditorId);
};

const toggleAuditor = (auditorId) => {
    const index = form.auditor_ids.indexOf(auditorId);
    if (index > -1) {
        form.auditor_ids.splice(index, 1);
    } else {
        if (form.auditor_ids.length >= 5) {
            alert('Maksimal penugasan 5 orang auditor per surat tugas audit.');
            return;
        }
        form.auditor_ids.push(auditorId);
    }
};

const submit = () => {
    if (useCustomStore.value) {
        if (!form.custom_store_name || !form.custom_store_name.trim()) {
            alert('Silakan ketik nama toko atau badan usaha yang dicek.');
            return;
        }
        form.store_id = '';
    } else {
        if (!form.store_id) {
            alert('Silakan pilih unit toko atau gudang sasaran.');
            return;
        }
        form.custom_store_name = '';
    }

    if (form.auditor_ids.length === 0) {
        alert('Silakan pilih minimal 1 auditor yang bertugas.');
        return;
    }
    form.post(route('admin.audits.store'));
};
</script>

<template>
    <AppLayout title="Jadwalkan Audit Baru">
        <Head title="Jadwalkan Audit Baru — Admin" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('admin.audits.index')" class="hover:text-blue-600">Audits</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">Jadwalkan Audit</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Jadwalkan Penugasan Audit Baru</h1>
            <p class="text-xs text-gray-500 mt-1">Pilih kategori audit, tentukan unit toko retail / gudang (CSA) atau ketik nama toko bebas, tanggal & waktu audit.</p>
        </div>

        <div class="max-w-4xl bg-white rounded-lg border border-gray-200 p-4 sm:p-6 shadow-xs text-xs">
            <form @submit.prevent="submit" class="space-y-4 sm:space-y-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Nomor Audit <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.audit_number"
                            type="text"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                        />
                        <div v-if="form.errors.audit_number" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_number }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Kategori Audit <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.category_id"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-semibold text-blue-900 bg-blue-50/40"
                        >
                            <option value="" disabled>-- Pilih Kategori Audit --</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <div v-if="form.errors.category_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.category_id }}</div>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Judul / Uraian Rencana Audit</label>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Contoh: Audit Stock Opname Bulanan"
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                    <div v-if="form.errors.title" class="text-red-600 text-[11px] mt-1">{{ form.errors.title }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Tanggal Pelaksanaan <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.audit_date"
                            type="date"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.audit_date" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_date }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Waktu / Jam Mulai</label>
                        <input
                            v-model="form.audit_time"
                            type="text"
                            placeholder="09:00"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                        />
                        <div v-if="form.errors.audit_time" class="text-red-600 text-[11px] mt-1">{{ form.errors.audit_time }}</div>
                    </div>
                </div>

                <!-- Unit Toko / Badan Usaha -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block font-medium text-gray-700">
                            Unit Toko / Badan Usaha <span class="text-red-500">*</span>
                        </label>
                        <div class="inline-flex rounded border border-gray-300 bg-gray-100 p-0.5 text-[11px]">
                            <button
                                type="button"
                                @click="useCustomStore = false"
                                :class="!useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 rounded transition-colors cursor-pointer"
                            >
                                Pilih dari Master Data
                            </button>
                            <button
                                type="button"
                                @click="useCustomStore = true"
                                :class="useCustomStore ? 'bg-white text-gray-900 font-medium shadow-xs' : 'text-gray-600 hover:text-gray-900'"
                                class="px-2.5 py-1 rounded transition-colors cursor-pointer"
                            >
                                Input Manual 
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown selection mode -->
                    <div v-if="!useCustomStore">
                        <select
                            v-model="form.store_id"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option value="" disabled>-- Pilih Unit Toko / Cabang CSA --</option>
                            <option v-for="s in stores" :key="s.id" :value="s.id">
                                [{{ s.code }}] {{ s.name }} {{ s.area ? `• ${s.area}` : '' }}
                            </option>
                        </select>
                        <div v-if="selectedStore && selectedStore.address" class="mt-1.5 text-[11px] text-gray-600 bg-gray-50 p-2 rounded border border-gray-100 flex items-start gap-1.5">
                            <span class="text-blue-600 font-semibold shrink-0">📍 Alamat Toko:</span>
                            <span>{{ selectedStore.address }}</span>
                        </div>
                    </div>

                    <!-- Free text input mode -->
                    <div v-else>
                        <input
                            v-model="form.custom_store_name"
                            type="text"
                            placeholder="Ketik nama toko atau badan usaha yang diperiksa"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div v-if="form.errors.store_id" class="text-red-600 text-[11px] mt-1">{{ form.errors.store_id }}</div>
                    <div v-if="form.errors.custom_store_name" class="text-red-600 text-[11px] mt-1">{{ form.errors.custom_store_name }}</div>
                </div>

                <!-- TIM AUDITOR: DROPDOWN SELECTOR -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block font-medium text-gray-700">
                            Tim Auditor Bertugas <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[11px] text-gray-500">
                            {{ form.auditor_ids.length }} / 5 Dipilih (Urutan pertama: Lead Auditor)
                        </span>
                    </div>

                    <select
                        @change="addAuditor"
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                        :disabled="form.auditor_ids.length >= 5"
                    >
                        <option value="" selected>
                            {{ form.auditor_ids.length === 0 ? '-- Pilih Auditor Bertugas (Lead Auditor) --' : (form.auditor_ids.length >= 5 ? 'Maksimal 5 auditor tercapai' : '+ Tambah Anggota Auditor...') }}
                        </option>
                        <option
                            v-for="auditor in availableAuditors"
                            :key="auditor.id"
                            :value="auditor.id"
                        >
                            {{ auditor.name }} ({{ auditor.email }})
                        </option>
                    </select>

                    <!-- Tags Auditor Terpilih -->
                    <div v-if="form.auditor_ids.length > 0" class="flex flex-wrap gap-2 mt-2">
                        <div
                            v-for="(auditorId, index) in form.auditor_ids"
                            :key="auditorId"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded text-xs border"
                            :class="index === 0 ? 'bg-blue-50 border-blue-200 text-blue-900 font-medium' : 'bg-gray-50 border-gray-200 text-gray-700'"
                        >
                            <span
                                v-if="index === 0"
                                class="px-1.5 py-0.5 rounded bg-blue-600 text-white font-bold text-[10px]"
                            >
                                Lead
                            </span>
                            <span>{{ getAuditor(auditorId)?.name || auditorId }}</span>
                            <button
                                type="button"
                                @click="removeAuditor(auditorId)"
                                class="text-gray-400 hover:text-red-600 font-bold ml-1 cursor-pointer text-sm leading-none"
                                title="Hapus dari tim"
                            >
                                &times;
                            </button>
                        </div>
                    </div>
                    <div v-if="form.errors.auditor_ids" class="text-red-600 text-[11px] mt-1">{{ form.errors.auditor_ids }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Lokasi Khusus (Opsional)</label>
                        <input
                            v-model="form.location"
                            type="text"
                            placeholder="Biarkan kosong untuk default alamat toko"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Status Audit</label>
                        <select
                            v-model="form.status"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                        >
                            <option value="PLANNED">Planned (Terencana)</option>
                            <option value="IN_PROGRESS">In Progress (Sedang Berjalan)</option>
                            <option value="COMPLETED">Completed (Selesai)</option>
                            <option value="CLOSED">Closed (Ditutup)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Catatan Tambahan</label>
                    <textarea
                        v-model="form.notes"
                        rows="3"
                        placeholder="Catatan khusus, instruksi, atau ruang lingkup audit..."
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="pt-4 border-t border-gray-200 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-2.5 sm:gap-3">
                    <Link
                        :href="route('admin.audits.index')"
                        class="w-full sm:w-auto text-center px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium cursor-pointer"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-5 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs cursor-pointer"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-1 h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <span>{{ form.processing ? 'Menyimpan...' : 'Jadwalkan Audit' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
