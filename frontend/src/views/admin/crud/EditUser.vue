<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Save, ArrowLeft, User, Shield, Phone, Mail, MapPin, Eye, EyeOff, X } from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const adminId = route.params.id;

const showPassword = ref(false);
const isLoading = ref(true);

const formData = reactive({
  name: '',
  email: '',
  password: '',
  phone: '',
  address: ''
});

// Fetch Data Admin
const fetchAdmin = async () => {
  try {
    const res = await api.get('/admin/admins');
    const admin = res.data.data.find(
      item => item.id === Number(adminId)
    );


    if (!admin) {
      Swal.fire('Gagal', 'Admin tidak ditemukan', 'error');
      router.push({ name: 'User' });
      return;
    }

    formData.name = admin.name;
    formData.email = admin.email;
    formData.phone = admin.phone;
    formData.address = admin.address;

  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Gagal mengambil data dari server', 'error');
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchAdmin);

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const validateForm = () => {
  if (!formData.name.trim()) {
    Swal.fire('Peringatan', 'Nama lengkap harus diisi', 'warning');
    return false;
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(formData.email)) {
    Swal.fire('Peringatan', 'Format email tidak valid', 'warning');
    return false;
  }
  if (formData.password && formData.password.length < 6) {
    Swal.fire('Peringatan', 'Password baru minimal 6 karakter', 'warning');
    return false;
  }
  return true;
};

const handleSubmit = async () => {
  if (!validateForm()) return;

  try {
    Swal.fire({
      title: 'Menyimpan...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    const payload = {
      name: formData.name,
      email: formData.email,
      phone: formData.phone || null,
      address: formData.address || null
    };

    if (formData.password) payload.password = formData.password;

    await api.put(`/admin/admins/${adminId}`, payload);

    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Data user telah diperbarui.',
      timer: 2000,
      showConfirmButton: false
    });

    router.push({ name: 'User' });
  } catch (error) {
    console.error(error);
    const msg = error.response?.data?.message || 'Gagal memperbarui data';
    Swal.fire('Gagal', msg, 'error');
  }
};

const goBack = () => {
  Swal.fire({
    title: 'Batalkan perubahan?',
    text: "Perubahan yang belum disimpan akan hilang!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Ya, Kembali',
    cancelButtonText: 'Batal'
  }).then((result) => {
    if (result.isConfirmed) {
      router.push({ name: 'User' });
    }
  });
};

const generateRandomPassword = () => {
  const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
  let password = '';
  for (let i = 0; i < 12; i++) {
    password += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  formData.password = password;
};
</script>

<template>
  <div class="min-h-screen bg-linear-to-br from-gray-50 to-gray-100">
    <div class="bg-linear-to-r from-blue-900 via-blue-800 to-blue-900 shadow-lg">
      <div class="px-8 py-6">
        <div class="flex items-center justify-between gap-6">
          <div class="flex-1">
            <h1 class="text-2xl font-bold text-white tracking-tight">Edit User</h1>
            <p class="text-blue-100 text-sm mt-1 opacity-90">
              Perbarui informasi data pengguna sistem
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
        <div v-if="isLoading"
          class="flex flex-col items-center justify-center py-20 bg-white rounded-2xl shadow-xl border border-gray-100">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          <p class="mt-4 text-gray-500">Memuat data...</p>
        </div>

        <div v-else class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
          <div class="px-8 py-6 bg-linear-to-r from-blue-50 to-blue-100 border-b border-blue-200">
            <div class="flex items-center">
              <div>
                <h2 class="text-xl font-bold text-gray-800">Form Edit User</h2>
                <p class="text-gray-600 text-sm mt-1">Ubah formulir berikut untuk memperbarui data user</p>
              </div>
            </div>
          </div>
          <form @submit.prevent="handleSubmit" class="p-6 md:p-8 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="group">
                <label class="block mb-2 text-sm font-semibold text-gray-700">Nama Lengkap</label>
                <div class="relative">
                  <User class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" />
                  <input v-model="formData.name" type="text" required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
              </div>

              <div class="group">
                <label class="block mb-2 text-sm font-semibold text-gray-700">Email</label>
                <div class="relative">
                  <Mail class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" />
                  <input v-model="formData.email" type="email" required
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
              </div>

              <div class="group">
                <label class="block mb-2 text-sm font-semibold text-gray-700">Ganti Password (Opsional)</label>
                <div class="relative">
                  <Shield class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" />
                  <input v-model="formData.password" :type="showPassword ? 'text' : 'password'"
                    class="w-full pl-10 pr-12 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"
                    placeholder="Isi jika ingin diubah">
                  <button type="button" @click="togglePasswordVisibility" class="absolute right-3 top-3.5">
                    <Eye v-if="!showPassword" class="w-5 h-5 text-gray-400" />
                    <EyeOff v-else class="w-5 h-5 text-gray-400" />
                  </button>
                </div>
                <div class="mt-2 flex justify-between">
                  <button type="button" @click="generateRandomPassword"
                    class="px-3 py-1.5 text-xs bg-linear-to-r from-blue-100 to-blue-200 text-blue-700 rounded-lg hover:from-blue-200 hover:to-blue-300 transition-all duration-200 font-medium">
                    Generate Password
                  </button>
                  <div class="text-xs text-gray-500">Isi hanya jika ingin mengganti password</div>
                </div>
              </div>

              <div class="group">
                <label class="block mb-2 text-sm font-semibold text-gray-700">Nomor Telepon</label>
                <div class="relative">
                  <Phone class="absolute left-3 top-3.5 w-5 h-5 text-gray-400" />
                  <input v-model="formData.phone" type="tel"
                    class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
              </div>
            </div>

            <div class="group">
              <label class="block mb-2 text-sm font-semibold text-gray-700">Alamat</label>
              <div class="relative">
                <MapPin class="absolute left-3 top-3 w-5 h-5 text-gray-400" />
                <textarea v-model="formData.address" rows="3"
                  class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
              </div>
            </div>

            <div class="flex flex-col sm:flex-row justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-6 border-t">
              <button type="button" @click="goBack"
                class="px-7 py-3 border border-gray-300 rounded-xl hover:bg-gray-50 transition-all font-medium flex items-center justify-center">
                <X class="w-4 h-4 mr-2" /> Batal
              </button>
              <button type="submit"
                class="px-7 py-3 bg-blue-700 text-white font-bold rounded-xl shadow-lg hover:bg-blue-800 transition-all transform hover:-translate-y-1 flex items-center justify-center">
                <Save class="w-5 h-5 mr-2" /> Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
button,
input,
select,
textarea,
label {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
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