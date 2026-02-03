<script setup>
import Navbar from '../../components/Navbar.vue';
import FlashSale from '../../components/landing/FlashSale.vue';
import FlashSaleContainer from '../../components/landing/FlashSaleContainer.vue';
import HeroCarousel from '../../components/landing/HeroCarousel.vue';
import ProductCard from '../../components/ProductCard.vue';
import Footer from '../../components/Footer.vue';
import { useRoute } from 'vue-router';
import carousel1 from '@/assets/carousel1.jpg';
import carousel2 from '@/assets/carousel2.jpg';
import carousel3 from '@/assets/carousel3.jpg';
import FlashSaleSkeleton from '@/components/skeletons/FlashSaleSkeleton.vue';
import ProductCardSkeleton from '@/components/skeletons/ProductCardSkeleton.vue';
import FlashSaleEmpty from '@/components/landing/FlashSaleEmpty.vue';
import { ref, onMounted, computed } from 'vue';
import api from '@/services/api';

const flashSale = ref(null);
const flashSaleProducts = ref([]);
const rawProducts = ref([]); 
const loading = ref(true);
const isLoggedIn = ref(false);

onMounted(async () => {
  const token = localStorage.getItem('token');
  isLoggedIn.value = !!token;

  loading.value = true;
  await Promise.all([
    fetchFlashSaleStatus(),
    fetchProducts()
  ]);
  loading.value = false;
  setTimeout(() => {
    loading.value = false;
  }, 300);
});

const heroImages = ref([
  carousel1,
  carousel2,
  carousel3
]);

const fetchFlashSaleStatus = async () => {
  try {
    const res = await api.get('/public/flash-sale');

    if (!res.data.data) {
      flashSale.value = null;
      flashSaleProducts.value = [];
      return;
    }

    flashSale.value = res.data.data;

    const detail = await api.get(`/public/flash-sale/${flashSale.value.id}`);

    flashSaleProducts.value = detail.data.data.items.map(item => ({
      id: item.id, 
      name: item.name,
      image_url: item.image_url,

      price: item.original_price,

      flash_sale: {
        discount_price: item.discount_price,
        stock: item.flash_sale_stock,
        sold: item.sold_count,
        remaining: item.flash_sale_stock - item.sold_count
      }
    }));
  } catch (e) {
    console.error("Error fetching flash sale:", e);
    flashSale.value = null;
    flashSaleProducts.value = [];
  }
};

const fetchProducts = async () => {
  try {
    const res = await api.get('/public/items');
    rawProducts.value = res.data.data;
  } catch (e) {
    console.error("Error fetching products:", e);
  }
};

const products = computed(() => {
  return rawProducts.value.map(item => {
    const flashItem = flashSaleProducts.value.find(fs => fs.id === item.id);

    if (flashItem) {
      const remainingStock = flashItem.flash_sale.remaining;

      return {
        ...item,
        price: item.price,

        isFlashSaleSoldOut: remainingStock <= 0,

        isGlobalSoldOut: item.total_stock <= 0,

        flash_sale: {
          discount_price: flashItem.flash_sale.discount_price,
          stock: flashItem.flash_sale.stock,
          sold: flashItem.flash_sale.sold,
          remaining: remainingStock
        }
      };
    }

    return {
      ...item,
      flash_sale: null,
      isFlashSaleSoldOut: false,
      isGlobalSoldOut: item.total_stock <= 0
    };
  });
});

const handleFlashSaleExpired = async () => {
  await Promise.all([
    fetchFlashSaleStatus(),
    fetchProducts()
  ]);
};

const hasFlashSaleStock = computed(() => {
  if (!Array.isArray(flashSaleProducts.value)) return false;
  return flashSaleProducts.value.some(p => p.flash_sale.remaining > 0);
});

const hasProducts = computed(() => products.value && products.value.length > 0);
const hasFlashSale = computed(() => flashSale.value !== null);

</script>

<template>
  <div class="landing-page-wrapper bg-gray-50 min-h-screen font-sans text-slate-900">
    <Navbar />

    <section class="w-full">
      <HeroCarousel :images="heroImages" />
    </section>

    <div class="mt-2 md:mt-6 bg-gray-100 border-y border-gray-100 py-6 md:py-10">
      <FlashSaleSkeleton v-if="loading" />

      <template v-else>
        <FlashSale v-if="flashSale" :isActive="true" :startTime="flashSale.start_time" :endTime="flashSale.end_time"
          @expired="handleFlashSaleExpired" />

        <FlashSaleContainer v-if="hasFlashSale && hasFlashSaleStock" :products="flashSaleProducts" />

        <div v-else-if="flashSale && !hasFlashSaleStock" class="py-10 text-center">
          <div class="text-3xl mb-2">😢</div>
          <p class="font-bold text-gray-600">
            Semua produk Flash Sale sudah habis
          </p>
          <p class="text-sm text-gray-400 mt-1">
            Tunggu promo berikutnya ya!
          </p>
        </div>

        <FlashSaleEmpty v-if="!flashSale" />
      </template>
    </div>

    <section class="max-w-7xl mx-auto px-4 my-8 md:my-16">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">

        <div class="relative rounded-2xl md:rounded-3xl overflow-hidden group cursor-pointer h-48 md:h-87.5">
          <img src="@/assets/banner-pria.jpg"
            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
          <div
            class="absolute inset-0 bg-linear-to-r from-blue-900/90 via-blue-900/40 to-transparent flex flex-col justify-center p-6 md:p-8">
            <h3 class="text-white text-xl md:text-4xl font-black mb-1 md:mb-2 uppercase leading-tight">
              Koleksi<br>Pria
            </h3>
            <p class="text-blue-100 text-[10px] md:text-sm mb-3 md:mb-4 opacity-90">Gaya urban minimalis.</p>
            <router-link :to="{ name: 'MenPages' }">
              <button
                class="bg-white text-blue-900 font-bold px-5 py-1.5 md:px-6 md:py-2 rounded-full w-fit text-[10px] md:text-sm hover:bg-blue-900 hover:text-white transition-colors cursor-pointer">
                Explore
              </button>
            </router-link>
          </div>
        </div>

        <div v-if="isLoggedIn"
          class="relative rounded-2xl md:rounded-3xl overflow-hidden group bg-linear-to-br from-slate-900 via-blue-900 to-slate-900 shadow-xl border border-blue-800 h-48 md:h-87.5">
          <div class="absolute inset-0 opacity-10 pattern-grid-lg pointer-events-none"></div>

          <div
            class="absolute -bottom-5 -left-5 w-24 h-24 md:w-40 md:h-40 bg-blue-500/20 rounded-full blur-2xl md:blur-3xl">
          </div>

          <div class="relative z-10 flex flex-col items-center justify-center h-full text-center p-6 md:p-8">
            <span
              class="bg-amber-500 text-white text-[8px] md:text-[10px] font-black px-2 py-0.5 md:px-3 md:py-1 rounded-full uppercase tracking-widest mb-2 shadow-lg">
              Most Wanted
            </span>
            <h3 class="text-white text-xl md:text-4xl font-black mb-1 md:mb-2 uppercase leading-tight tracking-tighter">
              Best <span class="text-amber-400">Seller</span>
            </h3>
            <p class="text-blue-100 text-[10px] md:text-sm mb-4 md:mb-6 px-4 leading-relaxed">
              Koleksi terpopuler <br class="md:hidden"> komunitas Moonolith.
            </p>
            <router-link :to="{ name: 'MostBuyPages' }" class="inline-block z-20">
              <button
                class="bg-amber-500 text-white px-6 py-2 md:px-8 md:py-3 rounded-full font-extrabold hover:bg-amber-400 hover:scale-105 active:scale-95 transition-all shadow-lg cursor-pointer border-none uppercase text-[10px] md:text-xs tracking-widest">
                LIHAT SEMUA
              </button>
            </router-link>
          </div>
        </div>

        <div v-else
          class="relative rounded-2xl md:rounded-3xl overflow-hidden group bg-blue-900 shadow-xl border border-blue-800 h-48 md:h-87.5">
          <div class="absolute inset-0 opacity-20 pattern-dots pointer-events-none"></div>
          <div class="relative z-10 flex flex-col items-center justify-center h-full text-center p-6 md:p-8">
            <h3 class="text-white text-xl md:text-3xl font-black mb-1 md:mb-2 uppercase italic leading-tight">
              Pesan Baju<br>Idamanmu
            </h3>
            <p class="text-blue-200 text-[10px] md:text-sm mb-4 md:mb-6 px-4">
              Ganti style mu yang <br class="md:hidden"> biasa-biasa aja
            </p>
            <router-link :to="{ name: 'Register' }" class="inline-block z-20">
              <button
                class="bg-white text-blue-900 px-6 py-2 md:px-8 md:py-3 rounded-full font-extrabold hover:bg-blue-50 hover:scale-105 transition-all shadow-lg cursor-pointer text-[10px] md:text-sm">
                GABUNG SEKARANG
              </button>
            </router-link>
          </div>
        </div>

      </div>
    </section>

    <div class="landing-page-wrapper bg-gray-50 min-h-screen font-sans">
      <main class="max-w-7xl mx-auto px-4 pb-20">
        <section class="mt-10 md:mt-16">
          <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-black text-slate-900 tracking-tight uppercase">
              Koleksi <span class="text-blue-900">Terbaru</span>
            </h2>
            <div class="w-20 h-1.5 bg-blue-900 mx-auto mt-4 rounded-full"></div>
          </div>

          <div v-if="!loading && !hasProducts" class="py-20 text-center">
            <div class="text-5xl mb-4">📦</div>
            <h3 class="text-lg font-bold text-gray-700">
              Belum ada produk
            </h3>
            <p class="text-sm text-gray-400 mt-2">
              Produk akan segera tersedia.
            </p>
          </div>

          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3 md:gap-6">
            <template v-if="loading">
              <ProductCardSkeleton v-for="i in 4" :key="i" />
            </template>
            <ProductCard v-else v-for="item in products" :key="item.id" :product="item" />
          </div>
        </section>

        <div class="flex justify-center mt-12 px-4">
          <router-link :to="{ name: 'Catalog' }">
            <button
              class="w-full md:w-auto bg-blue-800 text-white px-12 py-4 rounded-xl font-bold shadow-lg active:scale-95 transition-transform hover:cursor-pointer">
              LIHAT PRODUK LAINNYA
            </button>
          </router-link>
        </div>
      </main>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
button {
  position: relative;
  overflow: hidden;
}

button:after {
  content: '';
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transform: rotate(45deg);
  transition: 0.7s;
}

button:hover:after {
  left: 100%;
  top: 100%;
}
</style>