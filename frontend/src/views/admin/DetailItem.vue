<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Package, Ruler, Droplets, Layers, Zap, Users, AlertTriangle, XCircle } from 'lucide-vue-next';
import Sidebar from '../../components/Sidebar.vue';
import Swal from 'sweetalert2';
import api from '@/services/api';

const restockLoading = ref(false);
const route = useRoute();
const router = useRouter();
const isSidebarOpen = ref(true);
const item = ref({});
const loading = ref(true);
const error = ref(null);
const isEditingItem = ref(false);
const editForm = ref({});
const editLoading = ref(false);
const bulkStockData = ref({});

const colorPalette = {
  white: '#FFFFFF',
  black: '#000000',
  navy: '#001F3F',
  gray: '#6B7280',
  red: '#EF4444',
  blue: '#1E40AF',
  green: '#065F46',
  yellow: '#F59E0B',
  pink: '#EC4899',
  maroon: '#800000',
  khaki: '#C3B091',
  army: '#4B5320',
  cream: '#F5F5DC',
  brown: '#A52A2A',
  purple: '#9333EA',
  orange: '#F97316'
};

const stockStats = computed(() => {
  if (!item.value.sizes) return null;

  let totalVariants = 0;
  let emptyVariants = 0;
  let emptySizes = 0;
  let isCompletelyOutOfStock = true;

  item.value.sizes.forEach(size => {
    let sizeEmptyCount = 0;
    let sizeHasStock = false;

    size.colors.forEach(color => {
      totalVariants++;
      if (color.stock === 0) {
        emptyVariants++;
        sizeEmptyCount++;
      } else {
        sizeHasStock = true;
        isCompletelyOutOfStock = false;
      }
    });

    if (sizeEmptyCount === size.colors.length) {
      emptySizes++;
    }
  });

  return {
    totalVariants,
    emptyVariants,
    emptySizes,
    isCompletelyOutOfStock,
    emptyPercentage: totalVariants > 0 ? (emptyVariants / totalVariants) * 100 : 0
  };
});

const isSizeCompletelyEmpty = (size) => {
  return size.colors.every(c => c.stock === 0);
};

const hasEmptyVariants = computed(() => {
  return stockStats.value?.emptyVariants > 0;
});

const getStockBadge = computed(() => {
  if (!stockStats.value) return null;

  if (stockStats.value.isCompletelyOutOfStock) {
    return {
      text: 'STOK HABIS TOTAL',
      class: 'bg-red-600 text-white animate-pulse shadow-lg',
      icon: XCircle
    };
  }

  if (stockStats.value.emptyVariants > 0) {
    return {
      text: `${stockStats.value.emptyVariants} VARIAN KOSONG`,
      class: 'bg-red-500 text-white',
      icon: AlertTriangle
    };
  }

  if (item.value.total_stock < 10) {
    return {
      text: 'STOK KRITIS',
      class: 'bg-red-400 text-white',
      icon: AlertTriangle
    };
  }

  if (item.value.total_stock < 25) {
    return {
      text: 'STOK MENIPIS',
      class: 'bg-amber-500 text-white',
      icon: AlertTriangle
    };
  }

  return null;
});

const initEditForm = () => {
  editForm.value = {
    name: item.value.name,
    description: item.value.description,
    price: item.value.price,
    category_id: item.value.category?.id || item.value.category_id,
    bahan: item.value.bahan,
    tingkat_ketebalan: item.value.tingkat_ketebalan,
    status_produk: item.value.status_produk,
    gender: item.value.gender || 'unisex',
    image: null,
    imagePreview: item.value.image_url || null,
  };
};

const handleEditImage = (e) => {
  const file = e.target.files[0];
  if (!file) return;

  if (!file.type.startsWith('image/')) {
    return Swal.fire('Error', 'File harus berupa gambar', 'error');
  }

  if (file.size > 2 * 1024 * 1024) {
    return Swal.fire('Error', 'Maksimal ukuran 2MB', 'error');
  }

  editForm.value.image = file;
  editForm.value.imagePreview = URL.createObjectURL(file);
};

const ketebalanOptions = [
  { label: 'Tipis', value: 'tipis' },
  { label: 'Sedang', value: 'sedang' },
  { label: 'Tebal', value: 'tebal' }
];
const categories = ref([]);
const categoryLoading = ref(false);

const getImageSource = computed(() => {
  if (isEditingItem.value && editForm.value.image && editForm.value.imagePreview.startsWith('blob:')) {
    return editForm.value.imagePreview;
  }

  return item.value.image_url || null;
});

const isLowStock = computed(() => {
  return item.value && item.value.total_stock < 10;
});

const isWarningStock = computed(() => {
  return item.value && item.value.total_stock >= 10 && item.value.total_stock < 25;
});

const handleUpdateItem = async () => {
  if (editLoading.value) return;

  const fd = new FormData();
  fd.append('_method', 'PUT');
  fd.append('name', editForm.value.name);
  fd.append('description', editForm.value.description);
  fd.append('price', editForm.value.price);
  fd.append('category_id', editForm.value.category_id);
  fd.append('bahan', editForm.value.bahan || '');
  fd.append('tingkat_ketebalan', editForm.value.tingkat_ketebalan || '');
  fd.append('status_produk', editForm.value.status_produk);
  fd.append('gender', editForm.value.gender);

  if (editForm.value.image) {
    fd.append('image', editForm.value.image);
  }

  try {
    editLoading.value = true;

    Swal.fire({
      title: 'Menyimpan perubahan...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    const res = await api.post(
      `/admin/items/${item.value.id}`,
      fd
    );

    item.value = res.data.data;
    isEditingItem.value = false;

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Item berhasil diperbarui',
      timer: 2500,
      showConfirmButton: false,
    });
  } catch (err) {
    if (err.response?.status === 423) {
      showFlashSaleLockAlert();
    } else {
      Swal.fire('Gagal', err.response?.data?.message || 'Terjadi kesalahan', 'error');
    }
  } finally {
    editLoading.value = false;
  }
};

const fetchItemDetail = async () => {
  loading.value = true;
  error.value = null;

  try {
    const res = await api.get(`/admin/items/${route.params.id}`);
    item.value = res.data.data;
    initEditForm();
  } catch (err) {
    if (err.response?.status === 404) {
      error.value = 'Item tidak ditemukan';
    } else if (err.response?.status === 401) {
      router.push({ name: 'Login' });
    } else {
      error.value = 'Gagal mengambil data item';
    }
  } finally {
    loading.value = false;
  }
};

const fetchCategories = async () => {
  categoryLoading.value = true;
  try {
    const res = await api.get('/admin/categories');
    categories.value = res.data.data;
  } catch (err) {
    console.error('Gagal mengambil kategori:', err);
  } finally {
    categoryLoading.value = false;
  }
};

onMounted(() => {
  fetchItemDetail();
  fetchCategories();
});

const startEdit = () => {
  if (item.value.is_locked) {
    return showFlashSaleLockAlert();
  }
  initEditForm();
  isEditingItem.value = true;
};

const cancelEdit = () => {
  if (editForm.value.imagePreview?.startsWith('blob:')) {
    URL.revokeObjectURL(editForm.value.imagePreview);
  }
  isEditingItem.value = false;
};

const toggleColorInput = (size, colorName) => {
  if (!bulkStockData.value[size]) {
    bulkStockData.value[size] = {};
  }

  if (bulkStockData.value[size][colorName] !== undefined) {
    delete bulkStockData.value[size][colorName];

    if (Object.keys(bulkStockData.value[size]).length === 0) {
      delete bulkStockData.value[size];
    }
  } else {
    bulkStockData.value[size] = {
      ...bulkStockData.value[size],
      [colorName]: 0
    };
  }
};

const totalSelectedVariants = computed(() => {
  let count = 0;
  Object.values(bulkStockData.value).forEach(colors => {
    count += Object.keys(colors).length;
  });
  return count;
});

const handleRestock = async () => {
  if (restockLoading.value) return;

  if (totalSelectedVariants.value === 0) {
    return Swal.fire({
      icon: 'warning',
      title: 'Belum ada warna dipilih',
      text: 'Pilih minimal satu warna dan masukkan jumlah stoknya.',
      confirmButtonColor: '#2563eb',
    });
  }

  const confirm = await Swal.fire({
    title: 'Konfirmasi Restock',
    text: `Apakah Anda yakin ingin menambah stok untuk ${totalSelectedVariants.value} varian?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Ya, Simpan',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2563eb',
  });

  if (!confirm.isConfirmed) return;

  try {
    restockLoading.value = true;
    Swal.fire({
      title: 'Memproses...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    for (const [size, colors] of Object.entries(bulkStockData.value)) {
      const variants = Object.entries(colors)
        .map(([color, stock]) => ({
          color: color,
          stock: Number(stock)
        }))
        .filter(v => v.stock > 0);

      if (variants.length > 0) {
        await api.post(`/admin/items/${item.value.id}/variants/restock`, {
          size: size,
          variants: variants
        });
      }
    }

    bulkStockData.value = {};
    await fetchItemDetail();

    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: 'Stok berhasil diperbarui',
      showConfirmButton: false,
      timer: 2500,
    });
  } catch (err) {
    Swal.fire('Gagal', err.response?.data?.message || 'Terjadi kesalahan sistem', 'error');
  } finally {
    restockLoading.value = false;
  }
};

const handleDeleteItem = async () => {
  const confirm = await Swal.fire({
    title: 'Hapus Item?',
    html: `
      <div class="text-sm text-gray-600">
        Item <b>${item.value.name}</b> akan dihapus permanen.<br>
        <span class="text-red-600 font-semibold">
          Semua stok & variant akan ikut terhapus.
        </span>
      </div>
    `,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#9ca3af',
  });

  if (!confirm.isConfirmed) return;

  try {
    Swal.fire({
      title: 'Menghapus...',
      allowOutsideClick: false,
      didOpen: () => Swal.showLoading(),
    });

    await api.delete(`/admin/items/${item.value.id}`);

    Swal.fire({
      icon: 'success',
      title: 'Item dihapus',
      timer: 1500,
      showConfirmButton: false,
    });

    router.push({ name: 'Item' });

  } catch (err) {
    if (err.response?.status === 423) {
      showFlashSaleLockAlert();
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Gagal menghapus',
        text: err.response?.data?.message || 'Terjadi kesalahan',
      });
    }
  }
};

const isLocked = computed(() => item.value.is_locked === true);

const selectedUpdateSize = ref('S');
const availableSizes = ['S', 'M', 'L', 'XL'];

const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);

const handleSidebarToggle = (status) => { isSidebarOpen.value = status; };
const goBack = () => router.back();

const showFlashSaleLockAlert = () => {
  Swal.fire({
    iconHtml: '🔒',
    title: 'Item sedang dikunci',
    html: `
      <p class="text-gray-600">
        Item ini sedang digunakan dalam
        <b>flash sale yang aktif</b>.
      </p>
      <p class="mt-2 text-sm text-gray-500">
        Edit atau hapus hanya bisa dilakukan
        setelah flash sale berakhir.
      </p>
    `,
    confirmButtonText: 'Saya mengerti',
    confirmButtonColor: '#2563eb',
    background: '#ffffff',
    customClass: {
      popup: 'rounded-2xl shadow-xl',
      title: 'text-xl font-bold',
      confirmButton: 'rounded-xl px-6 py-2 font-bold'
    }
  });
};

const getStockColorClass = (stock) => {
  if (stock === 0) return 'text-red-600 font-bold';
  if (stock < 10) return 'text-red-500 font-semibold';
  if (stock < 25) return 'text-yellow-600 font-semibold';
  return 'text-slate-800';
};

const getStockBackgroundClass = (stock) => {
  if (stock === 0) return 'bg-red-100 border-red-200';
  if (stock < 10) return 'bg-red-50 border-red-100';
  if (stock < 25) return 'bg-yellow-50 border-yellow-100';
  return 'bg-gray-50 border-gray-200';
};

const highlightEmptyVariants = computed(() => {
  const highlights = {};
  if (item.value.sizes) {
    item.value.sizes.forEach(size => {
      size.colors.forEach(color => {
        if (color.stock === 0) {
          if (!highlights[size.size]) {
            highlights[size.size] = [];
          }
          highlights[size.size].push(color.color);
        }
      });
    });
  }
  return highlights;
});
</script>

<template>
  <div v-if="loading" class="p-10 text-center text-gray-400">
    <div class="animate-pulse">Memuat detail produk...</div>
  </div>
  <div v-else-if="error" class="p-10 text-center text-red-600">
    {{ error }}
  </div>
  <div v-else class="flex min-h-screen bg-gray-50">
    <Sidebar current-route="data-item" @toggle="handleSidebarToggle" />

    <div :class="['grow p-8 transition-all duration-300', isSidebarOpen ? 'ml-70' : 'ml-0']">
      <div class="flex items-center mb-6">
        <button @click="goBack"
          class="mr-4 p-2 bg-white rounded-full shadow-sm hover:shadow-md border border-gray-200 text-gray-600 hover:text-blue-600 transition-all">
          <ArrowLeft class="w-6 h-6" />
        </button>
        <div>
          <h1 class="text-2xl font-bold text-slate-800">Detail Produk</h1>
          <p class="text-sm text-gray-500">ID Produk: #{{ item.id }}</p>
        </div>
      </div>

      <div class="grid grid-cols-12 gap-8">
        <div class="col-span-12 lg:col-span-4">
          <div class="bg-white p-4 rounded-2xl shadow-sm border border-gray-100">
            <div
              class="aspect-square bg-gray-100 rounded-xl flex items-center justify-center border-2 border-dashed border-gray-200 overflow-hidden relative">
              <img v-if="getImageSource" :src="getImageSource" class="w-full h-full object-cover" />

              <div v-else class="text-center text-gray-400">
                <Package class="w-20 h-20 mx-auto mb-2 opacity-20" />
                <p class="text-sm">Belum ada foto produk</p>
              </div>

              <div v-if="isEditingItem"
                class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                <label class="cursor-pointer bg-white px-4 py-2 rounded-lg text-sm font-bold shadow-lg">
                  <input type="file" accept="image/*" class="hidden" @change="handleEditImage" />
                  Ganti Foto
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-12 lg:col-span-8 space-y-6">
          <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <div class="flex justify-between items-start mb-6">
              <div class="flex-1 mr-4">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                  <span v-if="!isEditingItem"
                    class="px-3 py-1 bg-slate-100 text-slate-600 text-xs font-black rounded-lg uppercase tracking-widest border border-slate-200">
                    {{ item.category.name }}
                  </span>

                  <span :class="{
                    'bg-emerald-100 text-emerald-700 border-emerald-200': item.status_produk === 'aktif',
                    'bg-amber-100 text-amber-700 border-amber-200': item.status_produk === 'terbatas',
                    'bg-rose-100 text-rose-700 border-rose-200': item.status_produk === 'non_aktif'
                  }" class="px-3 py-1 text-xs font-black rounded-lg border uppercase tracking-wider">
                    {{ item.status_produk }}
                  </span>

                  <template v-if="getStockBadge">
                    <div
                      :class="['flex items-center gap-1.5 px-3 py-1 rounded-lg shadow-md text-xs font-black', getStockBadge.class]">
                      <component :is="getStockBadge.icon" class="w-4 h-4" />
                      <span>{{ getStockBadge.text }}</span>
                    </div>
                  </template>

                  <div v-if="item.is_locked"
                    class="flex items-center gap-1.5 px-3 py-1 bg-linear-to-r from-orange-500 to-red-600 text-white text-xs font-black rounded-lg shadow-md animate-pulse">
                    <Zap class="w-4 h-4 fill-current" />
                    <span>SEDANG FLASH SALE</span>
                  </div>
                </div>

                <h2 v-if="!isEditingItem" class="text-4xl font-extrabold text-slate-800 tracking-tight leading-none">
                  {{ item.name }}
                </h2>
                <div v-else class="space-y-2">
                  <label class="text-xs font-bold text-gray-400 uppercase tracking-widest">Nama Produk</label>
                  <input v-model="editForm.name"
                    class="text-3xl font-bold text-slate-800 w-full border-b-2 border-blue-500 outline-none bg-transparent" />
                </div>
              </div>

              <div class="text-right">
                <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Harga Jual</p>
                <p v-if="!isEditingItem" class="text-3xl font-black text-green-600">{{ formatRupiah(item.price) }}</p>
                <input v-else type="number" v-model="editForm.price"
                  class="text-2xl font-black text-green-600 text-right w-32 border-b-2 border-blue-500 focus:outline-none bg-blue-50/50 p-1" />
              </div>
            </div>

            <hr class="my-6 border-gray-100" />

            <div v-if="stockStats" class="col-span-12 mb-8">
              <div :class="[
                'relative overflow-hidden p-5 rounded-2xl border-2 transition-all duration-500 shadow-xl',
                stockStats.isCompletelyOutOfStock ? 'bg-red-50 border-red-300' :
                  stockStats.emptyVariants > 0 ? 'bg-red-50/80 border-red-200' :
                    isLowStock ? 'bg-red-50 border-red-200' :
                      isWarningStock ? 'bg-amber-50 border-amber-200' :
                        'bg-blue-50 border-blue-200'
              ]">

                <div class="absolute -right-8 -top-8 opacity-10">
                  <AlertTriangle :class="stockStats.isCompletelyOutOfStock ? 'w-32 h-32 text-red-600' :
                    stockStats.emptyVariants > 0 ? 'w-32 h-32 text-red-500' :
                      isLowStock ? 'w-32 h-32 text-red-400' :
                        isWarningStock ? 'w-32 h-32 text-amber-500' :
                          'w-32 h-32 text-blue-500'" />
                </div>

                <div class="relative flex flex-col md:flex-row items-center gap-6">
                  <div class="relative shrink-0">
                    <div :class="[
                      'w-16 h-16 rounded-full flex items-center justify-center z-10 relative',
                      stockStats.isCompletelyOutOfStock ? 'bg-red-600 text-white' :
                        stockStats.emptyVariants > 0 ? 'bg-red-500 text-white' :
                          isLowStock ? 'bg-red-400 text-white' :
                            isWarningStock ? 'bg-amber-500 text-white' :
                              'bg-blue-500 text-white'
                    ]">
                      <XCircle v-if="stockStats.isCompletelyOutOfStock" class="w-8 h-8" />
                      <AlertTriangle v-else-if="stockStats.emptyVariants > 0 || isLowStock" class="w-8 h-8" />
                      <Package v-else class="w-8 h-8" />
                    </div>
                    <div :class="[
                      'absolute inset-0 rounded-full animate-ping opacity-25',
                      stockStats.isCompletelyOutOfStock ? 'bg-red-500' :
                        stockStats.emptyVariants > 0 ? 'bg-red-400' :
                          isLowStock ? 'bg-red-300' :
                            isWarningStock ? 'bg-amber-500' :
                              'bg-blue-500'
                    ]"></div>
                  </div>

                  <div class="flex-1 text-center md:text-left">
                    <h4 :class="['text-xl font-black uppercase tracking-tight',
                      stockStats.isCompletelyOutOfStock ? 'text-red-700' :
                        stockStats.emptyVariants > 0 ? 'text-red-600' :
                          isLowStock ? 'text-red-500' :
                            isWarningStock ? 'text-amber-700' :
                              'text-blue-700'
                    ]">
                      {{
                        stockStats.isCompletelyOutOfStock ? 'STOK HABIS TOTAL!' :
                          stockStats.emptyVariants > 0 ? 'ADA VARIAN KOSONG!' :
                            isLowStock ? 'STOK KRITIS!' :
                              isWarningStock ? 'PERHATIAN: STOK MENIPIS' :
                                'STOK AMAN'
                      }}
                    </h4>
                    <p :class="['text-sm font-medium leading-relaxed',
                      stockStats.isCompletelyOutOfStock ? 'text-red-600' :
                        stockStats.emptyVariants > 0 ? 'text-red-500' :
                          isLowStock ? 'text-red-600' :
                            isWarningStock ? 'text-amber-600' :
                              'text-blue-600'
                    ]">
                      <span v-if="stockStats.isCompletelyOutOfStock">
                        Semua varian produk ini habis. Segera restock untuk melanjutkan penjualan.
                      </span>
                      <span v-else-if="stockStats.emptyVariants > 0">
                        Terdapat <span class="font-bold text-lg">{{ stockStats.emptyVariants }} varian</span> yang
                        stoknya habis
                        dari total {{ stockStats.totalVariants }} varian
                        ({{ Math.round(stockStats.emptyPercentage) }}% kosong).
                      </span>
                      <span v-else-if="isLowStock">
                        Sisa <span class="font-bold text-lg">{{ item.total_stock }}</span> unit lagi.
                        Segera isi ulang stok agar pelanggan tidak kecewa.
                      </span>
                      <span v-else-if="isWarningStock">
                        Sisa <span class="font-bold text-lg">{{ item.total_stock }}</span> unit lagi.
                        Pantau terus penjualan produk ini.
                      </span>
                      <span v-else>
                        Stok produk dalam kondisi aman dengan total <span class="font-bold text-lg">{{ item.total_stock
                          }}</span>
                        unit.
                      </span>
                    </p>
                  </div>

                  <div class="w-full md:w-48 space-y-2">
                    <div class="flex justify-between text-[10px] font-black uppercase">
                      <span :class="stockStats.isCompletelyOutOfStock ? 'text-red-400' :
                        stockStats.emptyVariants > 0 ? 'text-red-400' :
                          isLowStock ? 'text-red-400' :
                            isWarningStock ? 'text-amber-400' :
                              'text-blue-400'">Status Inventori</span>
                      <span :class="stockStats.isCompletelyOutOfStock ? 'text-red-600' :
                        stockStats.emptyVariants > 0 ? 'text-red-600' :
                          isLowStock ? 'text-red-600' :
                            isWarningStock ? 'text-amber-600' :
                              'text-blue-600'">{{ item.total_stock }}/25 Unit</span>
                    </div>
                    <div class="h-3 w-full bg-gray-200 rounded-full overflow-hidden">
                      <div :class="['h-full transition-all duration-1000 ease-out rounded-full',
                        stockStats.isCompletelyOutOfStock ? 'bg-red-600' :
                          stockStats.emptyVariants > 0 ? 'bg-red-500' :
                            isLowStock ? 'bg-red-400' :
                              isWarningStock ? 'bg-amber-500' :
                                'bg-blue-500'
                      ]" :style="{ width: `${(item.total_stock / 25) * 100}%` }">
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div class="flex flex-col gap-4 p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center text-purple-600 mb-2">
                  <Users class="w-6 h-6 mr-2" /> <span class="text-xs font-black uppercase tracking-widest">Target
                    Gender</span>
                </div>

                <div v-if="isEditingItem" class="space-y-1">
                  <label class="text-[10px] font-bold text-gray-400 uppercase">Pilih Gender</label>
                  <select v-model="editForm.gender"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-base font-medium focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    <option value="men">Pria</option>
                    <option value="women">Wanita</option>
                    <option value="kids">Anak-Anak</option>
                    <option value="unisex">Unisex (Semua)</option>
                  </select>
                </div>

                <div v-else class="space-y-1">
                  <label class="text-[10px] font-bold text-gray-400 uppercase">Gender</label>
                  <p class="text-slate-700 font-bold text-xl capitalize">
                    {{
                      item.gender === 'men' ? 'Pria' :
                        item.gender === 'women' ? 'Wanita' :
                          item.gender === 'kids' ? 'Anak-Anak' : 'Unisex'
                    }}
                  </p>
                </div>
              </div>
              <div class="flex flex-col gap-4 p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center text-indigo-600 mb-2">
                  <Package class="w-6 h-6 mr-2" />
                  <span class="text-xs font-black uppercase tracking-widest">Identitas Produk</span>
                </div>

                <div v-if="isEditingItem" class="space-y-1">
                  <label class="text-[10px] font-bold text-gray-400 uppercase">Pilih Kategori</label>

                  <div v-if="categoryLoading" class="text-xs text-blue-500 animate-pulse">
                    Memuat daftar kategori...
                  </div>

                  <select v-else v-model="editForm.category_id"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-base font-medium focus:ring-2 focus:ring-blue-500 outline-none transition-all">
                    <option value="" disabled>-- Pilih Kategori --</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </option>
                  </select>
                </div>

                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-gray-400 uppercase">Material Bahan</label>
                  <p v-if="!isEditingItem" class="text-slate-700 font-bold text-xl">{{ item.bahan || '-' }}</p>
                  <input v-else v-model="editForm.bahan"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-base focus:ring-2 focus:ring-blue-500 outline-none" />
                </div>
              </div>

              <div class="flex flex-col justify-center p-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="flex items-center text-orange-500 mb-4">
                  <Layers class="w-6 h-6 mr-2" />
                  <span class="text-xs font-black uppercase tracking-widest">Spesifikasi Fisik</span>
                </div>

                <div class="space-y-1">
                  <label class="text-[10px] font-bold text-gray-400 uppercase">Tingkat Ketebalan</label>
                  <p v-if="!isEditingItem" class="text-slate-700 font-bold text-2xl capitalize">{{
                    item.tingkat_ketebalan || '-' }}</p>

                  <select v-else v-model="editForm.tingkat_ketebalan"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 text-base font-medium focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer">
                    <option v-for="opt in ketebalanOptions" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </option>
                  </select>
                </div>
              </div>
            </div>

            <div class="flex flex-wrap gap-3">
              <template v-if="!isEditingItem">
                <button @click="startEdit" :disabled="isLocked"
                  class="px-6 py-2.5 rounded-xl font-bold transition-all text-sm flex items-center gap-2"
                  :class="isLocked ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-blue-600 text-white hover:bg-blue-700 shadow-lg shadow-blue-100'">
                  <span v-if="isLocked">🔒 Terkunci (Flash Sale)</span>
                  <span v-else>Edit Produk</span>
                </button>

                <button @click="handleDeleteItem" :disabled="isLocked"
                  class="px-6 py-2.5 rounded-xl font-bold transition-all text-sm"
                  :class="isLocked ? 'bg-gray-100 text-gray-300 cursor-not-allowed' : 'bg-red-50 text-red-600 hover:bg-red-100'">
                  Hapus
                </button>
              </template>

              <template v-else>
                <button @click="handleUpdateItem" :disabled="editLoading"
                  class="px-8 py-2.5 bg-green-600 text-white rounded-xl font-bold shadow-lg shadow-green-100 hover:bg-green-700 transition-all flex items-center gap-2">
                  <span v-if="editLoading" class="animate-spin text-lg">◌</span>
                  {{ editLoading ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </button>
                <button @click="cancelEdit" :disabled="editLoading"
                  class="px-6 py-2.5 bg-gray-100 text-gray-600 rounded-xl font-bold hover:bg-gray-200 transition-all">
                  Batal
                </button>
              </template>
            </div>
          </div>

          <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
              <span class="w-1.5 h-6 bg-blue-600 rounded-full mr-3"></span>
              Deskripsi Produk
            </h3>
            <p v-if="!isEditingItem" class="text-gray-600 leading-relaxed whitespace-pre-line">{{ item.description }}
            </p>
            <textarea v-else v-model="editForm.description" rows="5"
              class="w-full p-4 bg-gray-50 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-gray-600"></textarea>
          </div>
        </div>
      </div>

      <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 mt-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-bold text-slate-800 flex items-center">
            <span class="w-1.5 h-6 bg-blue-600 rounded-full mr-3"></span>
            Rincian Stok Aktif
          </h3>

          <div v-if="stockStats" class="flex items-center gap-4 text-sm">
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-red-500"></div>
              <span class="text-gray-600">Varian Kosong:</span>
              <span class="font-bold text-red-600">{{ stockStats.emptyVariants }}</span>
            </div>
            <div class="flex items-center gap-2">
              <div class="w-3 h-3 rounded-full bg-red-300"></div>
              <span class="text-gray-600">Size Kosong:</span>
              <span class="font-bold text-red-500">{{ stockStats.emptySizes }}</span>
            </div>
          </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
          <div v-for="s in item.sizes" :key="s.size" :class="['p-4 rounded-xl border shadow-sm transition-all',
            isSizeCompletelyEmpty(s) ? 'bg-red-50 border-red-300' :
              s.colors.some(c => c.stock === 0) ? 'bg-red-50/80 border-red-200' :
                'bg-white border-gray-100'
          ]">
            <div class="flex justify-between items-center mb-3 border-b border-gray-50 pb-2">
              <p class="text-xs font-black uppercase" :class="isSizeCompletelyEmpty(s) ? 'text-red-700' :
                s.colors.some(c => c.stock === 0) ? 'text-red-600' : 'text-gray-400'">
                Size {{ s.size }}
                <span v-if="isSizeCompletelyEmpty(s)" class="ml-1 text-[10px] text-red-500">(KOSONG)</span>
                <span v-else-if="s.colors.filter(c => c.stock === 0).length > 0" class="ml-1 text-[10px] text-red-500">
                  ({{s.colors.filter(c => c.stock === 0).length}} kosong)
                </span>
              </p>
              <p :class="getStockColorClass(s.total_stock)" class="text-[12px] transition-all">
                {{ s.total_stock }} <span class="text-sm font-normal text-gray-400">Unit</span>
              </p>
            </div>

            <div class="space-y-2">
              <div v-for="c in s.colors" :key="c.color" :class="['flex justify-between items-center p-2 rounded-lg transition-all',
                getStockBackgroundClass(c.stock)
              ]">
                <div class="flex items-center">
                  <span class="w-4 h-4 rounded-full border mr-2 shadow-sm relative"
                    :style="{ backgroundColor: colorPalette[c.color.toLowerCase()] || '#e5e7eb' }">
                    <div v-if="c.stock === 0"
                      class="absolute -top-1 -right-1 w-3 h-3 bg-red-600 rounded-full border border-white"></div>
                  </span>
                  <span class="text-xs font-medium capitalize"
                    :class="c.stock === 0 ? 'text-red-700 line-through' : 'text-gray-600'">
                    {{ c.color }}
                  </span>
                </div>
                <span :class="getStockColorClass(c.stock)" class="text-xs font-black">
                  {{ c.stock === 0 ? '0 (KOSONG)' : c.stock }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="border-t border-dashed border-gray-200 pt-8 mt-8">
          <div class="mb-6">
            <h3 class="text-lg font-bold text-blue-600 mb-1">Bulk Restock (Banyak Size & Warna)</h3>
            <p class="text-xs text-blue-400">Pilih warna pada setiap tab ukuran untuk menambah stok sekaligus.</p>

            <div v-if="hasEmptyVariants" class="mt-2 p-3 bg-amber-50 border border-amber-200 rounded-lg">
              <div class="flex items-center text-amber-700">
                <AlertTriangle class="w-4 h-4 mr-2 shrink-0" />
                <div class="text-sm">
                  <span class="font-semibold">Varian kosong terdeteksi!</span>
                  <span class="text-amber-600 ml-1">
                    Warna dengan latar belakang merah adalah varian yang stoknya 0.
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="flex items-center space-x-2 mb-6 bg-gray-100 p-1.5 rounded-2xl w-fit">
            <button v-for="size in availableSizes" :key="size" @click="selectedUpdateSize = size" :class="[
              'px-6 py-2 rounded-xl text-sm font-black transition-all duration-200 flex items-center gap-2',
              selectedUpdateSize === size ? 'bg-white text-blue-600 shadow-sm scale-105' : 'text-gray-400 hover:text-gray-600'
            ]">
              SIZE {{ size }}
              <span v-if="bulkStockData[size]" class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
            </button>
          </div>

          <div :key="selectedUpdateSize" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <div v-for="(hex, name) in colorPalette" :key="`${selectedUpdateSize}-${name}`" :class="[
              'relative flex flex-wrap items-center p-3 rounded-xl border transition-all duration-200 cursor-pointer select-none',
              item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name && c.stock === 0)
                ? 'border-red-500 bg-red-50 shadow-sm' :
                bulkStockData[selectedUpdateSize]?.[name] !== undefined ? 'border-blue-500 bg-blue-50 shadow-sm' :
                  'border-gray-200 hover:border-gray-300'
            ]" @click="toggleColorInput(selectedUpdateSize, name)">

              <div class="flex items-center flex-1 min-w-30">
                <div class="w-5 h-5 rounded border mr-3 flex items-center justify-center transition-colors" :class="item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name && c.stock === 0)
                  ? 'border-red-500 bg-red-500' :
                  bulkStockData[selectedUpdateSize]?.[name] !== undefined ? 'bg-blue-600 border-blue-600' :
                    'border-gray-300'">
                  <svg v-if="bulkStockData[selectedUpdateSize]?.[name] !== undefined" class="w-3 h-3 text-white"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                  </svg>
                  <XCircle
                    v-else-if="item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name && c.stock === 0)"
                    class="w-3 h-3 text-white" />
                </div>

                <div class="w-5 h-5 rounded-full border mr-2 shadow-inner relative" :style="{ backgroundColor: hex }">
                  <div
                    v-if="item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name && c.stock === 0)"
                    class="absolute -top-1 -right-1 w-3 h-3 bg-red-600 rounded-full border border-white"></div>
                </div>
                <label class="text-sm font-semibold cursor-pointer" :class="item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name && c.stock === 0)
                  ? 'text-red-700' : 'text-slate-700'">
                  {{ name }}
                  <span
                    v-if="item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name && c.stock === 0)"
                    class="text-[10px] text-red-500 ml-1">(KOSONG)</span>
                </label>
              </div>

              <div v-if="bulkStockData[selectedUpdateSize]?.[name] !== undefined" class="mt-2 w-full">
                <input type="number" v-model="bulkStockData[selectedUpdateSize][name]" placeholder="+ Stok"
                  class="w-full px-3 py-1.5 text-xs font-bold rounded-lg border-2 focus:border-blue-500 outline-none bg-white"
                  :class="item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name && c.stock === 0)
                    ? 'border-red-300 text-red-700 focus:border-red-500' :
                    'border-blue-200 text-blue-700'" @click.stop @focus="$event.target.select()" />
              </div>

              <div v-else-if="item.sizes?.find(s => s.size === selectedUpdateSize)?.colors?.find(c => c.color === name)"
                class="mt-1 text-[10px] text-gray-500">
                Stok saat ini: {{
                  item.sizes?.find(s => s.size === selectedUpdateSize)
                    ?.colors?.find(c => c.color === name)?.stock || 0
                }}
              </div>
            </div>
          </div>

          <div class="mt-10 flex flex-col md:flex-row items-center justify-between border-t border-gray-50 pt-6">
            <div class="text-sm text-gray-400 mb-4 md:mb-0">
              <span v-if="totalSelectedVariants > 0">
                Siap mengupdate <span class="font-bold text-blue-600">{{ totalSelectedVariants }} varian</span>
                di total <span class="font-bold text-blue-600">{{ Object.keys(bulkStockData).length }} ukuran</span>.
              </span>
              <span v-else-if="hasEmptyVariants">
                <span class="text-red-500 font-semibold">⚠️ Ada {{ stockStats?.emptyVariants }} varian kosong.</span>
                Pilih warna di setiap tab size untuk mengisi stok.
              </span>
              <span v-else>Pilih warna di setiap tab size untuk mengisi stok.</span>
            </div>

            <div class="flex space-x-3">
              <button @click="bulkStockData = {}"
                class="px-6 py-2 text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                Reset Semua
              </button>
              <button @click="handleRestock" :disabled="restockLoading || totalSelectedVariants === 0" :class="[
                'px-8 py-2.5 text-white text-sm font-bold rounded-xl shadow-lg transition-all',
                restockLoading ? 'bg-blue-400 cursor-not-allowed' :
                  hasEmptyVariants ? 'bg-red-600 hover:bg-red-700' :
                    'bg-blue-600 hover:bg-blue-700'
              ]">
                {{ restockLoading ? 'Memproses...' :
                  hasEmptyVariants ? `Restock Varian Kosong (${stockStats?.emptyVariants})` :
                    `Simpan Semua Perubahan (${totalSelectedVariants})`
                }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.4s ease-out;
}

.slide-up-enter-from {
  opacity: 0;
  transform: translateY(20px);
}

.slide-up-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

@keyframes pulse-red {

  0%,
  100% {
    opacity: 1;
  }

  50% {
    opacity: 0.7;
  }
}

.border-red-300 {
  border-color: #fca5a5;
}

.bg-red-50 {
  background-color: #fef2f2;
}
</style>