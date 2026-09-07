<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    stores: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const statusFilter = ref('');
const typeFilter = ref('');
const importModalOpen = ref(false);
const currentPage = ref(1);

const importForm = useForm({
    file: null,
});

const submitImport = () => {
    importForm.post(route('admin.stores.import-csv'), {
        onSuccess: () => {
            importModalOpen.value = false;
            importForm.reset();
        },
    });
};

const filteredStores = computed(() => {
    return props.stores.filter((s) => {
        const query = searchQuery.value.toLowerCase().trim();
        const matchesSearch =
            !query ||
            (s.name && s.name.toLowerCase().includes(query)) ||
            (s.code && s.code.toLowerCase().includes(query)) ||
            (s.business_entity && s.business_entity.toLowerCase().includes(query)) ||
            (s.area && s.area.toLowerCase().includes(query)) ||
            (s.regional && s.regional.toLowerCase().includes(query));

        const matchesStatus = !statusFilter.value || s.status === statusFilter.value;
        const matchesType = !typeFilter.value || (s.type || 'TOKO').toUpperCase() === typeFilter.value.toUpperCase();

        return matchesSearch && matchesStatus && matchesType;
    });
});

watch([searchQuery, statusFilter, typeFilter], () => {
    currentPage.value = 1;
});

const paginatedStores = computed(() => {
    const start = (currentPage.value - 1) * 10;
    return filteredStores.value.slice(start, start + 10);
});

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

const deleteStore = (store) => {
    openConfirm({
        title: 'Hapus Unit / Toko',
        message: `Apakah Anda yakin ingin menghapus unit "${store.name} (${store.code})"?`,
        confirmText: 'Ya, Hapus Unit',
        type: 'danger',
        action: () => router.delete(route('admin.stores.destroy', store.id)),
    });
};
</script>

<template>
    <AppLayout title="Data Toko & Gudang">
        <Head title="Manajemen Unit Toko & CSA — Admin" />

        <!-- Header -->
        <div class="mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
            <div>
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900 tracking-tight">Master Unit, Toko & Gudang (CSA)</h1>
                <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 sm:mt-1">Data master toko retail, gudang logistik, dan entitas badan usaha pemeriksaan</p>
            </div>

            <div class="flex items-center flex-wrap gap-2 sm:gap-2.5">
                <span class="text-xs text-gray-500 font-mono hidden sm:inline">
                    {{ filteredStores.length }} Unit
                </span>

                <button
                    @click="importModalOpen = true"
                    type="button"
                    class="inline-flex items-center gap-1 px-2.5 sm:px-3 py-1.5 sm:py-2 text-xs font-medium rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition-colors shadow-2xs cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    Import CSV
                </button>

                <a
                    :href="route('admin.stores.download-template')"
                    class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 text-xs font-medium rounded-md border border-emerald-300 bg-white text-emerald-700 hover:bg-emerald-50 hover:border-emerald-400 transition-colors shadow-2xs cursor-pointer"
                    title="Download Seluruh Master Data Toko ke format Excel"
                >
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download Data
                </a>

                <Link
                    :href="route('admin.stores.create')"
                    class="inline-flex items-center gap-1 px-3 py-1.5 sm:py-2 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-slate-800 transition-colors shadow-2xs cursor-pointer"
                >
                    + Unit Manual
                </Link>
            </div>
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
                        placeholder="Cari kode, nama toko, badan usaha, area..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    />
                </div>

                <div class="w-full sm:w-44 shrink-0">
                    <select
                        v-model="typeFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Tipe Unit</option>
                        <option value="TOKO">TOKO (Retail)</option>
                        <option value="GUDANG">GUDANG (DC / Logistik)</option>
                        <option value="FINANCE">BADAN USAHA (Finance)</option>
                    </select>
                </div>

                <div class="w-full sm:w-40 shrink-0">
                    <select
                        v-model="statusFilter"
                        class="w-full py-2 px-3 text-xs rounded border-gray-300 focus:border-slate-500 focus:ring-slate-500 bg-white"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clean Dual View (Mobile Cards vs Desktop Table) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-2xs overflow-hidden">
            
            <!-- 1. MOBILE CARD VIEW (Visible on mobile screens) -->
            <div class="block md:hidden divide-y divide-gray-200">
                <div v-if="filteredStores.length === 0" class="p-8 text-center text-gray-400 text-xs">
                    Tidak ada data unit toko/gudang ditemukan.
                </div>
                <div
                    v-for="s in paginatedStores"
                    :key="'m-store-' + s.id"
                    class="p-4 space-y-2.5"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-semibold text-gray-900 text-xs">{{ s.name }}</div>
                            <div class="text-[10px] text-gray-500 font-mono mt-0.5">
                                Kode: <strong class="text-gray-800">{{ s.code }}</strong>
                                <span v-if="s.business_entity"> • {{ s.business_entity }}</span>
                            </div>
                            <div v-if="s.address" class="text-[10px] text-gray-500 mt-1 line-clamp-2">
                                📍 {{ s.address }}
                            </div>
                        </div>
                        <span
                            class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-medium border"
                            :class="s.status === 'active' ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                        >
                            <span class="w-1.5 h-1.5 rounded-full" :class="s.status === 'active' ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                            {{ s.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-600 pt-1.5 border-t border-gray-100">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            {{ s.type || 'TOKO' }}
                        </span>
                        <span class="font-mono text-gray-500 text-[10px]">
                            {{ s.area || '—' }} <span v-if="s.regional">({{ s.regional }})</span>
                        </span>
                    </div>

                    <div class="pt-2 flex items-center justify-between border-t border-gray-100">
                        <span class="inline-block px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                            {{ s.audits_count }} audit
                        </span>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('admin.stores.edit', s.id)"
                                class="px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 font-medium text-[11px]"
                            >
                                Edit
                            </Link>
                            <button
                                @click="deleteStore(s)"
                                class="px-2.5 py-1 rounded bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-medium text-[11px]"
                            >
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. DESKTOP TABLE VIEW (Visible on tablet & desktop) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] font-semibold tracking-wider border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap w-24">Kode</th>
                            <th class="px-4 py-3 min-w-[200px]">Nama Unit & Badan Usaha</th>
                            <th class="px-4 py-3 whitespace-nowrap w-32">Tipe</th>
                            <th class="px-4 py-3 whitespace-nowrap w-44">Area / Regional</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Total Audit</th>
                            <th class="px-4 py-3 whitespace-nowrap text-center w-28">Status</th>
                            <th class="px-4 py-3 whitespace-nowrap text-right w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-if="filteredStores.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                Tidak ada data unit toko/gudang ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="s in paginatedStores"
                            :key="s.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <td class="px-4 py-3 align-middle whitespace-nowrap font-mono font-bold text-gray-900">
                                {{ s.code }}
                            </td>
                            <td class="px-4 py-3 align-middle">
                                <div class="font-medium text-gray-900">{{ s.name }}</div>
                                <div v-if="s.address" class="text-[11px] text-gray-500 line-clamp-1 mt-0.5" :title="s.address">
                                    📍 {{ s.address }}
                                </div>
                                <div v-if="s.business_entity" class="text-[10px] text-slate-400 font-mono mt-0.5">
                                    {{ s.business_entity }}
                                </div>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ s.type || 'TOKO' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-gray-600 font-mono text-[11px]">
                                {{ s.area || '—' }} <span v-if="s.regional" class="text-gray-400">({{ s.regional }})</span>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <span class="inline-block px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                    {{ s.audits_count }} audit
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-center">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-medium border"
                                    :class="s.status === 'active' ? 'text-emerald-700 bg-emerald-50/60 border-emerald-200' : 'text-gray-500 bg-gray-50 border-gray-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="s.status === 'active' ? 'bg-emerald-600' : 'bg-gray-400'"></span>
                                    {{ s.status === 'active' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-middle whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-2">
                                    <Link
                                        :href="route('admin.stores.edit', s.id)"
                                        class="text-slate-600 hover:text-slate-900 font-medium hover:underline text-xs"
                                    >
                                        Edit
                                    </Link>
                                    <span class="text-slate-300">|</span>
                                    <button
                                        @click="deleteStore(s)"
                                        class="text-red-600 hover:text-red-800 font-medium hover:underline text-xs"
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
                :total-items="filteredStores.length"
                @update:current-page="currentPage = $event"
            />
        </div>

        <!-- ================= MODAL: IMPORT CSV CSA ================= -->
        <div v-if="importModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
            <div class="bg-white rounded-lg max-w-md w-full p-6 shadow-xl border border-gray-200 text-xs">
                <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">Import Data CSA (CSV)</h3>
                    <button @click="importModalOpen = false" class="text-gray-400 hover:text-gray-600 text-base font-bold">✕</button>
                </div>

                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Pilih File CSV CSA</label>
                        <input
                            type="file"
                            accept=".csv,text/csv"
                            required
                            @input="importForm.file = $event.target.files[0]"
                            class="w-full text-xs text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 cursor-pointer"
                        />
                        <div v-if="importForm.errors.file" class="text-red-600 text-[11px] mt-1">{{ importForm.errors.file }}</div>
                    </div>

                    <div class="p-3 bg-slate-50 border border-slate-200 rounded text-slate-600 leading-relaxed space-y-1">
                        <div class="font-semibold text-slate-800">Format Kolom Header CSV:</div>
                        <div class="font-mono text-[11px] text-slate-700">code, name, business_entity, type, area, regional, address</div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                        <button
                            type="button"
                            @click="importModalOpen = false"
                            class="px-3 py-1.5 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing"
                            class="px-4 py-1.5 rounded bg-slate-900 text-white hover:bg-slate-800 font-semibold disabled:opacity-50"
                        >
                            {{ importForm.processing ? 'Mengunggah...' : 'Upload & Import' }}
                        </button>
                    </div>
                </form>
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
