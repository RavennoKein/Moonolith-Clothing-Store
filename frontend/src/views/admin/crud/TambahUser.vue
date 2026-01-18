<script setup>
import { ref, reactive, computed } from 'vue';
import { useRouter } from 'vue-router';
import { X, Save, ArrowLeft, UserPlus, User, Shield, Phone, Mail, MapPin, Eye, EyeOff, ChevronDown } from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';

const router = useRouter();

// Opsi role
const roleOptions = [
    {
        value: 'admin',
        label: 'Administrator',
        description: 'Akses penuh ke semua fitur sistem',
        colorClass: 'border-blue-300 bg-gradient-to-r from-blue-50 to-blue-100 shadow-sm'
    }
];

const showRoleDropdown = ref(false);
const showPassword = ref(false);

const formData = reactive({
    name: '',
    email: '',
    password: '',
    phone: '',
    role: '',
    address: ''
});

// Helper functions untuk dropdown
const getSelectedRole = computed(() => roleOptions.find(option => option.value === formData.role));
const selectOption = (type, value) => { if (type === 'role') { formData.role = value; showRoleDropdown.value = false; } };
const clearSelection = (type) => { if (type === 'role') { formData.role = ''; showRoleDropdown.value = false; } };
const toggleDropdown = (type) => { if (type === 'role') showRoleDropdown.value = !showRoleDropdown.value; };
const closeAllDropdowns = () => { showRoleDropdown.value = false; };
const togglePasswordVisibility = () => { showPassword.value = !showPassword.value; };

const validateForm = () => {
    const toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });

    if (!formData.name.trim()) {
        toast.fire({ icon: 'error', title: 'Nama lengkap harus diisi' });
        return false;
    }
    if (!formData.email.trim()) {
        toast.fire({ icon: 'error', title: 'Email harus diisi' });
        return false;
    }
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formData.email)) {
        toast.fire({ icon: 'error', title: 'Format email tidak valid' });
        return false;
    }
    if (!formData.password) {
        toast.fire({ icon: 'error', title: 'Password harus diisi' });
        return false;
    }
    if (formData.password.length < 6) {
        toast.fire({ icon: 'error', title: 'Password minimal 6 karakter' });
        return false;
    }
    return true;
};

const handleSubmit = async () => {
    if (!validateForm()) return;

    Swal.fire({
        title: 'Mohon Tunggu',
        text: 'Sedang menyimpan data user...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const payload = {
            name: formData.name,
            email: formData.email,
            password: formData.password,
            phone: formData.phone || null,
            role: formData.role || 'admin',
            address: formData.address || null
        };

        await api.post('/admin/admins', payload);

        await Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'User baru telah berhasil ditambahkan.',
            confirmButtonColor: '#1e3a8a',
        });

        router.push({ name: 'User' });
    } catch (error) {
        console.error('Gagal menambahkan user:', error);

        const message =
            error.response?.data?.message ||
            'Terjadi kesalahan saat menyimpan data';

        Swal.fire({
            icon: 'error',
            title: 'Gagal Menyimpan',
            text: message,
            confirmButtonColor: '#d33',
        });
    }
};


const resetForm = () => {
    Object.assign(formData, {
        name: '',
        email: '',
        password: '',
        phone: '',
        role: '',
        address: ''
    });
    showPassword.value = false;
    closeAllDropdowns();
};

const generateRandomPassword = () => {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    formData.password = password;
};

const goBack = () => {
    Swal.fire({
        title: 'Apakah Anda yakin?',
        text: "Perubahan yang belum disimpan akan hilang!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#1e3a8a',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Kembali',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            router.push({ name: 'User' });
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-linear-to-br from-gray-50 to-gray-100" @click="closeAllDropdowns">
        <div class="bg-linear-to-r from-blue-900 via-blue-800 to-blue-900 shadow-lg">
            <div class="px-8 py-6">
                <div class="flex items-center justify-between gap-6">
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-white tracking-tight">Tambah User Baru</h1>
                        <p class="text-blue-100 text-sm mt-1 opacity-90">
                            Tambahkan pengguna baru yang akan memiliki akses ke sistem
                        </p>
                    </div>
                    <button @click="router.push({ name: 'User' })"
                        class="group flex items-center px-5 py-2.5 bg-white/10 backdrop-blur-md text-white border border-white/20 rounded-xl hover:bg-white/20 transition-all duration-300 transform hover:scale-105 hover:shadow-lg active:scale-95">
                        <ArrowLeft class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" />
                        <span class="font-medium">Kembali</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="absolute top-0 left-0 w-full h-2 bg-linear-to-r from-blue-500 via-blue-600 to-blue-700"></div>

        <div class="p-6 md:p-8">
            <div class="max-w-5xl mx-auto">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <div class="px-8 py-6 bg-linear-to-r from-blue-50 to-blue-100 border-b border-blue-200">
                        <div class="flex items-center">
                            <div>
                                <h2 class="text-xl font-bold text-gray-800">Form Tambah User Baru</h2>
                                <p class="text-gray-600 text-sm mt-1">Isi formulir berikut dengan data user yang ingin
                                    ditambahkan</p>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="p-6 md:p-8 space-y-8">
                        <div class="space-y-6">
  -                          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Nama Lengkap -->
                                <div class="group">
                                    <label
                                        class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">
                                        Nama Lengkap <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <User
                                                class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
                                        </div>
                                        <input v-model="formData.name" type="text" required
                                            class="w-full pl-10 pr-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-300 hover:shadow-sm group-hover:shadow-sm"
                                            placeholder="Contoh: John Doe">
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 group-hover:text-blue-500 transition-colors">
                                        Nama lengkap pengguna
                                    </div>
                                </div>

                                <!-- Email -->
                                <div class="group">
                                    <label
                                        class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Mail
                                                class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
                                        </div>
                                        <input v-model="formData.email" type="email" required
                                            class="w-full pl-10 pr-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-300 hover:shadow-sm group-hover:shadow-sm"
                                            placeholder="Contoh: johndoe@example.com">
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 group-hover:text-blue-500 transition-colors">
                                        Email aktif yang akan digunakan untuk login
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="group">
                                    <label
                                        class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">
                                        Password <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Shield
                                                class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
                                        </div>
                                        <input v-model="formData.password" :type="showPassword ? 'text' : 'password'"
                                            required
                                            class="w-full pl-10 pr-12 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-300 hover:shadow-sm group-hover:shadow-sm"
                                            placeholder="Minimal 6 karakter">
                                        <button type="button" @click="togglePasswordVisibility"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <Eye v-if="!showPassword"
                                                class="w-5 h-5 text-gray-400 hover:text-blue-500 transition-colors" />
                                            <EyeOff v-else
                                                class="w-5 h-5 text-gray-400 hover:text-blue-500 transition-colors" />
                                        </button>
                                    </div>
                                    <div class="mt-2 flex justify-between">
                                        <button type="button" @click="generateRandomPassword"
                                            class="px-3 py-1.5 text-xs bg-linear-to-r from-blue-100 to-blue-200 text-blue-700 rounded-lg hover:from-blue-200 hover:to-blue-300 transition-all duration-200 font-medium">
                                            Generate Password
                                        </button>
                                        <div class="text-xs text-gray-500 group-hover:text-blue-500 transition-colors">
                                            Minimal 6 karakter
                                        </div>
                                    </div>
                                </div>

                                <!-- Nomor Telepon -->
                                <div class="group">
                                    <label
                                        class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">
                                        Nomor Telepon
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <Phone
                                                class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
                                        </div>
                                        <input v-model="formData.phone" type="tel"
                                            class="w-full pl-10 pr-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-300 hover:shadow-sm group-hover:shadow-sm"
                                            placeholder="Contoh: 081234567890">
                                    </div>
                                    <div class="mt-1 text-xs text-gray-500 group-hover:text-blue-500 transition-colors">
                                        Nomor telepon yang dapat dihubungi
                                    </div>
                                </div>
                            </div>

                            <!-- Role Dropdown - Full Width -->
                            <div class="group">
                                <label
                                    class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">
                                    Role
                                </label>
                                <div class="relative" @click.stop>
                                    <!-- Selected option display -->
                                    <div @click="toggleDropdown('role')"
                                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-300 hover:shadow-sm group-hover:shadow-sm bg-white cursor-pointer flex items-center justify-between"
                                        :class="[getSelectedRole?.colorClass || '', { 'border-blue-400 ring-2 ring-blue-100': showRoleDropdown }]">
                                        <div v-if="getSelectedRole" class="flex items-center space-x-3">
                                            <div v-if="getSelectedRole.value === 'admin'"
                                                class="shrink-0 p-2 bg-linear-to-r from-blue-100 to-blue-200 rounded-lg shadow-sm">
                                                <Shield class="w-4 h-4 text-blue-600" />
                                            </div>
                                            <div v-if="getSelectedRole.value === 'user'"
                                                class="shrink-0 p-2 bg-linear-to-r from-blue-100 to-blue-200 rounded-lg shadow-sm">
                                                <User class="w-4 h-4 text-blue-600" />
                                            </div>
                                            <div class="text-left">
                                                <span class="font-semibold text-gray-800">{{ getSelectedRole.label
                                                    }}</span>
                                                <p class="text-xs text-gray-600 mt-0.5">{{ getSelectedRole.description
                                                    }}</p>
                                            </div>
                                        </div>
                                        <div v-else class="flex items-center space-x-3 text-gray-500">
                                            <div class="shrink-0 p-2 bg-gray-100 rounded-lg">
                                                <Shield class="w-4 h-4 text-gray-400" />
                                            </div>
                                            <span>Pilih Role Pengguna</span>
                                        </div>
                                        <ChevronDown class="w-5 h-5 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-180': showRoleDropdown }" />
                                    </div>

                                    <!-- Dropdown options -->
                                    <div v-if="showRoleDropdown"
                                        class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden animate-fadeIn">
                                        <div class="p-2 space-y-1 max-h-64 overflow-y-auto scrollbar-custom">
                                            <div v-for="option in roleOptions" :key="option.value"
                                                @click="selectOption('role', option.value)"
                                                class="p-3 rounded-lg cursor-pointer transition-all duration-200 hover:shadow-sm"
                                                :class="[option.colorClass, { 'ring-2 ring-blue-200': formData.role === option.value }]">
                                                <div class="flex items-start space-x-3">
                                                    <div class="shrink-0">
                                                        <div v-if="option.value === 'admin'"
                                                            class="p-2 bg-linear-to-r from-blue-100 to-blue-200 rounded-lg shadow-sm">
                                                            <Shield class="w-4 h-4 text-blue-600" />
                                                        </div>
                                                        <div v-if="option.value === 'user'"
                                                            class="p-2 bg-linear-to-r from-blue-100 to-blue-200 rounded-lg shadow-sm">
                                                            <User class="w-4 h-4 text-blue-600" />
                                                        </div>
                                                    </div>
                                                    <div class="grow">
                                                        <div class="flex items-center justify-between">
                                                            <span class="font-semibold text-gray-800">{{ option.label
                                                                }}</span>
                                                            <div v-if="formData.role === option.value"
                                                                class="w-5 h-5 rounded-full bg-linear-to-r from-blue-600 to-blue-800 flex items-center justify-center shadow-sm">
                                                                <svg class="w-3 h-3 text-white" fill="none"
                                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                        stroke-width="3" d="M5 13l4 4L19 7" />
                                                                </svg>
                                                            </div>
                                                        </div>
                                                        <p class="text-sm text-gray-600 mt-1">{{ option.description }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Clear selection option -->
                                        <div v-if="formData.role" class="border-t border-gray-100 p-2 bg-gray-50">
                                            <div @click="clearSelection('role')"
                                                class="p-3 rounded-lg cursor-pointer transition-all duration-200 hover:bg-gray-100 flex items-center justify-center space-x-2 text-gray-600 hover:text-gray-800">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                                <span class="text-sm font-medium">Hapus Pilihan</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div class="group">
                                <label
                                    class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">
                                    Alamat
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 pt-3 pointer-events-none">
                                        <MapPin
                                            class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
                                    </div>
                                    <textarea v-model="formData.address" rows="4"
                                        class="w-full pl-10 pr-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-300 hover:shadow-sm group-hover:shadow-sm"
                                        placeholder="Contoh: Jl. Contoh No. 123, Kota, Provinsi, Kode Pos"></textarea>
                                </div>
                                <div class="mt-1 text-xs text-gray-500 group-hover:text-blue-500 transition-colors">
                                    Alamat tempat tinggal pengguna (opsional)
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="flex flex-col sm:flex-row justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-8 border-t border-gray-100">
                            <button type="button" @click="goBack"
                                class="group px-7 py-3.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900 transition-all duration-200 font-medium hover:shadow-sm transform hover:-translate-y-0.5">
                                <span class="flex items-center justify-center">
                                    <X class="w-4 h-4 mr-2 group-hover:rotate-90 transition-transform" />
                                    Batal
                                </span>
                            </button>
                            <button type="button" @click="resetForm"
                                class="group px-7 py-3.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900 transition-all duration-200 font-medium hover:shadow-sm transform hover:-translate-y-0.5">
                                <span class="flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2 group-hover:rotate-180 transition-transform" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    Reset Form
                                </span>
                            </button>
                            <button type="submit"
                                class="group px-7 py-3.5 bg-linear-to-r from-blue-700 via-blue-800 to-blue-900 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl hover:from-blue-800 hover:via-blue-900 hover:to-blue-950 transition-all duration-300 transform hover:-translate-y-1">
                                <span class="flex items-center justify-center">
                                    <UserPlus class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" />
                                    Tambah User
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
}

.group-size:hover .group-size-hover\:text-blue-600 {
    color: #2563eb;
}

.group-size:hover .group-size-hover\:shadow-sm {
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.group-color:hover .group-color-hover\:text-gray-900 {
    color: #111827;
}

.group-option:hover .group-option-hover\:text-blue-700 {
    color: #1d4ed8;
}

button, input, select, textarea, label {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

input:focus, select:focus, textarea:focus {
    outline: 2px solid transparent;
    outline-offset: 2px;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

@keyframes pulse-subtle {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.9;
    }
}

.border-dashed:hover {
    animation: pulse-subtle 2s ease-in-out infinite;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.2s ease-out forwards;
}

.bg-blue-50 {
    background-color: #eff6ff;
}

.border-blue-100 {
    border-color: #dbeafe;
}

.from-blue-50 {
    --tw-gradient-from: #eff6ff;
}

.to-blue-100 {
    --tw-gradient-to: #dbeafe;
}

.from-blue-100 {
    --tw-gradient-from: #dbeafe;
}

.to-blue-200 {
    --tw-gradient-to: #bfdbfe;
}

.from-blue-200 {
    --tw-gradient-from: #bfdbfe;
}

.to-blue-300 {
    --tw-gradient-to: #93c5fd;
}
</style>
