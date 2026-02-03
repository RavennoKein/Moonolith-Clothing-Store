<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Navbar from '../../components/Navbar.vue';
import ProductCard from '../../components/ProductCard.vue';
import Footer from '../../components/Footer.vue';
import {
  ShoppingBag, ChevronRight, Star, Truck, ShieldCheck,
  RotateCcw, Flame, Minus, Plus, Share2, Heart, Info, CheckCircle2, Ruler, ArrowLeftRight, ArrowUpDown, Loader2
} from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';
import { useRoute, useRouter } from 'vue-router';
import { useCart } from '@/composables/useCart';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const product = ref(null);
const relatedProducts = ref([]);
const isFavorited = ref(false);
const isFavLoading = ref(false);
const isAddingToCart = ref(false);
const { fetchCartCount } = useCart();
const isBuyingNow = ref(false);

const fetchProduct = async () => {
  loading.value = true;
  try {
    const res = await api.get(`/public/items/${route.params.id}`);
    const data = res.data.data;

    product.value = data;

    relatedProducts.value = data.related_products || [];

  } catch (error) {
    console.error("Gagal memuat produk", error);
  } finally {
    loading.value = false;
  }
};

watch(() => route.params.id, (newId) => {
  if (newId) fetchProduct();
});

onMounted(fetchProduct);

const addToCart = async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    Swal.fire({
      icon: 'warning',
      title: 'Belum Login',
      text: 'Silahkan login untuk belanja',
      confirmButtonText: 'Login',
      showCancelButton: true,
      confirmButtonColor: '#0f172a'
    }).then((result) => {
      if (result.isConfirmed) {
        router.push({ name: 'Login' });
      }
    });
    return;
  }

  if (!selectedSize.value || !selectedColor.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Pilih Varian',
      text: 'Mohon pilih ukuran dan warna terlebih dahulu.',
      confirmButtonColor: '#0f172a'
    });
    return;
  }

  const selectedVariant = availableColors.value.find(c => c.color === selectedColor.value);

  if (!selectedVariant || !selectedVariant.id) {
    console.error("Variant ID tidak ditemukan pada data:", selectedVariant);
    Swal.fire('Error', 'Data varian produk tidak valid. Refresh halaman.', 'error');
    return;
  }

  isAddingToCart.value = true;

  try {
    const response = await api.post('/user/cart/add', {
      variant_id: selectedVariant.id,
      quantity: quantity.value
    });

    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
      didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
      }
    });

    Toast.fire({
      icon: 'success',
      title: 'Berhasil ditambahkan ke keranjang'
    });

    await fetchCartCount();

  } catch (error) {
    const errorMsg = error.response?.data?.message || 'Gagal menambahkan ke keranjang';
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: errorMsg,
      confirmButtonColor: '#0f172a'
    });
  } finally {
    isAddingToCart.value = false;
  }
};

const checkFavoriteStatus = async () => {
  const token = localStorage.getItem('token');
  if (!token || !product.value) return;

  try {
    const res = await api.get(`/user/favorites/check/${product.value.id}`);
    isFavorited.value = res.data.is_favorited;
  } catch (error) {
    console.error("Gagal cek status favorite", error);
  }
};

watch(product, (newVal) => {
  if (newVal) checkFavoriteStatus();
});

const toggleFavorite = async () => {
  const token = localStorage.getItem('token');

  if (!token) {
    Swal.fire({
      icon: 'warning',
      title: 'Belum Login',
      text: 'Silahkan login untuk menyimpan produk ke favorit',
      confirmButtonText: 'Login',
      showCancelButton: true,
      confirmButtonColor: '#0f172a'
    }).then((result) => {
      if (result.isConfirmed) {
        router.push({ name: 'Login' });
      }
    });
    return;
  }

  isFavLoading.value = true;
  try {
    const res = await api.post('/user/favorites/toggle', {
      item_id: product.value.id
    });

    isFavorited.value = res.data.is_favorited;

    const Toast = Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true
    });
    Toast.fire({
      icon: isFavorited.value ? 'success' : 'info',
      title: res.data.message
    });

  } catch (error) {
    Swal.fire('Error', 'Gagal memproses permintaan', 'error');
  } finally {
    isFavLoading.value = false;
  }
};

const displayPrice = computed(() => {
  if (product.value?.flash_sale) {
    return product.value.flash_sale.discount_price;
  }
  return product.value?.price || 0;
});

const hasFlashSale = computed(() => {
  return !!product.value?.flash_sale;
});
const selectedSize = ref(null);
const selectedColor = ref(null);

const availableColors = computed(() => {
  if (!product.value || !selectedSize.value) return [];
  const sizeObj = product.value.sizes.find(s => s.size === selectedSize.value);
  return sizeObj ? sizeObj.colors : [];
});

const isSizeSoldOut = (sizeName) => {
  const sizeObj = product.value?.sizes.find(s => s.size === sizeName);
  return !sizeObj || sizeObj.total_stock <= 0;
};

const isColorSoldOut = (colorName) => {
  const colorObj = availableColors.value.find(c => c.color === colorName);
  return !colorObj || colorObj.stock <= 0;
};

const currentSelectedColorStock = computed(() => {
  if (!selectedColor.value) return 0;
  const colorObj = availableColors.value.find(c => c.color === selectedColor.value);
  return colorObj ? colorObj.stock : 0;
});

watch(selectedColor, () => {
  if (quantity.value > currentSelectedColorStock.value) {
    quantity.value = currentSelectedColorStock.value > 0 ? 1 : 0;
  }
});

const quantity = ref(1);

const formatPrice = (value) =>
  new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(value);

const productFeatures = computed(() => {
  if (!product.value) return [];

  const thicknessDescriptions = {
    'tipis': 'Sangat ringan dan adem, pilihan terbaik untuk cuaca panas atau sebagai inner layer.',
    'sedang': 'Ketebalan yang pas untuk penggunaan sehari-hari, seimbang antara kenyamanan dan daya tahan.',
    'tebal': 'Bahan mantap dan kokoh, memberikan proteksi ekstra dan tampilan yang lebih bervolume.'
  };

  const currentThickness = product.value.tingkat_ketebalan?.toLowerCase() || '';
  const thicknessDesc = thicknessDescriptions[currentThickness] || 'Pas untuk cuaca tropis, tidak menerawang namun tetap sejuk.';

  return [
    {
      label: `Bahan ${product.value.bahan || 'Pilihan'}`,
      desc: "Kualitas premium yang dipilih dengan sangat hati-hati tanpa mengurangi daya tahan."
    },
    {
      label: `Ketebalan: ${product.value.tingkat_ketebalan || 'Standar'}`,
      desc: thicknessDesc
    }
  ];
});

const showSizeGuide = ref(false);

const sizeGuideData = {
  'Pria': [
    { size: 'S', lebar: 48, panjang: 68, rekomen_bb: '50-60kg' },
    { size: 'M', lebar: 50, panjang: 70, rekomen_bb: '60-70kg' },
    { size: 'L', lebar: 52, panjang: 72, rekomen_bb: '70-80kg' },
    { size: 'XL', lebar: 54, panjang: 74, rekomen_bb: '80-90kg' },
  ],
  'Wanita': [
    { size: 'S', lebar: 42, panjang: 60, rekomen_bb: '40-48kg' },
    { size: 'M', lebar: 44, panjang: 62, rekomen_bb: '48-55kg' },
    { size: 'L', lebar: 46, panjang: 64, rekomen_bb: '55-62kg' },
    { size: 'XL', lebar: 48, panjang: 66, rekomen_bb: '62-70kg' },
  ],
  'Anak': [
    { size: 'S', lebar: 32, panjang: 42, rekomen_bb: '10-15kg' },
    { size: 'M', lebar: 35, panjang: 45, rekomen_bb: '15-20kg' },
    { size: 'L', lebar: 38, panjang: 48, rekomen_bb: '20-25kg' },
    { size: 'XL', lebar: 41, panjang: 51, rekomen_bb: '25-30kg' },
  ]
};

const currentSizeGuide = computed(() => {
  if (!product.value) return [];
  const genderKey = product.value.gender || 'Pria';

  if (genderKey.toLowerCase().includes('women')) return sizeGuideData['Wanita'];
  if (genderKey.toLowerCase().includes('kids')) return sizeGuideData['Anak'];

  return sizeGuideData['Pria'];
});

const buyNow = async () => {
  const token = localStorage.getItem('token');
  if (!token) {
    Swal.fire({
      icon: 'warning',
      title: 'Belum Login',
      text: 'Silahkan login untuk belanja',
      confirmButtonText: 'Login',
      showCancelButton: true,
      confirmButtonColor: '#0f172a'
    }).then((result) => {
      if (result.isConfirmed) router.push({ name: 'Login' });
    });
    return;
  }

  if (!selectedSize.value || !selectedColor.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Pilih Varian',
      text: 'Mohon pilih ukuran dan warna terlebih dahulu.',
      confirmButtonColor: '#0f172a'
    });
    return;
  }

  const selectedVariant = availableColors.value.find(c => c.color === selectedColor.value);
  if (!selectedVariant) return;

  isBuyingNow.value = true;

  try {

    const directBuyItem = {
      variant_id: selectedVariant.id,
      quantity: quantity.value,
      product_name: product.value.name,
      product_image: product.value.image_url,
      price: displayPrice.value,
      size: selectedSize.value,
      color: selectedColor.value,
      stock: selectedVariant.stock,
      original_price: product.value.price,
      is_flash_sale: hasFlashSale.value,
      quantity: quantity.value
    };

    localStorage.setItem('direct_buy_data', JSON.stringify([directBuyItem]));

    await new Promise(resolve => setTimeout(resolve, 500));

    router.push('/checkout');

  } catch (error) {
    console.error(error);
    Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Gagal memproses pembelian langsung',
      confirmButtonColor: '#0f172a'
    });
  } finally {
    isBuyingNow.value = false;
  }
};
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-slate-900">
    <Navbar />

    <div class="bg-[#F8FAFC] border-b border-slate-200/60">
      <nav
        class="max-w-7xl mx-auto px-6 py-4 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">
        <router-link to="/" class="hover:text-slate-900 transition-colors">Store</router-link>
        <ChevronRight class="w-3 h-3 text-slate-300" />
        <span class="text-indigo-600 truncate">{{ product?.name }}</span>
      </nav>
    </div>

    <main v-if="!loading && product" class="max-w-7xl mx-auto px-6 py-8 md:py-16">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-20">

        <div class="lg:col-span-7">
          <div class="sticky top-28 space-y-8">
            <div
              class="relative group aspect-4/5 rounded-[3rem] overflow-hidden bg-white shadow-[0_30px_60px_-15px_rgba(0,0,0,0.1)] border border-slate-100">
              <img v-if="product" :src="product.image_url" :class="[
                'w-full h-full object-cover transition-all duration-1000 group-hover:scale-105',
                product.total_stock === 0 ? 'grayscale contrast-75 opacity-80' : ''
              ]" />

              <div v-if="product.total_stock === 0"
                class="absolute inset-0 bg-slate-900/10 backdrop-blur-[2px] flex items-center justify-center">
                <span
                  class="bg-white/90 px-6 py-3 rounded-2xl text-xs font-black uppercase tracking-[0.3em] text-slate-900 shadow-xl">
                  Currently Unavailable
                </span>
              </div>

              <div class="absolute top-8 right-8">
                <button @click="toggleFavorite" :disabled="isFavLoading"
                  class="w-14 h-14 bg-white/80 backdrop-blur-xl rounded-2xl flex items-center justify-center border border-slate-200/50 hover:bg-red-50 transition-all group shadow-sm disabled:opacity-70 disabled:cursor-not-allowed">

                  <Heart :class="[
                    'w-6 h-6 transition-all duration-300',
                    isFavorited ? 'fill-red-500 text-red-500' : 'text-slate-400 group-hover:text-red-500'
                  ]" />
                </button>
              </div>

              <div v-if="hasFlashSale" class="flash-sale-badge">
                🔥 Flash Sale -{{ product.flash_sale.discount_percentage }}%
              </div>
            </div>
          </div>
        </div>

        <div class="lg:col-span-5">
          <div class="flex flex-col space-y-10">
            <section class="space-y-4">
              <div class="flex items-center gap-3">
                <div v-if="product.total_stock > 0" class="flex items-center gap-2">
                  <span class="relative flex h-2 w-2">
                    <span
                      class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                  </span>
                  <span class="text-[10px] font-black uppercase tracking-widest text-emerald-600">
                    In Stock — {{ product.total_stock }} Unit Tersedia
                  </span>
                </div>

                <div v-else class="flex items-center gap-2">
                  <span class="h-2 w-2 rounded-full bg-red-500"></span>
                  <span class="text-[10px] font-black uppercase tracking-widest text-red-500">
                    Stok Habis Terjual
                  </span>
                </div>

                <div class="h-4 w-px bg-slate-200 mx-1"></div>

                <div class="flex text-amber-400 gap-0.5">
                  <Star v-for="s in 5" :key="s" class="w-3 h-3 fill-current" />
                </div>
              </div>

              <h1
                class="text-4xl md:text-6xl font-black italic uppercase tracking-tighter leading-[0.9] text-slate-900">
                {{ product.name }}
              </h1>
            </section>

            <section class="space-y-6">
              <div class="prose prose-sm text-slate-500 leading-relaxed font-medium">
                <p>{{ product.description || 'Tidak ada deskripsi tersedia untuk produk ini.' }}</p>
              </div>

              <div class="border rounded-2xl overflow-hidden border-slate-200/60">
                <button @click="showSizeGuide = !showSizeGuide"
                  class="w-full flex items-center justify-between p-4 bg-slate-50 hover:bg-slate-100 transition-colors text-left">
                  <div class="flex items-center gap-2">
                    <Ruler class="w-4 h-4 text-slate-900" />
                    <span class="text-xs font-black uppercase tracking-widest text-slate-900">Panduan Ukuran & Cara
                      Ukur</span>
                  </div>
                  <ChevronRight
                    :class="['w-4 h-4 text-slate-400 transition-transform duration-300', showSizeGuide ? 'rotate-90' : '']" />
                </button>

                <div v-show="showSizeGuide" class="bg-white transition-all">

                  <div class="p-4 overflow-x-auto">
                    <table class="w-full text-[11px] text-center">
                      <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider font-bold">
                          <th class="py-2 text-left pl-2">Size</th>
                          <th class="py-2">Lebar Dada</th>
                          <th class="py-2">Panjang</th>
                          <th class="py-2">Est. Berat</th>
                        </tr>
                      </thead>
                      <tbody class="text-slate-700 font-medium">
                        <tr v-for="(row, i) in currentSizeGuide" :key="i"
                          class="border-b border-slate-50 last:border-0 hover:bg-slate-50">
                          <td class="py-3 font-black text-slate-900 text-left pl-2">{{ row.size }}</td>
                          <td class="py-3">{{ row.lebar }} cm</td>
                          <td class="py-3">{{ row.panjang }} cm</td>
                          <td class="py-3 text-slate-500">{{ row.rekomen_bb }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>

                  <div class="bg-slate-50/50 p-5 border-t border-slate-100">
                    <h4 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Cara Pengukuran
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="flex gap-3 items-start p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
                        <div
                          class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0 text-indigo-600">
                          <ArrowLeftRight class="w-4 h-4" />
                        </div>
                        <div>
                          <span class="block text-[11px] font-black uppercase text-slate-900 mb-1">Lebar Dada</span>
                          <p class="text-[10px] text-slate-500 leading-relaxed">
                            Ukur mendatar dari ujung ketiak kiri ke ujung ketiak kanan (bukan melingkar) pada baju yang
                            diletakkan di lantai datar.
                          </p>
                        </div>
                      </div>

                      <div class="flex gap-3 items-start p-3 bg-white rounded-xl border border-slate-100 shadow-sm">
                        <div
                          class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0 text-indigo-600">
                          <ArrowUpDown class="w-4 h-4" />
                        </div>
                        <div>
                          <span class="block text-[11px] font-black uppercase text-slate-900 mb-1">Panjang Baju</span>
                          <p class="text-[10px] text-slate-500 leading-relaxed">
                            Ukur tegak lurus dari titik bahu tertinggi (dekat kerah) hingga ke ujung bagian bawah baju.
                          </p>
                        </div>
                      </div>
                    </div>

                    <p class="text-[10px] text-slate-400 mt-4 italic text-center">
                      *Disarankan mengukur baju yang biasa Anda pakai sebagai referensi. Toleransi selisih ukuran 1-2cm
                      wajar terjadi.
                    </p>
                  </div>
                </div>
              </div>
            </section>

            <div class="relative overflow-hidden bg-white p-8 rounded-[2.5rem] border border-slate-200/60 shadow-sm">
              <div class="relative z-10 flex flex-col gap-1">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Exclusive Price</span>
                <div class="flex items-baseline gap-4">
                  <h2 class="text-5xl font-black tracking-tighter text-slate-900">
                    {{ formatPrice(displayPrice) }}
                  </h2>
                  <span v-if="hasFlashSale" class="text-xl text-slate-300 line-through font-bold">
                    {{ formatPrice(product.price) }}
                  </span>
                </div>
              </div>
            </div>

            <section class="space-y-4">
              <div class="flex justify-between items-end">
                <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Pilih Ukuran</h3>
              </div>
              <div class="flex flex-wrap gap-3">
                <button v-for="size in ['S', 'M', 'L', 'XL']" :key="size"
                  @click="!isSizeSoldOut(size) && (selectedSize = size, selectedColor = null)"
                  :disabled="isSizeSoldOut(size)" :class="[
                    selectedSize === size ? 'bg-slate-900 text-white' : 'bg-white text-slate-400',
                    isSizeSoldOut(size) ? 'opacity-20 cursor-not-allowed' : 'hover:border-slate-900'
                  ]" class="w-14 h-14 rounded-2xl border flex items-center justify-center font-black">
                  {{ size }}
                </button>
              </div>
            </section>

            <section v-if="selectedSize" class="space-y-4 pt-4">
              <h3 class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Pilih Warna</h3>
              <div v-if="selectedSize" class="flex flex-wrap gap-3 mt-4">
                <button v-for="c in availableColors" :key="c.color"
                  @click="!isColorSoldOut(c.color) && (selectedColor = c.color)" :disabled="isColorSoldOut(c.color)"
                  :class="[
                    selectedColor === c.color ? 'bg-indigo-600 text-white' : 'bg-white text-slate-600',
                    isColorSoldOut(c.color) ? 'opacity-30 cursor-not-allowed bg-slate-100' : 'hover:border-slate-900'
                  ]" class="px-4 py-2 rounded-xl border text-[10px] font-black uppercase">
                  {{ c.color }} ({{ c.stock }})
                </button>
              </div>
            </section>

            <section class="pt-6 space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Jumlah</span>

                <div class="flex items-center bg-white border border-slate-200 rounded-xl p-1 h-12 w-32 shadow-sm">
                  <button @click="quantity > 1 ? quantity-- : null"
                    class="w-10 h-full flex items-center justify-center rounded-lg hover:bg-slate-50 active:scale-95 transition-all text-slate-600">
                    <Minus class="w-4 h-4" />
                  </button>
                  <span class="flex-1 text-center font-black text-slate-900 text-sm">{{ quantity }}</span>
                  <button @click="quantity < currentSelectedColorStock ? quantity++ : null"
                    :disabled="!selectedColor || quantity >= currentSelectedColorStock"
                    class="w-10 h-full flex items-center justify-center rounded-lg hover:bg-slate-50 disabled:opacity-30 active:scale-95 transition-all text-slate-600">
                    <Plus class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <div class="flex gap-3 w-full">
                <button @click="addToCart"
                  :disabled="!selectedSize || !selectedColor || isAddingToCart || quantity === 0"
                  class="flex-1 bg-white border border-slate-200 text-slate-900 h-14 rounded-xl font-bold hover:bg-slate-50 hover:border-slate-900 disabled:opacity-50 disabled:bg-slate-50 disabled:border-slate-100 transition-all uppercase tracking-wider text-[10px] flex flex-col sm:flex-row items-center justify-center gap-2 group">

                  <Loader2 v-if="isAddingToCart" class="w-4 h-4 animate-spin" />

                  <ShoppingBag v-else class="w-4 h-4 group-hover:-translate-y-0.5 transition-transform duration-300" />

                  <span class="text-center">{{ isAddingToCart ? 'Menambahkan...' : 'Keranjang' }}</span>
                </button>

                <button @click="buyNow"
                  :disabled="!selectedSize || !selectedColor || quantity === 0 || product.total_stock === 0 || isBuyingNow"
                  :class="[
                    'flex-1 h-14 rounded-xl font-bold flex items-center justify-center gap-2 transition-all duration-300 uppercase tracking-wider text-[10px] shadow-lg shadow-slate-200/50',
                    product.total_stock === 0
                      ? 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none'
                      : 'bg-slate-900 hover:bg-indigo-600 text-white'
                  ]">

                  <Loader2 v-if="isBuyingNow" class="w-4 h-4 animate-spin" />

                  <template v-else>
                    <span v-if="product.total_stock === 0">Habis</span>
                    <span v-else-if="!selectedSize">Pilih Size</span>
                    <span v-else-if="!selectedColor">Pilih Warna</span>
                    <span v-else>Beli Sekarang</span>

                    <ChevronRight v-if="selectedSize && selectedColor && product.total_stock > 0" class="w-3 h-3" />
                  </template>
                </button>
              </div>
            </section>
            <div class="grid grid-cols-1 gap-6 pt-10 border-t border-slate-100">
              <div v-for="(feat, idx) in productFeatures" :key="idx"
                class="flex items-start gap-4 group transition-all duration-500 transform">
                <div
                  class="w-10 h-10 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center group-hover:bg-indigo-600 group-hover:rotate-12 transition-all duration-300">
                  <CheckCircle2 class="w-5 h-5 text-slate-900 group-hover:text-white" />
                </div>
                <div class="flex flex-col">
                  <span
                    class="text-[11px] font-black uppercase tracking-widest text-slate-900 group-hover:text-indigo-600 transition-colors">{{
                      feat.label }}</span>
                  <p class="text-[11px] text-slate-500 font-medium leading-relaxed">{{ feat.desc }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <section class="mt-40 border-t border-slate-200/60 pt-20">
        <div class="flex flex-col items-center mb-16 space-y-4">
          <span class="text-slate-400 font-black text-[10px] uppercase tracking-[0.5em]">Collections</span>
          <h2 class="text-4xl md:text-5xl font-black text-slate-900 uppercase italic tracking-tighter">
            Lengkapi <span class="text-cyan-900">Gaya Anda</span>
          </h2>
          <div class="h-1.5 w-12 bg-slate-900 rounded-full"></div>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-8">
          <ProductCard v-for="item in relatedProducts" :key="item.id" :product="item" />
        </div>
      </section>
    </main>

    <div v-else-if="loading" class="flex justify-center items-center h-screen">
      <p>Memuat produk...</p>
    </div>

    <Footer />
  </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
  display: none;
}

.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

h1 {
  letter-spacing: -0.05em;
}

button {
  -webkit-tap-highlight-color: transparent;
}
</style>