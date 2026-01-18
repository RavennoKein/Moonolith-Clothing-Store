<script setup>
import { ref, watch, onMounted } from 'vue';
import { House, Server, User, DollarSign, Clipboard, LogOut, CircleUserRound, Menu, X, TriangleAlert } from 'lucide-vue-next';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const props = defineProps({
    currentRoute: {
        type: String,
        required: true
    }
});

const isOpen = ref(true);
const emit = defineEmits(['toggle']);
const auth = useAuthStore();
const router = useRouter();

const handleLogout = async () => {
    await auth.logout();
    router.push({ name: 'Login' });
};

const toggleSidebar = () => {
    isOpen.value = !isOpen.value;
    emit('toggle', isOpen.value);
};

const linkClass = (route) => {
    const base = 'flex items-center px-4 py-3 text-md font-medium rounded-lg transition duration-150 ease-in-out';
    if (props.currentRoute === route) {
        return `${base} bg-gray-700 text-indigo-400 font-semibold shadow-inner`;
    }
    return `${base} text-gray-300 hover:bg-gray-700 hover:text-indigo-400`;
};
</script>

<template>
    <button @click="toggleSidebar" :class="[
        'fixed top-10 z-50 flex items-center justify-center w-5 h-10 bg-gray-900 hover:bg-indigo-700 text-white rounded-r-md shadow-lg transition-all duration-300 group',
        isOpen ? 'left-70' : 'left-0'
    ]">
        <div :class="['transition-transform duration-500', isOpen ? 'rotate-0' : 'rotate-180']">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </div>
    </button>

    <div :class="[
        'h-screen flex flex-col bg-gray-900 text-gray-100 fixed top-0 left-0 z-40 transition-all duration-300 ease-in-out overflow-hidden',
        isOpen ? 'w-70 shadow-2xl border-r border-gray-800' : 'w-0 border-transparent shadow-none'
    ]">
        <div class="flex items-center justify-between h-20 px-6 border-b border-gray-800 min-w-70">
            <div class="flex items-center">
                <img src="../assets/logoAdmin.svg" alt="Moonolith Logo" class="h-15 w-auto"
                    onerror="this.onerror=null; this.src='https://placehold.co/150x40/1f2937/d1d5db?text=MOONOLITH';" />
            </div>
        </div>

        <nav class="grow p-4 space-y-1 overflow-y-auto min-w-70">
            <router-link :to="{ name: 'Dashboard' }" :class="linkClass('dashboard')">
                <House class="w-5 h-5 mr-3" /> Dashboard
            </router-link>
            <router-link :to="{ name: 'Category' }" :class="linkClass('data-category')">
                <Server class="w-5 h-5 mr-3" /> Data Kategori
            </router-link>
            <router-link :to="{ name: 'Item' }" :class="linkClass('data-item')">
                <Server class="w-5 h-5 mr-3" /> Data Item
            </router-link>
            <router-link :to="{ name: 'User' }" :class="linkClass('data-user')">
                <User class="w-5 h-5 mr-3" /> Data User
            </router-link>
            <router-link :to="{ name: 'Cashflow' }" :class="linkClass('keuangan')">
                <DollarSign class="w-5 h-5 mr-3" /> Keuangan
            </router-link>
            <router-link :to="{ name: 'Riwayat' }" :class="linkClass('transaksi')">
                <Clipboard class="w-5 h-5 mr-3" /> Riwayat Transaksi
            </router-link>
        </nav>

        <div class="p-4 border-t border-gray-800 min-w-70">
            <button @click="handleLogout"
                class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-lg text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-all duration-200">
                <LogOut class="w-5 h-5 mr-3" /> Logout
            </button>
        </div>
    </div>
</template>