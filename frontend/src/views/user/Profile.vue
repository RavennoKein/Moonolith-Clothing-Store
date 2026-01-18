<script setup>
import { ref, computed, watch, onMounted } from "vue";
import Navbar from "../../components/Navbar.vue";
import Footer from "../../components/Footer.vue";
import {
  User, Mail, Phone, MapPin, Package, LogOut,
  ChevronRight, Save, Edit3, ShieldCheck,
  Heart, Trash2, ShoppingBag, Map, Navigation,
  AlertCircle, Truck, FileText, X
} from "lucide-vue-next";
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import api from '@/services/api';

const activeTab = ref("akun");
const isEditing = ref(false);

const orders = ref([]);
const isLoadingOrders = ref(false);

const favoriteProducts = ref([]);
const isLoadingFav = ref(false);

const auth = useAuthStore();
const router = useRouter();
const saving = ref(false);
const showDetailModal = ref(false);
const selectedOrder = ref(null);


const handleLogout = async () => {
  const result = await Swal.fire({
    title: 'Keluar dari Akun?',
    text: "Anda perlu login kembali untuk mengakses profil.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#0f172a',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal',
    customClass: {
      popup: 'rounded-[2rem]',
      confirmButton: 'rounded-xl px-6 py-3',
      cancelButton: 'rounded-xl px-6 py-3'
    }
  });

  if (result.isConfirmed) {
    await auth.logout();
    router.push({ name: 'Home' });
  }
};

const userProfile = computed(() => auth.state.user);
const tempProfile = ref({});

watch(userProfile, (user) => {
  if (!user) {
    router.push({ name: 'Home' });
  } else {
    tempProfile.value = { ...user };
  }
}, { immediate: true });

const toggleEdit = () => {
  if (isEditing.value) {
    tempProfile.value = { ...userProfile.value };
  }
  isEditing.value = !isEditing.value;
};

const handleSave = async () => {
  try {
    saving.value = true;
    await auth.updateProfile({ ...tempProfile.value });
    isEditing.value = false;
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Profil Diperbarui',
      showConfirmButton: false,
      timer: 2000,
    });
  } catch (error) {
    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: error.response?.data?.message || 'Terjadi kesalahan' });
  } finally {
    saving.value = false;
  }
};

const userInitials = computed(() => {
  if (!userProfile.value) return '';
  const names = userProfile.value.name.split(" ");
  return names.length >= 2 ? (names[0][0] + names[1][0]).toUpperCase() : names[0][0].toUpperCase();
});

const fullAddressFormatted = computed(() => {
  const u = userProfile.value;
  if (!u) return 'Belum diatur';
  const parts = [u.address, u.city, u.province].filter(part => part && part.trim() !== '');
  let result = parts.join(', ');
  if (u.postal_code) result += ` ${u.postal_code}`;
  return result || 'Belum diatur';
});

const formatPrice = (value) => {
  if (!value) return 'Rp 0';
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0
  }).format(Number(value));
};

const checkStatus = (status, type) => {
  if (!status) return false;
  const s = status.toLowerCase();

  if (type === 'show_invoice') {
    return s !== 'cancelled' && s !== 'batal';
  }

  if (type === 'process') return ['pending', 'processing', 'dikemas', 'dikirim'].includes(s);
  if (type === 'done') return ['delivered', 'done', 'selesai'].includes(s);
  if (type === 'cancel') return ['cancelled', 'batal'].includes(s);

  return false;
};

const formatDate = (dateString) => {
  if (!dateString) return '-';
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return '-';

  const options = { year: 'numeric', month: 'short', day: 'numeric' };
  return date.toLocaleDateString('id-ID', options);
};

const fetchOrders = async () => {
  if (orders.value.length > 0) return;

  isLoadingOrders.value = true;
  try {
    const response = await api.get('user/orders');
    orders.value = response.data.data;
  } catch (error) {
    console.error("Gagal mengambil data pesanan:", error);
  } finally {
    isLoadingOrders.value = false;
  }
};

const viewInvoice = (invoiceCode) => {
  router.push({ name: 'Invoice', params: { invoice: invoiceCode } });
};

const getStatusColor = (status) => {
  switch (status) {
    case 'pending': return 'bg-yellow-100 text-yellow-700';
    case 'processing': return 'bg-blue-100 text-blue-700';
    case 'delivered': return 'bg-purple-100 text-purple-700';
    case 'done': return 'bg-green-100 text-green-700';
    case 'cancelled': return 'bg-red-100 text-red-700';
    default: return 'bg-slate-100 text-slate-700';
  }
};

const fetchFavorites = async () => {
  if (favoriteProducts.value.length === 0) isLoadingFav.value = true;
  try {
    const response = await api.get('/user/favorites/list');
    favoriteProducts.value = response.data.data.map(item => ({
      id: item.id,
      name: item.name,
      price: parseFloat(item.price),
      image: item.image_url,
      category: item.category ? item.category.name : 'Umum'
    }));
  } catch (error) {
    console.error("Gagal mengambil data favorit:", error);
  } finally {
    isLoadingFav.value = false;
  }
};

const removeFromWishlist = (id) => {
  Swal.fire({
    title: 'Hapus dari Favorit?',
    text: "Item akan dihapus dari daftar keinginan.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#ef4444',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    customClass: { popup: 'rounded-[2rem]', confirmButton: 'rounded-xl', cancelButton: 'rounded-xl' }
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        await api.post('/user/favorites/toggle', { item_id: id });
        favoriteProducts.value = favoriteProducts.value.filter(p => p.id !== id);
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'success',
          title: 'Item dihapus',
          showConfirmButton: false,
          timer: 1500
        });
      } catch (error) {
        Swal.fire('Gagal', 'Terjadi kesalahan saat menghapus', 'error');
      }
    }
  });
};

onMounted(() => {
  fetchFavorites();
});

watch(activeTab, (newTab) => {
  if (newTab === 'favorit') {
    fetchFavorites();
  } else if (newTab === 'pesanan') {
    fetchOrders();
  }
});
</script>

<template>
  <div class="min-h-screen bg-[#F8FAFC] font-sans text-slate-900">
    <Navbar />

    <div class="h-32 bg-slate-900 w-full"></div>

    <main class="max-w-6xl mx-auto px-4 -mt-16 pb-20">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <aside class="lg:col-span-4 space-y-6">
          <div
            class="bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.05)] border border-white p-8 relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-indigo-50 rounded-full blur-3xl"></div>

            <div class="relative z-10 flex flex-col items-center">
              <div class="relative group">
                <div
                  class="w-28 h-28 bg-linear-to-tr from-indigo-600 to-violet-500 rounded-4xl flex items-center justify-center text-white text-3xl font-black shadow-xl group-hover:rotate-6 transition-transform duration-500">
                  {{ userInitials }}
                </div>
                <div
                  class="absolute -bottom-2 -right-2 bg-green-500 border-4 border-white w-8 h-8 rounded-full shadow-lg">
                </div>
              </div>

              <div class="mt-6 text-center">
                <h2 class="text-2xl font-black tracking-tight text-slate-900">{{ userProfile?.name }}</h2>
                <div class="flex items-center justify-center gap-2 mt-1">
                  <ShieldCheck class="w-4 h-4 text-indigo-500" />
                  <span class="text-xs font-bold text-slate-400 capitalize">{{ userProfile?.role }}</span>
                </div>
              </div>

              <div class="grid grid-cols-2 w-full gap-4 mt-8 border-t border-slate-50 pt-8">
                <div class="text-center border-r border-slate-50">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Orders</p>
                  <p class="text-lg font-black text-slate-900">{{ orders.length }}</p>
                </div>
                <div class="text-center">
                  <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Wishlist</p>
                  <p class="text-lg font-black text-slate-900">{{ favoriteProducts.length }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-4xl shadow-sm border border-slate-100 p-3">
            <nav class="space-y-1">
              <button @click="activeTab = 'akun'; isEditing = false"
                :class="activeTab === 'akun' ? 'bg-slate-900 text-white shadow-lg shadow-slate-200' : 'text-slate-500 hover:bg-slate-50'"
                class="w-full flex items-center px-6 py-4 rounded-2xl text-sm font-black transition-all group">
                <User class="w-5 h-5 mr-4 transition-transform group-hover:scale-110" /> Profil Pribadi
              </button>

              <button @click="activeTab = 'favorit'; isEditing = false"
                :class="activeTab === 'favorit' ? 'bg-slate-900 text-white shadow-lg shadow-slate-200' : 'text-slate-500 hover:bg-slate-50'"
                class="w-full flex items-center px-6 py-4 rounded-2xl text-sm font-black transition-all group">
                <Heart class="w-5 h-5 mr-4 transition-transform group-hover:scale-110" /> Produk Favorit
              </button>

              <button @click="activeTab = 'pesanan'; isEditing = false"
                :class="activeTab === 'pesanan' ? 'bg-slate-900 text-white shadow-lg shadow-slate-200' : 'text-slate-500 hover:bg-slate-50'"
                class="w-full flex items-center px-6 py-4 rounded-2xl text-sm font-black transition-all group">
                <Package class="w-5 h-5 mr-4 transition-transform group-hover:scale-110" /> Histori Pesanan
              </button>

              <div class="my-2 border-t border-slate-50"></div>

              <button @click="handleLogout"
                class="w-full flex items-center px-6 py-4 rounded-2xl text-sm font-black text-red-500 hover:bg-red-50 transition-all group">
                <LogOut class="w-5 h-5 mr-4 transition-transform group-hover:translate-x-1" /> Keluar Sesi
              </button>
            </nav>
          </div>
        </aside>

        <div class="lg:col-span-8">
          <div
            class="bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.03)] border border-white p-8 md:p-12 min-h-125">

            <div v-if="activeTab === 'akun'" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
              <div class="flex items-center justify-between mb-12">
                <div>
                  <h3 class="text-3xl font-black italic tracking-tighter uppercase text-slate-900">Pengaturan <span
                      class="text-indigo-600">Akun</span></h3>
                  <p class="text-slate-400 text-sm font-medium mt-1">Kelola informasi identitas dan pengiriman Anda</p>
                </div>

                <button v-if="!isEditing" @click="isEditing = true"
                  class="flex items-center px-6 py-3 bg-slate-50 text-slate-900 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-900 hover:text-white transition-all">
                  <Edit3 class="w-4 h-4 mr-2" /> Edit
                </button>
                <div v-else class="flex gap-3">
                  <button @click="toggleEdit"
                    class="px-5 py-3 text-xs font-black uppercase text-slate-400 hover:text-slate-600">Batal</button>
                  <button @click="handleSave" :disabled="saving"
                    class="flex items-center px-6 py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 shadow-lg shadow-indigo-100 disabled:opacity-50">
                    <Save class="w-4 h-4 mr-2" /> {{ saving ? 'Proses...' : 'Simpan' }}
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="space-y-3">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nama Lengkap</label>
                  <div v-if="!isEditing" class="flex items-center gap-3 text-slate-700 font-bold p-1">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                      <User class="w-5 h-5" />
                    </div>
                    {{ userProfile.name }}
                  </div>
                  <input v-else v-model="tempProfile.name" type="text" placeholder="Masukkan nama lengkap"
                    class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-2xl outline-none font-bold transition-all" />
                </div>

                <div class="space-y-3">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Alamat Email</label>
                  <div v-if="!isEditing" class="flex items-center gap-3 text-slate-700 font-bold p-1">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                      <Mail class="w-5 h-5" />
                    </div>
                    {{ userProfile.email }}
                  </div>
                  <input v-else v-model="tempProfile.email" type="email" placeholder="email@contoh.com"
                    class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-2xl outline-none font-bold transition-all" />
                </div>

                <div class="md:col-span-2 space-y-3">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kontak
                    WhatsApp</label>
                  <div v-if="!isEditing" class="flex items-center gap-3 text-slate-700 font-bold p-1">
                    <div class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                      <Phone class="w-5 h-5" />
                    </div>
                    {{ userProfile.phone_number }}
                  </div>
                  <input v-else v-model="tempProfile.phone_number" type="text" placeholder="08xxxxxxxx"
                    class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-2xl outline-none font-bold transition-all" />
                </div>

                <div class="md:col-span-2 pt-6 border-t border-slate-50">
                  <h4 class="text-sm font-black uppercase tracking-widest text-slate-900 mb-6">Detail Pengiriman</h4>

                  <div v-if="!isEditing" class="space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Alamat
                      Lengkap</label>
                    <div class="flex items-start gap-3 text-slate-700 font-bold p-1 leading-relaxed">
                      <div
                        class="w-10 h-10 shrink-0 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400">
                        <MapPin class="w-5 h-5" />
                      </div>
                      <span class="mt-2">{{ fullAddressFormatted }}</span>
                    </div>
                  </div>

                  <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2 space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Jalan / RT /
                        RW</label>
                      <textarea v-model="tempProfile.address" rows="2" placeholder="Nama Jalan, No. Rumah, RT/RW"
                        class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-2xl outline-none font-bold transition-all resize-none"></textarea>
                    </div>

                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kota / Kab</label>
                      <div class="relative">
                        <Map class="absolute top-1/2 -translate-y-1/2 left-4 w-4 h-4 text-slate-400" />
                        <input v-model="tempProfile.city" type="text" placeholder="Contoh: Surabaya"
                          class="w-full pl-12 pr-5 py-4 bg-slate-50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-2xl outline-none font-bold transition-all" />
                      </div>
                    </div>

                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Provinsi</label>
                      <div class="relative">
                        <Navigation class="absolute top-1/2 -translate-y-1/2 left-4 w-4 h-4 text-slate-400" />
                        <input v-model="tempProfile.province" type="text" placeholder="Contoh: Jawa Timur"
                          class="w-full pl-12 pr-5 py-4 bg-slate-50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-2xl outline-none font-bold transition-all" />
                      </div>
                    </div>

                    <div class="space-y-2">
                      <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Kode Pos</label>
                      <input v-model="tempProfile.postal_code" type="text" placeholder="60xxx"
                        class="w-full px-5 py-4 bg-slate-50 border-2 border-transparent focus:border-indigo-600 focus:bg-white rounded-2xl outline-none font-bold transition-all" />
                    </div>

                  </div>
                </div>

              </div>
            </div>

            <div v-if="activeTab === 'favorit'" class="animate-in fade-in slide-in-from-bottom-4 duration-700">
              <div class="flex items-end justify-between mb-8">
                <div>
                  <h3 class="text-3xl font-black italic tracking-tighter uppercase text-slate-900">Produk <span
                      class="text-indigo-600">Favorit</span></h3>
                  <p class="text-slate-400 text-sm font-medium mt-1">Item yang Anda simpan di wishlist</p>
                </div>
                <div v-if="!isLoadingFav"
                  class="bg-indigo-50 px-4 py-2 rounded-xl text-indigo-600 text-xs font-black uppercase tracking-widest">
                  {{ favoriteProducts.length }} Items
                </div>
              </div>

              <div v-if="isLoadingFav" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="i in 3" :key="i"
                  class="bg-white border border-slate-100 rounded-3xl overflow-hidden p-4 animate-pulse">
                  <div class="bg-slate-200 h-64 rounded-2xl mb-4"></div>
                  <div class="h-4 bg-slate-200 rounded w-1/3 mb-2"></div>
                  <div class="h-6 bg-slate-200 rounded w-3/4 mb-4"></div>
                  <div class="flex justify-between mt-4">
                    <div class="h-8 bg-slate-200 rounded w-1/3"></div>
                    <div class="h-10 w-10 bg-slate-200 rounded-xl"></div>
                  </div>
                </div>
              </div>

              <div v-else>
                <div v-if="favoriteProducts.length === 0"
                  class="flex flex-col items-center justify-center py-20 text-center">
                  <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                    <Heart class="w-10 h-10 text-slate-300" />
                  </div>
                  <h4 class="text-lg font-black text-slate-900 mb-2">Belum ada Favorit</h4>
                  <p class="text-slate-400 text-sm max-w-xs mx-auto">Jelajahi toko dan simpan item yang Anda suka
                    disini.</p>
                  <button @click="router.push('/newest')"
                    class="mt-6 px-6 py-3 bg-slate-900 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-600 transition-colors">
                    Mulai Belanja
                  </button>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                  <div v-for="product in favoriteProducts" :key="product.id"
                    class="group bg-white border border-slate-100 rounded-3xl overflow-hidden hover:shadow-xl hover:shadow-slate-200/50 transition-all duration-500 flex flex-col">

                    <div class="relative aspect-square overflow-hidden bg-slate-100">
                      <img :src="product.image" :alt="product.name"
                        class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />

                      <button @click="removeFromWishlist(product.id)"
                        class="absolute top-4 right-4 w-10 h-10 bg-white/80 backdrop-blur-sm rounded-xl flex items-center justify-center text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors shadow-sm">
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>

                    <div class="p-5 flex flex-col flex-1">
                      <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">{{
                        product.category }}</span>
                      <h4
                        class="font-bold text-slate-900 leading-tight mb-2 line-clamp-2 group-hover:text-indigo-600 transition-colors">
                        {{ product.name }}</h4>

                      <div class="mt-auto pt-4 flex items-center justify-between">
                        <span class="text-lg font-black text-slate-900">{{ formatPrice(product.price) }}</span>

                        <button @click="router.push(`/item-detail/${product.id}`)"
                          class="w-10 h-10 bg-slate-900 text-white rounded-xl flex items-center justify-center hover:bg-indigo-600 transition-colors">
                          <ShoppingBag class="w-4 h-4" />
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="space-y-4">
              <div v-for="order in orders" :key="order.id"
                class="group p-6 bg-white border border-slate-100 rounded-3xl hover:shadow-xl hover:shadow-slate-200/50 transition-all flex flex-col md:flex-row md:items-center justify-between gap-6">

                <div class="flex items-center gap-5">
                  <div
                    class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center group-hover:bg-indigo-50 transition-colors shrink-0">
                    <Package class="w-8 h-8 text-slate-400 group-hover:text-indigo-600 transition-colors" />
                  </div>
                  <div>
                    <p class="text-xs font-black text-indigo-600 uppercase mb-1">{{ order.no_resi }}</p>

                    <p class="text-lg font-black text-slate-900 leading-none">
                      {{ order.tanggal_beli }}
                    </p>

                    <span class="text-xs text-slate-400 font-medium mt-1 block">
                      {{ order.daftar_barang ? order.daftar_barang.length : 0 }} Barang
                    </span>
                  </div>
                </div>

                <div class="flex flex-col md:flex-row items-start md:items-center gap-4 md:gap-8 w-full md:w-auto">

                  <div class="flex items-center justify-between w-full md:w-auto md:block text-left md:text-right">
                    <div>
                      <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total Bayar</p>
                      <p class="font-black text-slate-900 text-lg">
                        {{ formatPrice(order.total_harga) }}
                      </p>
                    </div>

                    <div class="md:hidden mt-2">
                      <span :class="getStatusColor(order.status.toLowerCase())"
                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">
                        {{ order.status }}
                      </span>
                    </div>
                  </div>

                  <div class="flex flex-col items-end gap-3 w-full md:w-auto">
                    <span :class="getStatusColor(order.status.toLowerCase())"
                      class="hidden md:inline-block px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-1">
                      {{ order.status }}
                    </span>

                    <div class="flex gap-2 w-full md:w-auto">
                      <button
                        v-if="order.status.toLowerCase() !== 'cancelled' && order.status.toLowerCase() !== 'batal'"
                        @click="viewInvoice(order.no_resi)"
                        class="flex-1 md:flex-none px-5 py-2 bg-indigo-600 text-white shadow-lg shadow-indigo-200 hover:bg-indigo-700 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex items-center justify-center gap-2">
                        <FileText class="w-4 h-4" />
                        Invoice
                      </button>

                      <button v-else
                        class="flex-1 md:flex-none px-5 py-2 bg-slate-100 text-slate-400 cursor-not-allowed rounded-xl text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2">
                        <AlertCircle class="w-4 h-4" />
                        Dibatalkan
                      </button>
                    </div>
                  </div>

                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>

    <Footer />
  </div>
</template>

<style scoped>
.animate-in {
  animation: 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes slide-in-from-bottom-4 {
  from {
    transform: translateY(1rem);
    opacity: 0;
  }

  to {
    transform: translateY(0);
    opacity: 1;
  }
}

input,
textarea {
  transition: all 0.3s ease;
}
</style>