<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  ArrowLeft, Zap, Clock, Package,
  TrendingUp, BarChart3, ChevronLeft,
  AlertCircle, CheckCircle2, ShoppingBag
} from 'lucide-vue-next';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();
const isLoading = ref(true);
const flashSale = ref(null);

const fetchDetail = async () => {
  isLoading.value = true;
  try {
    const response = await api.get(`/admin/flash-sales/${route.params.id}`);
    flashSale.value = response.data.data;
  } catch (error) {
    console.error("Gagal mengambil detail flash sale:", error);
  } finally {
    isLoading.value = false;
  }
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(value);
};

const calculateProgress = (sold, total) => {
  return Math.min(Math.round((sold / total) * 100), 100);
};

onMounted(fetchDetail);
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-6 md:p-10">
    <div class="max-w-7xl mx-auto">

      <button @click="router.back()"
        class="flex items-center text-slate-500 hover:text-blue-600 font-semibold mb-8 group transition-all">
        <div class="p-2 bg-white rounded-xl shadow-sm group-hover:shadow-md mr-3">
          <ChevronLeft class="w-5 h-5" />
        </div>
        Kembali ke Daftar
      </button>

      <div v-if="isLoading" class="flex flex-col items-center justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-4"></div>
        <p class="text-slate-400 font-medium">Memuat rincian kampanye...</p>
      </div>

      <div v-else-if="flashSale" class="space-y-8">

        <div class="bg-white rounded-[2.5rem] p-8 md:p-10 shadow-sm border border-slate-100 relative overflow-hidden">
          <div class="absolute top-0 right-0 p-10 opacity-5">
            <Zap class="w-40 h-40 text-blue-600 fill-blue-600" />
          </div>

          <div class="relative z-10">
            <div class="flex flex-wrap items-center gap-3 mb-6">
              <span :class="[
                'px-4 py-1.5 rounded-full text-xs font-black uppercase tracking-widest',
                flashSale.status === 'active' ? 'bg-green-100 text-green-600' :
                  flashSale.status === 'scheduled' ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500'
              ]">
                <template v-if="flashSale.status === 'active'">● Sedang Berjalan</template>
                <template v-else-if="flashSale.status === 'scheduled'">● Terjadwal</template>
                <template v-else>● Selesai (Riwayat)</template>
              </span>
              <span
                class="px-4 py-1.5 bg-blue-50 text-blue-600 rounded-full text-xs font-black uppercase tracking-widest">
                ID Campaign: #FS-{{ flashSale.id }}
              </span>
            </div>

            <h1 class="text-4xl font-black text-slate-800 mb-4">{{ flashSale.name }}</h1>
            <p class="text-slate-500 text-lg max-w-3xl leading-relaxed mb-8">
              {{ flashSale.description || 'Tidak ada deskripsi untuk kampanye ini.' }}
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-8 border-t border-slate-50">
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-yellow-50 rounded-2xl flex items-center justify-center text-yellow-600">
                  <Calendar class="w-6 h-6" />
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Waktu Mulai</p>
                  <p class="font-bold text-slate-700">{{ flashSale.start_time }}</p>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-600">
                  <Clock class="w-6 h-6" />
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Waktu Berakhir</p>
                  <p class="font-bold text-slate-700">{{ flashSale.end_time }}</p>
                </div>
              </div>
              <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                  <Package class="w-6 h-6" />
                </div>
                <div>
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-tighter">Total Produk</p>
                  <p class="font-bold text-slate-700">{{ flashSale.items_count }} SKU Terdaftar</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden">
          <div class="p-8 border-b border-slate-50 flex justify-between items-center">
            <h2 class="text-xl font-black text-slate-800 flex items-center gap-3">
              <BarChart3 class="w-6 h-6 text-blue-600" />
              DAFTAR ITEM PROMO
            </h2>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <thead>
                <tr class="bg-slate-50/50">
                  <th class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">Produk</th>
                  <th class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center">
                    Harga Promo</th>
                  <th class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest text-center">Stok
                    Sesi</th>
                  <th class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">Performa
                    Penjualan</th>
                  <th class="px-8 py-5 text-[11px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-50">
                <tr v-for="item in flashSale.items" :key="item.item_id"
                  class="group hover:bg-slate-50/50 transition-colors">
                  <td class="px-8 py-6">
                    <div class="flex items-center gap-4">
                      <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-400">
                        <ShoppingBag class="w-6 h-6" />
                      </div>
                      <div>
                        <p class="font-bold text-slate-800">{{ item.name }}</p>
                        <p class="text-xs text-slate-400 line-through">{{ formatCurrency(item.original_price) }}</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-8 py-6 text-center">
                    <div class="inline-block px-3 py-1 bg-green-50 text-green-600 rounded-lg font-black text-sm mb-1">
                      {{ formatCurrency(item.discount_price) }}
                    </div>
                    <p class="text-[10px] font-bold text-red-500">HEMAT {{ item.discount_percentage }}%</p>
                  </td>
                  <td class="px-8 py-6 text-center">
                    <p class="font-bold text-slate-700">{{ item.flash_sale_stock }}</p>
                    <p class="text-[10px] text-slate-400 uppercase font-bold">Unit</p>
                  </td>
                  <td class="px-8 py-6">
                    <div class="w-full max-w-40">
                      <div class="flex justify-between text-[10px] font-black uppercase mb-1.5">
                        <span class="text-slate-400">Terjual {{ item.sold_count }}</span>
                        <span class="text-blue-600">{{ calculateProgress(item.sold_count, item.flash_sale_stock)
                          }}%</span>
                      </div>
                      <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full transition-all duration-1000"
                          :style="{ width: calculateProgress(item.sold_count, item.flash_sale_stock) + '%' }"></div>
                      </div>
                    </div>
                  </td>
                  <td class="px-8 py-6">
                    <span v-if="item.status === 'sold_out'"
                      class="flex items-center gap-1.5 text-red-500 font-bold text-xs uppercase tracking-tight">
                      <AlertCircle class="w-4 h-4" /> Habis
                    </span>
                    <span v-else
                      class="flex items-center gap-1.5 text-green-500 font-bold text-xs uppercase tracking-tight">
                      <CheckCircle2 class="w-4 h-4" /> Tersedia ({{ item.remaining_stock }})
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>