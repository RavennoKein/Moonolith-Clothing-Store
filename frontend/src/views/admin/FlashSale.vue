<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  Zap, Plus, Clock, Calendar,
  ChevronRight, Package,
  AlertCircle, CheckCircle2, History, ArrowLeft
} from 'lucide-vue-next';
import api from '@/services/api';

const router = useRouter();
const isLoading = ref(false);

const activeFlashSale = ref(null);
const flashSaleHistory = ref([]);

const goBackToItems = () => router.push({ name: 'Item' });
const goToCreate = () => {
  if (!activeFlashSale.value) {
    router.push({ name: 'TambahFlashsale' });
  }
};

const fetchFlashSales = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/admin/flash-sales');

    activeFlashSale.value = res.data.data.active;
    flashSaleHistory.value = res.data.data.history;
  } catch (err) {
    console.error('Gagal mengambil flash sale', err);
  } finally {
    isLoading.value = false;
  }
};

onMounted(fetchFlashSales);

</script>

<template>
  <div class="min-h-screen bg-gray-50 p-6 md:p-10">
    <div class="max-w-7xl mx-auto">

      <div class="mb-6">
        <button @click="goBackToItems"
          class="flex items-center text-slate-500 hover:text-blue-600 font-semibold transition-colors group ">
          <div class="p-2 bg-white rounded-lg shadow-sm group-hover:shadow-md mr-3 transition-all">
            <ArrowLeft class="w-5 h-5" />
          </div>
          Kembali ke Data Item
        </button>
      </div>

      <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-6">
        <div>
          <h1 class="text-3xl font-black text-slate-800 flex items-center gap-3">
            <Zap class="w-8 h-8 text-yellow-500 fill-yellow-500" />
            DASHBOARD FLASH SALE
          </h1>
          <p class="text-slate-500 mt-1 uppercase text-xs font-bold tracking-widest opacity-70">Admin Control Panel</p>
        </div>

        <div class="relative group">
          <button @click="goToCreate" :disabled="!!activeFlashSale" :class="[
            'flex items-center px-7 py-4 rounded-2xl font-black transition-all duration-300 shadow-xl',
            activeFlashSale
              ? 'bg-slate-200 text-slate-400 cursor-not-allowed grayscale'
              : 'bg-linear-to-r from-blue-600 to-blue-800 text-white hover:shadow-blue-200 hover:-translate-y-1'
          ]">
            <Plus class="w-5 h-5 mr-2 stroke-[3px]" />
            BUAT FLASH SALE BARU
          </button>

          <div v-if="activeFlashSale"
            class="absolute bottom-full mb-3 right-0 w-72 p-4 bg-slate-900 text-white text-xs rounded-2xl opacity-0 group-hover:opacity-100 transition-all translate-y-2 group-hover:translate-y-0 pointer-events-none shadow-2xl z-50">
            <div class="flex gap-3">
              <AlertCircle class="w-5 h-5 text-yellow-400 shrink-0" />
              <p class="leading-relaxed">
                Tombol dinonaktifkan karena ada <strong>{{ activeFlashSale.name }}</strong> yang sedang berjalan. Akhiri
                sesi saat ini untuk membuat yang baru.
              </p>
            </div>
            <div class="absolute top-full right-10 w-3 h-3 bg-slate-900 rotate-45 -mt-1.5"></div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-10">

        <section>
          <div class="flex items-center gap-2 mb-5">
            <div class="w-3 h-3 rounded-full bg-green-500 animate-pulse"></div>
            <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest">FlashSale Aktif</h2>
          </div>
          <div v-if="isLoading" class="py-20 text-center text-slate-400">
            Memuat data flash sale...
          </div>

          <div v-if="activeFlashSale"
            class="group relative bg-slate-900 rounded-4xl p-8 md:p-12 text-white shadow-2xl shadow-blue-900/20 overflow-hidden">
            <div class="absolute -top-24 -left-24 w-64 h-64 bg-blue-600/20 rounded-full blur-[80px]"></div>

            <div class="relative z-10 flex flex-col lg:flex-row justify-between items-start lg:items-center gap-8">
              <div class="space-y-4 max-w-2xl">
                <span
                  class="inline-flex items-center px-4 py-1.5 bg-blue-500/20 border border-blue-500/30 rounded-full text-blue-400 text-xs font-bold">
                  <Clock class="w-4 h-4 mr-2" />
                  Live Sekarang
                </span>
                <h3 class="text-4xl md:text-5xl font-black tracking-tight leading-tight">
                  {{ activeFlashSale.name }}
                </h3>
                <p class="text-slate-400 text-lg leading-relaxed">
                  {{ activeFlashSale.description }}
                </p>

                <div class="flex flex-wrap gap-6 pt-4">
                  <div>
                    <p class="text-[10px] uppercase font-black text-slate-500 mb-1">Berakhir Pada</p>
                    <p class="font-mono text-xl text-yellow-400">{{ activeFlashSale.end_time }}</p>
                  </div>
                  <div class="w-px h-10 bg-slate-800 hidden md:block"></div>
                  <div>
                    <p class="text-[10px] uppercase font-black text-slate-500 mb-1">Produk Terdaftar</p>
                    <p class="text-xl font-bold">{{ activeFlashSale.items_count }} SKU Items</p>
                  </div>
                </div>
              </div>

              <div class="shrink-0 w-full lg:w-auto">
                <button @click="router.push({ name: 'DetailFlashsale', params: { id: activeFlashSale.id } })"
                  class="w-full lg:w-auto px-10 py-5 bg-white text-slate-900 font-black rounded-2xl hover:bg-yellow-400 transition-all duration-300 hover:scale-105 active:scale-95 shadow-xl">
                  PANTAU REAL-TIME
                </button>
              </div>
            </div>
          </div>

          <div v-else class="bg-white border-4 border-dashed border-slate-100 rounded-4xl py-20 text-center">
            <Package class="w-16 h-16 text-slate-200 mx-auto mb-4" />
            <h3 class="text-slate-400 font-bold text-xl">Tidak Ada Flash Sale Berjalan</h3>
            <p class="text-slate-300">Silahkan buat campaign baru untuk memulai promosi</p>
          </div>
        </section>

        <section>
          <div class="flex items-center gap-2 mb-5">
            <History class="w-5 h-5 text-slate-400" />
            <h2 class="text-sm font-black text-slate-400 uppercase tracking-widest">Riwayat & Terjadwal</h2>
          </div>

          <div class="bg-white rounded-4xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left border-collapse">
                <thead>
                  <tr class="bg-slate-50/50">
                    <th
                      class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100">
                      Info Flashsale</th>
                    <th
                      class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100 text-center">
                      Durasi</th>
                    <th
                      class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100 text-center">
                      Items</th>
                    <th
                      class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100">
                      Status</th>
                    <th
                      class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-tighter border-b border-slate-100">
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                  <div v-if="isLoading" class="py-20 text-center text-slate-400">
                    Memuat data flash sale...
                  </div>
                  <tr v-for="sale in flashSaleHistory" :key="sale.id" class="group hover:bg-blue-50/30 transition-all">
                    <td class="px-8 py-6">
                      <div class="flex items-center gap-4">
                        <div
                          class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400 group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">
                          <Calendar class="w-5 h-5" />
                        </div>
                        <div>
                          <p class="font-bold text-slate-800">{{ sale.name }}</p>
                          <p class="text-[10px] text-slate-400 font-mono tracking-widest">REF: #FS-{{ sale.id }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-8 py-6">
                      <div class="text-center">
                        <p class="text-xs font-bold text-slate-600">{{ sale.start_time }}</p>
                        <p class="text-[10px] text-slate-400">sampai</p>
                        <p class="text-xs font-bold text-slate-600">{{ sale.end_time }}</p>
                      </div>
                    </td>
                    <td class="px-8 py-6 text-center">
                      <span
                        class="inline-block px-3 py-1 bg-slate-100 text-slate-500 rounded-lg text-[10px] font-black">
                        {{ sale.items_count }} SKU
                      </span>
                    </td>
                    <td class="px-8 py-6">
                      <div class="flex items-center gap-2">
                        <div :class="[
                          'w-2 h-2 rounded-full',
                          sale.status === 'inactive' ? 'bg-slate-300' : 'bg-blue-500'
                        ]"></div>

                        <span :class="[
                          'text-xs font-bold uppercase tracking-widest',
                          sale.status === 'inactive' ? 'text-slate-400' : 'text-blue-600'
                        ]">
                          <template v-if="sale.status === 'inactive'">Selesai</template>
                          <template v-else-if="sale.status === 'scheduled'">Terjadwal</template>
                          <template v-else>{{ sale.status }}</template>
                        </span>
                      </div>
                    </td>
                    <td class="px-8 py-6 text-right">
                      <button @click="router.push({ name: 'DetailFlashsale', params: { id: sale.id } })"
                        class="p-2 bg-slate-50 hover:bg-white rounded-xl shadow-none hover:shadow-md transition-all group/btn">
                        <div class="flex items-center gap-2">
                          <span
                            class="text-[10px] font-bold text-slate-400 group-hover/btn:text-blue-600 transition-colors">DETAIL</span>
                          <ChevronRight class="w-5 h-5 text-slate-300 group-hover/btn:text-blue-600" />
                        </div>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom Shadow untuk tombol disabled */
button:disabled {
  box-shadow: none !important;
  transform: none !important;
}
</style>