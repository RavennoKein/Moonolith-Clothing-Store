<script setup>
import { ref, computed, onMounted } from 'vue';
import Sidebar from '../../components/Sidebar.vue';
import { Plus, Pencil, Trash2, ArrowUp, ArrowDown } from 'lucide-vue-next';
import api from '@/services/api';
import axios from 'axios';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const activeRoute = ref('data-user');
const isSidebarOpen = ref(true);

const handleSidebarToggle = (status) => {
    isSidebarOpen.value = status;
};

const auth = useAuthStore()
const isSuperAdmin = computed(() => auth.state.user?.role === 'super')
const isAdmin = computed(() =>
    ['admin', 'super'].includes(auth.state.user?.role)
)
const currentUser = computed(() => auth.state.user);

const rawUsers = ref([]);
const loading = ref(true)

const itemsPerPage = ref(10);
const searchTerm = ref('');
const currentPage = ref(1);
const sortKey = ref('id');
const sortOrder = ref('asc');

const fetchUserData = async () => {
    try {
        const response = await api.get('/admin/admins');
        if (response.data.success) {
            rawUsers.value = response.data.data;
        }
    } catch (error) {
        console.error('Gagal mengambil data User:', error);
    } finally {
        loading.value = false
    }
};

onMounted(() => {
    fetchUserData();
});

// Konfigurasi Kolom - Ditambahkan Kolom Role
const tableColumns = ref([
    { key: 'id', label: 'No', sortable: false },
    { key: 'name', label: 'Nama User', sortable: true },
    { key: 'email', label: 'Email', sortable: true },
    { key: 'role', label: 'Role', sortable: true }, // Kolom Baru
    { key: 'phone', label: 'Nomor Telepon', sortable: true },
    { key: 'address', label: 'Alamat', sortable: true },
    { key: 'actions', label: 'Aksi', sortable: false }
]);

const filteredUsers = computed(() => {
    const term = searchTerm.value.toLowerCase();
    if (!term) return rawUsers.value;
    return rawUsers.value.filter(user =>
        user.name.toLowerCase().includes(term) ||
        user.email.toLowerCase().includes(term) ||
        user.role.toLowerCase().includes(term) || // Ditambahkan filter pencarian by role
        user.phone.toLowerCase().includes(term) ||
        user.address.toLowerCase().includes(term)
    );
});

const sortedUsers = computed(() => {
    const users = [...filteredUsers.value];
    const key = sortKey.value;
    const order = sortOrder.value === 'asc' ? 1 : -1;
    users.sort((a, b) => {
        const aValue = (typeof a[key] === 'string' ? a[key].toLowerCase() : a[key]);
        const bValue = (typeof b[key] === 'string' ? b[key].toLowerCase() : b[key]);
        if (aValue < bValue) return -1 * order;
        if (aValue > bValue) return 1 * order;
        return 0;
    });
    return users;
});

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return sortedUsers.value.slice(start, end);
});

const totalPages = computed(() => Math.ceil(filteredUsers.value.length / itemsPerPage.value));

const pageInfo = computed(() => {
    const total = filteredUsers.value.length;
    if (total === 0) return 'Showing 0 to 0 of 0 entries';
    const start = (currentPage.value - 1) * itemsPerPage.value + 1;
    const end = Math.min(start + itemsPerPage.value - 1, total);
    return `Showing ${start} to ${end} of ${total} entries`;
});

const sortTable = (key) => {
    if (sortKey.value === key) {
        sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortOrder.value = 'asc';
    }
    currentPage.value = 1;
};

const changePage = (page) => {
    if (page >= 1 && page <= totalPages.value) currentPage.value = page;
};

const handleEdit = (id) => {
    router.push({
        name: 'EditUser',
        params: { id }
    });
};
const handleDelete = async (id) => {
    const confirmDelete = confirm('Apakah Anda yakin ingin menghapus admin ini?');
    if (!confirmDelete) return;

    try {
        await api.delete(`/admin/admins/${id}`);

        rawUsers.value = rawUsers.value.filter(user => user.id !== id);

        alert('Admin berhasil dihapus');
    } catch (error) {
        console.error(error);

        const message =
            error.response?.data?.message ||
            'Gagal menghapus admin';

        alert(message);
    }
};
</script>

<template>
    <div class="flex min-h-screen bg-gray-100">
        <Sidebar :current-route="activeRoute" @toggle="handleSidebarToggle" />

        <div
            :class="['grow bg-gray-100 p-8 pt-10 transition-all duration-300 w-full', isSidebarOpen ? 'ml-70' : 'ml-0']">
            <h1 class="text-3xl font-bold text-slate-800 mb-8">Data User</h1>

            <div class="bg-white rounded-xl shadow-lg p-6">

                <div class="mb-6">
                    <router-link :to="{ name: 'TambahUser' }"
                        class="group inline-flex items-center px-4 py-3 bg-linear-to-r from-blue-600 to-blue-800 text-white font-semibold rounded-lg shadow-md hover:shadow-xl hover:from-blue-700 hover:to-blue-900 transition-all duration-300 transform hover:-translate-y-0.5 w-fit">
                        <Plus class="w-5 h-5 mr-2 group-hover:rotate-90 transition-transform duration-300" />
                        Tambah Data User
                    </router-link>
                </div>

                <div class="flex flex-col md:flex-row justify-between items-center mb-4 space-y-3 md:space-y-0">
                    <div class="flex items-center space-x-2 text-gray-600">
                        <select v-model.number="itemsPerPage"
                            class="group p-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 hover:border-blue-400 hover:shadow-sm transition-all duration-200 cursor-pointer">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option :value="rawUsers.length">All</option>
                        </select>
                        <span class="text-sm">entries per page</span>
                    </div>

                    <div class="w-full md:w-auto">
                        <label for="search" class="sr-only">Search</label>
                        <input type="text" id="search" v-model="searchTerm" placeholder="Cari user atau role..."
                            class="group w-full md:w-64 p-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 hover:border-blue-400 hover:shadow-sm">
                    </div>
                </div>

                <div class="w-full border border-gray-200 rounded-xl shadow-sm bg-white overflow-hidden">
                    <table class="w-full table-auto">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr class="text-gray-700 text-[13px] uppercase font-semibold">
                                <th v-for="col in tableColumns" :key="col.key"
                                    class="group px-4 py-4 text-left transition-colors duration-200 select-none" :class="[
                                        col.sortable ? 'cursor-pointer hover:bg-blue-100' : '',
                                        col.key === 'actions' || col.key === 'role' ? 'text-center' : '',
                                        col.key === 'id' ? 'w-12' : ''
                                    ]" @click="col.sortable ? sortTable(col.key) : null">

                                    <div class="flex items-center"
                                        :class="{ 'justify-center': col.key === 'actions' || col.key === 'role' }">
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
                        <tbody class="divide-y divide-gray-100">
                            <tr v-if="loading">
                                <td colspan="7" class="text-center py-6 text-gray-500">
                                    Memuat data...
                                </td>
                            </tr>

                            <tr v-else-if="paginatedUsers.length === 0">
                                <td colspan="7" class="text-center py-6 text-gray-500">
                                    Tidak ada data admin
                                </td>
                            </tr>

                            <tr v-for="(user, index) in paginatedUsers" :key="user.id"
                                class="hover:bg-blue-50/50 transition-colors">
                                <td class="px-4 py-4 text-sm text-gray-500">{{ (currentPage - 1) * itemsPerPage + index
                                    + 1 }}</td>
                                <td class="px-4 py-4 text-sm font-bold text-gray-800">{{ user.name }}</td>
                                <td class="px-4 py-4 text-sm text-blue-600 hidden lg:table-cell">{{ user.email }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span :class="[
                                        'px-2.5 py-1 rounded-md text-[10px] font-black uppercase tracking-tighter border',
                                        user.role === 'admin' ? 'bg-purple-100 text-purple-700 border-purple-200' : 'bg-blue-100 text-blue-700 border-blue-200'
                                    ]">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm text-gray-600 whitespace-nowrap">{{ user.phone }}</td>
                                <td class="px-4 py-4 text-sm text-gray-500 max-w-50">
                                    <p class="line-clamp-1" :title="user.address">{{ user.address }}</p>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="isSuperAdmin || currentUser.id === user.id"
                                            @click="handleEdit(user.id)"
                                            class="inline-flex items-center justify-center px-3 py-1.5 bg-orange-500 hover:bg-orange-600 text-white rounded-md text-xs font-medium transition-colors duration-200 shadow-sm min-w-17.5">
                                            <Pencil class="w-3.5 h-3.5 mr-1.5" />
                                            <span>Ubah</span>
                                        </button>

                                        <button v-if="isSuperAdmin" @click="handleDelete(user.id)"
                                            class="inline-flex items-center justify-center px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-md text-xs font-medium transition-colors duration-200 shadow-sm min-w-17.5">
                                            <Trash2 class="w-3.5 h-3.5 mr-1.5" />
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col md:flex-row justify-between items-center mt-6 space-y-3 md:space-y-0">
                    <p class="text-sm text-gray-600">{{ pageInfo }}</p>

                    <div class="flex items-center space-x-1">
                        <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                            class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50 disabled:hover:bg-white disabled:hover:border-gray-300 disabled:hover:text-gray-600">
                            <span
                                class="group-hover:-translate-x-0.5 transition-transform duration-200 inline-block">«</span>
                        </button>

                        <div v-for="page in totalPages" :key="page">
                            <button @click="changePage(page)" :class="{
                                'group bg-linear-to-r from-blue-600 to-blue-800 text-white font-semibold shadow-md hover:shadow-lg hover:-translate-y-0.5': page === currentPage,
                                'group bg-white text-gray-700 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 hover:shadow-sm': page !== currentPage
                            }" class="px-4 py-2 border border-gray-300 rounded-lg transition-all duration-200">
                                {{ page }}
                            </button>
                        </div>

                        <button @click="changePage(currentPage + 1)" :disabled="currentPage === totalPages"
                            class="group p-2.5 border border-gray-300 rounded-lg text-gray-600 hover:bg-linear-to-r hover:from-blue-50 hover:to-blue-100 hover:border-blue-300 hover:text-blue-600 transition-all duration-200 disabled:opacity-50 disabled:hover:bg-white disabled:hover:border-gray-300 disabled:hover:text-gray-600">
                            <span
                                class="group-hover:translate-x-0.5 transition-transform duration-200 inline-block">»</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
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

th.group:hover {
    background-color: #dbeafe;
}

button[class*="from-blue"]:hover {
    box-shadow: 0 6px 12px -2px rgba(37, 99, 235, 0.3);
}

button[class*="from-red"]:hover {
    box-shadow: 0 6px 12px -2px rgba(239, 68, 68, 0.3);
}

button[class*="from-orange"]:hover {
    box-shadow: 0 6px 12px -2px rgba(249, 115, 22, 0.3);
}

.bg-gradient-to-r.from-blue-600.to-blue-800:hover {
    box-shadow: 0 8px 16px -3px rgba(30, 58, 138, 0.4);
}

button:disabled {
    cursor: not-allowed;
}

button:disabled:hover {
    transform: none !important;
    box-shadow: none !important;
    background: white !important;
}

.router-link-active:hover {
    box-shadow: 0 10px 20px -3px rgba(30, 58, 138, 0.4);
}

.bg-white.text-gray-700:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px -2px rgba(0, 0, 0, 0.1);
}

.text-gray-300.group-hover\:text-gray-400 {
    transition: color 0.2s ease;
}

.text-blue-600 {
    color: #2563eb;
}

.bg-gradient-to-r.from-blue-50.to-blue-100 {
    background-image: linear-gradient(to right, #eff6ff, #dbeafe);
}
</style>