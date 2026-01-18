<template>
  <div class="py-8 bg-gray-100 animate-pulse">
    <div class="flex justify-center mb-6">
      <div class="h-8 w-40 bg-gray-300 rounded"></div>
    </div>

    <div class="flex justify-center gap-3 mb-8">
      <div v-for="i in 4" :key="i" class="w-12 h-12 bg-gray-300 rounded-lg"></div>
    </div>

    <div class="flex gap-4 px-4 overflow-hidden">
      <div v-for="i in 5" :key="i" class="min-w-50">
        <ProductCardSkeleton />
      </div>
    </div>
  </div>
</template>

<script setup>
import ProductCardSkeleton from './ProductCardSkeleton.vue';
</script>
