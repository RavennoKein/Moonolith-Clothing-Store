<template>
  <footer class="bg-white border-t border-gray-100 py-10 mt-20">
    <div class="max-w-7xl mx-auto px-4">
      <div class="flex flex-col md:flex-row items-center justify-between gap-6">

        <div class="text-gray-500 text-sm order-3 md:order-1">
          © 2025 Moonolith. All rights reserved.
        </div>

        <div class="flex items-center gap-2 order-1 md:order-2">
          <img src="../assets/logoLogin.svg" alt="Moonolith Logo" class="h-15 w-auto" />
        </div>

        <div class="flex flex-wrap justify-center items-center gap-4 md:gap-6 text-gray-500 text-sm order-2 md:order-3">
          <a href="#" class="hover:text-blue-600 transition-colors">Privacy Policy</a>
          <a href="#" class="hover:text-blue-600 transition-colors">Terms and Condition</a>
          <a href="#" class="hover:text-blue-600 transition-colors">Cookie Policy</a>

          <div class="flex items-center gap-1 ml-2 text-gray-800 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9h18" />
            </svg>
            <span>Indonesia</span>
          </div>
        </div>

      </div>
    </div>
  </footer>
</template>

<style scoped>
a {
  @apply transition-all duration-200;
}
</style>