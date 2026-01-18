import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import Dashboard from '../views/admin/Dashboard.vue'
import Home from '../views/user/Home.vue'
import Item from '../views/admin/Item.vue'
import Login from '../views/Login.vue'
import Register from '../views/Register.vue'
import User from '../views/admin/User.vue'
import Cashflow from '../views/admin/Cashflow.vue'
import Riwayat from '../views/admin/Riwayat.vue'
import TambahItem from '../views/admin/crud/TambahItem.vue'
import EditItem from '../views/admin/crud/EditItem.vue'
import TambahUser from '../views/admin/crud/TambahUser.vue'
import EditUser from '../views/admin/crud/EditUser.vue'
import DetailItem from '@/views/admin/DetailItem.vue'
import TambahFlashsale from '@/views/admin/crud/TambahFlashsale.vue'
import Profile from '@/views/user/Profile.vue'
import Category from '@/views/admin/Category.vue'
import TambahCategory from '@/views/admin/crud/TambahCategory.vue'
import EditCategory from '@/views/admin/crud/EditCategory.vue'
import Flashsale from '@/views/admin/FlashSale.vue'
import DetailFlashsale from '@/views/admin/DetailFlashsale.vue'
import MenPages from '@/views/user/MenPages.vue'
import WomenPages from '@/views/user/WomenPages.vue'
import KidsPages from '@/views/user/KidsPages.vue'
import MostBuyPages from '@/views/user/MostBuyPages.vue'
import ItemDetail from '@/views/user/ItemDetail.vue'
import Cart from '@/views/user/Cart.vue'
import Checkout from '@/views/user/Checkout.vue'
import Catalog from '@/views/user/Catalog.vue'
import Invoice from '@/views/user/Invoice.vue'

const routes = [
  { path: '/', name: 'Home', component: Home},
  { path: '/admin/dashboard', name: 'Dashboard', component: Dashboard, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/item', name: 'Item', component: Item, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/user', name: 'User', component: User, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/cashflow', name: 'Cashflow', component: Cashflow, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/riwayat', name: 'Riwayat', component: Riwayat, meta: { requiresAuth: true, roles: ['admin', 'super'] } },

  { path: '/login', name: 'Login', component: Login, meta: { guestOnly: true } },
  { path: '/register', name: 'Register', component: Register, meta: { guestOnly: true } },

  { path: '/tambah-item', name: 'TambahItem', component: TambahItem, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/edit-item/:id', name: 'EditItem', component: EditItem, props: true, meta: { requiresAuth: true, roles: ['admin', 'super'] } },

  { path: '/tambah-user', name: 'TambahUser', component: TambahUser, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/edit-user/:id', name: 'EditUser', component: EditUser, meta: { requiresAuth: true, roles: ['admin', 'super'] } },

  { path: '/detail-item/:id', name: 'DetailItem', component: DetailItem, props: true, meta: { requiresAuth: true, roles: ['admin', 'super'] } },

  { path: '/tambah-flashsale', name: 'TambahFlashsale', component: TambahFlashsale, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/flashsale', name: 'Flashsale', component: Flashsale, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/detail-flashsale/:id', name: 'DetailFlashsale', component: DetailFlashsale, props: true, meta: { requiresAuth: true, roles: ['admin', 'super'] } },

  { path: '/category', name: 'Category', component: Category, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/tambah-category', name: 'TambahCategory', component: TambahCategory, meta: { requiresAuth: true, roles: ['admin', 'super'] } },
  { path: '/edit-category/:id', name: 'EditCategory', component: EditCategory, props: true, meta: { requiresAuth: true, roles: ['admin', 'super'] } },

  { path: '/profile', name: 'Profile', component: Profile, meta: { requiresAuth: true, roles: ['user'] } },

  { path: '/men', name: 'MenPages', component: MenPages },
  { path: '/women', name: 'WomenPages', component: WomenPages },
  { path: '/kids', name: 'KidsPages', component: KidsPages },
  { path: '/most-buy', name: 'MostBuyPages', component: MostBuyPages },
  { path: '/item-detail/:id', name: 'ItemDetail', component: ItemDetail, props: true },
  { path: '/cart', name: 'Cart', component: Cart, meta: { requiresAuth: true, roles: ['user'] } },
  { path: '/checkout', name: 'Checkout', component: Checkout, meta: { requiresAuth: true, roles: ['user'] } },
  { path: '/invoice/:invoice', name: 'Invoice', component: Invoice, meta: { requiresAuth: true, roles: ['user'] } },
  { path: '/catalog', name: 'Catalog', component: Catalog },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  if (!auth.state.initialized) {
    return next();
  }

  if (to.meta.requiresAuth && !auth.isLoggedIn.value) {
    return next({ name: 'Login' })
  }

  if (to.meta.guestOnly && auth.isLoggedIn.value) {
    if (auth.role.value === 'admin' || auth.role.value === 'super') {
      return next({ name: 'Dashboard' });
    }
    return next({ name: 'Home' });
  }

  if (to.meta.roles && !to.meta.roles.includes(auth.role.value)) {
    if (auth.role.value === 'admin' || auth.role.value === 'super') {
      return next({ name: 'Dashboard' });
    }
    return next({ name: 'Home' });
  }

  next()
})

export default router
