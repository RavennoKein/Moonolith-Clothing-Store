<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { CheckCircle, Clock, Info, XCircle, Printer, Download } from 'lucide-vue-next';
import api from '@/services/api';
import Swal from 'sweetalert2';

const route = useRoute();
const router = useRouter();
const invoiceNumber = route.params.invoice;

const orderDetails = ref(null);
const isLoading = ref(true);
const errorMessage = ref('');
const isCancelling = ref(false);
const isDownloading = ref(false);
const showRatingModal = ref(false);
const selectedItem = ref(null);
const ratingForm = ref({
  rating: 0,
  review: ''
});
const isSubmittingRating = ref(false);

const MIDTRANS_CLIENT_KEY = 'Mid-client-k54WL5nGvcG2Xkr8';
const IS_PRODUCTION = false;

const formatRupiah = (number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', minimumFractionDigits: 0
  }).format(number);
};

const openRatingModal = (item) => {
  console.log('Selected item:', item);
  selectedItem.value = item;
  ratingForm.value = { rating: 0, review: '' };
  showRatingModal.value = true;
};

const closeRatingModal = () => {
  showRatingModal.value = false;
  selectedItem.value = null;
};

const submitRating = async () => {
  if (ratingForm.value.rating === 0) {
    return Swal.fire('Oops', 'Silakan beri rating terlebih dahulu', 'warning');
  }

  isSubmittingRating.value = true;

  try {
    await api.post('/user/reviews', {
      order_id: orderDetails.value.id,
      item_id: selectedItem.value.item_id,
      rating: ratingForm.value.rating,
      review: ratingForm.value.review
    });

    Swal.fire({
      icon: 'success',
      title: 'Terima kasih!',
      text: 'Ulasan kamu berhasil dikirim',
      timer: 2000,
      showConfirmButton: false
    });

    closeRatingModal();
    fetchOrder();

  } catch (e) {
    Swal.fire('Gagal', e.response?.data?.message || 'Gagal mengirim ulasan', 'error');
  } finally {
    isSubmittingRating.value = false;
  }
};


const fetchOrder = async () => {
  try {
    isLoading.value = true;
    const response = await api.get(`user/orders/${invoiceNumber}`);
    orderDetails.value = response.data.data;
  } catch (error) {
    console.error("Error fetch order", error);
    errorMessage.value = "Data pesanan tidak ditemukan atau terjadi kesalahan.";
  } finally {
    isLoading.value = false;
  }
};

const downloadInvoice = async () => {
  isDownloading.value = true;
  try {
    const response = await api.get(`/user/orders/${invoiceNumber}/pdf`, {
      responseType: 'blob'
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', `Invoice-${invoiceNumber}.pdf`);
    document.body.appendChild(link);
    link.click();

    link.remove();
    window.URL.revokeObjectURL(url);

    Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Invoice berhasil didownload',
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 3000
    });

  } catch (error) {
    console.error("Gagal download PDF", error);
    Swal.fire('Gagal', 'Terjadi kesalahan saat mengunduh invoice.', 'error');
  } finally {
    isDownloading.value = false;
  }
};

const payNow = () => {
  const token = orderDetails.value?.snap_token;

  if (!token) {
    Swal.fire('Error', 'Token pembayaran belum tersedia. Coba refresh halaman.', 'error');
    return;
  }

  window.snap.pay(token, {
    onSuccess: function (result) {
      Swal.fire('Berhasil!', 'Pembayaran diterima.', 'success').then(() => fetchOrder());
    },
    onPending: function (result) {
      Swal.fire('Pending', 'Menunggu pembayaran.', 'info').then(() => fetchOrder());
    },
    onError: function (result) {
      Swal.fire('Gagal', 'Pembayaran gagal.', 'error');
    },
    onClose: function () {
      console.log('Customer closed the popup');
    }
  });
};

const cancelOrder = async (orderId) => {
  const result = await Swal.fire({
    title: 'Batalkan Pesanan?',
    text: "Pesanan yang dibatalkan tidak dapat dikembalikan!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Ya, Batalkan!',
    cancelButtonText: 'Tidak, Kembali',
    reverseButtons: true
  });

  if (!result.isConfirmed) return;

  isCancelling.value = true;
  try {
    const response = await api.post(`/user/orders/${orderId}/cancel`);

    if (response.data.success) {
      await Swal.fire({
        title: 'Dibatalkan!',
        text: 'Pesanan Anda telah berhasil dibatalkan.',
        icon: 'success',
        confirmButtonColor: '#3085d6'
      });
      fetchOrder();
    }
  } catch (error) {
    console.error(error);
    Swal.fire({
      title: 'Gagal!',
      text: error.response?.data?.message || "Gagal membatalkan pesanan.",
      icon: 'error',
      confirmButtonColor: '#d33'
    });
  } finally {
    isCancelling.value = false;
  }
};

onMounted(() => {
  fetchOrder();

  const snapSrcUrl = IS_PRODUCTION
    ? 'https://app.midtrans.com/snap/snap.js'
    : 'https://app.sandbox.midtrans.com/snap/snap.js';

  const existingScript = document.querySelector(`script[src="${snapSrcUrl}"]`);

  if (!existingScript) {
    const script = document.createElement('script');
    script.src = snapSrcUrl;
    script.setAttribute('data-client-key', MIDTRANS_CLIENT_KEY);
    script.async = true;

    document.body.appendChild(script);
  }
});

const statusClass = computed(() => {
  const status = orderDetails.value?.status?.toLowerCase();

  if (['done', 'selesai', 'settlement', 'paid', 'success'].includes(status)) {
    return 'bg-emerald-100 text-emerald-700 border-emerald-200';
  }
  else if (['processing', 'dikemas', 'dikirim', 'delivered'].includes(status)) {
    return 'bg-blue-100 text-blue-700 border-blue-200';
  }
  else if (['pending'].includes(status)) {
    return 'bg-amber-100 text-amber-700 border-amber-200';
  }
  else {
    return 'bg-red-100 text-red-700 border-red-200';
  }
});

const statusLabel = computed(() => {
  const status = orderDetails.value?.status?.toLowerCase();

  if (['settlement', 'paid', 'success'].includes(status)) return 'PEMBAYARAN DITERIMA';
  if (['processing', 'dikemas'].includes(status)) return 'DIPROSES PENJUAL';
  if (['dikirim', 'delivered'].includes(status)) return 'SEDANG DIKIRIM';
  if (['done', 'selesai'].includes(status)) return 'SELESAI / DITERIMA';
  if (['pending'].includes(status)) return 'MENUNGGU PEMBAYARAN';
  if (['cancelled', 'batal', 'expire', 'deny'].includes(status)) return 'DIBATALKAN';

  return status;
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 py-10 px-4">
    <div class="max-w-2xl mx-auto space-y-6">

      <div v-if="isLoading" class="text-center py-20">
        <div class="animate-spin w-10 h-10 border-4 border-blue-600 border-t-transparent rounded-full mx-auto"></div>
        <p class="mt-4 text-slate-500">Memuat detail pesanan...</p>
      </div>

      <div v-else-if="errorMessage" class="bg-red-50 border border-red-200 p-6 rounded-xl text-center">
        <XCircle class="w-12 h-12 text-red-500 mx-auto mb-2" />
        <h3 class="text-red-800 font-bold">{{ errorMessage }}</h3>
        <button @click="router.push('/')" class="mt-4 text-blue-600 underline">Kembali ke Beranda</button>
      </div>

      <div v-else class="space-y-6">

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
          <div class="p-6 border-b border-slate-100 flex justify-between items-center flex-wrap gap-2">
            <h2 class="text-xl font-bold text-slate-800">Detail Tagihan</h2>

            <div class="flex gap-3">
              <button @click="downloadInvoice" :disabled="isDownloading"
                class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors border border-slate-200 disabled:opacity-50">
                <Printer v-if="!isDownloading" class="w-4 h-4" />
                <div v-else class="animate-spin w-4 h-4 border-2 border-slate-500 border-t-transparent rounded-full">
                </div>
                {{ isDownloading ? 'Unduh...' : 'Cetak PDF' }}
              </button>

              <button @click="fetchOrder" class="text-xs text-blue-600 hover:underline self-center">
                Refresh Status
              </button>
            </div>
          </div>

          <div class="p-6 space-y-4">
            <div class="flex justify-between items-start">
              <div>
                <p class="text-sm text-slate-500">Status</p>
                <span :class="['inline-block mt-1 px-3 py-1 text-xs font-bold rounded border uppercase', statusClass]">
                  {{ statusLabel }}
                </span>
              </div>
              <div class="text-right">
                <p class="text-sm text-slate-500">No. Pesanan</p>
                <p class="font-bold text-slate-900 mt-1">{{ orderDetails.invoice }}</p>
              </div>
            </div>

            <div class="space-y-2 pt-4">
              <div class="flex justify-between text-sm text-slate-600">
                <span>Total Harga Produk</span>
                <span>{{ formatRupiah(parseInt(orderDetails.total_price) - parseInt(orderDetails.shipping_cost || 0))
                }}</span>
              </div>
              <div class="flex justify-between text-sm text-slate-600">
                <span>Total Ongkos Kirim</span>
                <span>{{ formatRupiah(orderDetails.shipping_cost || 0) }}</span>
              </div>
              <div class="flex justify-between text-lg font-bold text-slate-900 pt-2 border-t border-slate-100 mt-2">
                <span>Total Bayar</span>
                <span>{{ formatRupiah(orderDetails.total_price) }}</span>
              </div>
            </div>
          </div>
        </div>

        <div v-if="orderDetails.status === 'pending'" class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
          <h3 class="font-bold text-slate-800 mb-4">Selesaikan Pembayaran</h3>
          <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 flex items-start gap-3 mb-4">
            <Info class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
            <p class="text-sm text-amber-800">
              Pesanan Anda belum lunas. Silakan klik tombol di bawah untuk melanjutkan pembayaran via Midtrans.
            </p>
          </div>
          <button @click="payNow"
            class="w-full bg-blue-700 hover:bg-blue-800 text-white font-bold py-3 rounded-lg transition-all shadow-lg hover:shadow-xl">
            Bayar Sekarang
          </button>
          <button @click="cancelOrder(orderDetails.id)" :disabled="isCancelling"
            class="w-full py-3 bg-red-50 text-red-600 font-bold rounded-lg border border-red-200 hover:bg-red-100 transition-all flex justify-center items-center gap-2 mt-4">

            <span v-if="isCancelling">Memproses...</span>
            <span v-else>Batalkan Pesanan</span>

          </button>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
          <h3 class="font-bold text-slate-800 mb-4">Produk Yang Dibeli</h3>
          <div class="space-y-6">
            <div v-for="item in orderDetails.items" :key="item.id"
              class="flex gap-4 border-b border-slate-50 pb-4 last:border-0">
              <div class="w-14 h-16 bg-slate-200 rounded shrink-0 overflow-hidden">
                <img :src="item.product_image || 'https://via.placeholder.com/150'"
                  class="w-full h-full object-cover" />
              </div>
              <div class="flex-1">
                <div>
                  <h3 class="font-semibold text-slate-900 line-clamp-2">{{ item.product_name }}</h3>
                  <p class="text-sm text-slate-500">Ukuran: {{ item.variant.size }} <span class="mx-0.5">|</span>
                    Qty: {{
                      item.qty }}</p>
                  <span class="flex items-center gap-1 capitalize">
                    <span class="w-2 h-2 rounded-full border border-slate-200 shadow-sm"
                      :style="{ backgroundColor: item.variant.color }"></span>
                    <p class="text-sm text-slate-500">{{ item.variant.color }}</p>
                  </span>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-bold text-blue-600">{{ formatRupiah(item.price) }}</p>
                <button v-if="orderDetails.status === 'done' && !item.is_reviewed" @click="openRatingModal(item)"
                  class="mt-2 text-xs font-bold text-amber-600 hover:underline">
                  ⭐ Beri Ulasan
                </button>

                <span v-else-if="item.is_reviewed" class="mt-2 inline-block text-xs text-emerald-600 font-semibold">
                  ✔ Sudah diulas
                </span>
              </div>
            </div>
          </div>

          <div class="mt-6 pt-4 border-t border-slate-100">
            <div class="flex justify-between text-sm mb-1">
              <span class="text-slate-500">Ekspedisi</span>
              <span class="font-medium text-slate-900">Kurir Toko</span>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
          <h3 class="font-bold text-slate-800 mb-2">Alamat Pengiriman</h3>
          <p class="font-medium text-slate-900 text-sm">{{ orderDetails.name }}</p>
          <p class="text-sm text-slate-500">{{ orderDetails.phone }}</p>
          <p class="text-sm text-slate-500 mt-1">{{ orderDetails.address }}</p>
        </div>

        <div class="text-center pb-8">
          <button @click="router.push('/catalog')"
            class="text-blue-600 font-medium hover:text-blue-800 hover:underline">
            Kembali Belanja
          </button>
        </div>

      </div>

    </div>
  </div>

  <div v-if="showRatingModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center px-4">

    <div class="bg-white w-full max-w-md rounded-xl shadow-xl p-6 relative">
      <button @click="closeRatingModal" class="absolute top-3 right-3 text-slate-400 hover:text-slate-600">
        ✕
      </button>

      <h3 class="text-lg font-bold text-slate-800 mb-2">
        Beri Ulasan Produk
      </h3>

      <p class="text-sm text-slate-500 mb-4">
        {{ selectedItem?.product?.name || selectedItem?.product_name }}
      </p>

      <div class="flex gap-2 mb-4">
        <button v-for="i in 5" :key="i" @click="ratingForm.rating = i" class="focus:outline-none">
          <svg class="w-8 h-8" :class="i <= ratingForm.rating ? 'text-amber-400 fill-current' : 'text-slate-300'"
            viewBox="0 0 20 20">
            <path
              d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.37 2.449a1 1 0 00-.364 1.118l1.287 3.957c.3.921-.755 1.688-1.54 1.118l-3.37-2.449a1 1 0 00-1.176 0l-3.37 2.449c-.784.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.075 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" />
          </svg>
        </button>
      </div>

      <textarea v-model="ratingForm.review" placeholder="Tulis ulasan (opsional)"
        class="w-full border border-slate-200 rounded-lg p-3 text-sm resize-none focus:ring-2 focus:ring-amber-400"
        rows="3"></textarea>

      <button @click="submitRating" :disabled="isSubmittingRating"
        class="w-full mt-4 bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 rounded-lg transition-all">
        {{ isSubmittingRating ? 'Mengirim...' : 'Kirim Ulasan' }}
      </button>
    </div>
  </div>
</template>