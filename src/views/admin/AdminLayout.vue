<script setup>
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div>
    <!-- Sidebar -->
    <div style="min-height:100vh;background:#1a1a2e;width:240px;position:fixed;top:0;left:0;">
      <div style="padding:1.2rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.1);color:#fff;font-weight:700;font-size:1.1rem;">
        <i class="fas fa-futbol"></i> Futsal Admin
      </div>
      <nav class="nav flex-column mt-2">
        <RouterLink to="/admin/dashboard"
          class="nav-link"
          :class="route.name === 'admin.dashboard' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;">
          <i class="bi bi-speedometer2" style="margin-right:8px;"></i> Dashboard
        </RouterLink>
        <RouterLink to="/admin/lapangan"
          class="nav-link"
          :class="route.name === 'admin.lapangan' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;">
          <i class="bi bi-grid-3x3-gap" style="margin-right:8px;"></i> Lapangan
        </RouterLink>
        <RouterLink to="/admin/booking"
          class="nav-link"
          :class="route.name === 'admin.booking' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;">
          <i class="bi bi-calendar-check" style="margin-right:8px;"></i> Booking
        </RouterLink>
        <RouterLink to="/admin/pembayaran"
          class="nav-link"
          :class="route.name === 'admin.pembayaran' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;">
          <i class="bi bi-credit-card" style="margin-right:8px;"></i> Pembayaran
        </RouterLink>
      </nav>
      <div style="position:absolute;bottom:0;width:100%;padding:.75rem;">
        <button @click="logout" class="btn btn-outline-danger btn-sm w-100">
          <i class="bi bi-box-arrow-right"></i> Logout
        </button>
      </div>
    </div>

    <!-- Main Content -->
    <div style="margin-left:240px;">
      <!-- Topbar -->
      <div style="background:#fff;padding:.75rem 1.5rem;border-bottom:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;">
        <h6 class="mb-0 fw-semibold">
          <span v-if="route.name === 'admin.dashboard'">Dashboard</span>
          <span v-else-if="route.name === 'admin.lapangan'">Manajemen Lapangan</span>
          <span v-else-if="route.name === 'admin.booking'">Manajemen Booking</span>
          <span v-else-if="route.name === 'admin.pembayaran'">Verifikasi Pembayaran</span>
          <span v-else>Admin</span>
        </h6>
        <span class="text-muted small"><i class="bi bi-person-circle"></i> {{ auth.admin?.nama }}</span>
      </div>
      <!-- Content Area -->
      <div style="padding:1.5rem;background:#f0f4f8;min-height:calc(100vh - 54px);">
        <RouterView />
      </div>
    </div>
  </div>
</template>

<style scoped>
.nav-link:hover,
.nav-link.active {
  color: #fff !important;
  background: rgba(255,255,255,0.1);
  border-left: 3px solid #0d6efd;
}
.nav-link:not(.active) {
  border-left: 3px solid transparent;
}
</style>
