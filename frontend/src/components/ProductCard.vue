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
    minimumFractionDigits: 0
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
    discount: hasFlashSale ? Math.round(((p.price - p.flash_sale.discount_price) / p.price) * 100) : null,
    flashSaleInfo: hasFlashSale ? {
      remaining: p.flash_sale.remaining,
    } : null,
    isNew: isNewItem(p.created_at, 7),
  };
});

</script>


<template>
  <router-link :to="{ name: 'ItemDetail', params: { id: product.id } }" class="block group">
    <div class="relative bg-white rounded-2xl overflow-hidden transition-all duration-500 border border-slate-100"
      :class="{
        'opacity-50 grayscale pointer-events-none': product.isDisabled,
        'hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)] hover:-translate-y-2': !product.isDisabled
      }">
      <div class="relative aspect-3/4 overflow-hidden bg-slate-50">
        <img :src="cardProduct.image" :alt="cardProduct.name"
          class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />

        <div v-if="product.isSoldOut"
          class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px] flex items-center justify-center z-20">
          <span class="bg-white text-slate-900 text-[10px] font-black px-4 py-2 rounded-full tracking-widest shadow-xl">
            HABIS
          </span>
        </div>

        <div v-if="cardProduct.discount"
          class="absolute top-3 left-3 bg-red-600 text-white text-[11px] font-black px-2.5 py-1 rounded-lg shadow-lg shadow-red-600/30">
          -{{ cardProduct.discount }}%
        </div>

        <div v-if="cardProduct.isNew"
          class="absolute top-3 right-3 bg-blue-600/90 backdrop-blur-md text-white text-[9px] font-black uppercase px-2 py-1 rounded-md shadow-sm border border-white/20">
          NEW
        </div>

        <div
          class="absolute inset-0 bg-blue-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 hidden md:flex items-end justify-center pb-4">
          <span
            class="bg-white/90 backdrop-blur px-4 py-2 rounded-full text-[10px] font-bold text-blue-900 shadow-lg transform translate-y-4 group-hover:translate-y-0 transition-transform duration-500">
            LIHAT DETAIL
          </span>
        </div>
      </div>

      <div class="p-4">
        <div v-if="cardProduct.flashSaleInfo && cardProduct.flashSaleInfo.remaining !== undefined" class="mb-3">
          <div class="flex justify-between text-[9px] font-bold mb-1.5 uppercase tracking-tighter">
            <span class="text-slate-400">Tersisa</span>
            <span class="text-red-600">{{ cardProduct.flashSaleInfo.remaining }} Stok</span>
          </div>
          <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full bg-red-500 rounded-full transition-all duration-1000"
              :style="{ width: (cardProduct.flashSaleInfo.remaining / 50 * 100) + '%' }"></div>
          </div>
        </div>

        <h3
          class="text-xs md:text-sm font-bold text-slate-800 line-clamp-2 leading-snug h-10 mb-2 group-hover:text-blue-700 transition-colors">
          {{ cardProduct.name }}
        </h3>

        <div class="flex flex-col gap-0.5">
          <div class="flex items-center gap-2">
            <span class="text-blue-900 font-black text-sm md:text-lg tracking-tight">
              {{ formatPrice(cardProduct.price) }}
            </span>
          </div>
          <span v-if="cardProduct.oldPrice"
            class="text-slate-400 line-through text-[10px] md:text-xs decoration-red-400/50">
            {{ formatPrice(cardProduct.oldPrice) }}
          </span>
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

.group:hover .relative.bg-white {
  border-color: rgba(30, 58, 138, 0.1);
}

@keyframes shimmer {
  0% {
    background-position: -200% 0;
  }

  100% {
    background-position: 200% 0;
  }
}

.bg-red-500 {
  background: linear-gradient(90deg, #ef4444, #f87171, #ef4444);
  background-size: 200% 100%;
  animation: shimmer 2s infinite linear;
}
</style>