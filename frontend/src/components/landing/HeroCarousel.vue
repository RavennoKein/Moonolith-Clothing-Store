<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  images: {
    type: Array,
    default: () => [
      "https://images.unsplash.com/photo-1596701082181-e2df807e35b0?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
      "https://images.unsplash.com/photo-1543163521-1bf539c55dd2?q=80&w=2060&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
      "https://images.unsplash.com/photo-1602167425121-7290940176cd?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D"
    ]
  },
  autoPlay: {
    type: Boolean,
    default: true
  },
  interval: {
    type: Number,
    default: 5000
  }
});

const currentSlideIndex = ref(0);
let autoPlayTimer = null;

const nextSlide = () => {
  currentSlideIndex.value = (currentSlideIndex.value + 1) % props.images.length;
};

const prevSlide = () => {
  currentSlideIndex.value = (currentSlideIndex.value - 1 + props.images.length) % props.images.length;
};

const goToSlide = (index) => {
  currentSlideIndex.value = index;
  resetAutoPlay();
};

const startAutoPlay = () => {
  if (props.autoPlay && props.images.length > 1) {
    autoPlayTimer = setInterval(nextSlide, props.interval);
  }
};

const stopAutoPlay = () => {
  clearInterval(autoPlayTimer);
};

const resetAutoPlay = () => {
  stopAutoPlay();
  startAutoPlay();
};

onMounted(() => {
  startAutoPlay();
});

onUnmounted(() => {
  stopAutoPlay();
});
</script>

<template>
  <div class="relative w-full aspect-21/9 overflow-hidden bg-gray-200 mb-8 rounded-lg shadow-lg">
    <div class="flex transition-transform duration-700 ease-in-out h-full"
      :style="{ transform: `translateX(-${currentSlideIndex * 100}%)` }">
      <img v-for="(image, index) in images" :key="index" :src="image" class="w-full shrink-0 object-cover"
        :alt="`Slide ${index + 1}`" />
    </div>

    <button @click="prevSlide"
      class="absolute top-1/2 left-4 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-3 rounded-full transition-colors hidden md:block">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </button>
    <button @click="nextSlide"
      class="absolute top-1/2 right-4 -translate-y-1/2 bg-black/30 hover:bg-black/50 text-white p-3 rounded-full transition-colors hidden md:block">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
      </svg>
    </button>

    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex space-x-2">
      <button v-for="(image, index) in images" :key="index" @click="goToSlide(index)"
        :class="['w-3 h-3 rounded-full transition-colors', currentSlideIndex === index ? 'bg-white' : 'bg-white/50 hover:bg-white/70']"></button>
    </div>
  </div>
</template>