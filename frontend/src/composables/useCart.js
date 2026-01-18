import { ref } from 'vue';
import api from '@/services/api';

const cartCount = ref(0);

export function useCart() {

  const fetchCartCount = async () => {
    const token = localStorage.getItem('token');

    if (!token) {
      cartCount.value = 0;
      return;
    }

    try {
      const res = await api.get('/user/cart');
      const responseData = res.data.data;

      if (responseData && Array.isArray(responseData.items)) {
        cartCount.value = responseData.items.length;

      } else {
        cartCount.value = 0;
      }

    } catch (error) {
      console.error("Gagal update cart count:", error);
      cartCount.value = 0;
    }
  };

  return {
    cartCount,
    fetchCartCount
  };
}