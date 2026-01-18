<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '@/components/Navbar.vue';
import Footer from '@/components/Footer.vue';
import ProductCard from '@/components/ProductCard.vue';
import api from '@/services/api';
import { Trash2, Minus, Plus, ShoppingBag, ArrowRight, Loader2, AlertCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { useCart } from '@/composables/useCart';

const router = useRouter();
const cartData = ref(null);
const recommendations = ref([]);
const loading = ref(true);
const processingId = ref(null);
const isCheckingOut = ref(false);
const { fetchCartCount } = useCart();

const formatRupiah = (number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(number);
};


const fetchCart = async () => {
  try {
    const response = await api.get('/user/cart');
    cartData.value = response.data.data;
  } catch (error) {
    console.error("Gagal memuat keranjang:", error);
  }
};

const fetchRecommendations = async () => {
  try {
    const response = await api.get('/public/items');
    recommendations.value = response.data.data
      .sort(() => 0.5 - Math.random())
      .slice(0, 4);
  } catch (error) {
    console.error("Gagal memuat rekomendasi:", error);
  } finally {
    loading.value = false;
  }
};

const updateQuantity = async (itemId, currentQty, change, maxStock) => {
  const newQty = currentQty + change;

  if (newQty < 1) return;
  if (newQty > maxStock) {
    Swal.fire('Stok Terbatas', 'Jumlah melebihi stok yang tersedia', 'warning');
    return;
  }

  processingId.value = itemId;

  try {
    await api.put(`/user/cart/item/${itemId}`, {
      quantity: newQty
    });

    await fetchCart();
  } catch (error) {
    Swal.fire('Gagal', error.response?.data?.message || 'Gagal mengupdate keranjang', 'error');
  } finally {
    processingId.value = null;
  }
};

const removeItem = async (itemId) => {
  const result = await Swal.fire({
    title: 'Hapus item?',
    text: "Item ini akan dihapus dari keranjangmu.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Ya, hapus!',
    cancelButtonText: 'Batal'
  });

  if (result.isConfirmed) {
    processingId.value = itemId;
    try {
      await api.delete(`/user/cart/item/${itemId}`);
      await fetchCart();
      Swal.fire('Terhapus!', 'Item telah dihapus.', 'success');
      await fetchCartCount();
    } catch (error) {
      Swal.fire('Error', 'Gagal menghapus item', 'error');
    } finally {
      processingId.value = null;
    }
  }
};

const handleCheckout = async () => {
  isCheckingOut.value = true;
  try {
    await api.post('/user/cart/validate');

    router.push('/checkout');
  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Tidak dapat memproses',
      text: error.response?.data?.message || 'Ada perubahan stok pada item di keranjangmu.'
    });
    await fetchCart();
  } finally {
    isCheckingOut.value = false;
  }
};

onMounted(() => {
  fetchCart();
  fetchRecommendations();
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 font-sans text-slate-800">
    <Navbar />

    <div class="bg-white border-b border-slate-200 pt-8 pb-6 px-4 mb-8">
      <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl md:text-3xl font-bold text-slate-900">Keranjang Belanja</h1>
        <p class="text-slate-500 text-sm mt-1">
          {{ cartData?.items?.length || 0 }} Produk di Keranjang
        </p>
      </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 pb-24">
      <div v-if="loading" class="flex justify-center py-20">
        <Loader2 class="w-10 h-10 animate-spin text-blue-600" />
      </div>

      <div v-else-if="!cartData || cartData.items.length === 0"
        class="flex flex-col items-center justify-center py-16 bg-white rounded-3xl shadow-sm border border-slate-100 min-h-125">
        <div class="relative mb-6">
          <div class="absolute inset-0 bg-blue-100 rounded-full blur-2xl opacity-50"></div>
          <ShoppingBag class="w-32 h-32 text-slate-200 relative z-10" />
        </div>

        <h2 class="text-2xl font-bold text-slate-800 mb-2">Opps!</h2>
        <p class="text-slate-500 mb-8 text-center max-w-xs">
          Keranjang masih kosong nih, yuk belanja sekarang!
        </p>
        <router-link to="/catalog"
          class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-3 rounded-xl font-semibold transition-all shadow-lg shadow-blue-700/30 flex items-center gap-2">
          Belanja Sekarang
          <ArrowRight class="w-4 h-4" />
        </router-link>
      </div>

      <div v-else class="flex flex-col lg:flex-row gap-8">

        <div class="flex-1 space-y-4">
          <div v-for="item in cartData.items" :key="item.cart_item_id"
            class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 flex gap-4 transition-all hover:border-blue-100 group relative overflow-hidden">

            <div v-if="processingId === item.cart_item_id"
              class="absolute inset-0 bg-white/60 z-10 flex items-center justify-center backdrop-blur-[1px]">
              <Loader2 class="w-6 h-6 animate-spin text-blue-600" />
            </div>

            <div class="w-24 h-32 md:w-32 md:h-40 shrink-0 bg-slate-100 rounded-xl overflow-hidden relative">
              <img :src="item.image || '/placeholder-image.jpg'" :alt="item.name" class="w-full h-full object-cover">
              <div v-if="item.is_flashsale"
                class="absolute top-0 left-0 bg-red-600 text-white text-[10px] font-bold px-2 py-1 rounded-br-lg">
                FLASH SALE
              </div>
            </div>

            <div class="flex-1 flex flex-col justify-between py-1">
              <div>
                <div class="flex justify-between items-start">
                  <h3 class="font-bold text-slate-900 line-clamp-2 md:text-lg">{{ item.name }}</h3>
                  <button @click="removeItem(item.cart_item_id)"
                    class="text-slate-300 hover:text-red-500 transition-colors p-1">
                    <Trash2 class="w-5 h-5" />
                  </button>
                </div>

                <div
                  class="flex items-center gap-2 mt-2 text-xs font-medium text-slate-500 bg-slate-50 w-fit px-2 py-1 rounded-md border border-slate-100">
                  <span>{{ item.size }}</span>
                  <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                  <span class="flex items-center gap-1">
                    <span class="w-3 h-3 rounded-full border border-slate-200 shadow-sm"
                      :style="{ backgroundColor: item.color }"></span>
                    {{ item.color }}
                  </span>
                </div>

                <div v-if="item.stock < 5" class="flex items-center gap-1 text-orange-500 text-xs mt-2 font-medium">
                  <AlertCircle class="w-3 h-3" /> Sisa stok: {{ item.stock }}
                </div>
              </div>

              <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mt-4">
                <div>
                  <div v-if="item.is_flashsale" class="flex flex-col">
                    <span class="text-xs text-slate-400 line-through">{{ formatRupiah(item.price) }}</span>
                    <span class="text-lg font-bold text-red-600">{{ formatRupiah(item.flash_price) }}</span>
                  </div>
                  <div v-else>
                    <span class="text-lg font-bold text-blue-900">{{ formatRupiah(item.price) }}</span>
                  </div>
                </div>

                <div class="flex items-center bg-slate-50 rounded-lg border border-slate-200 overflow-hidden w-fit">
                  <button @click="updateQuantity(item.cart_item_id, item.quantity, -1, item.stock)"
                    :disabled="item.quantity <= 1 || processingId === item.cart_item_id"
                    class="p-2 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent transition-colors">
                    <Minus class="w-4 h-4" />
                  </button>
                  <input type="text" :value="item.quantity" readonly
                    class="w-12 text-center bg-transparent text-sm font-bold border-none focus:ring-0 p-0 text-slate-900">
                  <button @click="updateQuantity(item.cart_item_id, item.quantity, 1, item.stock)"
                    :disabled="item.quantity >= item.stock || processingId === item.cart_item_id"
                    class="p-2 hover:bg-white hover:text-blue-600 disabled:opacity-30 disabled:hover:bg-transparent transition-colors">
                    <Plus class="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="lg:w-96 shrink-0">
          <div class="bg-white p-6 rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100 sticky top-24">
            <h3 class="font-bold text-lg mb-6 text-slate-900">Ringkasan Belanja</h3>

            <div class="space-y-3 mb-6">
              <div class="flex justify-between text-slate-500 text-sm">
                <span>Total Item</span>
                <span>{{ cartData.total_quantity }} Pcs</span>
              </div>
              <div class="flex justify-between text-slate-500 text-sm">
                <span>Subtotal Produk</span>
                <span>{{ formatRupiah(cartData.total_price) }}</span>
              </div>
              <div class="flex justify-between text-slate-500 text-sm">
                <span>Diskon</span>
                <span class="text-green-600 font-medium">-</span>
              </div>
            </div>

            <div class="border-t border-dashed border-slate-200 pt-4 mb-6">
              <div class="flex justify-between items-center">
                <span class="font-bold text-slate-900">Total Harga</span>
                <span class="font-black text-xl text-blue-700">{{ formatRupiah(cartData.total_price) }}</span>
              </div>
            </div>

            <button @click="handleCheckout" :disabled="isCheckingOut || processingId !== null"
              class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-4 rounded-xl shadow-lg shadow-blue-700/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex justify-center items-center gap-2">
              <Loader2 v-if="isCheckingOut" class="w-5 h-5 animate-spin" />
              <span v-else>Checkout ({{ cartData.total_quantity }})</span>
            </button>

            <p class="text-xs text-center text-slate-400 mt-4">
              Pajak dan biaya pengiriman dihitung saat checkout.
            </p>
          </div>
        </div>
      </div>

      <section class="mt-20 border-t border-slate-200 pt-12">
        <h2 class="text-xl md:text-2xl font-bold text-slate-900 mb-6">Rekomendasi Produk Lainnya</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <ProductCard v-for="rec in recommendations" :key="rec.id" :product="rec" />
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>

<style scoped>
input[type=number]::-webkit-inner-spin-button,
input[type=number]::-webkit-outer-spin-button {
  -webkit-appearance: none;
  margin: 0;
}
</style>