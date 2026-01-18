<script setup>
import { ref } from 'vue';
import { Mail, Lock, Eye, EyeOff } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import Swal from 'sweetalert2';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const router = useRouter();
const emailOrPhone = ref('');
const password = ref('');
const rememberMe = ref(false);
const showPassword = ref(false);

const handleLogin = async () => {
    try {
        Swal.fire({
            title: 'Sedang Memproses...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading(),
        });

        await auth.login({
            login: emailOrPhone.value,
            password: password.value,
        });

        const role = auth.state.user?.role;

        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Selamat datang!',
            timer: 1000,
            showConfirmButton: false,
        })

        if (role === 'admin' || role === 'super') {
            router.push({ name: 'Dashboard' });
        } else {
            router.push({ name: 'Home' });
        }

    } catch (error) {
        console.error(error);
        Swal.fire({
            icon: 'error',
            title: 'Login Gagal',
            text: error.response?.data?.message || 'Terjadi kesalahan',
        });
    }
};

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};
</script>

<template>
    <div class="flex items-center justify-center min-h-screen bg-linear-to-br from-gray-50 to-gray-200 p-4">
        <div
            class="flex w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] border border-gray-100 transition-all duration-500">

            <div class="relative w-1/2 hidden md:block overflow-hidden group/image">
                <img src="../assets/loginPhoto.svg" alt="Fantasy Character" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-blue-900/10"></div>

                <div class="absolute top-10 left-1/2 transform -translate-x-1/2 z-10">
                    <img src="../assets/logoLogin.svg" alt="Moonolith Logo" class="h-28 w-auto rounded-xl object-cover"
                        onerror="this.onerror=null; this.src='https://placehold.co/150x40/1e3a8a/ffffff?text=MOONOLITH';" />
                </div>
            </div>

            <div class="w-full md:w-1/2 p-10 flex flex-col justify-center overflow-y-auto bg-white">

                <div class="mb-8 transform transition-all duration-500">
                    <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Selamat Datang!</h2>
                    <p class="text-gray-500 mt-2">Yuk, Login untuk mulai belanja.</p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-6">
                    <div class="group">
                        <label for="emailOrPhone"
                            class="block text-sm font-semibold text-gray-700 mb-2 group-focus-within:text-blue-800 transition-colors">
                            Email / No. Telepon
                        </label>
                        <div class="relative">
                            <input type="text" id="emailOrPhone" v-model="emailOrPhone"
                                placeholder="Masukkan email atau nomor telepon" required
                                class="w-full p-3.5 pl-11 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-800 text-gray-900 transition-all outline-none hover:border-blue-400 hover:shadow-sm">
                            <Mail
                                class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2 group-focus-within:text-blue-800 group-hover:text-blue-500 transition-colors" />
                        </div>
                    </div>

                    <div class="group">
                        <label for="password"
                            class="block text-sm font-semibold text-gray-700 mb-2 group-focus-within:text-blue-800 transition-colors">
                            Password
                        </label>
                        <div class="relative">
                            <input :type="showPassword ? 'text' : 'password'" id="password" v-model="password"
                                placeholder="Masukkan password" required
                                class="w-full p-3.5 pl-11 pr-12 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-800 text-gray-900 transition-all outline-none hover:border-blue-400 hover:shadow-sm">

                            <Lock
                                class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2 group-focus-within:text-blue-800 group-hover:text-blue-500 transition-colors" />

                            <button type="button" @click="togglePasswordVisibility"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-blue-800 transition-all duration-300 hover:scale-110">
                                <Eye v-if="showPassword" class="w-5 h-5" />
                                <EyeOff v-else class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                        class="w-full py-4 bg-linear-to-r from-blue-900 via-blue-800 to-blue-900 text-white font-bold rounded-xl shadow-lg hover:shadow-blue-900/40 hover:-translate-y-1 active:scale-95 transition-all duration-300 group/btn overflow-hidden relative">
                        <span class="relative z-10 flex items-center justify-center">
                            Masuk
                        </span>
                        <div
                            class="absolute inset-0 w-full h-full bg-linear-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover/btn:animate-shimmer">
                        </div>
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-gray-600">
                    Belum punya akun?
                    <router-link :to="{ name: 'Register' }"
                        class="inline-block font-bold text-blue-800 hover:text-blue-600 transition-all duration-300 hover:scale-105 underline-offset-4 hover:underline">Daftar
                    </router-link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 1.5s infinite;
}

input:focus {
    box-shadow: 0 0 0 4px rgba(30, 58, 138, 0.1);
}

div::-webkit-scrollbar {
    width: 5px;
}

div::-webkit-scrollbar-track {
    background: #f1f1f1;
}

div::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

div::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>