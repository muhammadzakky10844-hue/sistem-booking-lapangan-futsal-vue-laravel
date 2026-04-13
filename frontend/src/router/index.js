import { createRouter, createWebHashHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

// Public
import HomeView        from '@/views/HomeView.vue'
import BookingView     from '@/views/BookingView.vue'
import PembayaranView  from '@/views/PembayaranView.vue'

// Auth
import LoginView       from '@/views/LoginView.vue'

// Admin
import AdminLayout     from '@/views/admin/AdminLayout.vue'
import DashboardView   from '@/views/admin/DashboardView.vue'
import AdminLapangan   from '@/views/admin/LapanganView.vue'
import AdminBooking    from '@/views/admin/BookingView.vue'
import AdminPembayaran from '@/views/admin/PembayaranView.vue'

const routes = [
  // Public
  { path: '/',                name: 'home',       component: HomeView },
  { path: '/booking',         name: 'booking',    component: BookingView },
  { path: '/pembayaran/:id',  name: 'pembayaran', component: PembayaranView },

  // Auth
  { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },

  // Admin
  {
    path: '/admin',
    component: AdminLayout,
    meta: { requiresAuth: true },
    children: [
      { path: '',           redirect: '/admin/dashboard' },
      { path: 'dashboard',  name: 'admin.dashboard',  component: DashboardView },
      { path: 'lapangan',   name: 'admin.lapangan',   component: AdminLapangan },
      { path: 'booking',    name: 'admin.booking',    component: AdminBooking },
      { path: 'pembayaran', name: 'admin.pembayaran', component: AdminPembayaran },
    ],
  },

  // 404
  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHashHistory(),
  routes,
})

// Navigation guard
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!auth.isAuthenticated) {
    await auth.checkAuth()
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return '/admin/dashboard'
  }
})

export default router
