<script setup>
import { ref, onMounted, watch } from 'vue';
import { CircleUserRound, ShoppingCart, CircleStar, Search, Menu, X, LogOut } from 'lucide-vue-next';
import { useRouter, useRoute } from 'vue-router';
import { useCart } from '@/composables/useCart';
import Swal from 'sweetalert2';

const router = useRouter();
const route = useRoute();
const { cartCount, fetchCartCount } = useCart();

const isLoggedIn = ref(false);
const isScrolled = ref(false);
const searchQuery = ref('');
const isMobileMenuOpen = ref(false);

const handleLogout = async () => {
  const result = await Swal.fire({
    title: 'Logout?',
    text: "Anda akan keluar dari sesi ini.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#0f172a',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Logout',
    cancelButtonText: 'Batal'
  });

  if (result.isConfirmed) {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    isLoggedIn.value = false;

    if (typeof fetchCartCount === 'function') {
    }

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil Logout',
      showConfirmButton: false,
      timer: 1500
    });

    router.push({ name: 'Login' });
  }
};

const handleSearch = () => {
  if (searchQuery.value.trim()) {
    router.push({
      name: 'Catalog',
      query: { q: searchQuery.value }
    });
    isMobileMenuOpen.value = false;
  }
};

const toggleMobileMenu = () => {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
};

const checkLoginStatus = () => {
  const token = localStorage.getItem('token');
  isLoggedIn.value = !!token;
};

onMounted(() => {
  checkLoginStatus();

  if (isLoggedIn.value) {
    fetchCartCount();
  }

  window.addEventListener('scroll', () => {
    isScrolled.value = window.scrollY > 20;
  });

  if (route.query.q) {
    searchQuery.value = route.query.q;
  }
});

watch(() => route.path, () => {
  checkLoginStatus();
});
</script>

<template>
  <nav :class="[
    'sticky top-0 z-50 transition-all duration-500 border-b',
    isScrolled
      ? 'bg-slate-900/95 backdrop-blur-lg border-blue-900/20 py-2 shadow-2xl'
      : 'bg-white/80 backdrop-blur-md border-slate-200 py-3'
  ]">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex items-center justify-between">

        <div class="flex items-center gap-4">
          <button @click="toggleMobileMenu" class="md:hidden focus:outline-none transition-colors"
            :class="isScrolled ? 'text-white' : 'text-slate-900'">
            <Menu v-if="!isMobileMenuOpen" class="w-6 h-6" />
            <X v-else class="w-6 h-6" />
          </button>

          <div class="shrink-0">
            <router-link to="/" class="block hover:scale-105 transition-transform duration-300">
              <img src="@/assets/logoLogin.svg" alt="Moonolith Logo"
                :class="['h-8 md:h-10 w-auto transition-all', isScrolled ? 'brightness-0 invert' : '']" />
            </router-link>
          </div>
        </div>

        <div class="hidden md:flex items-center space-x-8 font-bold text-[12px] tracking-[0.15em]">
          <router-link :to="{ name: 'MenPages' }"
            :class="isScrolled ? 'nav-link-dark' : 'nav-link-light'">PRIA</router-link>
          <router-link :to="{ name: 'WomenPages' }"
            :class="isScrolled ? 'nav-link-dark' : 'nav-link-light'">WANITA</router-link>
          <router-link :to="{ name: 'KidsPages' }"
            :class="isScrolled ? 'nav-link-dark' : 'nav-link-light'">ANAK</router-link>
          <router-link :to="{ name: 'MostBuyPages' }"
            class="flex items-center text-amber-500 hover:text-amber-400 transition-colors">
            <CircleStar class="mr-1.5 h-4 w-4 text-amber-500" /> LARIS
          </router-link>
        </div>

        <div class="hidden lg:flex flex-1 max-w-xs mx-6">
          <div class="relative w-full group">
            <input v-model="searchQuery" @keydown.enter="handleSearch" type="text" placeholder="Cari..." :class="[
              'w-full border-none rounded-full py-2 px-4 pl-10 text-sm transition-all outline-none ring-1',
              isScrolled
                ? 'bg-white/10 text-white placeholder-slate-400 ring-white/20 focus:ring-white/50 focus:bg-white/20'
                : 'bg-slate-100 text-slate-900 ring-slate-200 focus:ring-blue-900/30 focus:bg-white'
            ]" />
            <Search @click="handleSearch"
              :class="['absolute left-3 top-2.5 h-4 w-4 transition-colors cursor-pointer hover:text-blue-500', isScrolled ? 'text-slate-400' : 'text-slate-500']" />
          </div>
        </div>

        <div class="flex items-center space-x-3 md:space-x-5">
          <button @click="router.push({ name: 'Cart' })"
            :class="['relative p-2 rounded-full transition-all group', isScrolled ? 'hover:text-blue-300 text-white' : 'hover:text-blue-900 text-slate-700']">
            <span v-if="cartCount > 0"
              class="absolute top-0 right-0 bg-blue-600 text-white text-[9px] font-black rounded-full w-4 h-4 flex items-center justify-center border-2 border-slate-900 animate-bounce-short">
              {{ cartCount }}
            </span>
            <ShoppingCart class="w-5 h-5 md:w-6 md:h-6 group-hover:scale-110 transition-transform" />
          </button>

          <div :class="['h-6 w-px hidden md:block', isScrolled ? 'bg-white/20' : 'bg-slate-200']"></div>

          <div class="flex items-center space-x-3 md:space-x-4"> <template v-if="isLoggedIn">
              <div class="flex items-center gap-2 md:gap-4">
                <router-link :to="{ name: 'Profile' }"
                  :class="['transition-all hover:scale-110', isScrolled ? 'text-white hover:text-blue-300' : 'text-slate-700 hover:text-blue-900']">
                  <CircleUserRound class="w-6 h-6 md:w-7 md:h-7" />
                </router-link>

                <button @click="handleLogout" :class="['p-1.5 rounded-lg transition-all flex items-center gap-1 group',
                  isScrolled ? 'hover:bg-white/10 text-white' : 'hover:bg-slate-100 text-slate-700']">
                  <LogOut class="w-5 h-5 group-hover:text-red-500 transition-colors" />
                  <span
                    class="hidden lg:inline text-[10px] font-black uppercase tracking-wider group-hover:text-red-500">Keluar</span>
                </button>
              </div>
            </template>

            <router-link v-else :to="{ name: 'Login' }" :class="[
              'flex items-center space-x-2 px-3 py-1.5 md:px-4 md:py-2 rounded-full font-bold text-[10px] md:text-xs uppercase transition-all',
              isScrolled
                ? 'bg-white text-slate-900 hover:bg-blue-400'
                : 'bg-blue-900 text-white hover:bg-slate-800 shadow-lg shadow-blue-900/20'
            ]">
              <CircleUserRound class="w-4 h-4" />
              <span class="hidden sm:inline">Masuk</span>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <transition name="slide-fade">
      <div v-if="isMobileMenuOpen" :class="['md:hidden absolute top-full left-0 w-full border-t shadow-xl flex flex-col p-4 space-y-4',
        isScrolled ? 'bg-slate-900/95 border-blue-900/20 text-white' : 'bg-white border-slate-100 text-slate-900']">

        <div class="relative w-full">
          <input v-model="searchQuery" @keydown.enter="handleSearch" type="text" placeholder="Cari produk..." :class="[
            'w-full border-none rounded-xl py-3 px-5 pl-11 text-sm transition-all outline-none ring-1',
            isScrolled
              ? 'bg-white/10 text-white placeholder-slate-400 ring-white/20 focus:ring-white/50'
              : 'bg-slate-100 text-slate-900 ring-slate-200 focus:ring-blue-900/30'
          ]" />
          <Search @click="handleSearch"
            :class="['absolute left-4 top-3.5 h-4 w-4', isScrolled ? 'text-slate-400' : 'text-slate-500']" />
        </div>

        <div class="flex flex-col space-y-2 font-bold text-sm tracking-wider">
          <router-link :to="{ name: 'MenPages' }" @click="isMobileMenuOpen = false"
            class="py-3 border-b border-white/10 hover:pl-2 transition-all">PRIA</router-link>
          <router-link :to="{ name: 'WomenPages' }" @click="isMobileMenuOpen = false"
            class="py-3 border-b border-white/10 hover:pl-2 transition-all">WANITA</router-link>
          <router-link :to="{ name: 'KidsPages' }" @click="isMobileMenuOpen = false"
            class="py-3 border-b border-white/10 hover:pl-2 transition-all">ANAK</router-link>
          <router-link :to="{ name: 'MostBuyPages' }" @click="isMobileMenuOpen = false"
            class="py-3 flex items-center text-amber-500 hover:pl-2 transition-all">
            <CircleStar class="mr-2 h-4 w-4" /> LARIS
          </router-link>
        </div>
      </div>
    </transition>
  </nav>
</template>

<style scoped>
@reference "../assets/main.css";

@keyframes bounce-short {

  0%,
  100% {
    transform: translateY(0);
  }

  50% {
    transform: translateY(-3px);
  }
}

.animate-bounce-short {
  animation: bounce-short 0.3s ease-in-out;
}

.nav-link-light,
.nav-link-dark {
  @apply relative py-1 transition-all duration-300;
}

.nav-link-light::after,
.nav-link-dark::after {
  content: '';
  @apply absolute bottom-0 left-0 w-0 h-0.5 transition-all duration-300 rounded-full;
}

.nav-link-light:hover::after,
.nav-link-dark:hover::after {
  @apply w-full;
}

.nav-link-light {
  @apply text-slate-700 hover:text-blue-900;
}

.nav-link-light::after {
  @apply bg-blue-900;
}

.nav-link-dark {
  @apply text-white/80 hover:text-white;
}

.nav-link-dark::after {
  @apply bg-blue-400;
}

/* Animasi untuk Mobile Menu */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease-out;
}

.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}
</style>