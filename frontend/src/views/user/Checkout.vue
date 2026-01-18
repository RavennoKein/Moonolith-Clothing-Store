<script setup>
import { ref, onMounted, computed, onUnmounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/services/api';
import { useCart } from '@/composables/useCart';
import {
  MapPin, Loader2, CreditCard, Smartphone, Building2,
  Store, QrCode, Truck, User, Phone, CheckCircle2, ChevronRight
} from 'lucide-vue-next';

const router = useRouter();
const { fetchCartCount } = useCart();

const MIDTRANS_CLIENT_KEY = 'Mid-client-k54WL5nGvcG2Xkr8';
const SNAP_SCRIPT_URL = 'https://app.sandbox.midtrans.com/snap/snap.js';

const isLoading = ref(true);
const isProcessing = ref(false);
const cartItems = ref([]);
const addressData = ref(null);
const isEditingAddress = ref(true);
const isSavingAddress = ref(false);
const isCalculatingShipping = ref(false);
const currentShippingCost = ref(0);
const selectedPaymentMethod = ref('');

const paymentMethods = [
  { id: 'gopay', name: 'GoPay', type: 'E-Wallet', icon: Smartphone, group: 'ewallet' },
  { id: 'shopeepay', name: 'ShopeePay', type: 'E-Wallet', icon: Smartphone, group: 'ewallet' },
  { id: 'qris', name: 'QRIS', type: 'Scan QR', icon: QrCode, group: 'ewallet' },
  { id: 'bca_va', name: 'BCA Virtual Account', type: 'Auto Check', icon: Building2, group: 'bank' },
  { id: 'echannel', name: 'Mandiri Bill', type: 'Auto Check', icon: Building2, group: 'bank' },
  { id: 'bni_va', name: 'BNI Virtual Account', type: 'Auto Check', icon: Building2, group: 'bank' },
  { id: 'bri_va', name: 'BRI Virtual Account', type: 'Auto Check', icon: Building2, group: 'bank' },
  { id: 'permata_va', name: 'Permata VA', type: 'Auto Check', icon: Building2, group: 'bank' },
  { id: 'credit_card', name: 'Kartu Kredit', type: 'Visa/Master', icon: CreditCard, group: 'card' },
  { id: 'indomaret', name: 'Indomaret', type: 'Gerai', icon: Store, group: 'store' },
  { id: 'alfamart', name: 'Alfamart', type: 'Gerai', icon: Store, group: 'store' },
];

const addressForm = reactive({
  name: '',
  phone_number: '',
  address: '',
  city: '',
  postal_code: '',
  province: ''
});

const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);

const getFlashSaleInfo = (item) => {
  if (item.price !== undefined && item.name !== undefined) {
    return {
      is_active: item.is_flashsale || false,
      price: parseFloat(item.flash_price || item.price),
      original_price: parseFloat(item.price)
    };
  }
  const flashSale = item.variant?.item?.flash_sale_items?.[0];
  return flashSale
    ? { is_active: true, price: flashSale.discount_price, original_price: item.variant.item.price }
    : { is_active: false, price: item.variant?.item?.price || 0, original_price: item.variant?.item?.price || 0 };
};

const fetchShippingCost = async (cityName) => {
  if (!cityName) return;
  isCalculatingShipping.value = true;
  try {
    const res = await api.post('/user/check-shipping', { city: cityName });
    if (res.data) currentShippingCost.value = res.data.cost;
  } catch (error) { currentShippingCost.value = 0; } finally { isCalculatingShipping.value = false; }
};

const fetchCart = async () => {
  isLoading.value = true;
  try {
    const res = await api.get('/user/cart');
    if (res.data.success) {
      cartItems.value = res.data.data.items || [];
    }
  } catch (e) { console.error(e) } finally { isLoading.value = false }
};

const fetchAddress = async () => {
  try {
    const res = await api.get('/user/profile');
    if (res.data.success && res.data.data.address) {
      addressData.value = res.data.data;
      Object.assign(addressForm, res.data.data);
      if (addressForm.city) {
        await fetchShippingCost(addressForm.city);
        isEditingAddress.value = false;
      }
    }
  } catch (e) { }
};

const saveAddress = async () => {
  if (!addressForm.city || !addressForm.address) return alert("Kota dan Alamat wajib diisi");

  isSavingAddress.value = true;
  try {
    addressData.value = { ...addressForm };
    await fetchShippingCost(addressForm.city);
    isEditingAddress.value = false;
  }
  catch (e) { } finally { isSavingAddress.value = false; }
};

const cancelEdit = () => {
  if (!addressData.value) return;
  isEditingAddress.value = false;
};

onUnmounted(() => {
  const script = document.querySelector(`script[src="${SNAP_SCRIPT_URL}"]`);
  if (script) script.remove();
});

const summary = computed(() => {
  let grossTotal = 0, subtotal = 0;
  cartItems.value.forEach(item => {
    const info = getFlashSaleInfo(item);
    const qty = parseInt(item.quantity);
    grossTotal += (info.original_price * qty);
    subtotal += (info.price * qty);
  });
  return {
    grossTotal, subtotal, shipping: currentShippingCost.value,
    discount: grossTotal - subtotal, total: subtotal + currentShippingCost.value
  };
});

const handlePayment = async () => {
  if (!addressForm.city) { alert("Lengkapi alamat pengiriman terlebih dahulu."); isEditingAddress.value = true; return; }
  if (currentShippingCost.value === 0) { alert("Sedang menghitung ongkir, mohon tunggu sebentar..."); return; }
  if (!selectedPaymentMethod.value) { alert("Silakan pilih metode pembayaran."); return; }

  isProcessing.value = true;
  try {
    const payload = {
      full_name: addressForm.name,      
      phone_number: addressForm.phone_number, 
      address: addressForm.address,
      city: addressForm.city,
      province: addressForm.province,
      postal_code: addressForm.postal_code,
      payment_method: selectedPaymentMethod.value,
    };

    const res = await api.post('/user/checkout', payload);

    if (res.data.success && res.data.snap_token) {
      window.snap.pay(res.data.snap_token, {
        onSuccess: (result) => {
          router.push({ name: 'Invoice', params: { invoice: res.data.invoice } });
          fetchCartCount();
        },
        onPending: (result) => {
          router.push({ name: 'Invoice', params: { invoice: res.data.invoice } });
          fetchCartCount();
        },
        onError: (result) => {
          alert("Pembayaran Gagal");
        },
        onClose: () => {
          alert('Anda menutup popup pembayaran. Pesanan menunggu pembayaran.');
          router.push({ name: 'Invoice', params: { invoice: res.data.invoice } });
          fetchCartCount();
        }
      });
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Error Checkout');
  } finally {
    isProcessing.value = false;
  }
};

onMounted(() => {
  fetchCart();
  fetchAddress();
  const script = document.createElement('script');
  script.src = SNAP_SCRIPT_URL;
  script.setAttribute('data-client-key', MIDTRANS_CLIENT_KEY);
  document.head.appendChild(script);
});
</script>

<template>
  <div class="min-h-screen bg-[#f8f9fa] py-12 px-4 font-sans text-slate-800">
    <div class="max-w-7xl mx-auto">

      <div v-if="isLoading" class="flex flex-col items-center justify-center py-32 space-y-4">
        <Loader2 class="animate-spin text-blue-600 w-12 h-12" />
        <p class="text-slate-500 font-medium">Memuat data keranjang...</p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <div class="lg:col-span-8 space-y-8">

          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 flex justify-between items-center bg-white">
              <h2 class="text-lg font-bold flex items-center gap-2 text-slate-800">
                <div class="p-2 bg-blue-50 rounded-lg text-blue-600">
                  <Truck class="w-5 h-5" />
                </div>
                Informasi Pengiriman
              </h2>
              <button v-if="!isEditingAddress && addressData" @click="isEditingAddress = true"
                class="text-sm font-semibold text-blue-600 hover:text-blue-700 hover:bg-blue-50 px-3 py-1.5 rounded-lg transition-colors">
                Ubah Alamat
              </button>
            </div>

            <div v-if="isEditingAddress" class="p-6 bg-slate-50/50">
              <form @submit.prevent="saveAddress" class="space-y-5">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Penerima</label>
                    <div class="relative group">
                      <User
                        class="absolute left-3 top-3 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                      <input v-model="addressForm.name" placeholder="Contoh: Budi Santoso"
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm font-medium placeholder:font-normal"
                        required />
                    </div>
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Nomor HP</label>
                    <div class="relative group">
                      <Phone
                        class="absolute left-3 top-3 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                      <input v-model="addressForm.phone_number" placeholder="Contoh: 08123456789" type="tel"
                        class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm font-medium placeholder:font-normal"
                        required />
                    </div>
                  </div>
                </div>

                <div class="space-y-1.5">
                  <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Alamat Lengkap</label>
                  <div class="relative group">
                    <MapPin
                      class="absolute left-3 top-3 w-5 h-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                    <textarea v-model="addressForm.address"
                      placeholder="Nama Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan" rows="2"
                      class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm font-medium placeholder:font-normal resize-none"
                      required></textarea>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kota / Kab</label>
                    <input v-model="addressForm.city" placeholder="Jakarta Pusat"
                      class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
                      required />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Provinsi</label>
                    <input v-model="addressForm.province" placeholder="DKI Jakarta"
                      class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm"
                      required />
                  </div>
                  <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Kode Pos</label>
                    <input v-model="addressForm.postal_code" placeholder="10110"
                      class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all text-sm" />
                  </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                  <button type="submit" :disabled="isSavingAddress"
                    class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-lg shadow-lg shadow-slate-900/20 transition-all flex items-center gap-2">
                    <span v-if="isSavingAddress">
                      <Loader2 class="animate-spin w-4 h-4" /> Menyimpan...
                    </span>
                    <span v-else>Simpan & Hitung Ongkir</span>
                  </button>
                  <button v-if="addressData" type="button" @click="cancelEdit"
                    class="px-4 py-2.5 text-slate-500 hover:text-slate-700 text-sm font-semibold transition-colors">
                    Batal
                  </button>
                </div>
              </form>
            </div>

            <div v-else class="p-6">
              <div class="flex items-start gap-4">
                <div class="p-3 bg-green-50 rounded-full shrink-0">
                  <CheckCircle2 class="w-6 h-6 text-green-600" />
                </div>
                <div>
                  <div class="flex items-center gap-2 mb-1">
                    <p class="font-bold text-slate-900 text-base">{{ addressData.name }}</p>
                    <span class="w-1 h-1 bg-slate-300 rounded-full"></span>
                    <p class="text-slate-500 text-sm">{{ addressData.phone_number }}</p>
                  </div>
                  <p class="text-slate-700 leading-relaxed">{{ addressData.address }}</p>
                  <p class="text-slate-500 text-sm mt-1">{{ addressData.city }}, {{ addressData.province }} {{
                    addressData.postal_code }}</p>

                  <div
                    class="mt-3 flex items-center gap-2 text-sm text-green-700 bg-green-50 px-3 py-1.5 rounded-md border border-green-100">
                    <Truck class="w-4 h-4" />
                    <span>Ongkir ke {{ addressData.city }}: <b>{{ formatRupiah(currentShippingCost) }}</b></span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 bg-white">
              <h2 class="text-lg font-bold flex items-center gap-2 text-slate-800">
                <div class="p-2 bg-orange-50 rounded-lg text-orange-600">
                  <Store class="w-5 h-5" />
                </div>
                Item Pesanan
              </h2>
            </div>
            <div class="divide-y divide-slate-50">
              <div v-for="item in cartItems" :key="item.cart_item_id"
                class="p-4 sm:p-6 flex gap-4 hover:bg-slate-50 transition-colors">
                <div class="w-20 h-20 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 shrink-0">
                  <img :src="item.image" class="w-full h-full object-cover" />
                </div>
                <div class="flex-1">
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 class="font-semibold text-slate-900 line-clamp-2">{{ item.name }}</h3>
                      <p class="text-sm text-slate-500 mt-1">Ukuran: {{ item.size }} <span class="mx-1">•</span> Qty: {{
                        item.quantity }}</p>
                    </div>
                    <div class="text-right">
                      <div v-if="item.is_flashsale">
                        <p class="font-bold text-red-600">{{ formatRupiah(item.flash_price) }}</p>
                        <p class="text-xs text-slate-400 line-through">{{ formatRupiah(item.price) }}</p>
                      </div>
                      <div v-else>
                        <p class="font-bold text-slate-900">{{ formatRupiah(item.price) }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-50 bg-white">
              <h2 class="text-lg font-bold flex items-center gap-2 text-slate-800">
                <div class="p-2 bg-purple-50 rounded-lg text-purple-600">
                  <CreditCard class="w-5 h-5" />
                </div>
                Metode Pembayaran
              </h2>
            </div>

            <div class="p-6">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div v-for="method in paymentMethods" :key="method.id" @click="selectedPaymentMethod = method.id"
                  class="group relative border rounded-xl p-4 cursor-pointer transition-all duration-200 hover:shadow-md flex items-center gap-4 select-none"
                  :class="selectedPaymentMethod === method.id
                    ? 'border-blue-600 bg-blue-50/30 ring-1 ring-blue-600'
                    : 'border-slate-200 hover:border-blue-300 bg-white'">

                  <div class="w-12 h-12 rounded-lg flex items-center justify-center shrink-0 transition-colors"
                    :class="selectedPaymentMethod === method.id ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-600 group-hover:bg-blue-50 group-hover:text-blue-600'">
                    <component :is="method.icon" class="w-6 h-6" />
                  </div>

                  <div class="flex-1 min-w-0">
                    <p class="font-bold text-sm text-slate-900 group-hover:text-blue-700 transition-colors">{{
                      method.name }}</p>
                    <p class="text-xs text-slate-500">{{ method.type }}</p>
                  </div>

                  <div class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors"
                    :class="selectedPaymentMethod === method.id ? 'border-blue-600' : 'border-slate-300 group-hover:border-blue-400'">
                    <div v-if="selectedPaymentMethod === method.id"
                      class="w-2.5 h-2.5 bg-blue-600 rounded-full shadow-sm"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="lg:col-span-4">
          <div class="bg-white rounded-2xl shadow-sm border border-slate-100 sticky top-24 overflow-hidden">
            <div class="p-5 border-b border-slate-50 bg-slate-50/50">
              <h2 class="font-bold text-slate-900">Ringkasan Belanja</h2>
            </div>

            <div class="p-6 space-y-4">
              <div class="flex justify-between text-slate-600 text-sm">
                <span>Total Harga ({{ cartItems.length }} barang)</span>
                <span class="font-medium text-slate-900">{{ formatRupiah(summary.grossTotal) }}</span>
              </div>
              <div v-if="summary.discount > 0" class="flex justify-between text-green-600 text-sm">
                <span>Total Diskon</span>
                <span class="font-medium">-{{ formatRupiah(summary.discount) }}</span>
              </div>
              <div class="flex justify-between text-slate-600 text-sm">
                <span>Biaya Pengiriman</span>
                <div v-if="isCalculatingShipping" class="flex items-center gap-2 text-blue-600">
                  <Loader2 class="w-3 h-3 animate-spin" /> Hitung...
                </div>
                <span v-else class="font-medium text-slate-900">{{ summary.shipping ? formatRupiah(summary.shipping) :
                  'Rp 0' }}</span>
              </div>

              <div class="border-t border-dashed border-slate-200 my-4"></div>

              <div class="flex justify-between items-end">
                <span class="text-slate-900 font-bold text-lg">Total Tagihan</span>
                <span class="text-blue-700 font-bold text-xl">{{ formatRupiah(summary.total) }}</span>
              </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-100">
              <button @click="handlePayment" :disabled="isProcessing || summary.shipping === 0"
                class="w-full bg-slate-900 text-white py-4 rounded-xl font-bold text-base hover:bg-slate-800 disabled:opacity-50 disabled:cursor-not-allowed flex justify-center items-center gap-2 transition-all shadow-lg shadow-slate-900/10">
                <span v-if="isProcessing" class="flex items-center gap-2">
                  <Loader2 class="w-5 h-5 animate-spin" /> Memproses...
                </span>
                <span v-else class="flex items-center gap-2">
                  Bayar Sekarang
                  <ChevronRight class="w-5 h-5" />
                </span>
              </button>
              <p v-if="summary.shipping === 0 && !isCalculatingShipping" class="text-xs text-center text-red-500 mt-3">
                *Mohon lengkapi dan simpan alamat untuk lanjut.
              </p>
            </div>
          </div>

          <div
            class="mt-6 flex justify-center gap-4 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
            <div class="h-6 w-10 bg-slate-300 rounded"></div>
            <div class="h-6 w-10 bg-slate-300 rounded"></div>
            <div class="h-6 w-10 bg-slate-300 rounded"></div>
            <div class="h-6 w-10 bg-slate-300 rounded"></div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>