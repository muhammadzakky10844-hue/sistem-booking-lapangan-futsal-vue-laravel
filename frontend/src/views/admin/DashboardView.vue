<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/api'
import { useAuthStore } from '@/stores/auth'

const stats   = ref(null)
const loading = ref(true)
const auth    = useAuthStore()

onMounted(async () => {
  try {
    const res = await api.get('/admin/dashboard')
    stats.value = res.data
  } finally {
    loading.value = false
  }
})

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n) }

function formatTanggal(t) {
  if (!t) return '-'
  return new Date(t).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })
}

const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' })
</script>

<template>
  <div>
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-warning"></div>
    </div>

    <template v-else-if="stats">
      <!-- Welcome Banner -->
      <div class="rounded-3 mb-4 px-4 py-4 text-white" style="background:linear-gradient(135deg,#1a1a2e,#0d6efd);">
        <div class="d-flex align-items-center gap-3">
          <div style="font-size:2.5rem;"><i class="bi bi-speedometer2"></i></div>
          <div>
            <h5 class="mb-0 fw-bold">Selamat Datang, {{ auth.admin?.nama }}!</h5>
            <small class="opacity-75">{{ today }} &mdash; Sistem Booking Lapangan Futsal</small>
          </div>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center"
                   style="width:56px;height:56px;background:#e0f2fe;font-size:1.6rem;color:#0284c7;flex-shrink:0;">
                <i class="bi bi-grid-3x3-gap-fill"></i>
              </div>
              <div>
                <div class="text-muted small">Total Lapangan</div>
                <div class="fw-bold fs-3 lh-1">{{ stats.lapangan.total }}</div>
                <div style="font-size:.75rem;">
                  <span class="text-success"><i class="bi bi-circle-fill" style="font-size:.5rem;"></i> {{ stats.lapangan.tersedia }} Tersedia</span>
                  &nbsp;
                  <span class="text-danger"><i class="bi bi-circle-fill" style="font-size:.5rem;"></i> {{ stats.lapangan.perbaikan }} Perbaikan</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center"
                   style="width:56px;height:56px;background:#fef9c3;font-size:1.6rem;color:#ca8a04;flex-shrink:0;">
                <i class="bi bi-calendar-check-fill"></i>
              </div>
              <div>
                <div class="text-muted small">Total Booking</div>
                <div class="fw-bold fs-3 lh-1">{{ stats.booking.total }}</div>
                <div style="font-size:.75rem;" class="text-warning">
                  <i class="bi bi-hourglass-split"></i> {{ stats.booking.menunggu }} menunggu konfirmasi
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center"
                   style="width:56px;height:56px;background:#fce7f3;font-size:1.6rem;color:#db2777;flex-shrink:0;">
                <i class="bi bi-credit-card-2-front-fill"></i>
              </div>
              <div>
                <div class="text-muted small">Pembayaran Masuk</div>
                <div class="fw-bold fs-3 lh-1">{{ stats.pembayaran.total }}</div>
                <div style="font-size:.75rem;" class="text-danger">
                  <i class="bi bi-exclamation-circle"></i> {{ stats.pembayaran.menunggu }} perlu diverifikasi
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-sm-6 col-xl-3">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="rounded-3 d-flex align-items-center justify-content-center"
                   style="width:56px;height:56px;background:#dcfce7;font-size:1.6rem;color:#16a34a;flex-shrink:0;">
                <i class="bi bi-cash-stack"></i>
              </div>
              <div>
                <div class="text-muted small">Total Pendapatan</div>
                <div class="fw-bold fs-4 lh-1" style="color:#16a34a;">
                  Rp {{ formatRupiah(stats.total_pendapatan) }}
                </div>
                <div style="font-size:.75rem;" class="text-muted">
                  dari {{ stats.booking.selesai }} booking selesai
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Rows -->
      <div class="row g-3 mb-4">
        <div class="col-lg-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Status Booking</h6>
              <div class="row g-2">
                <div class="col-6">
                  <div class="rounded-3 p-3 text-center" style="background:#fff3cd;">
                    <div class="fw-bold fs-4 text-warning">{{ stats.booking.menunggu }}</div>
                    <div class="small text-muted">Menunggu</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="rounded-3 p-3 text-center" style="background:#cff4fc;">
                    <div class="fw-bold fs-4" style="color:#0284c7;">{{ stats.booking.terkonfirmasi }}</div>
                    <div class="small text-muted">Terkonfirmasi</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="rounded-3 p-3 text-center" style="background:#d1fae5;">
                    <div class="fw-bold fs-4 text-success">{{ stats.booking.selesai }}</div>
                    <div class="small text-muted">Selesai</div>
                  </div>
                </div>
                <div class="col-6">
                  <div class="rounded-3 p-3 text-center" style="background:#fee2e2;">
                    <div class="fw-bold fs-4 text-danger">{{ stats.booking.batal }}</div>
                    <div class="small text-muted">Batal</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-success me-2"></i>Status Verifikasi Pembayaran</h6>
              <div class="row g-2">
                <div class="col-4">
                  <div class="rounded-3 p-3 text-center" style="background:#fff3cd;">
                    <div class="fw-bold fs-4 text-warning">{{ stats.pembayaran.menunggu }}</div>
                    <div class="small text-muted">Menunggu</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="rounded-3 p-3 text-center" style="background:#d1fae5;">
                    <div class="fw-bold fs-4 text-success">{{ stats.pembayaran.diterima }}</div>
                    <div class="small text-muted">Diterima</div>
                  </div>
                </div>
                <div class="col-4">
                  <div class="rounded-3 p-3 text-center" style="background:#fee2e2;">
                    <div class="fw-bold fs-4 text-danger">{{ stats.pembayaran.ditolak }}</div>
                    <div class="small text-muted">Ditolak</div>
                  </div>
                </div>
              </div>
              <div class="mt-3 d-flex gap-2">
                <RouterLink v-if="stats.pembayaran.menunggu > 0" to="/admin/pembayaran" class="btn btn-sm btn-warning">
                  <i class="bi bi-bell-fill me-1"></i>{{ stats.pembayaran.menunggu }} pembayaran perlu diverifikasi
                </RouterLink>
                <span v-else class="text-muted small"><i class="bi bi-check-circle-fill text-success me-1"></i>Semua pembayaran sudah diverifikasi</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Booking Terbaru -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="px-4 py-3 fw-bold border-bottom d-flex justify-content-between align-items-center"
               style="background:#f8f9fa;border-radius:14px 14px 0 0;">
            <span><i class="bi bi-clock-history text-primary me-2"></i>Booking Terbaru</span>
            <RouterLink to="/admin/booking" class="btn btn-sm btn-outline-primary">Lihat Semua</RouterLink>
          </div>
          <div class="table-responsive">
            <table class="table table-hover mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Penyewa</th>
                  <th>Lapangan</th>
                  <th>Tanggal</th>
                  <th>Jam</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="b in stats.booking_terbaru" :key="b.booking_id">
                  <td class="ps-4">
                    <div class="fw-semibold">{{ b.nama_penyewa }}</div>
                    <small class="text-muted">{{ b.no_hp }}</small>
                  </td>
                  <td>Lapangan {{ b.lapangan?.nomor_lapangan ?? '-' }}</td>
                  <td>{{ formatTanggal(b.tanggal_booking) }}</td>
                  <td>{{ b.jam_mulai?.substring(0,5) }} - {{ b.jam_selesai?.substring(0,5) }}</td>
                  <td>Rp {{ formatRupiah(b.total_harga) }}</td>
                  <td>
                    <span class="badge"
                      :class="{
                        'bg-warning text-dark': b.status === 'menunggu',
                        'bg-primary': b.status === 'terkonfirmasi',
                        'bg-success': b.status === 'selesai',
                        'bg-danger': b.status === 'batal',
                      }">{{ b.status }}</span>
                  </td>
                </tr>
                <tr v-if="!stats.booking_terbaru?.length">
                  <td colspan="6" class="text-center text-muted py-4">Belum ada data booking.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>
