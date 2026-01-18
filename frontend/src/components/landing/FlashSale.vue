<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const emit = defineEmits(['expired']);

const props = defineProps({
  isActive: {
    type: Boolean,
    required: false
  },
  startTime: {
    type: String,
    required: true
  },
  endTime: {
    type: String,
    required: true
  }
});

const timeLeft = ref({
  days: 0,
  hours: 0,
  minutes: 0,
  seconds: 0
});

const isExpired = ref(false);
let timerInterval = null;

const calculateTime = () => {
  if (!props.isActive) return;

  const end = new Date(props.endTime);
  const now = new Date();
  const diff = end - now;

  if (diff <= 0) {
    isExpired.value = true;
    clearInterval(timerInterval);
    emit('expired');
    return;
  }

  timeLeft.value = {
    days: Math.floor(diff / (1000 * 60 * 60 * 24)),
    hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
    minutes: Math.floor((diff / (1000 * 60)) % 60),
    seconds: Math.floor((diff / 1000) % 60),
  };
};

onMounted(() => {
  calculateTime();

  if (props.isActive) {
    timerInterval = setInterval(calculateTime, 1000);
  }
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});

const pad = (num) => (num < 10 ? '0' + num : num);
</script>

<template>
  <div v-if="props.isActive && !isExpired" class="py-8 bg-gray-100 flex flex-col items-center">
    <div class="flex items-center mb-6">
      <div class="bg-red-600 text-white px-3 py-1 italic font-black text-xl flex items-center gap-2 skew-x-[-10deg]">
        <span class="not-italic">⚡</span> FLASH SALE
      </div>
    </div>

    <div class="flex items-center space-x-3">
      <div class="flex flex-col items-center">
        <div class="bg-black text-white font-bold text-xl px-3 py-2 rounded-lg shadow-md w-12 text-center">
          {{ pad(timeLeft.days) }}
        </div>
        <span class="text-[10px] mt-1 font-bold text-gray-500 uppercase">Hari</span>
      </div>

      <div class="text-black font-bold text-xl mb-5">:</div>

      <div class="flex flex-col items-center">
        <div class="bg-black text-white font-bold text-xl px-3 py-2 rounded-lg shadow-md w-12 text-center">
          {{ pad(timeLeft.hours) }}
        </div>
        <span class="text-[10px] mt-1 font-bold text-gray-500 uppercase">Jam</span>
      </div>

      <div class="text-black font-bold text-xl mb-5">:</div>

      <div class="flex flex-col items-center">
        <div class="bg-black text-white font-bold text-xl px-3 py-2 rounded-lg shadow-md w-12 text-center">
          {{ pad(timeLeft.minutes) }}
        </div>
        <span class="text-[10px] mt-1 font-bold text-gray-500 uppercase">Menit</span>
      </div>

      <div class="text-black font-bold text-xl mb-5">:</div>

      <div class="flex flex-col items-center">
        <div
          class="bg-red-600 text-white font-bold text-xl px-3 py-2 rounded-lg shadow-md w-12 text-center animate-pulse">
          {{ pad(timeLeft.seconds) }}
        </div>
        <span class="text-[10px] mt-1 font-bold text-gray-500 uppercase">Detik</span>
      </div>
    </div>
  </div>
</template>