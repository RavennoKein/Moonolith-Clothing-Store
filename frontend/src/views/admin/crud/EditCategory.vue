<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { Tag, X, Save, ArrowLeft } from 'lucide-vue-next'
import api from '@/services/api'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const form = ref({ name: '' })

const fetchCategory = async () => {
  try {
    const res = await api.get('/admin/categories')
    const data = res.data.data.find(c => c.id == route.params.id)
    if (data) form.value.name = data.name
  } catch (e) {
    console.error("Gagal memuat data", e)
  }
}

const submit = async () => {
  loading.value = true
  try {
    await api.put(`/admin/categories/${route.params.id}`, form.value)
    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Kategori telah diperbarui.',
      confirmButtonColor: '#1e3a8a',
    })
    router.push({ name: 'Category' })
  } catch (e) {
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: e.response?.data?.message || 'Terjadi kesalahan'
    })
  } finally {
    loading.value = false
  }
}

onMounted(fetchCategory)
</script>

<template>
  <div class="min-h-screen bg-gray-50">
    <div class="bg-linear-to-r from-blue-900 via-blue-800 to-blue-900 shadow-lg relative">
      <div class="px-8 py-6">
        <div class="flex items-center justify-between gap-6">
          <div class="flex-1">
            <h1 class="text-2xl font-bold text-white tracking-tight">Edit Kategori</h1>
            <p class="text-blue-100 text-sm mt-1 opacity-90">Perbarui informasi kategori yang sudah ada</p>
          </div>
          <button @click="router.back()"
            class="group flex items-center px-5 py-2.5 bg-white/10 backdrop-blur-md text-white border border-white/20 rounded-xl hover:bg-white/20 transition-all duration-300">
            <ArrowLeft class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" />
            <span class="font-medium">Kembali</span>
          </button>
        </div>
      </div>
    </div>

    <div class="p-6 md:p-8">
      <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
          <div class="px-8 py-6 bg-linear-to-r from-blue-50 to-blue-100 border-b border-blue-200">
            <h2 class="text-xl font-bold text-gray-800">Formulir Perubahan</h2>
          </div>

          <form @submit.prevent="submit" class="p-6 md:p-8 space-y-8">
            <div class="group">
              <label
                class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors">Nama
                Kategori</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Tag class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
                </div>
                <input v-model="form.name" type="text" required
                  class="w-full pl-10 pr-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                  placeholder="Contoh: Elektronik">
              </div>
            </div>

            <div class="flex justify-end space-x-4 pt-8 border-t border-gray-100">
              <button type="button" @click="router.back()"
                class="px-7 py-3.5 text-gray-700 font-medium hover:text-gray-900">
                Batal
              </button>
              <button type="submit" :disabled="loading"
                class="group px-7 py-3.5 bg-blue-700 text-white font-semibold rounded-xl shadow-lg hover:bg-blue-800 transition-all flex items-center">
                <Save class="w-5 h-5 mr-2 group-hover:scale-110 transition-transform" />
                Simpan Perubahan
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

form {
  animation: fadeIn 0.3s ease-out forwards;
}
</style>