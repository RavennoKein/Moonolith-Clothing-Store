<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Sidebar from '../../components/Sidebar.vue';
import { Plus, Eye, AlertTriangle, Zap, Package, Box, XCircle, ChevronRight } from 'lucide-vue-next';
import api from '@/services/api';

const router = useRouter();
const activeRoute = ref('data-item');
const isSidebarOpen = ref(true);

const handleSidebarToggle = (status) => { isSidebarOpen.value = status; };

const getColorHex = (colorValue) => ({
    'white': '#FFFFFF',
    'black': '#000000',
    'navy': '#001F3F',
    'gray': '#6B7280',
    'red': '#EF4444',
    'blue': '#1E40AF',
    'green': '#065F46',
    'yellow': '#F59E0B',
    'pink': '#EC4899',
    'maroon': '#800000',
    'khaki': '#C3B091',
    'army': '#4B5320',
    'cream': '#F5F5DC',
    'brown': '#A52A2A',
    'purple': '#9333EA',
    'orange': '#F97316'
}[colorValue] || '#9CA3AF');

const rawItems = ref([]);

const fetchItems = async () => {
    const res = await api.get('/admin/items');
    rawItems.value = res.data.data.map(mapItemForIndex);
};

onMounted(() => {
    fetchItems();
});

const mapItemForIndex = (item) => {
    const sizesMap = {};
    let totalStock = 0;
    let emptyVariantsCount = 0;
    let isCompletelyOutOfStock = true; 

    item.variants.forEach(v => {
        totalStock += v.stock;

        if (v.stock > 0) {
            isCompletelyOutOfStock = false;
        }

        if (v.stock === 0) {
            emptyVariantsCount++;
        }

        if (!sizesMap[v.size]) {
            sizesMap[v.size] = { size: v.size, colors: [] };
        }

        sizesMap[v.size].colors.push({
            color: v.color,
            stock: v.stock
        });
    });

    return {
        id: item.id,
        name: item.name,
        description: item.description,
        price: item.price,
        category: typeof item.category === 'object' ? item.category.name : item.category,
        status: item.status_produk,
        createdAt: item.created_at,
        totalStock,
        sizes: Object.values(sizesMap),
        is_flashsale: item.is_flashsale === true,
        emptyVariantsCount,
        isCompletelyOutOfStock,
        highlightLevel: isCompletelyOutOfStock ? 'critical' :
            emptyVariantsCount > 0 ? 'warning' :
                totalStock < 25 ? 'low' : 'normal'
    };
};

const isAnyFlashsaleActive = computed(() =>
    rawItems.value.some(item => item.is_flashsale === true)
);

const itemsPerPage = ref(5);
const searchTerm = ref('');
const currentPage = ref(1);
const sortBy = ref('id');
const filterStock = ref('all');

const formatRupiah = (number) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(number);

const hasEmptyVariant = (item) => item.emptyVariantsCount > 0;
const isCompletelyOutOfStock = (item) => item.isCompletelyOutOfStock;
const isLowStock = (item) => item.totalStock < 25 && !hasEmptyVariant(item);

const getRowColorClass = (item) => {
    if (isCompletelyOutOfStock(item)) return 'bg-red-50 hover:bg-red-100 border-l-4 border-red-600';
    if (hasEmptyVariant(item)) return 'bg-red-50/80 hover:bg-red-100 border-l-4 border-red-500';
    if (isLowStock(item)) return 'bg-yellow-50 hover:bg-yellow-100 border-l-4 border-yellow-500';
    return 'hover:bg-gray-50';
};

const getBadgeForItem = (item) => {
    if (isCompletelyOutOfStock(item)) {
        return {
            text: 'STOK KOSONG',
            class: 'bg-red-600 text-white animate-pulse shadow-lg',
            icon: XCircle
        };
    }
    if (hasEmptyVariant(item)) {
        return {
            text: `VARIAN KOSONG (${item.emptyVariantsCount})`,
            class: 'bg-red-500 text-white',
            icon: AlertTriangle
        };
    }
    if (isLowStock(item)) {
        return {
            text: 'STOK MENIPIS',
            class: 'bg-yellow-500 text-white',
            icon: AlertTriangle
        };
    }
    return null;
};

const filteredItems = computed(() => {
    let items = [...rawItems.value];

    if (filterStock.value === 'empty') {
        items = items.filter(item => isCompletelyOutOfStock(item));
    } else if (filterStock.value === 'partial') {
        items = items.filter(item => hasEmptyVariant(item) && !isCompletelyOutOfStock(item));
    } else if (filterStock.value === 'low') {
        items = items.filter(item => isLowStock(item));
    } else if (filterStock.value === 'safe') {
        items = items.filter(item => !hasEmptyVariant(item) && item.totalStock >= 25);
    }

    const term = searchTerm.value.toLowerCase();
    if (term) {
        items = items.filter(item =>
            item.name.toLowerCase().includes(term) ||
            item.category?.toLowerCase().includes(term) ||
            item.price.toString().includes(term)
        );
    }

    if (sortBy.value === 'stock_asc') {
        items.sort((a, b) => a.totalStock - b.totalStock);
    } else if (sortBy.value === 'stock_desc') {
        items.sort((a, b) => b.totalStock - a.totalStock);
    } else if (sortBy.value === 'critical_first') {
        items.sort((a, b) => {
            const priority = { 'critical': 0, 'warning': 1, 'low': 2, 'normal': 3 };
            return priority[a.highlightLevel] - priority[b.highlightLevel] || a.id - b.id;
        });
    } else if (sortBy.value === 'id') {
        items.sort((a, b) => a.id - b.id);
    }

    return items;
});

const emptyStockCount = computed(() => rawItems.value.filter(item => isCompletelyOutOfStock(item)).length);
const partialEmptyCount = computed(() => rawItems.value.filter(item => hasEmptyVariant(item) && !isCompletelyOutOfStock(item)).length);
const lowStockCount = computed(() => rawItems.value.filter(item => isLowStock(item)).length);

const criticalItemsList = computed(() =>
    rawItems.value.filter(item => isCompletelyOutOfStock(item) || hasEmptyVariant(item))
);

const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredItems.value.slice(start, end);
});

const totalPages = computed(() => Math.ceil(filteredItems.value.length / itemsPerPage.value));

const pageInfo = computed(() => {
    const total = filteredItems.value.length;
    if (total === 0) return 'Showing 0 to 0 of 0 entries';
    const start = (currentPage.value - 1) * itemsPerPage.value + 1;
    const end = Math.min(start + itemsPerPage.value - 1, total);
    return `Showing ${start} to ${end} of ${total} entries`;
});

const changePage = (page) => { if (page >= 1 && page <= totalPages.value) { currentPage.value = page; } };

const handleAdd = () => router.push({ name: 'TambahItem' });
const handleFlashsale = () => { router.push({ name: 'Flashsale' }); };
const handleDetail = (id) => router.push({ name: 'DetailItem', params: { id } });
const isAlertOpen = ref(false);

const showLowStockAlert = () => {
    if (criticalItemsList.value.length > 0) {
        isAlertOpen.value = true;
    }
};

const getEmptyVariantsInfo = (item) => {
    const empty = [];
    item.sizes.forEach(s => {
        s.colors.forEach(c => {
            if (c.stock === 0) {
                empty.push(`${s.size} (${c.color})`);
            }
        });
    });
    return empty.join(', ');
};

const goToItemDetail = (id) => {
    isAlertOpen.value = false;
    router.push({ name: 'DetailItem', params: { id } });
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar :current-route="activeRoute" @toggle="handleSidebarToggle" />
        <div
            :class="['grow bg-gray-100 p-8 pt-10 transition-all duration-300 w-full', isSidebarOpen ? 'ml-70' : 'ml-0']">

            <div class="flex justify-between items-center mb-8">
                <div class="flex items-center gap-4">
                    <h1 class="text-3xl font-bold text-slate-800">Data Item</h1>
                    <div v-if="emptyStockCount > 0 || partialEmptyCount > 0" @click="showLowStockAlert"
                        class="group flex items-center gap-2 px-4 py-2 bg-linear-to-r from-red-500 to-red-600 text-white rounded-lg shadow-lg cursor-pointer hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200">
                        <div class="relative">
                            <AlertTriangle class="w-5 h-5 animate-pulse" />
                            <span v-if="emptyStockCount > 0"
                                class="absolute -top-1 -right-1 w-4 h-4 bg-white text-red-600 text-[10px] font-bold rounded-full flex items-center justify-center">
                                {{ emptyStockCount }}
                            </span>
                        </div>
                        <div class="text-sm font-semibold">
                            <span v-if="emptyStockCount > 0">{{ emptyStockCount }} item STOK HABIS</span>
                            <span v-if="emptyStockCount > 0 && partialEmptyCount > 0"> • </span>
                            <span v-if="partialEmptyCount > 0">{{ partialEmptyCount }} item ada varian kosong</span>
                        </div>
                        <ChevronRight class="w-4 h-4 opacity-80 group-hover:translate-x-1 transition-transform" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="mb-6 flex justify-between">
                    <div class="flex items-center justify-center">
                        <button @click="handleAdd"
                            class="group inline-flex items-center px-4 py-3 bg-linear-to-r from-blue-600 to-blue-800 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:from-blue-700 hover:to-blue-900 transition-all duration-300 transform hover:-translate-y-0.5">
                            <Plus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" /> Tambah
                            Data Item
                        </button>
                    </div>
                    <div class="flex items-center flex-col">
                        <div class="relative group">
                            <button @click="handleFlashsale"
                                class="group inline-flex items-center px-4 py-3 bg-linear-to-r from-amber-500 to-orange-600 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:from-amber-600 hover:to-orange-700 transition-all duration-300 transform hover:-translate-y-0.5">
                                <Zap class="w-5 h-5 mr-2 fill-current" />
                                Manajemen Flashsale
                            </button>

                            <span v-if="isAnyFlashsaleActive" class="absolute -top-2 -right-2 flex h-5 w-5">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span
                                    class="relative inline-flex rounded-full h-5 w-5 bg-red-500 text-[10px] items-center justify-center text-white font-bold">!</span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="p-4 bg-gray-50 rounded-lg border">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Filter Status Stok</h3>
                        <select v-model="filterStock"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 hover:border-blue-400 transition-all duration-200 cursor-pointer">
                            <option value="all">Semua Stok</option>
                            <option value="empty">Stok Habis Total</option>
                            <option value="partial">Ada Varian Kosong</option>
                            <option value="low">Stok Menipis (&lt; 25)</option>
                            <option value="safe">Stok Aman</option>
                        </select>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg border">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Urutkan Berdasarkan</h3>
                        <select v-model="sortBy"
                            class="w-full p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 hover:border-blue-400 transition-all duration-200 cursor-pointer">
                            <option value="critical_first">Prioritas Masalah Stok</option>
                            <option value="id">ID Produk</option>
                            <option value="stock_asc">Stok Terendah ke Tertinggi</option>
                            <option value="stock_desc">Stok Tertinggi ke Terendah</option>
                        </select>
                    </div>

                    <div class="p-4 bg-gray-50 rounded-lg border">
                        <h3 class="text-sm font-medium text-gray-700 mb-2">Statistik Stok</h3>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Stok Habis Total:</span>
                                <span class="px-2 py-1 bg-red-600 text-white rounded text-xs font-bold shadow-sm">
                                    {{ emptyStockCount }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Ada Varian Kosong:</span>
                                <span class="px-2 py-1 bg-red-500 text-white rounded text-xs font-bold shadow-sm">
                                    {{ partialEmptyCount }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Stok Menipis:</span>
                                <span class="px-2 py-1 bg-yellow-500 text-white rounded text-xs font-bold shadow-sm">
                                    {{ lowStockCount }}
                                </span>
                            </div>
                            <div class="flex justify-between items-center border-t pt-2">
                                <span class="text-sm font-medium">Total Item:</span>
                                <span class="px-2 py-1 bg-blue-600 text-white rounded text-xs font-bold shadow-sm">
                                    {{ rawItems.length }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center mb-4 space-y-3 md:space-y-0">
                    <div class="flex items-center space-x-2 text-gray-600">
                        <select v-model.number="itemsPerPage"
                            class="group p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 hover:border-blue-400 hover:shadow-sm transition-all duration-200 cursor-pointer">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option :value="rawItems.length">All</option>
                        </select>
                        <span class="text-sm">entries per page</span>
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="search" class="sr-only">Search</label>
                        <input type="text" id="search" v-model="searchTerm" placeholder="Cari item..."
                            class="group w-full md:w-64 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-400 hover:shadow-sm">
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-xs font-medium text-gray-700 uppercase tracking-wider w-12 text-center">
                                    No
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-64">
                                    Nama Produk
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-32">
                                    Kategori
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-32">
                                    Harga
                                </th>
                                <th
                                    class="px-4 py-3 text-xs font-medium text-gray-700 uppercase tracking-wider w-28 text-center">
                                    Stok Total
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-80">
                                    Stok per Size & Warna
                                </th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider w-24">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="paginatedItems.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <Box class="w-10 h-10 mb-2 opacity-20" />
                                        <p>Tidak ada data item ditemukan.</p>
                                    </div>
                                </td>
                            </tr>

                            <tr v-for="(item, index) in paginatedItems" :key="item.id" :class="getRowColorClass(item)"
                                class="transition-colors duration-150">

                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center">
                                    {{ (currentPage - 1) * itemsPerPage + index + 1 }}
                                </td>

                                <td class="px-4 py-4">
                                    <div class="font-medium text-gray-900">{{ item.name }}</div>
                                    <div class="flex flex-wrap items-center gap-2 mt-1">
                                        <span :class="{
                                            'bg-green-100 text-green-800': item.status === 'aktif',
                                            'bg-yellow-100 text-yellow-800': item.status === 'terbatas',
                                            'bg-red-100 text-red-800': item.status === 'non_aktif'
                                        }"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider">
                                            {{ item.status === 'aktif' ? 'Aktif' : item.status === 'terbatas' ?
                                                'Terbatas' : 'Nonaktif' }}
                                        </span>

                                        <template v-if="getBadgeForItem(item)">
                                            <component :is="getBadgeForItem(item).icon" class="w-3 h-3 mr-1" />
                                            <span
                                                :class="['flex items-center text-[10px] px-2 py-0.5 rounded-full font-bold', getBadgeForItem(item).class]">
                                                {{ getBadgeForItem(item).text }}
                                            </span>
                                        </template>

                                        <span v-if="item.is_flashsale"
                                            class="flex items-center px-2.5 py-0.5 text-[10px] font-black bg-linear-to-r from-amber-500 to-orange-500 text-white rounded-md shadow-sm animate-pulse">
                                            <Zap class="w-3 h-3 mr-1 fill-current" /> FLASHSALE
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-medium text-gray-700 capitalize">{{ item.category || 'Tanpa Kategori' }}</div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="text-sm font-bold text-indigo-700">{{ formatRupiah(item.price) }}</div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <span :class="[
                                            'text-sm font-bold',
                                            isCompletelyOutOfStock(item) ? 'text-red-600 animate-pulse' :
                                                hasEmptyVariant(item) ? 'text-red-500' :
                                                    isLowStock(item) ? 'text-yellow-600' : 'text-gray-900'
                                        ]">
                                            {{ item.totalStock }}
                                        </span>
                                        <span v-if="item.emptyVariantsCount > 0"
                                            class="text-[10px] text-red-500 font-semibold mt-1">
                                            ({{ item.emptyVariantsCount }} varian kosong)
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <div v-for="sizeGroup in item.sizes" :key="sizeGroup.size" :class="['rounded-lg p-2 border transition-colors',
                                            sizeGroup.colors.every(c => c.stock === 0) ? 'bg-red-100 border-red-300' :
                                                sizeGroup.colors.some(c => c.stock === 0) ? 'bg-red-50/80 border-red-200' :
                                                    'bg-gray-50 border-gray-200']">

                                            <div
                                                class="flex items-center justify-between border-b border-gray-200 pb-1 mb-1">
                                                <span :class="['text-[11px] font-black',
                                                    sizeGroup.colors.every(c => c.stock === 0) ? 'text-red-700' :
                                                        sizeGroup.colors.some(c => c.stock === 0) ? 'text-red-600' :
                                                            'text-gray-700']">
                                                    SIZE {{ sizeGroup.size }}
                                                </span>
                                                <span v-if="sizeGroup.colors.some(c => c.stock === 0)"
                                                    class="text-[10px] text-red-600 font-bold">
                                                    {{sizeGroup.colors.filter(c => c.stock === 0).length}} kosong
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap gap-1">
                                                <div v-for="c in sizeGroup.colors" :key="c.color" :class="['flex items-center rounded border px-1.5 py-0.5 shadow-sm',
                                                    c.stock === 0 ? 'bg-red-200 border-red-400 relative' :
                                                        'bg-white border-gray-300']"
                                                    :title="`${c.color}: ${c.stock} unit`">

                                                    <div v-if="c.stock === 0"
                                                        class="absolute -top-1 -right-1 w-3 h-3 bg-red-600 rounded-full border border-white">
                                                    </div>

                                                    <span class="w-2 h-2 rounded-full mr-1"
                                                        :style="{ backgroundColor: getColorHex(c.color) }"></span>
                                                    <span
                                                        :class="['text-[10px] font-bold',
                                                            c.stock === 0 ? 'text-red-700 line-through' : 'text-gray-800']">
                                                        {{ c.stock === 0 ? '0' : c.stock }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <button @click="handleDetail(item.id)" :class="[
                                        'group w-full flex items-center justify-center px-3 py-2 text-xs font-bold rounded-lg transition-all shadow-md active:scale-95',
                                        isCompletelyOutOfStock(item) ? 'bg-red-600 hover:bg-red-700 text-white' :
                                            hasEmptyVariant(item) ? 'bg-red-500 hover:bg-red-600 text-white' :
                                                'bg-indigo-600 hover:bg-indigo-700 text-white'
                                    ]">
                                        <Eye class="w-3.5 h-3.5 mr-1.5" /> DETAIL
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center mt-6 space-y-3 md:space-y-0">
                    <p class="text-sm text-gray-600">{{ pageInfo }}</p>
                    <div class="flex items-center space-x-1">
                        <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                            class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:border-gray-300 disabled:hover:text-gray-600">
                            <span
                                class="group-hover:-translate-x-0.5 transition-transform duration-200 inline-block">«</span>
                        </button>
                        <div v-for="page in totalPages" :key="page">
                            <button @click="changePage(page)"
                                :class="{ 'group bg-linear-to-r from-blue-600 to-blue-800 text-white font-semibold shadow-md hover:shadow-lg hover:-translate-y-0.5': page === currentPage, 'group bg-white text-gray-700 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 hover:shadow-sm': page !== currentPage }"
                                class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200">{{ page
                                }}</button>
                        </div>
                        <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
                            class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-white disabled:hover:border-gray-300 disabled:hover:text-gray-600">
                            <span
                                class="group-hover:translate-x-0.5 transition-transform duration-200 inline-block">»</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <Transition name="fade">
            <div v-if="isAlertOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
                    <div class="bg-linear-to-r from-red-500 to-red-600 p-6 text-white">
                        <div class="flex items-center">
                            <div class="bg-white/20 p-3 rounded-full mr-4">
                                <AlertTriangle class="w-8 h-8" />
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold">Perhatian Stok!</h3>
                                <p class="text-white/90">Item yang memerlukan perhatian segera</p>
                            </div>
                            <div class="ml-auto flex items-center gap-4">
                                <div class="text-center">
                                    <div class="text-3xl font-bold">{{ emptyStockCount }}</div>
                                    <div class="text-xs opacity-90">STOK HABIS</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-3xl font-bold">{{ partialEmptyCount }}</div>
                                    <div class="text-xs opacity-90">ADA VARIAN KOSONG</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <div class="space-y-4">
                            <div v-if="emptyStockCount > 0">
                                <h4 class="font-bold text-red-700 mb-2 flex items-center">
                                    <XCircle class="w-4 h-4 mr-2" /> Stok Habis Total ({{ emptyStockCount }})
                                </h4>
                                <div class="space-y-2">
                                    <div v-for="item in rawItems.filter(i => isCompletelyOutOfStock(i))" :key="item.id"
                                        class="p-4 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition-colors cursor-pointer"
                                        @click="goToItemDetail(item.id)">
                                        <div class="flex justify-between items-center">
                                            <div>
                                                <div class="font-bold text-red-800">{{ item.name }}</div>
                                                <div class="text-sm text-red-600 mt-1">ID: #{{ item.id }}</div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-red-700 font-bold text-lg">STOK: 0</div>
                                                <div class="text-xs text-red-500">Klik untuk restock</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div v-if="partialEmptyCount > 0">
                                <h4 class="font-bold text-orange-600 mb-2 flex items-center">
                                    <AlertTriangle class="w-4 h-4 mr-2" /> Ada Varian Kosong ({{ partialEmptyCount }})
                                </h4>
                                <div class="space-y-3">
                                    <div v-for="item in rawItems.filter(i => hasEmptyVariant(i) && !isCompletelyOutOfStock(i))"
                                        :key="item.id"
                                        class="p-4 bg-orange-50 border border-orange-200 rounded-xl hover:bg-orange-100 transition-colors cursor-pointer"
                                        @click="goToItemDetail(item.id)">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="font-bold text-orange-800">{{ item.name }}</div>
                                                <div class="text-sm text-orange-600 mt-1">Total Stok: {{ item.totalStock
                                                    }}
                                                </div>
                                                <div class="mt-2">
                                                    <div class="text-xs font-semibold text-orange-700 mb-1">Varian
                                                        Kosong:</div>
                                                    <div class="flex flex-wrap gap-1">
                                                        <span v-for="s in item.sizes" :key="s.size">
                                                            <span v-for="c in s.colors" :key="c.color">
                                                                <span v-if="c.stock === 0"
                                                                    class="inline-flex items-center px-2 py-1 bg-white border border-orange-300 text-orange-700 text-xs rounded-md capitalize">
                                                                    <span class="w-2 h-2 rounded-full mr-1"
                                                                        :style="{ backgroundColor: getColorHex(c.color) }"></span>
                                                                    {{ s.size }} - {{ c.color }}
                                                                </span>
                                                            </span>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button @click.stop="goToItemDetail(item.id)"
                                                class="ml-4 px-3 py-1.5 bg-orange-600 text-white text-xs font-bold rounded-lg hover:bg-orange-700 transition-colors">
                                                Restock
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-4 bg-gray-50 flex justify-between items-center">
                        <button @click="isAlertOpen = false"
                            class="px-6 py-2.5 bg-gray-800 text-white font-bold rounded-xl hover:bg-black transition-all active:scale-95 shadow-lg">
                            Tutup
                        </button>
                        <div class="text-sm text-gray-600">
                            Total: <span class="font-bold">{{ emptyStockCount + partialEmptyCount }}</span> item perlu
                            perhatian
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
@keyframes shake {

    0%,
    100% {
        transform: translateX(0) rotate(0);
    }

    25% {
        transform: translateX(-1px) rotate(-3deg);
    }

    50% {
        transform: translateX(1px) rotate(3deg);
    }

    75% {
        transform: translateX(-1px) rotate(-3deg);
    }
}

.shake-animation {
    display: inline-block;
}

.group:hover .shake-animation {
    animation: shake 0.4s ease-in-out;
}

button,
select,
input,
.group {
    transition-property: all;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

select:hover,
input:hover {
    border-color: #60a5fa;
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
}

button[class*="from-blue"]:hover {
    box-shadow: 0 6px 12px -2px rgba(37, 99, 235, 0.3);
}

button:disabled {
    cursor: not-allowed;
}

button:disabled:hover {
    transform: none !important;
    box-shadow: none !important;
    background: white !important;
}

button[class*="from-blue-600"]:hover:not(:disabled) {
    box-shadow: 0 10px 20px -3px rgba(30, 58, 138, 0.4);
}

.bg-white.text-gray-700:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1);
}

.w-12 {
    width: 3rem;
}

.w-20 {
    width: 5rem;
}

.w-24 {
    width: 6rem;
}

.w-28 {
    width: 7rem;
}

.w-32 {
    width: 8rem;
}

.w-48 {
    width: 12rem;
}

.w-64 {
    width: 16rem;
}

.w-80 {
    width: 20rem;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {

    0%,
    100% {
        opacity: 1;
    }

    50% {
        opacity: 0.5;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.fade-enter-active .transform {
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.fade-enter-from .transform {
    transform: scale(0.9) translateY(20px);
}
</style>