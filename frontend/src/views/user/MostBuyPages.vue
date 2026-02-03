<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import Navbar from '../../components/Navbar.vue';
import ProductCard from '../../components/ProductCard.vue';
import Footer from '../../components/Footer.vue';
import ProductCardSkeleton from '@/components/skeletons/ProductCardSkeleton.vue';
import api from '@/services/api';
import { ChevronDown, ListFilter, Check, ChevronLeft, ChevronRight, Award, X, Flame } from 'lucide-vue-next';

const sizeOptions = ['S', 'M', 'L', 'XL'];

const colorOptions = [
  { value: 'white', label: 'Putih', hex: '#FFFFFF' },
  { value: 'black', label: 'Hitam', hex: '#000000' },
  { value: 'navy', label: 'Navy', hex: '#001F3F' },
  { value: 'gray', label: 'Abu-abu', hex: '#6B7280' },
  { value: 'red', label: 'Merah', hex: '#EF4444' },
  { value: 'maroon', label: 'Maroon', hex: '#800000' },
  { value: 'blue', label: 'Biru', hex: '#1E40AF' },
  { value: 'green', label: 'Hijau', hex: '#065F46' },
  { value: 'yellow', label: 'Kuning', hex: '#F59E0B' },
  { value: 'pink', label: 'Pink', hex: '#EC4899' },
  { value: 'purple', label: 'Ungu', hex: '#9333EA' },
  { value: 'cream', label: 'Cream', hex: '#F5F5DC' }
];

const statusOptions = [
  { value: 'aktif', label: 'Aktif' },
  { value: 'terbatas', label: 'Terbatas' }
];

const sortOptions = [
  { label: 'Terpopuler', value: 'popular' },
  { label: 'Harga: Termurah', value: 'price_low' },
  { label: 'Harga: Termahal', value: 'price_high' }
];

const rawProducts = ref([]);
const categoryOptions = ref([]);
const loading = ref(true);
const activeDropdown = ref(null);
const currentPage = ref(1);
const itemsPerPage = 10;
const flashSaleItems = ref([]);

const filters = reactive({
  status: null,
  category: null,
  size: null,
  colors: [],
  sortBy: 'popular'
});

const fetchFlashSaleData = async () => {
  try {
    const res = await api.get('/public/flash-sale');

    if (!res.data.data) {
      flashSaleItems.value = [];
      return;
    }

    const detail = await api.get(`/public/flash-sale/${res.data.data.id}`);

    flashSaleItems.value = detail.data.data.items.map(item => ({
      id: item.id,
      original_price: item.original_price,
      discount_price: item.discount_price,
      stock: item.flash_sale_stock,
      sold: item.sold_count,
      remaining: item.flash_sale_stock - item.sold_count
    }));

  } catch (error) {
    console.error("Gagal memuat flash sale:", error);
    flashSaleItems.value = [];
  }
};

const fetchData = async () => {
  try {
    loading.value = true;

    const [_, resProducts, resCategories] = await Promise.all([
      fetchFlashSaleData(),
      api.get('/public/most-buy'),
      api.get('/public/categories')
    ]);

    const allItems = resProducts.data.data;

    const processedItems = allItems.map(item => {
      const fsItem = flashSaleItems.value.find(fs => fs.id === item.id);

      if (fsItem) {
        return {
          ...item,
          price: fsItem.original_price,
          isFlashSaleSoldOut: fsItem.remaining <= 0,
          isGlobalSoldOut: item.total_stock <= 0,
          flash_sale: {
            discount_price: fsItem.discount_price,
            stock: fsItem.stock,
            sold: fsItem.sold,
            remaining: fsItem.remaining
          }
        };
      } else {
        return {
          ...item,
          flash_sale: null,
          isFlashSaleSoldOut: false,
          isGlobalSoldOut: item.total_stock <= 0
        };
      }
    });

    rawProducts.value = processedItems.filter(item => item.status_produk !== 'non_aktif');

    categoryOptions.value = resCategories.data.data.map(cat => ({
      value: cat.id,
      label: cat.name
    }));

  } catch (error) {
    console.error("Gagal memuat data best seller:", error);
  } finally {
    loading.value = false;
  }
};

const toggleColorFilter = (value) => {
  const index = filters.colors.indexOf(value);
  if (index > -1) filters.colors.splice(index, 1);
  else filters.colors.push(value);
  currentPage.value = 1;
};

const selectSingleFilter = (type, value) => {
  filters[type] = filters[type] === value ? null : value;
  currentPage.value = 1;
  activeDropdown.value = null;
};

const filteredProducts = computed(() => {
  let result = [...rawProducts.value];

  if (filters.status) {
    result = result.filter(p => p.status_produk === filters.status);
  }

  if (filters.category) {
    result = result.filter(p => p.category && p.category.id === filters.category);
  }

  if (filters.size) {
    result = result.filter(p => p.variants?.some(v => v.size === filters.size && v.stock > 0));
  }

  if (filters.colors.length > 0) {
    result = result.filter(p =>
      p.variants?.some(v => filters.colors.includes(v.color.toLowerCase()))
    );
  }


  if (filters.sortBy === 'price_low') {
    result.sort((a, b) => {
      const priceA = a.flash_sale ? a.flash_sale.discount_price : a.price;
      const priceB = b.flash_sale ? b.flash_sale.discount_price : b.price;
      return priceA - priceB;
    });
  } else if (filters.sortBy === 'price_high') {
    result.sort((a, b) => {
      const priceA = a.flash_sale ? a.flash_sale.discount_price : a.price;
      const priceB = b.flash_sale ? b.flash_sale.discount_price : b.price;
      return priceB - priceA;
    });
  } else {
    result.sort((a, b) => b.total_sold - a.total_sold);
  }

  return result;
});

const paginatedProducts = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage;
  return filteredProducts.value.slice(start, start + itemsPerPage);
});

const totalPages = computed(() => Math.ceil(filteredProducts.value.length / itemsPerPage));

const resetAllFilters = () => {
  Object.assign(filters, { status: null, category: null, size: null, colors: [], sortBy: 'popular' });
  currentPage.value = 1;
  activeDropdown.value = null;
};

const isFilterActive = computed(() => filters.status !== null || filters.category !== null || filters.size !== null || filters.colors.length > 0 || filters.sortBy !== 'popular');

const setPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page;
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
};

onMounted(fetchData);
</script>

<template>
  <div class="category-page bg-[#fffcf5] min-h-screen font-sans text-slate-900" @click="activeDropdown = null">
    <Navbar />

    <div class="w-full bg-[#0f1117] pt-12 pb-24 px-4 relative overflow-hidden">
      <div class="absolute inset-0 opacity-10 pattern-grid-lg pointer-events-none"></div>
      <div class="absolute -top-24 -right-24 w-96 h-96 bg-amber-600/20 rounded-full blur-[120px]"></div>
      <div class="absolute -bottom-24 -left-24 w-72 h-72 bg-orange-500/10 rounded-full blur-[100px]"></div>

      <div class="max-w-7xl mx-auto w-full relative z-10">
        <nav class="flex text-amber-400/80 text-xs mb-4 uppercase font-bold tracking-[0.2em]">
          <router-link to="/" class="hover:text-white transition-colors">Home</router-link>
          <span class="mx-2 text-slate-700">/</span>
          <span class="text-white">Best Sellers</span>
        </nav>
        <div class="flex items-center gap-3 mb-2">
          <Flame class="w-6 h-6 md:w-8 md:h-8 text-orange-500 animate-pulse" />
          <span class="text-orange-500 font-black uppercase tracking-[0.3em] text-xs md:text-sm">Hot Collection</span>
        </div>
        <h1 class="text-4xl md:text-7xl font-black text-white uppercase italic tracking-tighter leading-tight">
          Best <span class="text-transparent bg-clip-text bg-linear-to-r from-amber-400 to-orange-300">Sellers</span>
        </h1>
        <p class="text-slate-400 mt-5 max-w-xl text-sm md:text-base leading-relaxed font-medium">
          Produk paling dicari musim ini. Dapatkan koleksi terlaris yang menggabungkan gaya ikonik dan kualitas tak
          tertandingi sebelum kehabisan.
        </p>
        <div class="w-24 h-1.5 bg-linear-to-r from-amber-600 to-transparent mt-8 rounded-full"></div>
      </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 -mt-12 pb-20 relative z-20">
      <section
        class="bg-white p-4 md:p-6 rounded-4xl shadow-2xl shadow-amber-900/5 mb-12 border border-white flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">

          <div class="relative">
            <button @click.stop="activeDropdown = activeDropdown === 'status' ? null : 'status'"
              class="px-5 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 transition-all border border-slate-100 hover:border-amber-500 hover:bg-amber-50"
              :class="{ 'bg-amber-900 border-amber-900 text-white shadow-lg shadow-amber-900/20': filters.status }">
              {{statusOptions.find(s => s.value === filters.status)?.label || 'Status'}}
              <ChevronDown class="w-4 h-4" :class="{ 'rotate-180': activeDropdown === 'status' }" />
            </button>
            <div v-if="activeDropdown === 'status'"
              class="absolute mt-3 w-48 bg-white border border-slate-50 shadow-2xl rounded-2xl p-2 z-50">
              <div v-for="st in statusOptions" :key="st.value" @click="selectSingleFilter('status', st.value)"
                class="p-3 hover:bg-amber-50 rounded-xl cursor-pointer flex justify-between items-center text-xs font-bold transition-colors">
                {{ st.label }}
                <Check v-if="filters.status === st.value" class="w-4 h-4 text-amber-600" />
              </div>
            </div>
          </div>

          <div class="relative">
            <button @click.stop="activeDropdown = activeDropdown === 'cat' ? null : 'cat'"
              class="px-5 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 transition-all border border-slate-100 hover:border-amber-500 hover:bg-amber-50"
              :class="{ 'bg-amber-900 border-amber-900 text-white shadow-lg': filters.category }">
              {{categoryOptions.find(c => c.value === filters.category)?.label || 'Kategori'}}
              <ChevronDown class="w-4 h-4" :class="{ 'rotate-180': activeDropdown === 'cat' }" />
            </button>
            <div v-if="activeDropdown === 'cat'"
              class="absolute mt-3 w-52 bg-white border border-slate-50 shadow-2xl rounded-2xl p-2 z-50 max-h-64 overflow-y-auto scrollbar-custom">

              <div v-if="categoryOptions.length === 0" class="p-3 text-xs text-slate-400 text-center">
                Memuat kategori...
              </div>

              <div v-else v-for="cat in categoryOptions" :key="cat.value"
                @click="selectSingleFilter('category', cat.value)"
                class="p-3 hover:bg-amber-50 rounded-xl cursor-pointer flex justify-between items-center text-xs font-bold transition-colors">
                {{ cat.label }}
                <Check v-if="filters.category === cat.value" class="w-4 h-4 text-amber-600" />
              </div>
            </div>
          </div>

          <div class="relative">
            <button @click.stop="activeDropdown = activeDropdown === 'size' ? null : 'size'"
              class="px-5 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 transition-all border border-slate-100 hover:border-amber-500 hover:bg-amber-50"
              :class="{ 'bg-amber-900 border-amber-900 text-white shadow-lg': filters.size }">
              Size: {{ filters.size || 'Semua' }}
              <ChevronDown class="w-4 h-4" :class="{ 'rotate-180': activeDropdown === 'size' }" />
            </button>
            <div v-if="activeDropdown === 'size'"
              class="absolute mt-3 w-32 bg-white border border-slate-50 shadow-2xl rounded-2xl p-2 z-50">
              <div v-for="size in sizeOptions" :key="size" @click="selectSingleFilter('size', size)"
                class="p-3 hover:bg-amber-50 rounded-xl cursor-pointer flex justify-between items-center text-xs font-bold transition-colors">
                {{ size }}
                <Check v-if="filters.size === size" class="w-4 h-4 text-amber-600" />
              </div>
            </div>
          </div>

          <div class="relative">
            <button @click.stop="activeDropdown = activeDropdown === 'color' ? null : 'color'"
              class="px-5 py-3 rounded-2xl text-xs font-bold flex items-center gap-2 transition-all border border-slate-100 hover:border-amber-500 hover:bg-amber-50"
              :class="{ 'bg-amber-900 border-amber-900 text-white shadow-lg': filters.colors.length > 0 }">
              Warna <span v-if="filters.colors.length" class="bg-amber-400 text-amber-950 px-1.5 rounded-md ml-1">{{
                filters.colors.length }}</span>
              <ChevronDown class="w-4 h-4" :class="{ 'rotate-180': activeDropdown === 'color' }" />
            </button>
            <div v-if="activeDropdown === 'color'" @click.stop
              class="absolute mt-3 w-60 bg-white border border-slate-50 shadow-2xl rounded-2xl p-3 z-50 max-h-80 overflow-y-auto scrollbar-custom">
              <label v-for="color in colorOptions" :key="color.value"
                class="flex items-center gap-3 p-2.5 hover:bg-amber-50 rounded-xl cursor-pointer group">
                <input type="checkbox" :value="color.value" :checked="filters.colors.includes(color.value)"
                  @change="toggleColorFilter(color.value)" class="w-4 h-4 accent-amber-600">
                <span class="w-5 h-5 rounded-full border border-slate-200"
                  :style="{ backgroundColor: color.hex }"></span>
                <span class="text-xs font-bold">{{ color.label }}</span>
              </label>
            </div>
          </div>

          <button v-if="isFilterActive" @click="resetAllFilters"
            class="flex items-center gap-2 px-5 py-3 text-xs font-black text-red-500 hover:bg-red-50 rounded-2xl transition-all">
            <X class="w-4 h-4" /> RESET
          </button>
        </div>

        <div class="relative">
          <button @click.stop="activeDropdown = activeDropdown === 'sort' ? null : 'sort'"
            class="flex items-center gap-3 px-6 py-3.5 bg-slate-900 text-white rounded-2xl text-xs font-black hover:bg-amber-800 transition-all shadow-xl shadow-slate-900/20 active:scale-95">
            <ListFilter class="w-4 h-4 text-amber-400" />
            {{sortOptions.find(s => s.value === filters.sortBy).label}}
          </button>
          <div v-if="activeDropdown === 'sort'"
            class="absolute right-0 mt-3 w-52 bg-white border border-slate-50 shadow-2xl rounded-2xl p-2 z-50">
            <div v-for="opt in sortOptions" :key="opt.value" @click="selectSingleFilter('sortBy', opt.value)"
              class="p-3 hover:bg-amber-50 rounded-xl cursor-pointer text-xs font-bold flex justify-between items-center transition-colors">
              {{ opt.label }}
              <Check v-if="filters.sortBy === opt.value" class="w-4 h-4 text-amber-600" />
            </div>
          </div>
        </div>
      </section>

      <section>
        <div v-if="loading" class="grid grid-cols-2 md:grid-cols-5 gap-6 md:gap-8">
          <ProductCardSkeleton v-for="i in 10" :key="i" />
        </div>
        <div v-else-if="paginatedProducts.length > 0" class="grid grid-cols-2 md:grid-cols-5 gap-6 md:gap-8">
          <ProductCard v-for="product in paginatedProducts" :key="product.id" :product="product" />
        </div>

        <div v-else class="py-32 text-center bg-white rounded-[3rem] border border-amber-50 shadow-sm">
          <div class="w-24 h-24 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-6">
            <Award class="w-10 h-10 text-amber-200" />
          </div>
          <h3 class="text-2xl font-black text-slate-800 uppercase italic tracking-tight">Belum Ada Juara</h3>
          <p class="text-slate-400 mt-2 text-sm">Produk dengan filter ini belum tersedia dalam daftar terlaris kami.</p>
        </div>

        <div v-if="totalPages > 1" class="flex justify-center items-center gap-4 mt-20">
          <button @click="setPage(currentPage - 1)" :disabled="currentPage === 1"
            class="w-14 h-14 flex items-center justify-center border border-slate-200 rounded-2xl hover:bg-amber-900 hover:text-white disabled:opacity-20 transition-all">
            <ChevronLeft class="w-6 h-6" />
          </button>
          <div class="flex items-center gap-2">
            <button v-for="page in totalPages" :key="page" @click="setPage(page)"
              class="w-14 h-14 rounded-2xl border text-sm font-black transition-all"
              :class="currentPage === page ? 'bg-amber-900 text-white border-amber-900 shadow-xl shadow-amber-900/30 -translate-y-1' : 'bg-white hover:bg-amber-50 text-slate-400 border-slate-100'">
              {{ page }}
            </button>
          </div>
          <button @click="setPage(currentPage + 1)" :disabled="currentPage === totalPages"
            class="w-14 h-14 flex items-center justify-center border border-slate-200 rounded-2xl hover:bg-amber-900 hover:text-white disabled:opacity-20 transition-all">
            <ChevronRight class="w-6 h-6" />
          </button>
        </div>
      </section>
    </main>
    <Footer />
  </div>
</template>

<style scoped>
.pattern-grid-lg {
  background-image: radial-gradient(circle, #f59e0b 0.8px, transparent 0.8px);
  background-size: 40px 40px;
}

.absolute {
  animation: slideInUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.scrollbar-custom::-webkit-scrollbar {
  width: 6px;
}

.scrollbar-custom::-webkit-scrollbar-track {
  background: transparent;
}

.scrollbar-custom::-webkit-scrollbar-thumb {
  background: #fef3c7;
  border-radius: 10px;
}

.scrollbar-custom::-webkit-scrollbar-thumb:hover {
  background: #fde68a;
}

section:hover {
  border-color: rgba(245, 158, 11, 0.1);
}
</style>