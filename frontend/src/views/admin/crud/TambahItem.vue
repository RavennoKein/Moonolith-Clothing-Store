<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { Save, ArrowLeft, Camera, ChevronDown, Package, AlertTriangle } from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';

const router = useRouter();

const categories = ref([]);
const loadingCategory = ref(false);

const sizeOptions = ['S', 'M', 'L', 'XL'];

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

const colorOptions = [
    { value: 'white', label: 'Putih', hex: '#FFFFFF' },
    { value: 'black', label: 'Hitam', hex: '#000000' },
    { value: 'navy', label: 'Navy', hex: '#001F3F' },
    { value: 'gray', label: 'Abu-abu', hex: '#6B7280' },
    { value: 'red', label: 'Merah', hex: '#EF4444' },
    { value: 'maroon', label: 'Maroon', hex: '#800000' },
    { value: 'blue', label: 'Biru', hex: '#1E40AF' },
    { value: 'green', label: 'Hijau', hex: '#065F46' },
    { value: 'army', label: 'Hijau Army', hex: '#4B5320' },
    { value: 'yellow', label: 'Kuning', hex: '#F59E0B' },
    { value: 'orange', label: 'Oranye', hex: '#F97316' },
    { value: 'pink', label: 'Pink', hex: '#EC4899' },
    { value: 'purple', label: 'Ungu', hex: '#9333EA' },
    { value: 'brown', label: 'Coklat', hex: '#A52A2A' },
    { value: 'khaki', label: 'Khaki', hex: '#C3B091' },
    { value: 'cream', label: 'Cream', hex: '#F5F5DC' }
];

const genderOptions = [
    { value: 'men', label: 'Men' },
    { value: 'women', label: 'Women' },
    { value: 'kids', label: 'Kids' },
    { value: 'unisex', label: 'Unisex' }
];

const tingkat_ketebalanOptions = [
    { value: 'tipis', label: 'Tipis' },
    { value: 'sedang', label: 'Sedang' },
    { value: 'tebal', label: 'Tebal' }
];

const showCategoryDropdown = ref(false);
const showThicknessDropdown = ref(false);
const showGenderDropdown = ref(false);

const bulkStockData = ref({});
const selectedUpdateSize = ref('S');
const restockLoading = ref(false);

const formData = reactive({
    name: '',
    description: '',
    price: '',
    category_id: null,
    bahan: '',
    tingkat_ketebalan: null,
    status_produk: 'aktif',
    gender: 'unisex',
    image: null,
    imagePreview: null,
});

const fetchCategories = async () => {
    loadingCategory.value = true;
    try {
        const res = await api.get('/admin/categories');
        categories.value = res.data.data;
    } finally {
        loadingCategory.value = false;
    }
};

onMounted(fetchCategories);

const selectedGender = computed(() =>
    genderOptions.find(g => g.value === formData.gender)
);

const selectedCategory = computed(() =>
    categories.value.find(c => c.id === formData.category_id)
);

const selectedThickness = computed(() =>
    tingkat_ketebalanOptions.find(t => t.value === formData.tingkat_ketebalan)
);

const totalStock = computed(() => {
    let total = 0;
    Object.values(bulkStockData.value).forEach(colors => {
        Object.values(colors).forEach(stock => {
            total += Number(stock) || 0;
        });
    });
    return total;
});

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

const variantsPayload = computed(() => {
    const variants = [];

    Object.entries(bulkStockData.value).forEach(([size, colors]) => {
        Object.entries(colors).forEach(([color, stock]) => {
            const stockNum = Number(stock);
            if (stockNum > 0) {
                variants.push({
                    color: color,
                    size: size,
                    stock: stockNum
                });
            }
        });
    });

    return variants;
});


const selectOption = (type, value) => {
    if (type === 'category') formData.category_id = value;
    if (type === 'tingkat_ketebalan') formData.tingkat_ketebalan = value;
    if (type === 'gender') formData.gender = value;
    closeAllDropdowns();
};

const clearSelection = (type) => {
    if (type === 'category') formData.category_id = null;
    if (type === 'tingkat_ketebalan') formData.tingkat_ketebalan = null;
    closeAllDropdowns();
};

const toggleDropdown = (type) => {
    showCategoryDropdown.value = type === 'category';
    showThicknessDropdown.value = type === 'tingkat_ketebalan';
    showGenderDropdown.value = type === 'gender';
};

const closeAllDropdowns = () => {
    showCategoryDropdown.value = false;
    showThicknessDropdown.value = false;
    showGenderDropdown.value = false;
};

const handleFileUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    formData.image = file;
    formData.imagePreview = URL.createObjectURL(file);
};

const removePhoto = () => {
    formData.image = null;
    formData.imagePreview = null;
};

const formatLabel = (text) => {
    if (!text) return '';
    return text.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

const handleSubmit = async () => {
    if (!formData.name || !formData.category_id || !formData.price) {
        return Swal.fire('Error', 'Nama, kategori, dan harga wajib diisi', 'error');
    }

    if (totalSelectedVariants.value === 0) {
        return Swal.fire('Error', 'Pilih minimal satu varian (warna & ukuran) dengan jumlah stok', 'error');
    }

    const fd = new FormData();
    fd.append('name', formData.name);
    fd.append('description', formData.description);
    fd.append('price', formData.price);
    fd.append('category_id', formData.category_id);
    fd.append('bahan', formData.bahan);
    fd.append('tingkat_ketebalan', formData.tingkat_ketebalan || '');
    fd.append('status_produk', formData.status_produk);
    fd.append('gender', formData.gender);

    if (formData.image) fd.append('image', formData.image);

    variantsPayload.value.forEach((v, i) => {
        fd.append(`variants[${i}][color]`, v.color);
        fd.append(`variants[${i}][size]`, v.size);
        fd.append(`variants[${i}][stock]`, v.stock);
    });

    try {
        restockLoading.value = true;
        Swal.fire({
            title: 'Menyimpan...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        await api.post('/admin/items', fd);

        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: 'Item berhasil ditambahkan',
            timer: 2000,
            showConfirmButton: false
        });

        router.push({ name: 'Item' });
    } catch (e) {
        Swal.fire('Gagal', e.response?.data?.message || 'Terjadi kesalahan', 'error');
    } finally {
        restockLoading.value = false;
    }
};

const goBack = () => {
    Swal.fire({
        title: 'Batalkan?',
        text: 'Data yang diisi akan hilang',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Kembali'
    }).then(res => {
        if (res.isConfirmed) router.push({ name: 'Item' });
    });
};

const resetForm = () => {
    formData.name = '';
    formData.description = '';
    formData.price = '';
    formData.category_id = null;
    formData.bahan = '';
    formData.tingkat_ketebalan = null;
    formData.status_produk = 'aktif';
    formData.image = null;
    formData.imagePreview = null;
    bulkStockData.value = {};
};

const getColorLabel = (colorValue) => {
    const color = colorOptions.find(c => c.value === colorValue);
    return color ? color.label : colorValue;
};

const getVariantsForSize = (size) => {
    if (!bulkStockData.value[size]) return [];

    return Object.entries(bulkStockData.value[size])
        .filter(([_, stock]) => Number(stock) > 0)
        .map(([color, stock]) => ({
            color,
            stock: Number(stock)
        }));
};

const getTotalStockForSize = (size) => {
    const variants = getVariantsForSize(size);
    return variants.reduce((total, variant) => total + variant.stock, 0);
};
</script>

<template>
    <div class="min-h-screen bg-linear-to-br from-gray-50 to-gray-100" @click="closeAllDropdowns">
        <div class="bg-linear-to-r from-blue-900 via-blue-800 to-blue-900 shadow-lg">
            <div class="px-8 py-6">
                <div class="flex items-center justify-between gap-6">
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-white tracking-tight">Tambah Data Item</h1>
                        <p class="text-blue-100 text-sm mt-1 opacity-90">Tambahkan produk baru yang akan muncul di
                            katalog toko Anda</p>
                    </div>
                    <div class="flex items-center">
                        <button @click="goBack"
                            class="group flex items-center px-5 py-2.5 bg-white/10 backdrop-blur-md text-white border border-white/20 rounded-xl hover:bg-white/20 transition-all duration-300 transform hover:scale-105 active:scale-95">
                            <ArrowLeft class="w-5 h-5 mr-2 group-hover:-translate-x-1 transition-transform" />
                            <span class="font-medium">Kembali</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-8">
            <div class="max-w-5xl mx-auto">
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <form @submit.prevent="handleSubmit" class="p-6 md:p-8 space-y-8">

                        <div class="space-y-6">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-bold">
                                    1</div>
                                <h3 class="text-lg font-bold text-gray-800">Informasi Dasar Produk</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="group">
                                    <label class="block mb-2 text-sm font-semibold text-gray-700">Nama Produk <span
                                            class="text-red-500">*</span></label>
                                    <input v-model="formData.name" type="text" required
                                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-hidden transition-all"
                                        placeholder="Contoh: Kaos Polos Premium">
                                </div>

                                <div class="group">
                                    <label class="block mb-2 text-sm font-semibold text-gray-700">Kategori <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative" @click.stop>
                                        <div @click="toggleDropdown('category')"
                                            class="w-full px-4 py-3.5 border border-gray-300 rounded-xl cursor-pointer flex items-center justify-between bg-white hover:border-blue-400"
                                            :class="{ 'ring-2 ring-blue-100 border-blue-500': showCategoryDropdown }">
                                            <span v-if="selectedCategory" class="text-gray-800 font-medium">
                                                {{ selectedCategory?.name }}
                                            </span>
                                            <span v-else class="text-gray-400">Pilih Kategori</span>
                                            <ChevronDown class="w-5 h-5 text-gray-400"
                                                :class="{ 'rotate-180': showCategoryDropdown }" />
                                        </div>

                                        <div v-if="showCategoryDropdown"
                                            class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-200 max-h-60 overflow-y-auto overflow-x-hidden">
                                            <div v-for="cat in categories" :key="cat.id"
                                                @click="selectOption('category', cat.id)"
                                                class="p-4 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                                <p class="font-semibold text-gray-800">{{ cat.name }}</p>
                                            </div>
                                            <div v-if="formData.category_id" @click="clearSelection('category')"
                                                class="p-3 text-center text-red-500 text-sm font-medium hover:bg-red-50 cursor-pointer">
                                                Hapus Pilihan</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="group">
                                    <label class="block mb-2 text-sm font-semibold text-gray-700">Gender <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative" @click.stop>
                                        <div @click="toggleDropdown('gender')"
                                            class="w-full px-4 py-3.5 border border-gray-300 rounded-xl cursor-pointer flex items-center justify-between bg-white hover:border-blue-400 transition-all"
                                            :class="{ 'ring-2 ring-blue-100 border-blue-500': showGenderDropdown }">
                                            <span class="text-gray-800 font-medium">
                                                {{ selectedGender?.label || 'Pilih Gender' }}
                                            </span>
                                            <ChevronDown class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                                :class="{ 'rotate-180': showGenderDropdown }" />
                                        </div>

                                        <div v-if="showGenderDropdown"
                                            class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden animate-fadeIn">
                                            <div v-for="gen in genderOptions" :key="gen.value"
                                                @click="selectOption('gender', gen.value)"
                                                class="p-4 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                                <p class="font-semibold text-gray-800">{{ gen.label }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="group">
                                <label class="block mb-2 text-sm font-semibold text-gray-700">Deskripsi Produk <span
                                        class="text-red-500">*</span></label>
                                <textarea v-model="formData.description" rows="4" required
                                    class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-hidden transition-all"
                                    placeholder="Jelaskan detail produk..."></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="group">
                                    <label class="block mb-2 text-sm font-semibold text-gray-700">Harga (IDR) <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-3.5 text-gray-500">Rp</span>
                                        <input v-model.number="formData.price" type="number" required
                                            class="w-full pl-12 pr-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-hidden"
                                            placeholder="0">
                                    </div>
                                </div>

                                <div class="group">
                                    <label class="block mb-2 text-sm font-semibold text-gray-700">Status Produk</label>
                                    <div class="flex gap-3">
                                        <label v-for="status in ['aktif', 'non_aktif', 'terbatas']" :key="status"
                                            class="flex-1 flex items-center justify-center p-3.5 border rounded-xl cursor-pointer transition-all text-sm font-medium"
                                            :class="formData.status_produk === status ? 'bg-blue-600 border-blue-600 text-white shadow-md' : 'bg-white border-gray-200 text-gray-600 hover:border-blue-300'">
                                            <input type="radio" :value="status" v-model="formData.status_produk"
                                                class="hidden">
                                            {{ formatLabel(status) }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-bold">
                                    2</div>
                                <h3 class="text-lg font-bold text-gray-800">Foto Produk</h3>
                            </div>
                            <div class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-gray-300 rounded-2xl bg-gray-50 hover:bg-blue-50 transition-all cursor-pointer"
                                @click="$refs.fileInput.click()">
                                <input type="file" ref="fileInput" @change="handleFileUpload" accept="image/*"
                                    class="hidden">

                                <div v-if="formData.imagePreview" class="text-center">
                                    <img :src="formData.imagePreview"
                                        class="mx-auto h-48 w-48 object-cover rounded-xl shadow-md mb-4">
                                    <button @click.stop="removePhoto" type="button"
                                        class="text-red-600 font-medium hover:underline">Hapus & Ganti Foto</button>
                                </div>
                                <div v-else class="text-center">
                                    <Camera class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                                    <p class="text-blue-600 font-medium">Klik untuk upload foto</p>
                                    <p class="text-xs text-gray-400 mt-1">Maksimal ukuran file: 2MB</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-bold">
                                    3</div>
                                <h3 class="text-lg font-bold text-gray-800">Detail Tambahan</h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="group">
                                    <label class="block mb-2 text-sm font-semibold text-gray-700">Bahan Produk</label>
                                    <input v-model="formData.bahan" type="text"
                                        class="w-full px-4 py-3.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 outline-hidden transition-all"
                                        placeholder="Contoh: Cotton Combed 30s">
                                </div>

                                <div class="group">
                                    <label class="block mb-2 text-sm font-semibold text-gray-700">Tingkat
                                        Ketebalan</label>
                                    <div class="relative" @click.stop>
                                        <div @click="toggleDropdown('tingkat_ketebalan')"
                                            class="w-full px-4 py-3.5 border border-gray-300 rounded-xl cursor-pointer flex items-center justify-between bg-white hover:border-blue-400 transition-all"
                                            :class="{ 'ring-2 ring-blue-100 border-blue-500': showThicknessDropdown }">
                                            <span v-if="formData.tingkat_ketebalan" class="text-gray-800 font-medium">
                                                {{ selectedThickness?.label }}
                                            </span>
                                            <span v-else class="text-gray-400">Pilih Ketebalan</span>
                                            <ChevronDown class="w-5 h-5 text-gray-400 transition-transform duration-300"
                                                :class="{ 'rotate-180': showThicknessDropdown }" />
                                        </div>

                                        <div v-if="showThicknessDropdown"
                                            class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-2xl border border-gray-100 overflow-hidden animate-fadeIn">
                                            <div v-for="opt in tingkat_ketebalanOptions" :key="opt.value"
                                                @click="selectOption('tingkat_ketebalan', opt.value)"
                                                class="p-4 hover:bg-blue-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors">
                                                <p class="font-semibold text-gray-800">{{ opt.label }}</p>
                                            </div>
                                            <div v-if="formData.tingkat_ketebalan"
                                                @click="clearSelection('tingkat_ketebalan')"
                                                class="p-3 text-center text-red-500 text-xs font-bold hover:bg-red-50 cursor-pointer uppercase tracking-wider">
                                                Hapus Pilihan
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                      <div class="mt-8">
                            <h4 class="text-lg font-bold text-slate-800 mb-4 flex items-center">
                                <span class="w-1.5 h-6 bg-blue-600 rounded-full mr-3"></span>
                                Ringkasan Stok per Ukuran
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="size in sizeOptions" :key="size"
                                    class="p-4 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-center mb-3 border-b border-gray-50 pb-2">
                                        <p class="text-xs text-gray-400 font-black uppercase">Size {{ size }}</p>
                                        <p class="text-[12px] font-bold text-slate-700">
                                            {{ getTotalStockForSize(size) || 0 }} <span
                                                class="text-sm font-normal text-gray-400">Unit</span>
                                        </p>
                                    </div>

                                    <div v-if="getVariantsForSize(size).length > 0" class="space-y-2">
                                        <div v-for="variant in getVariantsForSize(size)" :key="variant.color"
                                            class="flex justify-between items-center bg-gray-50 p-2 rounded-lg">
                                            <div class="flex items-center">
                                                <span class="w-4 h-4 rounded-full border border-gray-200 mr-2 shadow-sm"
                                                    :style="{ backgroundColor: colorPalette[variant.color.toLowerCase()] || '#e5e7eb' }"></span>
                                                <span class="text-xs text-gray-600 font-medium capitalize">{{
                                                    variant.color }}</span>
                                            </div>
                                            <span class="text-xs font-black text-slate-800">{{ variant.stock
                                            }}</span>
                                        </div>
                                    </div>
                                    <div v-else class="text-center py-4">
                                        <p class="text-xs text-gray-400">Belum ada stok untuk size ini</p>
                                        <p class="text-[10px] text-gray-300 mt-1">Pilih warna di atas untuk menambah
                                            stok</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 bg-blue-600 text-white rounded-full text-sm font-bold">
                                        4</div>
                                    <h3 class="text-lg font-bold text-gray-800">Tambah Stok (Semua Ukuran)</h3>
                                </div>
                                <div class="px-4 py-2 bg-blue-100 text-blue-800 rounded-lg font-bold">Total: {{
                                    totalStock }} pcs</div>
                            </div>

                            <div class="flex items-center space-x-2 mb-6 bg-gray-100 p-1.5 rounded-2xl w-fit">
                                <button v-for="size in sizeOptions" :key="size" type="button"
                                    @click="selectedUpdateSize = size"
                                    :class="[
                                    'px-6 py-2 rounded-xl text-sm font-black transition-all duration-200 flex items-center gap-2',
                                    selectedUpdateSize === size ? 'bg-white text-blue-600 shadow-sm scale-105' :
                                    'text-gray-400 hover:text-gray-600'
                                    ]">
                                    SIZE {{ size }}
                                    <span v-if="bulkStockData[size]"
                                        class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                                </button>
                            </div>

                            <div :key="selectedUpdateSize"
                                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div v-for="(hex, name) in colorPalette" :key="`${selectedUpdateSize}-${name}`" :class="[
                                    'relative flex flex-wrap items-center p-3 rounded-xl border transition-all duration-200 cursor-pointer select-none',
                                    bulkStockData[selectedUpdateSize]?.[name] !== undefined ? 'border-blue-500 bg-blue-50 shadow-sm' : 'border-gray-200 hover:border-gray-300'
                                ]">

                                    <div class="flex items-center flex-1 min-w-30"
                                        @click="toggleColorInput(selectedUpdateSize, name)">
                                        <div class="w-5 h-5 rounded border border-gray-300 mr-3 flex items-center justify-center transition-colors"
                                            :class="{ 'bg-blue-600 border-blue-600': bulkStockData[selectedUpdateSize]?.[name] !== undefined }">
                                            <svg v-if="bulkStockData[selectedUpdateSize]?.[name] !== undefined"
                                                class="w-3 h-3 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                        </div>

                                        <div class="w-5 h-5 rounded-full border border-gray-200 mr-2 shadow-inner"
                                            :style="{ backgroundColor: hex }"></div>
                                        <label class="text-sm font-semibold text-slate-700 capitalize cursor-pointer">{{
                                            name }}</label>
                                    </div>

                                    <div v-if="bulkStockData[selectedUpdateSize]?.[name] !== undefined"
                                        class="mt-2 w-full">
                                        <input type="number" v-model="bulkStockData[selectedUpdateSize][name]"
                                            placeholder="+ Stok"
                                            class="w-full px-3 py-1.5 text-xs font-bold rounded-lg border-2 border-blue-200 focus:border-blue-500 outline-none text-blue-700 bg-white"
                                            @click.stop
                                        @focus="$event.target.select()" />
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-6 flex flex-col md:flex-row items-center justify-between border-t border-gray-50 pt-6">
                                <div class="text-sm text-gray-400 mb-4 md:mb-0">
                                    <span v-if="totalSelectedVariants > 0">
                                        Siap menyimpan <span class="font-bold text-blue-600">{{ totalSelectedVariants }}
                                            varian</span>
                                        di total <span class="font-bold text-blue-600">{{
                                            Object.keys(bulkStockData).length }} ukuran</span>.
                                    </span>
                                    <span v-else>Pilih warna di setiap tab size untuk mengisi stok.</span>
                                </div>

                                <div class="flex space-x-3">
                                    <button @click="bulkStockData = {}" type="button"
                                        class="px-6 py-2 text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors">
                                        Reset Semua
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex flex-col sm:flex-row justify-end space-y-4 sm:space-y-0 sm:space-x-4 pt-8 border-t">
                            <button type="button" @click="resetForm"
                                class="px-7 py-3.5 border border-gray-300 text-gray-700 rounded-xl hover:bg-gray-50 transition-all font-medium">
                                Reset Form
                            </button>
                            <button type="submit" :disabled="restockLoading"
                                class="group px-10 py-3.5 bg-blue-700 text-white font-bold rounded-xl shadow-lg hover:bg-blue-800 transition-all flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                                <Save class="w-5 h-5 mr-2" />
                                {{ restockLoading ? 'Menyimpan...' : 'Simpan Data Produk' }}
                            </button>
                        </div>
                    </form>
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

input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
    opacity: 1;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fadeIn {
    animation: fadeIn 0.2s ease-out forwards;
}
</style>