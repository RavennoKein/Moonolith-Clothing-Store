<script setup>
import {
  Zap, ArrowLeft, Clock, Tag, RefreshCw,
  ShoppingBag, Plus, Trash2, Save, RotateCcw,
  PackageSearch
} from 'lucide-vue-next';
import { ref, onMounted } from 'vue';
import api from '@/services/api';
import Swal from 'sweetalert2';
import { useRouter } from 'vue-router';

const router = useRouter();
const isSubmitting = ref(false);

const session = ref({
  name: '',
  description: '',
  startTime: '',
  endTime: '',
  isPermanent: false,
  status: 'scheduled'
});

const selectedItems = ref([]);

const availableProducts = ref([]);

const fetchAvailableProducts = async () => {
  const res = await api.get('/admin/flash-sales/available-items');
  availableProducts.value = res.data.data;
};

onMounted(fetchAvailableProducts);

const getOriginalPrice = (itemId) => {
  const product = availableProducts.value.find(p => p.id === itemId);
  return product ? product.price : 0;
};

const getDiscountPercentage = (item) => {
  const originalPrice = getOriginalPrice(item.item_id);
  if (!originalPrice || !item.discount_price) return 0;

  const discount =
    ((originalPrice - item.discount_price) / originalPrice) * 100;

  return Math.round(discount);
};


const addItem = () => {
  selectedItems.value.push({
    item_id: null,
    discount_price: 0,
    flash_sale_stock: 0
  });
};

const removeItem = (index) => {
  selectedItems.value.splice(index, 1);
};

const goBack = () => {
  window.history.back();
};

const resetForm = () => {
  session.value = {
    name: '',
    description: '',
    startTime: '',
    endTime: '',
    isPermanent: false,
    status: 'scheduled'
  };
  selectedItems.value = [];
};

const handleSubmit = async () => {

  if (selectedItems.value.length === 0) {
    Swal.fire('Perhatian', 'Harap tambahkan minimal satu produk diskon', 'info');
    return;
  }

  const isInvalid = selectedItems.value.some(
    item => !item.item_id || item.discount_price <= 0 || item.flash_sale_stock <= 0
  );

  if (isInvalid) {
    Swal.fire(
      'Data Tidak Lengkap',
      'Pastikan semua item memiliki produk, harga promo, dan stok yang valid',
      'warning'
    );
    return;
  }

  const payload = {
    name: session.value.name,
    description: session.value.description,
    startTime: session.value.startTime || null,
    endTime: session.value.endTime || null,
    isPermanent: session.value.isPermanent,

    items: selectedItems.value.map(item => ({
      item_id: item.item_id,
      discount_price: item.discount_price,
      flash_sale_stock: item.flash_sale_stock
    }))
  };

  isSubmitting.value = true;

  try {
    Swal.fire({
      title: 'Memproses...',
      text: 'Sedang mempublikasikan Flash Sale',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading()
    });

    await api.post('/admin/flash-sales', payload);

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Flash Sale telah berhasil dipublikasikan',
      timer: 2000,
      showConfirmButton: false
    });

    router.push({ name: 'Item' });

  } catch (err) {
    Swal.fire({
      icon: 'warning',
      title: 'Gagal Mempublikasikan',
      text: err.response?.data?.message || 'Terjadi kesalahan saat memproses data.',
      confirmButtonColor: '#1e40af',
    });
  } finally {
    isSubmitting.value = false;
  }
};


</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <div class="bg-linear-to-r from-blue-900 via-blue-800 to-blue-900 shadow-lg relative overflow-hidden">
      <div class="absolute top-0 left-0 w-full h-1.5 bg-linear-to-r from-blue-500 via-blue-600 to-blue-700"></div>

      <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex-1">
          <h1 class="text-3xl font-black text-white tracking-tight flex items-center gap-3">
            <Zap class="w-8 h-8 text-yellow-400 fill-yellow-400" />
            PENGATURAN FLASH SALE
          </h1>
          <p class="text-blue-100 text-sm mt-1 opacity-90">
            Kelola periode diskon terbatas dan kuota produk untuk meningkatkan penjualan
          </p>
        </div>
        <button @click="goBack"
          class="group flex items-center w-fit px-5 py-2.5 bg-white/10 backdrop-blur-md text-white border border-white/20 rounded-xl hover:bg-white/20 transition-all duration-300 transform hover:scale-105 active:scale-95">
          <ArrowLeft class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" />
          <span class="font-medium">Kembali</span>
        </button>
      </div>
    </div>

    <main class="p-6 md:p-10">
      <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">

          <div class="px-8 py-6 bg-linear-to-r from-blue-50 to-blue-100 border-b border-blue-200">
            <h2 class="text-xl font-bold text-gray-800">Form Konfigurasi Kampanye</h2>
            <p class="text-gray-600 text-sm mt-1">Lengkapi detail waktu dan produk di bawah ini</p>
          </div>

          <form @submit.prevent="handleSubmit" class="p-8 space-y-12">
            <section class="space-y-8">
              <div class="flex items-center gap-3">
                <div
                  class="h-10 w-10 bg-blue-700 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                  <Clock class="w-6 h-6" />
                </div>
                <h3 class="text-xl font-bold text-gray-800 tracking-tight">Konfigurasi Waktu & Sesi</h3>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="md:col-span-2 group">
                  <label
                    class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors uppercase tracking-wider">
                    Nama Kampanye <span class="text-red-500">*</span>
                  </label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                      <Tag class="w-5 h-5 text-gray-400 group-hover:text-blue-500 transition-colors" />
                    </div>
                    <input v-model="session.name" type="text" required
                      class="w-full pl-12 pr-4 py-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all hover:border-blue-300"
                      placeholder="Contoh: Promo Gila Harbolnas 12.12">
                  </div>
                </div>

                <div class="md:col-span-2 group mt-4">
                  <label
                    class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 transition-colors uppercase tracking-wider">
                    Deskripsi Kampanye
                  </label>
                  <div class="relative">
                    <textarea v-model="session.description" rows="3"
                      class="w-full px-4 py-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all hover:border-blue-300 bg-white"
                      placeholder="Berikan keterangan detail mengenai promo ini..."></textarea>
                  </div>
                </div>

                <div class="group">
                  <label
                    class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 uppercase tracking-wider">Mulai
                    Tanggal</label>
                  <input v-model="session.startTime" type="datetime-local"
                    class="w-full p-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all bg-gray-50/50">
                </div>

                <div class="group">
                  <label
                    class="block mb-2 text-sm font-semibold text-gray-700 group-hover:text-blue-600 uppercase tracking-wider">Berakhir
                    Tanggal</label>
                  <input v-model="session.endTime" type="datetime-local"
                    class="w-full p-4 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 transition-all bg-gray-50/50">
                </div>

                <div
                  class="md:col-span-2 p-5 bg-linear-to-r from-blue-50 to-indigo-50 rounded-2xl border border-blue-100 flex items-center justify-between">
                  <div class="flex items-center gap-4">
                    <div class="p-3 bg-white rounded-xl shadow-sm">
                      <RefreshCw class="w-6 h-6 text-blue-600" />
                    </div>
                    <div>
                      <p class="font-bold text-blue-900">Ulangi Otomatis</p>
                      <p class="text-sm text-blue-700 opacity-80">Sesi akan diperbarui otomatis setiap 48 jam</p>
                    </div>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer group">
                    <input type="checkbox" v-model="session.isPermanent" class="sr-only peer">

                    <div
                      class="w-14 h-7 bg-gray-300 rounded-full peer peer-focus:ring-4 peer-focus:ring-blue-100 peer-checked:bg-blue-600  transition-all duration-300 after:content-[''] after:absolute after:top-1 after:left-1 after:bg-white after:border-gray-300  after:border after:rounded-full after:h-5 after:w-5 after:transition-all after:duration-300 peer-checked:after:translate-x-7 peer-checked:after:border-white shadow-inner">
                    </div>
                  </label>
                </div>
              </div>
            </section>

            <hr class="border-gray-100">

            <section class="space-y-8">
              <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                  <div
                    class="h-10 w-10 bg-blue-700 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-200">
                    <ShoppingBag class="w-6 h-6" />
                  </div>
                  <h3 class="text-xl font-bold text-gray-800 tracking-tight">Daftar Produk Diskon</h3>
                </div>
                <button @click.prevent="addItem"
                  class="flex items-center justify-center px-6 py-3 bg-white border-2 border-blue-700 text-blue-700 font-bold rounded-xl hover:bg-blue-700 hover:text-white transition-all shadow-sm">
                  <Plus class="w-5 h-5 mr-2" />
                  Tambah Item
                </button>
              </div>

              <div class="space-y-6">
                <div v-if="selectedItems.length === 0"
                  class="py-16 text-center border-3 border-dashed border-gray-200 rounded-3xl bg-gray-50/50">
                  <div class="inline-flex p-5 bg-white rounded-full shadow-sm mb-4">
                    <PackageSearch class="w-10 h-10 text-gray-300" />
                  </div>
                  <p class="text-gray-500 font-bold">Belum ada produk terpilih</p>
                  <p class="text-gray-400 text-sm">Klik tombol di atas untuk mulai menambahkan produk promo</p>
                </div>

                <div v-for="(item, index) in selectedItems" :key="index"
                  class="group relative bg-white p-6 rounded-2xl border border-gray-200 hover:border-blue-400 hover:shadow-xl transition-all duration-300 flex flex-wrap lg:flex-nowrap gap-6 items-end">

                  <div class="flex-1 min-w-75">
                    <label class="block mb-2 text-[11px] font-black text-gray-400 uppercase">Pilih Produk</label>
                    <select v-model="item.item_id"
                      class="w-full bg-gray-50 p-4 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white appearance-none transition-all">
                      <option :value="null">-- Cari Produk di Katalog --</option>
                      <option v-for="p in availableProducts" :key="p.id" :value="p.id">{{ p.name }}</option>
                    </select>
                  </div>

                  <div class="w-full lg:w-56 group/input">
                    <div class="flex flex-row items-center">
                      <label
                        class="block mb-2 text-[11px] font-black text-gray-400 uppercase group-hover/input:text-blue-600 transition-colors">Harga
                        Promo (Rp)</label>
                      <div v-if="item.item_id && item.discount_price > 0" class="ml-auto mb-2 text-[11px] font-bold text-green-600">
                        Diskon {{ getDiscountPercentage(item) }}%
                      </div>
                    </div>
                    <input v-model="item.discount_price" type="number"
                      class="w-full bg-gray-50 p-4 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
                      placeholder="0">
                  </div>

                  <div class="w-full lg:w-40 group/input">
                    <label
                      class="block mb-2 text-[11px] font-black text-gray-400 uppercase group-hover/input:text-blue-600 transition-colors">Kuota
                      Stok</label>
                    <input v-model="item.flash_sale_stock" type="number"
                      class="w-full bg-gray-50 p-4 border border-gray-200 rounded-xl outline-none focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all"
                      placeholder="0">
                  </div>

                  <button @click.prevent="removeItem(index)"
                    class="shrink-0 bg-red-50 text-red-500 p-4 rounded-xl hover:bg-red-500 hover:text-white transition-all transform hover:rotate-12">
                    <Trash2 class="w-6 h-6" />
                  </button>
                </div>
              </div>
            </section>

            <div class="flex flex-col sm:flex-row justify-end gap-4 pt-10 border-t border-gray-100">
              <button type="button" @click="resetForm"
                class="px-8 py-4 border border-gray-300 text-gray-600 font-bold rounded-2xl hover:bg-gray-50 transition-all flex items-center justify-center">
                <RotateCcw class="w-5 h-5 mr-2" />
                Reset Form
              </button>
              <button type="submit"
                class="px-10 py-4 bg-linear-to-r from-blue-700 via-blue-800 to-blue-900 text-white font-black rounded-2xl shadow-xl hover:shadow-blue-200 hover:-translate-y-1 active:translate-y-0 transition-all flex items-center justify-center">
                <Save v-if="!isSubmitting" class="w-5 h-5 mr-3" />
                <RefreshCw v-else class="w-5 h-5 mr-3 animate-spin" />
                {{ isSubmitting ? 'MEMPROSES...' : 'PUBLIKASIKAN FLASH SALE' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
input,
select {
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}

input:focus {
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 10px;
}
</style>