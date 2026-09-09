<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || '',
    password: '',
    role: props.user.role || 'auditor',
    is_active: Boolean(props.user.is_active),
});

const submit = () => {
    form.put(route('admin.users.update', props.user.id));
};
</script>

<template>
    <AppLayout :title="`Edit User - ${user.name}`">
        <Head :title="`Edit User - ${user.name} — Admin`" />

        <div class="mb-6">
            <div class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <Link :href="route('admin.users.index')" class="hover:text-blue-600">Users</Link>
                <span>/</span>
                <span class="text-gray-900 font-medium">{{ user.name }}</span>
            </div>
            <h1 class="text-xl font-semibold text-gray-900 tracking-tight">Edit Data Pengguna</h1>
            <p class="text-xs text-gray-500 mt-1">Perbarui informasi profil, role jabatan, dan status akun</p>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 shadow-xs max-w-2xl text-xs">
            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block font-medium text-gray-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                    <div v-if="form.errors.name" class="text-red-600 text-[11px] mt-1">{{ form.errors.name }}</div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        />
                        <div v-if="form.errors.email" class="text-red-600 text-[11px] mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">No. WhatsApp</label>
                        <input
                            v-model="form.phone"
                            type="text"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-mono"
                        />
                        <div v-if="form.errors.phone" class="text-red-600 text-[11px] mt-1">{{ form.errors.phone }}</div>
                    </div>
                </div>

                <div>
                    <label class="block font-medium text-gray-700 mb-1">Password Baru (Biarkan kosong jika tidak diubah)</label>
                    <input
                        v-model="form.password"
                        type="password"
                        placeholder="••••••••"
                        class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                    />
                    <div v-if="form.errors.password" class="text-red-600 text-[11px] mt-1">{{ form.errors.password }}</div>
                </div>

                <div class="grid grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Role / Jabatan <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.role"
                            required
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 font-medium"
                        >
                            <option value="admin">Admin (Administrator - Full Access)</option>
                            <option value="chief">Chief Auditor (Head of Audit)</option>
                            <option value="asmen">Asisten Manager (Asmen)</option>
                            <option value="coordinator">Koordinator Audit</option>
                            <option value="auditor">Auditor Lapangan</option>
                        </select>
                        <div v-if="form.errors.role" class="text-red-600 text-[11px] mt-1">{{ form.errors.role }}</div>
                    </div>

                    <div>
                        <label class="block font-medium text-gray-700 mb-1">Status Akun</label>
                        <select
                            v-model="form.is_active"
                            class="w-full text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        >
                            <option :value="true">Aktif</option>
                            <option :value="false">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200 flex items-center justify-end gap-3">
                    <Link
                        :href="route('admin.users.index')"
                        class="px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 font-medium"
                    >
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2 rounded bg-blue-600 text-white hover:bg-blue-700 font-semibold disabled:opacity-50 shadow-xs"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Perbarui User' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
