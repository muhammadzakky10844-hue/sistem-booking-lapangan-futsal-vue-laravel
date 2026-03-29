<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/utils/api'

const router    = useRouter()
const lapangans = ref([])
const loading   = ref(true)
const apiBase   = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`).replace(/\/+$/, '')

onMounted(async () => {
  try {
    const res = await api.get('/lapangan')
    lapangans.value = res.data
  } finally {
    loading.value = false
  }
})

function bookNow(lapanganId) {
  router.push({ name: 'booking', query: { lapangan_id: lapanganId } })
}

function formatRupiah(n) {
  return new Intl.NumberFormat('id-ID').format(n)
}
</script>

<template>
  <div style="background:#f5f6fa; font-family:'Segoe UI',sans-serif; min-height:100vh;">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark"
         style="background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);box-shadow:0 2px 15px rgba(0,0,0,0.3);">
      <div class="container">
        <RouterLink to="/" class="navbar-brand fw-bold" style="font-size:1.3rem;letter-spacing:.5px;">
          <i class="fas fa-futbol text-warning"></i> J<span style="color:#f59e0b;">F</span>
        </RouterLink>
        <div class="ms-auto">
          <RouterLink to="/booking" class="btn fw-semibold"
                      style="background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;border:none;border-radius:50px;padding:.4rem 1.2rem;">
            <i class="bi bi-calendar-plus"></i> Booking Sekarang
          </RouterLink>
        </div>
      </div>
    </nav>

    <!-- Content -->
    <div class="container my-4">

      <!-- Hero Banner -->
      <div class="rounded-3 mb-4 px-4 py-5 text-white text-center"
           style="background:linear-gradient(135deg,#0f2027,#203a43,#2c5364);">
        <div style="font-size:3rem;"><i class="fas fa-futbol text-warning"></i></div>
        <h3 class="fw-bold mt-2 mb-1">J <span class="text-warning">F</span></h3>
        <h3 class="fw-bold mt-2 mb-1">Booking Lapangan Futsal</h3>
        <p class="opacity-75 mb-3">Pilih lapangan yang tersedia lalu klik <strong>Sewa Sekarang</strong></p>
        <span class="badge px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.15);font-size:.9rem;">
          <i class="bi bi-circle-fill text-success me-1" style="font-size:.5rem;"></i>
          {{ lapangans.length }} Lapangan Tersedia
        </span>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-warning"></div>
      </div>

      <!-- Cards -->
      <div v-else class="row g-4">
        <template v-if="lapangans.length === 0">
          <div class="col-12 text-center py-5">
            <div style="font-size:4rem;color:#dee2e6;"><i class="bi bi-inbox"></i></div>
            <h5 class="text-muted mt-3">Belum ada lapangan tersedia saat ini</h5>
          </div>
        </template>

        <div v-for="lap in lapangans" :key="lap.lapangan_id" class="col-md-6 col-lg-4">
          <div class="card border-0 shadow-sm h-100" style="border-radius:16px;overflow:hidden;">
            <img v-if="lap.gambar"
              :src="`${apiBase}/media/lapangan/${encodeURIComponent(lap.gambar)}`"
                 :alt="`Lapangan ${lap.nomor_lapangan}`"
                 style="width:100%;height:200px;object-fit:cover;">
            <div v-else class="d-flex align-items-center justify-content-center text-white"
                 style="height:200px;background:linear-gradient(135deg,#0f2027,#2c5364);">
              <div class="text-center">
                <i class="fas fa-futbol" style="font-size:4rem;opacity:.5;"></i>
                <div class="mt-2 opacity-50 small">Foto belum tersedia</div>
              </div>
            </div>

            <div style="position:relative;">
              <span class="badge position-absolute"
                    :class="lap.is_full_today ? 'bg-danger' : 'bg-success'"
                    style="top:-14px;right:16px;padding:.4rem .9rem;font-size:.8rem;border-radius:20px;">
                <i :class="lap.is_full_today ? 'bi bi-x-circle me-1' : 'bi bi-check-circle me-1'"></i>
                {{ lap.is_full_today ? 'Full Hari Ini' : 'Tersedia' }}
              </span>
            </div>

            <div class="card-body p-4 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-start mb-2">
                <h5 class="fw-bold mb-0">Lapangan {{ lap.nomor_lapangan }}</h5>
                <span class="fw-bold" style="color:#f59e0b;font-size:1rem;white-space:nowrap;">
                  Rp {{ formatRupiah(lap.harga_per_jam) }}<small class="fw-normal text-muted">/jam</small>
                </span>
              </div>

              <p class="text-muted small mb-3">
                {{ lap.deskripsi || 'Lapangan futsal standar, kondisi baik dan siap digunakan.' }}
              </p>

              <!-- Mini Slot Grid Hari Ini -->
              <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small fw-semibold text-secondary" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.4px;">
                    <i class="bi bi-clock me-1"></i>Slot Hari Ini
                  </span>
                  <div class="d-flex gap-2" style="font-size:.68rem;color:#888;">
                    <span><span style="display:inline-block;width:9px;height:9px;background:#22c55e;border-radius:2px;"></span> Tersedia</span>
                    <span><span style="display:inline-block;width:9px;height:9px;background:#ef4444;border-radius:2px;"></span> Terisi</span>
                  </div>
                </div>
                <div class="d-flex flex-wrap gap-1">
                  <span v-for="slot in lap.slots_hari_ini" :key="slot.jam"
                        class="badge"
                        :style="slot.terisi
                          ? 'background:#fee2e2;color:#dc2626;border:1px solid #fca5a5;font-weight:500;font-size:.68rem;padding:3px 6px;border-radius:6px;'
                          : 'background:#dcfce7;color:#16a34a;border:1px solid #86efac;font-weight:500;font-size:.68rem;padding:3px 6px;border-radius:6px;'">
                    {{ slot.jam }}
                  </span>
                </div>
              </div>

              <!-- Full hari ini: tampilkan info, sembunyikan tombol -->
              <div v-if="lap.is_full_today"
                   class="rounded-3 p-3 text-center"
                   style="background:#fff1f2;border:1.5px dashed #f87171;">
                <i class="bi bi-calendar-x text-danger" style="font-size:1.4rem;"></i>
                <div class="fw-bold text-danger mt-1" style="font-size:.9rem;">Full Booking Hari Ini</div>
                <div class="text-muted mt-1" style="font-size:.78rem;">
                  Semua slot sudah terisi. Coba booking untuk hari lain.
                </div>
              </div>

              <!-- Tersedia: tampilkan tombol sewa -->
              <button v-else @click="bookNow(lap.lapangan_id)" class="btn fw-bold w-100"
                      style="background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;border:none;border-radius:10px;padding:.6rem;">
                <i class="bi bi-calendar-plus me-2"></i>Sewa Sekarang
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- How It Works -->
      <div class="card border-0 shadow-sm mt-4" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
        <div class="card-body py-3 px-4">
          <div class="row g-3 text-center">
            <div class="col-md-4">
              <i class="fas fa-futbol fs-4 text-success"></i>
              <div class="small mt-1 fw-semibold">1. Pilih Lapangan</div>
              <div class="small text-muted">Klik Sewa Sekarang</div>
            </div>
            <div class="col-md-4">
              <i class="bi bi-pencil-square fs-4 text-primary"></i>
              <div class="small mt-1 fw-semibold">2. Isi Form Booking</div>
              <div class="small text-muted">Data penyewa &amp; jadwal</div>
            </div>
            <div class="col-md-4">
              <i class="bi bi-credit-card fs-4 text-warning"></i>
              <div class="small mt-1 fw-semibold">3. Upload Pembayaran</div>
              <div class="small text-muted">Transfer &amp; kirim bukti</div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Footer -->
    <footer class="text-center text-muted py-4 mt-4" style="font-size:.85rem;border-top:1px solid #e9ecef;">
      &copy; {{ new Date().getFullYear() }} Mhmmdzakky &mdash; Sistem Booking Lapangan Futsal
    </footer>
  </div>
</template>
