<script setup>
import { computed } from 'vue';

const props = defineProps({
  product: {
    type: Object,
    required: true
  }
});

const formatPrice = (value) =>
  new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value);

const isNewItem = (createdAt, days = 7) => {
  if (!createdAt) return false;
  const created = new Date(createdAt);
  const now = new Date();
  const diffInDays = (now - created) / (1000 * 60 * 60 * 24);
  return diffInDays <= days;
};

const cardProduct = computed(() => {
  const p = props.product;
  const hasFlashSale = !!p.flash_sale;

  return {
    id: p.id,
    name: p.name,
    image: p.image_url,
    price: hasFlashSale ? p.flash_sale.discount_price : p.price,
    oldPrice: hasFlashSale ? p.price : null,

    discount: hasFlashSale
      ? Math.round(((p.price - p.flash_sale.discount_price) / p.price) * 100)
      : null,

    flashSaleInfo: hasFlashSale ? {
      remaining: p.flash_sale.remaining,
      percentage: Math.min((p.flash_sale.remaining / (p.flash_sale.stock || 50)) * 100, 100)
    } : null,

    isNew: isNewItem(p.created_at, 7),
    isRegular: !hasFlashSale
  };
});
</script>

<template>
  <router-link :to="{ name: 'ItemDetail', params: { id: product.id } }" class="block group h-full">

    <div
      class="relative h-full flex flex-col bg-white rounded-2xl overflow-hidden transition-all duration-300 border border-slate-100 hover:border-blue-200 hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)] hover:-translate-y-1"
      :class="{ 'opacity-60 grayscale pointer-events-none': product.isDisabled }">

      <div class="relative aspect-square overflow-hidden bg-slate-50">

        <img :src="cardProduct.image" :alt="cardProduct.name"
          class="w-72 h-full object-cover object-center transition-transform duration-700 ease-out group-hover:scale-110" />

        <div v-if="product.isSoldOut"
          class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center z-20">
          <div
            class="bg-white text-slate-900 text-[10px] font-black px-4 py-2 rounded-full tracking-widest shadow-xl uppercase border border-slate-200">
            Habis
          </div>
        </div>

        <div class="absolute top-3 left-3 flex flex-col gap-1.5 z-10">
          <span v-if="cardProduct.discount"
            class="bg-rose-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-lg shadow-lg shadow-rose-600/20 backdrop-blur-sm">
            -{{ cardProduct.discount }}%
          </span>

          <span v-if="cardProduct.isNew"
            class="bg-blue-600/90 backdrop-blur-md text-white text-[9px] font-bold px-2 py-1 rounded-md shadow-lg shadow-blue-600/20 uppercase tracking-wide w-fit border border-white/10">
            New Arrival
          </span>
        </div>

        <div
          class="absolute bottom-0 left-0 right-0 p-4 translate-y-full group-hover:translate-y-0 transition-transform duration-300 z-10 hidden md:block">
          <button
            class="w-full bg-white/95 backdrop-blur text-slate-900 text-xs font-bold py-3 rounded-xl shadow-lg hover:bg-blue-600 hover:text-white transition-colors flex items-center justify-center gap-2">
            Lihat Detail
          </button>
        </div>
      </div>

      <div class="p-4 flex flex-col flex-1">

        <h3
          class="text-sm font-semibold text-slate-800 leading-snug line-clamp-2 mb-2 group-hover:text-blue-700 transition-colors min-h-10">
          {{ cardProduct.name }}
        </h3>

        <div class="mt-auto flex flex-col gap-2">

         <div class="min-h-10.5 flex flex-col justify-end">

            <div v-if="product.isGlobalSoldOut || (product.stock !== undefined && product.stock <= 0)"
              class="flex items-center gap-2">
              <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-400 w-full cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 6.524a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                    clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] font-bold uppercase tracking-wide">Stok Habis</span>
              </div>
            </div>

            <div v-else-if="product.isFlashSaleSoldOut"
              class="bg-gray-100 rounded-lg px-2.5 py-1.5 border border-gray-200 opacity-80">
              <div class="flex justify-between items-center mb-1">
                <span class="text-[9px] font-black text-gray-500 uppercase tracking-tighter flex items-center gap-1">
                  ⚡ Flash Deal
                </span>
                <span class="text-[9px] font-bold text-red-500 uppercase tracking-wider">
                  Sold Out
                </span>
              </div>
              <div class="w-full h-1.5 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full bg-gray-400 w-full"></div>
              </div>
            </div>

            <div v-else-if="cardProduct.flashSaleInfo"
              class="bg-rose-50 rounded-lg px-2.5 py-1.5 border border-rose-100">
              <div class="flex justify-between items-end mb-1">
                <span class="text-[9px] font-black text-rose-600 uppercase tracking-tighter flex items-center gap-1">
                  <span class="animate-pulse">⚡</span> Flash Deal
                </span>
                <span class="text-[9px] font-semibold text-rose-400">
                  Sisa {{ cardProduct.flashSaleInfo.remaining }}
                </span>
              </div>
              <div class="w-full h-1.5 bg-rose-200/50 rounded-full overflow-hidden">
                <div class="h-full bg-linear-to-r from-rose-500 to-red-600 rounded-full"
                  :style="{ width: cardProduct.flashSaleInfo.percentage + '%' }"></div>
              </div>
            </div>

            <div v-else class="flex items-center gap-2">
              <div
                class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-emerald-50 border border-emerald-100 text-emerald-600 w-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                    clip-rule="evenodd" />
                </svg>
                <span class="text-[10px] font-bold uppercase tracking-wide">Ready Stock</span>
              </div>
            </div>

          </div>

          <div class="flex flex-col pt-1">
            <div class="h-4 flex items-center">
              <span v-if="cardProduct.oldPrice" class="text-[11px] text-slate-400 line-through decoration-slate-300">
                {{ formatPrice(cardProduct.oldPrice) }}
              </span>
            </div>

            <div class="flex items-center justify-between">
              <span class="text-lg font-bold text-slate-900 tracking-tight">
                {{ formatPrice(cardProduct.price) }}
              </span>

              <div
                class="w-8 h-8 rounded-full bg-slate-50 text-slate-400 group-hover:bg-blue-600 group-hover:text-white flex items-center justify-center transition-all duration-300 md:hidden shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                  stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </router-link>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>