<script setup>
import ProductCard from '@/components/ProductCard.vue';

const props = defineProps({
  products: {
    type: Array,
    default: () => []
  }
});
</script>

<template>
  <div
    class="relative w-full bg-linear-to-br from-[#020617] via-[#1e293b] to-[#020617] py-8 md:py-14 px-4 md:px-8 mb-10 overflow-hidden md:rounded-[40px] shadow-2xl border-y md:border border-blue-500/10">

    <div
      class="absolute -top-6 -right-5 text-7xl md:text-[140px] font-black text-white/3 select-none uppercase italic leading-none">
      Limited
    </div>

    <div class="absolute -bottom-20 -left-20 w-64 h-64 bg-blue-600/20 rounded-full blur-[100px] pointer-events-none">
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
      <div class="flex flex-row items-end justify-between mb-6 md:mb-10">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="flex h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
            <span class="text-red-500 text-[10px] md:text-xs font-bold uppercase tracking-[0.2em]">Live Now</span>
          </div>
          <h2 class="text-2xl md:text-5xl font-black text-white italic tracking-tighter">
            KILAT <span class="text-blue-400 drop-shadow-[0_0_10px_rgba(96,165,250,0.5)]">SALE</span>
          </h2>
        </div>

        <div class="flex md:hidden items-center text-slate-400 text-[10px] gap-1 font-medium italic opacity-70">
          Geser <span>&rarr;</span>
        </div>
      </div>

      <div class="flex overflow-x-auto gap-4 md:gap-8 pb-4 scrollbar-hide snap-x snap-mandatory touch-pan-x">
        <div v-for="product in props.products" :key="product.id"
          class="min-w-43.75 md:min-w-70 snap-center transition-transform duration-300 active:scale-95">
          <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-1 border border-white/10 shadow-2xl">
            <ProductCard :product="product" class="border-none! shadow-none! bg-transparent!" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
  -webkit-overflow-scrolling: touch;
}

.snap-center {
  animation: fadeInRight 0.5s ease-out both;
}

@keyframes fadeInRight {
  from {
    opacity: 0;
    transform: translateX(20px);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.snap-center:nth-child(1) {
  animation-delay: 0.1s;
}

.snap-center:nth-child(2) {
  animation-delay: 0.2s;
}

.snap-center:nth-child(3) {
  animation-delay: 0.3s;
}
</style>