<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import Sidebar from '../../components/Sidebar.vue';
import { LineChart, DollarSign, Calendar, TrendingUp, Package } from 'lucide-vue-next';
import Chart from 'chart.js/auto';
import api from '@/services/api';

const activeRoute = ref('keuangan');
const isSidebarOpen = ref(true);

const handleSidebarToggle = (status) => {
    isSidebarOpen.value = status;
};

const monthlyData = ref([]);
const dailyData = ref([]);

const fetchCashflow = async () => {
    try {
        const res = await api.get('/admin/cashflow', {
            params: { type: viewMode.value }
        });

        if (res.data.success) {
            const mapped = res.data.data.map(item => ({
                label: item.label,
                amount: Number(item.amount),
                sold: Number(item.sold || 0)
            }));


            if (viewMode.value === 'monthly') {
                monthlyData.value = mapped;
            } else {
                dailyData.value = mapped;
            }
        }
    } catch (e) {
        console.error('Gagal load cashflow', e);
    }
};

const viewMode = ref('monthly');
const chartCanvas = ref(null);
let salesChart = null;

const formatRupiah = (number) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
    }).format(number);
};

const activeData = computed(() => {
    return viewMode.value === 'monthly' ? monthlyData.value : dailyData.value;
});

const chartTitle = computed(() => {
    return viewMode.value === 'monthly' ? 'Total Penjualan Bulanan (12 Bulan Terakhir)' : 'Total Penjualan Harian (7 Hari Terakhir)';
});

const totalSales = computed(() => {
    return activeData.value.reduce((sum, item) => sum + item.amount, 0);
});

const averageSales = computed(() => {
    if (activeData.value.length === 0) return 0;
    return totalSales.value / activeData.value.length;
});

const highestSalesRecord = computed(() => {
    if (activeData.value.length === 0) return { label: '-', amount: 0 };

    return activeData.value.reduce((max, item) =>
        item.amount > max.amount ? item : max
        , activeData.value[0]);
});

const createOrUpdateChart = () => {
    if (!chartCanvas.value || typeof Chart === 'undefined') {
        console.warn("Chart element or Chart.js not found.");
        return;
    }

    const data = activeData.value;
    const labels = data.map(item => item.label);
    const amounts = data.map(item => item.amount);

    const chartConfig = {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Penjualan (IDR)',
                data: amounts,
                backgroundColor: 'rgba(30, 58, 138, 0.1)',
                borderColor: 'rgba(30, 58, 138, 1)',
                borderWidth: 2,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            if (value >= 1000000) {
                                return formatRupiah(value).replace(/Rp\s?/, 'Rp').replace(/,\d+/, '');
                            }
                            return formatRupiah(value).replace(/Rp\s?/, 'Rp').replace(/,\d+/, '');
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: chartTitle.value
                }
            }
        }
    };

    if (salesChart) {
        salesChart.data.labels = chartConfig.data.labels;
        salesChart.data.datasets = chartConfig.data.datasets;
        salesChart.options.plugins.title.text = chartConfig.options.plugins.title.text;
        salesChart.update();
    } else {
        salesChart = new Chart(chartCanvas.value, chartConfig);
    }
};

onBeforeUnmount(() => {
    if (salesChart) {
        salesChart.destroy();
    }
});

watch(viewMode, async () => {
    await fetchCashflow();
    createOrUpdateChart();
});

onMounted(async () => {
    await fetchCashflow();
    createOrUpdateChart();
});


const totalItemsSold = computed(() => {
    return activeData.value.reduce((sum, item) => sum + (item.sold || 0), 0);
});

</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar :current-route="activeRoute" @toggle="handleSidebarToggle" />

        <div
            :class="['grow bg-gray-100 p-8 pt-10 transition-all duration-300', isSidebarOpen ? 'ml-70 w-[calc(100%-17.5rem)]' : 'ml-0 w-full']">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div
                    class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-blue-900 transition-transform hover:scale-[1.02] duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Total Penjualan {{ viewMode === 'monthly' ?
                                'Tahun Ini' : 'Minggu Ini' }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatRupiah(totalSales) }}</p>
                        </div>
                        <DollarSign class="w-8 h-8 text-blue-800 opacity-70" />
                    </div>
                </div>

                <div
                    class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-green-600 transition-transform hover:scale-[1.02] duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">Rata-rata Penjualan {{ viewMode === 'monthly' ?
                                '/ Bulan' : '/ Hari' }}</p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">{{ formatRupiah(averageSales) }}</p>
                        </div>
                        <Calendar class="w-8 h-8 text-green-600 opacity-70" />
                    </div>
                </div>

                <div
                    class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-orange-500 transition-transform hover:scale-[1.02] duration-300">
                    <div class="flex items-start justify-between">
                        <div class="flex flex-col justify-between h-full">
                            <p class="text-sm font-medium text-gray-500">
                                Rekor Tertinggi {{ viewMode === 'monthly' ? 'Bulanan' : 'Harian' }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-auto">
                                {{ formatRupiah(highestSalesRecord.amount) }}
                            </p>
                        </div>

                        <div class="flex flex-col items-end gap-2">
                            <span class="text-xs font-bold text-orange-600 bg-orange-100 px-2.5 py-1 rounded-md">
                                {{ highestSalesRecord.label || '-' }}
                            </span>

                            <TrendingUp class="w-8 h-8 text-orange-500 opacity-70" />
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-purple-600 transition-transform hover:scale-[1.02] duration-300">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-500">
                                Produk Terjual {{ viewMode === 'monthly' ? 'Tahun Ini' : 'Minggu Ini' }}
                            </p>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ totalItemsSold.toLocaleString('id-ID') }} <span
                                    class="text-sm font-normal text-gray-400">Pcs</span>
                            </p>
                        </div>
                        <Package class="w-8 h-8 text-purple-600 opacity-70" />
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">

                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800 flex items-center tracking-tight">
                        <LineChart class="w-6 h-6 mr-2 text-blue-900" />
                        Grafik Penjualan
                    </h2>

                    <div class="flex space-x-2 bg-gray-100 p-1.5 rounded-xl border border-gray-200">
                        <button @click="viewMode = 'monthly'" :class="{
                            'bg-linear-to-r from-blue-900 to-blue-800 text-white shadow-lg scale-105': viewMode === 'monthly',
                            'text-gray-600 hover:bg-white hover:text-blue-900': viewMode !== 'monthly'
                        }"
                            class="px-5 py-2 text-sm font-bold rounded-lg transition-all duration-300 ease-in-out cursor-pointer">
                            Bulan ke Bulan
                        </button>
                        <button @click="viewMode = 'daily'" :class="{
                            'bg-linear-to-r from-blue-900 to-blue-800 text-white shadow-lg scale-105': viewMode === 'daily',
                            'text-gray-600 hover:bg-white hover:text-blue-900': viewMode !== 'daily'
                        }"
                            class="px-5 py-2 text-sm font-bold rounded-lg transition-all duration-300 ease-in-out cursor-pointer">
                            Hari ke Hari
                        </button>
                    </div>
                </div>

                <div class="relative h-96">
                    <canvas ref="chartCanvas"></canvas>

                    <div v-if="typeof Chart === 'undefined'"
                        class="absolute inset-0 flex items-center justify-center bg-gray-50/70 p-4 rounded-xl">
                        <p class="text-center text-red-500 font-medium">
                            ⚠️ <b>Peringatan:</b> Library <b>Chart.js</b> belum dimuat.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>