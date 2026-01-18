import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import api from '@/services/api';
import { useAuthStore } from '@/stores/auth';

const app = createApp(App);

const token = localStorage.getItem('token');

if (token) {
  api.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

app.use(router);

router.isReady().then(async () => {
  const auth = useAuthStore();
  if (token) {
    await auth.fetchMe();
  }else{
    auth.state.initialized = true;
  }
  app.mount('#app');
});
