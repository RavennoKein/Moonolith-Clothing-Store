import { reactive, computed } from 'vue';
import api from '@/services/api';

const state = reactive({
  user: null,
  loading: false,
  initialized: false,
});

export const useAuthStore = () => {
  const isLoggedIn = computed(() => !!state.user);
  const role = computed(() => state.user?.role || null);

  const fetchMe = async () => {
    try {
      state.loading = true;
      const res = await api.get('/auth/me');
      state.user = res.data.user;
    } catch (e) {
      state.user = null;
      localStorage.removeItem('token');
    } finally {
      state.loading = false;
      state.initialized = true;
    }
  };

  const login = async (payload) => {
    const res = await api.post('/auth/login', payload);
    localStorage.setItem('token', res.data.token);
    await fetchMe();
  };

  const register = async (payload) => {
    const res = await api.post('/auth/register', payload);
    localStorage.setItem('token', res.data.token);
    await fetchMe();
  };

  const logout = async () => {
    try {
      await api.post('/auth/logout');
    } catch (_) {
      
    } finally {
      state.user = null;
      state.initialized = true;
      localStorage.removeItem('token');
    }
  };

  const updateProfile = async (payload) => {
    await api.put('/user/profile/update', payload);
    await fetchMe();
  };

  return {
    state,
    isLoggedIn,
    role,
    fetchMe,
    login,
    register,
    logout,
    updateProfile,
  };
};
