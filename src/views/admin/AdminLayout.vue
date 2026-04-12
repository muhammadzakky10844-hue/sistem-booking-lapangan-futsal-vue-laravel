<script setup>
import { ref, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route  = useRoute()
const auth   = useAuthStore()
const sidebarOpen = ref(false)

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}

function closeSidebar() {
  sidebarOpen.value = false
}

watch(() => route.fullPath, () => {
  closeSidebar()
})

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="admin-shell">
    <div
      v-if="sidebarOpen"
      class="sidebar-backdrop d-lg-none"
      @click="closeSidebar"></div>

    <!-- Sidebar -->
    <aside class="admin-sidebar" :class="{ 'is-open': sidebarOpen }">
      <div style="padding:1.2rem 1.5rem;border-bottom:1px solid rgba(255,255,255,0.1);color:#fff;font-weight:700;font-size:1.1rem;">
        <i class="fas fa-futbol"></i> Futsal Admin
      </div>
      <nav class="nav flex-column mt-2 admin-nav-scroll">
        <RouterLink to="/admin/dashboard"
          class="nav-link"
          :class="route.name === 'admin.dashboard' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;"
          @click="closeSidebar">
          <i class="bi bi-speedometer2" style="margin-right:8px;"></i> Dashboard
        </RouterLink>
        <RouterLink to="/admin/lapangan"
          class="nav-link"
          :class="route.name === 'admin.lapangan' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;"
          @click="closeSidebar">
          <i class="bi bi-grid-3x3-gap" style="margin-right:8px;"></i> Lapangan
        </RouterLink>
        <RouterLink to="/admin/booking"
          class="nav-link"
          :class="route.name === 'admin.booking' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;"
          @click="closeSidebar">
          <i class="bi bi-calendar-check" style="margin-right:8px;"></i> Booking
        </RouterLink>
        <RouterLink to="/admin/pembayaran"
          class="nav-link"
          :class="route.name === 'admin.pembayaran' ? 'active' : ''"
          style="color:rgba(255,255,255,0.7);padding:.65rem 1.5rem;transition:all .2s;"
          @click="closeSidebar">
          <i class="bi bi-credit-card" style="margin-right:8px;"></i> Pembayaran
        </RouterLink>
      </nav>
      <div style="padding:.75rem;margin-top:auto;">
        <button @click="logout" class="btn btn-outline-danger btn-sm w-100">
          <i class="bi bi-box-arrow-right"></i> Logout
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
      <!-- Topbar -->
      <div class="admin-topbar">
        <div class="d-flex align-items-center gap-2">
          <button @click="toggleSidebar" class="btn btn-outline-secondary btn-sm d-lg-none" type="button" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
          </button>
          <h6 class="mb-0 fw-semibold admin-page-title">
            <span v-if="route.name === 'admin.dashboard'">Dashboard</span>
            <span v-else-if="route.name === 'admin.lapangan'">Manajemen Lapangan</span>
            <span v-else-if="route.name === 'admin.booking'">Manajemen Booking</span>
            <span v-else-if="route.name === 'admin.pembayaran'">Pembayaran</span>
            <span v-else>Admin</span>
          </h6>
        </div>
        <span class="text-muted small text-truncate" style="max-width:45%;"><i class="bi bi-person-circle"></i> {{ auth.admin?.nama }}</span>
      </div>

      <!-- Content Area -->
      <div class="admin-content-area">
        <RouterView />
      </div>
    </div>
  </div>
</template>

<style scoped>
.admin-shell {
  min-height: 100vh;
  background: #f0f4f8;
}

.sidebar-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 1030;
}

.admin-sidebar {
  min-height: 100vh;
  background: #1a1a2e;
  width: 240px;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1040;
  display: flex;
  flex-direction: column;
  transition: transform 0.25s ease;
}

.admin-nav-scroll {
  overflow-y: auto;
}

.admin-main {
  margin-left: 240px;
  min-height: 100vh;
}

.admin-topbar {
  background: #fff;
  padding: 0.75rem 1.5rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
}

.admin-content-area {
  padding: 1.5rem;
  background: #f0f4f8;
  min-height: calc(100vh - 54px);
}

.nav-link:hover,
.nav-link.active {
  color: #fff !important;
  background: rgba(255,255,255,0.1);
  border-left: 3px solid #0d6efd;
}
.nav-link:not(.active) {
  border-left: 3px solid transparent;
}

@media (max-width: 991.98px) {
  .admin-sidebar {
    transform: translateX(-100%);
  }

  .admin-sidebar.is-open {
    transform: translateX(0);
  }

  .admin-main {
    margin-left: 0;
  }

  .admin-topbar {
    padding: 0.65rem 1rem;
  }

  .admin-page-title {
    font-size: 0.95rem;
  }

  .admin-content-area {
    padding: 1rem;
  }
}
</style>
