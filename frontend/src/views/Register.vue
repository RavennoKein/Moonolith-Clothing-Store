<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { User, Mail, Lock, Phone, MapPin, Eye, EyeOff } from 'lucide-vue-next'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const fullName = ref('')
const email = ref('')
const phone = ref('')
const address = ref('')
const password = ref('')
const confirmPassword = ref('')
const showPassword = ref(false)

const handleRegister = async () => {
    if (password.value !== confirmPassword.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Oops...',
            text: 'Password dan Konfirmasi Password tidak cocok!',
            confirmButtonColor: '#1e3a8a',
        })
        return
    }

    Swal.fire({
        title: 'Sedang Memproses...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    })

    try {
        await auth.register({
            name: fullName.value,
            email: email.value,
            password: password.value,
            password_confirmation: confirmPassword.value,
            phone_number: phone.value,
            address: address.value,
        })

        const role = auth.state.user?.role;

        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Akun Anda berhasil dibuat',
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: true,
        })

        if (role === 'admin' || role === 'super') {
            router.push({ name: 'Dashboard' });
        } else {
            router.push({ name: 'Home' });
        }
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Register Gagal',
            text: error.message || 'Terjadi kesalahan sistem',
            confirmButtonColor: '#1e3a8a',
        })
    }
}

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value
}
</script>


<template>
    <div class="flex items-center justify-center min-h-screen bg-linear-to-br from-gray-50 to-gray-200 p-4">
        <div
            class="flex w-full max-w-6xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[95vh] border border-gray-100 transition-all duration-500">

            <div class="relative w-1/2 hidden md:block overflow-hidden group/image">
                <img src="../assets/loginPhoto.svg" alt="Fantasy Character" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-blue-900/10"></div>

                <div class="absolute top-10 left-1/2 transform -translate-x-1/2 z-10 text-center w-full px-6">
                    <img src="../assets/logoLogin.svg" alt="Moonolith Logo"
                        class="h-28 w-auto rounded-xl object-cover mx-auto mb-4"
                        onerror="this.onerror=null; this.src='https://placehold.co/150x40/1e3a8a/ffffff?text=MOONOLITH';" />
                </div>
            </div>

            <div class="w-full md:w-1/2 p-8 lg:p-12 flex flex-col justify-center overflow-y-auto bg-white">

                <div class="mb-6 transform transition-all duration-500">
                    <h2 class="text-3xl font-bold text-gray-800 tracking-tight">Buat Akun Baru</h2>
                    <p class="text-gray-500 mt-1">Lengkapi data di bawah untuk bergabung.</p>
                </div>

                <form @submit.prevent="handleRegister" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nama Lengkap</label>
                            <div class="relative">
                                <input type="text" v-model="fullName" placeholder="Nama Lengkap" required
                                    class="input-field pl-11">
                                <User class="input-icon" />
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                            <div class="relative">
                                <input type="email" v-model="email" placeholder="contoh@mail.com" required
                                    class="input-field pl-11">
                                <Mail class="input-icon" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Nomor Telepon</label>
                            <div class="relative">
                                <input type="tel" v-model="phone" placeholder="0812xxxx" required
                                    class="input-field pl-11">
                                <Phone class="input-icon" />
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Alamat</label>
                            <div class="relative">
                                <input type="text" v-model="address" placeholder="Alamat Lengkap" required
                                    class="input-field pl-11">
                                <MapPin class="input-icon" />
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" v-model="password"
                                    placeholder="Min. 8 Karakter" required class="input-field pl-11 pr-12">
                                <Lock class="input-icon" />
                                <button type="button" @click="togglePasswordVisibility" class="password-toggle">
                                    <Eye v-if="showPassword" class="w-5 h-5" />
                                    <EyeOff v-else class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <div class="group">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5">Konfirmasi Password</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" v-model="confirmPassword"
                                    placeholder="Ulangi Password" required class="input-field pl-11">
                                <Lock class="input-icon" />
                            </div>
                        </div>
                    </div>

                    <div class="pt-2 text-xs text-gray-500">
                        Dengan mendaftar, Anda menyetujui <a href="#"
                            class="text-blue-800 font-semibold hover:underline">Syarat & Ketentuan</a> kami.
                    </div>

                    <button type="submit"
                        class="w-full py-4 bg-linear-to-r from-blue-900 via-blue-800 to-blue-900 text-white font-bold rounded-xl shadow-lg hover:shadow-blue-900/40 hover:-translate-y-1 active:scale-95 transition-all duration-300 group/btn overflow-hidden relative">
                        <span class="relative z-10">Daftar Sekarang</span>
                        <div
                            class="absolute inset-0 w-full h-full bg-linear-to-r from-transparent via-white/10 to-transparent -translate-x-full group-hover/btn:animate-shimmer">
                        </div>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-gray-600">
                    Sudah punya akun?
                    <router-link :to="{ name: 'Login' }"
                        class="font-bold text-blue-800 hover:text-blue-600 underline-offset-4 hover:underline">
                        Login
                    </router-link>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@reference "../assets/main.css";

.input-field {
    @apply w-full p-3.5 pl-12 border border-gray-300 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-800 text-gray-900 transition-all outline-none hover:border-gray-400;
}

.input-field::placeholder {
    color: #94a3b8;
    opacity: 1;
}

.input-icon {
    @apply w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2 group-focus-within:text-blue-800 transition-colors;
}

.password-toggle {
    @apply absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-800 transition-all;
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

.animate-shimmer {
    animation: shimmer 1.5s infinite;
}

div::-webkit-scrollbar {
    width: 5px;
}

div::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}

.swal2-styled.swal2-confirm {
    border-radius: 0.75rem !important;
    padding: 0.6rem 2rem !important;
    font-weight: 600 !important;
}

.swal2-popup {
    border-radius: 1.5rem !important;
    padding: 2rem !important;
}
</style>