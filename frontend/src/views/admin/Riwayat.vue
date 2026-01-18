<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import Sidebar from '../../components/Sidebar.vue';
import { ArrowUp, ArrowDown, Search, List, FileText } from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';

const rawTransactions = ref([]);
const isLoading = ref(false);
const totalEntries = ref(0);

const activeRoute = ref('transaksi');
const itemsPerPage = ref(10);
const searchTerm = ref('');
const currentPage = ref(1);
const sortKey = ref('id');
const sortOrder = ref('desc');
const activeItemDetails = ref(null);
const isSidebarOpen = ref(true);

const tableColumns = ref([
    { key: 'id', label: 'No', sortable: true },
    { key: 'no_resi', label: 'No. Resi', sortable: true },
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'address', label: 'Alamat', sortable: true },
    { key: 'tanggal_beli', label: 'Tanggal', sortable: true },
    { key: 'total_harga', label: 'Total', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Daftar Barang', sortable: false }
]);

const handleSidebarToggle = (status) => { isSidebarOpen.value = status; };

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const fetchTransactions = async () => {
    isLoading.value = true;
    try {
        const response = await api.get('/admin/orders', {
            params: {
                page: currentPage.value,
                per_page: itemsPerPage.value,
                search: searchTerm.value,
                sort_key: sortKey.value,
                sort_order: sortOrder.value
            }
        });

        rawTransactions.value = response.data.data || [];
        totalEntries.value = response.data.total || 0;

    } catch (error) {
        console.error("Gagal mengambil data transaksi:", error);
        rawTransactions.value = [];
    } finally {
        isLoading.value = false;
    }
};
const paginatedTransactions = computed(() => rawTransactions.value || []);

const totalPages = computed(() => Math.ceil(totalEntries.value / itemsPerPage.value));

const pageInfo = computed(() => {
    if (totalEntries.value === 0) return 'Showing 0 to 0 of 0 entries';
    const start = (currentPage.value - 1) * itemsPerPage.value + 1;
    const end = Math.min(start + itemsPerPage.value - 1, totalEntries.value);
    return `Showing ${start} to ${end} of ${totalEntries.value} entries`;
});

watch([itemsPerPage, sortKey, sortOrder], () => {
    currentPage.value = 1;
    fetchTransactions();
});

watch(currentPage, () => {
    fetchTransactions();
});

let searchTimeout;
watch(searchTerm, (newVal) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage.value = 1;
        fetchTransactions();
    }, 500);
});

onMounted(() => {
    fetchTransactions();
});

const getStatusBadgeClass = (status) => {
    switch (status) {
        case 'Done': case 'Delivered': return 'bg-green-500';
        case 'On Road': case 'Shipped': return 'bg-orange-500';
        case 'Cancelled': return 'bg-red-500';
        case 'Pending': return 'bg-yellow-500';
        case 'Paid': return 'bg-blue-500';
        default: return 'bg-gray-500';
    }
}

const sortTable = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = 'asc';
    }
};

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};

const showItemDetails = (transaction) => { activeItemDetails.value = transaction; };
const closeItemDetails = () => { activeItemDetails.value = null; };

const updateOrderStatus = async (orderId, status) => {
    const result = await Swal.fire({
        title: 'Konfirmasi Update',
        text: `Apakah Anda yakin ingin mengubah status order menjadi ${status.toUpperCase()}?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2563eb', 
        cancelButtonColor: '#d33',
        confirmButtonText: 'Ya, Ubah Status',
        cancelButtonText: 'Batal',
        reverseButtons: true
    });

    if (!result.isConfirmed) return;

    Swal.fire({
        title: 'Memproses...',
        text: 'Mohon tunggu sebentar',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        await api.patch(`/admin/orders/${orderId}/status`, { status });

        await fetchTransactions();

        Swal.fire({
            title: 'Berhasil!',
            text: `Status order berhasil diubah menjadi ${status}.`,
            icon: 'success',
            confirmButtonColor: '#16a34a',
            timer: 2000,
            timerProgressBar: true
        });

    } catch (error) {
        Swal.fire({
            title: 'Gagal!',
            text: error.response?.data?.message || 'Terjadi kesalahan saat update status.',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
    }
};

</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar :current-route="activeRoute" @toggle="handleSidebarToggle" />

        <div
            :class="['grow bg-gray-100 p-8 pt-10 transition-all duration-300 w-full', isSidebarOpen ? 'ml-70' : 'ml-0']">
            <h1 class="text-3xl font-bold text-slate-800 mb-8 tracking-tight">Riwayat Transaksi Penjualan</h1>

            <div class="bg-white rounded-xl shadow-lg p-6">

                <div class="flex flex-col md:flex-row justify-between items-center mb-6 space-y-3 md:space-y-0">
                    <div class="flex items-center space-x-2 text-gray-600">
                        <select v-model.number="itemsPerPage"
                            class="group p-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 hover:border-blue-400 hover:shadow-sm transition-all duration-200 cursor-pointer">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                        </select>
                        <span class="text-sm font-medium">entries per page</span>
                    </div>

                    <div class="w-full md:w-auto relative">
                        <input type="text" v-model="searchTerm" placeholder="Cari transaksi..."
                            class="group w-full md:w-64 p-2.5 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-400 hover:shadow-sm">
                        <Search class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
                    </div>
                </div>

                <div class="overflow-x-auto border border-gray-200 rounded-xl shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th v-for="col in tableColumns" :key="col.key"
                                    class="group px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider transition select-none"
                                    :class="{ 'cursor-pointer hover:bg-blue-100': col.sortable }"
                                    @click="col.sortable ? sortTable(col.key) : null">
                                    <div class="flex items-center">
                                        {{ col.label }}
                                        <template v-if="col.sortable">
                                            <ArrowUp v-if="sortKey === col.key && sortOrder === 'asc'"
                                                class="w-3 h-3 ml-1 text-blue-600" />
                                            <ArrowDown v-else-if="sortKey === col.key && sortOrder === 'desc'"
                                                class="w-3 h-3 ml-1 text-blue-600" />
                                            <ArrowUp v-else
                                                class="w-3 h-3 ml-1 text-gray-300 group-hover:text-blue-400 opacity-50" />
                                        </template>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="paginatedTransactions.length === 0">
                                <td :colspan="tableColumns.length"
                                    class="px-4 py-12 text-center text-lg text-gray-400 font-medium tracking-wide">
                                    Tidak ada riwayat transaksi ditemukan.
                                </td>
                            </tr>
                            <tr v-else v-for="tx in paginatedTransactions" :key="tx.id"
                                class="hover:bg-blue-50/50 transition-colors duration-150">
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600 font-medium">{{ tx.id }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-blue-600">{{ tx.no_resi }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-900 font-semibold">{{ tx.name
                                }}</td>
                                <td class="px-4 py-4 max-w-xs truncate text-sm text-gray-500" :title="tx.address">{{
                                    tx.address }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-600">{{ tx.tanggal_beli }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-green-700">{{
                                    formatRupiah(tx.total_harga) }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span :class="getStatusBadgeClass(tx.status)"
                                        class="text-white text-[10px] font-bold px-3 py-1.5 rounded-full shadow-sm uppercase tracking-tighter">
                                        {{ tx.status }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center space-x-2">
                                        <button v-if="tx.status === 'Processing'"
                                            @click="updateOrderStatus(tx.id, 'delivered')"
                                            class="px-3 py-2 text-xs font-bold bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors duration-200 shadow-sm">
                                            Kirim Pesanan
                                        </button>

                                        <button v-if="tx.status === 'Delivered'"
                                            @click="updateOrderStatus(tx.id, 'done')"
                                            class="px-3 py-2 text-xs font-bold bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors duration-200 shadow-sm">
                                            Selesaikan
                                        </button>

                                        <button @click="showItemDetails(tx)"
                                            class="group px-4 py-2 bg-linear-to-r from-blue-600 to-blue-800 text-white text-xs font-bold rounded-lg shadow-md hover:shadow-lg hover:from-blue-700 hover:to-blue-900 transition-all duration-300 transform hover:-translate-y-0.5 flex items-center shrink-0">
                                            <List
                                                class="w-3.5 h-3.5 mr-1.5 group-hover:rotate-12 transition-transform duration-300" />
                                            <span>Detail</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center mt-8 space-y-3 md:space-y-0">
                    <p
                        class="text-sm font-medium text-gray-500 bg-gray-50 px-3 py-1 rounded-full border border-gray-100">
                        {{ pageInfo }}
                    </p>

                    <div class="flex items-center space-x-1">
                        <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                            class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50">
                            <span
                                class="group-hover:-translate-x-0.5 transition-transform duration-200 inline-block">«</span>
                        </button>

                        <div class="flex space-x-1">
                            <button v-for="page in totalPages" :key="page" @click="changePage(page)" :class="{
                                'bg-blue-600 text-white font-bold shadow-md hover:shadow-lg hover:-translate-y-0.5': page === currentPage,
                                'bg-white text-gray-700 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600': page !== currentPage
                            }"
                                class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200 font-semibold">
                                {{ page }}
                            </button>
                        </div>

                        <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
                            class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50">
                            <span
                                class="group-hover:translate-x-0.5 transition-transform duration-200 inline-block">»</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="activeItemDetails"
            class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 animate-in zoom-in duration-300">
                <div class="flex justify-between items-start mb-6 border-b border-gray-100 pb-4">
                    <h3 class="text-xl font-extrabold text-slate-800 flex items-center tracking-tight">
                        <FileText class="w-6 h-6 mr-3 text-blue-600" />
                        Invoice #{{ activeItemDetails.no_resi }}
                    </h3>
                    <button @click="closeItemDetails"
                        class="text-gray-400 hover:text-red-500 text-2xl transition-colors">&times;</button>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-6 bg-blue-50/50 p-4 rounded-xl border border-blue-100/50">
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-1 tracking-widest">Customer</p>
                        <p class="text-sm font-bold text-slate-700">{{ activeItemDetails.name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] uppercase font-bold text-gray-400 mb-1 tracking-widest">Date</p>
                        <p class="text-sm font-bold text-slate-700">{{ activeItemDetails.tanggal_beli }}</p>
                    </div>
                </div>

                <div class="overflow-hidden border border-gray-100 rounded-xl mb-6 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50/50">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                    Item</th>
                                <th
                                    class="px-4 py-3 text-center text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                    Qty</th>
                                <th
                                    class="px-4 py-3 text-right text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                    Price</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            <tr v-for="(item, index) in activeItemDetails.daftar_barang" :key="index">
                                <td class="px-4 py-3 text-sm text-slate-700 font-medium">{{ item.name }}</td>
                                <td class="px-4 py-3 text-sm text-center text-slate-600">{{ item.qty }}</td>
                                <td class="px-4 py-3 text-sm text-right text-slate-800 font-bold">{{
                                    formatRupiah(item.price) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between items-center py-4 border-t-2 border-dashed border-gray-100">
                    <span class="text-sm font-bold text-gray-500 uppercase">Grand Total</span>
                    <span class="text-2xl font-black text-green-700">{{ formatRupiah(activeItemDetails.total_harga)
                        }}</span>
                </div>

                <div class="mt-8">
                    <button @click="closeItemDetails"
                        class="w-full py-3.5 bg-linear-to-r from-slate-700 to-slate-900 text-white font-extrabold rounded-xl shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-300">
                        CLOSE INVOICE
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
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

th.group:hover {
    background-color: #dbeafe;
}

button[class*="from-blue"]:hover {
    box-shadow: 0 8px 16px -3px rgba(30, 58, 138, 0.4);
}

.animate-in {
    animation: zoomIn 0.25s ease-out;
}

@keyframes zoomIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }

    to {
        opacity: 1;
        transform: scale(1);
    }
}

::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>