<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Sidebar from '../../components/Sidebar.vue';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';
import { Box, CircleCheckBig, Clock, DollarSign } from 'lucide-vue-next';

const activeRoute = ref('dashboard');
const isSidebarOpen = ref(true);
const auth = useAuthStore()

const handleSidebarToggle = (status) => {
    isSidebarOpen.value = status;
};

const adminName = computed(() => auth.state.user?.name || '')

const dashboardData = ref({
    totalItems: 0,
    itemsSold: 0,
    itemsShipped: 0,
    monthlyEarnings: 0,
    recentPurchases: []
});
const loading = ref(true)

const fetchDashboardData = async () => {
    try {
        const response = await api.get('/admin/dashboard');
        if (response.data.success) {
            dashboardData.value = response.data.data;
        }
    } catch (error) {
        console.error('Gagal mengambil data dashboard:', error);
    } finally {
        loading.value = false
    }
};

onMounted(() => {
    fetchDashboardData();
});

const getStatusColor = (status) => {
    if (status === 'Done') {
        return 'bg-green-500';
    } else if (status === 'Delivered') {
        return 'bg-amber-500';
    } else if (status === 'Processing') {
        return 'bg-gray-500';
    }
    return 'bg-red-500';
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar :current-route="activeRoute" @toggle="handleSidebarToggle"/>

        <div :class="['grow bg-gray-100 p-8 pt-10 transition-all duration-300 w-full', isSidebarOpen ? 'ml-70' : 'ml-0']">
            <div class="flex flex-col md:flex-row md:items-center justify-between mb-8 gap-2">
                <div>
                    <h1 class="text-3xl font-bold text-slate-800">Dashboard Admin</h1>
                    <p class="text-gray-500 text-sm mt-1">Pantau performa tokomu hari ini.</p>
                </div>
                <div class="bg-white px-6 py-3 rounded-2xl shadow-sm border border-gray-200 flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center font-bold text-lg">
                        {{ adminName.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase">Selamat Datang,</p>
                        <p class="text-lg font-bold text-slate-800 leading-tight">{{ adminName }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="p-6 bg-white rounded-xl shadow-lg transition hover:shadow-xl border-t-4 border-indigo-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-500 uppercase">Total Item</p>
                            <p class="text-4xl font-extrabold text-gray-900 mt-1">{{ dashboardData.totalItems }}</p>
                            <p class="text-xs text-gray-400">Item</p>
                        </div>
                        <Box color="#554ce5" class="w-7 h-7" />
                    </div>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-lg transition hover:shadow-xl border-t-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-500 uppercase">Item Terjual</p>
                            <p class="text-4xl font-extrabold text-gray-900 mt-1">{{ dashboardData.itemsSold }}</p>
                            <p class="text-xs text-gray-400">Item</p>
                        </div>
                        <CircleCheckBig color="#554ce5" class="w-7 h-7" />
                    </div>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-lg transition hover:shadow-xl border-t-4 border-yellow-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-500 uppercase">Item Dikirim</p>
                            <p class="text-4xl font-extrabold text-gray-900 mt-1">{{ dashboardData.itemsShipped }}</p>
                            <p class="text-xs text-gray-400">Item</p>
                        </div>
                        <Clock color="#554ce5" class="w-7 h-7" />
                    </div>
                </div>
            </div>
            <div class="p-6 bg-white rounded-xl shadow-lg transition hover:shadow-xl border-t-4 border-zinc-500 mb-8 w-[50%]">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-sm font-semibold text-gray-500 uppercase">Penghasilan Bulan Ini</p>
                            <p class="text-4xl font-extrabold text-gray-900 mt-1">Rp. {{ dashboardData.monthlyEarnings }}</p>
                        </div>
                        <DollarSign color="#554ce5" class="w-7 h-7" />
                    </div>
                </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-slate-800 mb-6">Riwayat Pembelian Terbaru</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NO.</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NO. RESI</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NAMA</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ALAMAT</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TANGGAL BELI</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="(item, index) in dashboardData.recentPurchases" :key="index">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ index + 1 }}.</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">{{ item.receipt }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ item.name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ item.address }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">{{ item.date }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full text-white"
                                            :class="getStatusColor(item.status)">
                                            {{ item.status }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>