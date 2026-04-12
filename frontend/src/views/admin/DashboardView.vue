<script setup>
import { ref, onMounted, onBeforeUnmount, nextTick } from 'vue'
import api from '@/utils/api'
import { useAuthStore } from '@/stores/auth'
import Chart from 'chart.js/auto'

const stats   = ref(null)
const loading = ref(true)
const chartLoading = ref(false)
const auth    = useAuthStore()
const periodePendapatan = ref('7')
const pendapatanChartRef = ref(null)
const bookingChartRef = ref(null)
const pembayaranChartRef = ref(null)

let pendapatanChart = null
let bookingChart = null
let pembayaranChart = null

function destroyCharts() {
  if (pendapatanChart) {
    pendapatanChart.destroy()
    pendapatanChart = null
  }
  if (bookingChart) {
    bookingChart.destroy()
    bookingChart = null
  }
  if (pembayaranChart) {
    pembayaranChart.destroy()
    pembayaranChart = null
  }
}

function renderCharts() {
  destroyCharts()

  if (!stats.value) return
  if (!pendapatanChartRef.value || !bookingChartRef.value || !pembayaranChartRef.value) return

  const chartPayload = stats.value.charts || {}

  const pendapatanSource = chartPayload.pendapatan_harian || chartPayload.pendapatan_harian_7_hari || {}
  const pendapatanLabels = pendapatanSource.labels || []
  const pendapatanValues = pendapatanSource.values || []

  const bookingLabels = chartPayload.status_booking?.labels || ['Menunggu', 'Terkonfirmasi', 'Selesai', 'Batal']
  const bookingValues = chartPayload.status_booking?.values || [
    stats.value.booking?.menunggu || 0,
    stats.value.booking?.terkonfirmasi || 0,
    stats.value.booking?.selesai || 0,
    stats.value.booking?.batal || 0,
  ]

  const pembayaranLabels = chartPayload.status_pembayaran?.labels || ['Pending', 'Berhasil', 'Gagal']
  const pembayaranValues = chartPayload.status_pembayaran?.values || [
    stats.value.pembayaran?.menunggu || 0,
    stats.value.pembayaran?.diterima || 0,
    stats.value.pembayaran?.ditolak || 0,
  ]

  pendapatanChart = new Chart(pendapatanChartRef.value, {
    type: 'line',
    data: {
      labels: pendapatanLabels,
      datasets: [
        {
          label: `Pendapatan Harian (${periodePendapatan.value} hari)`,
          data: pendapatanValues,
          borderColor: '#2563eb',
          backgroundColor: 'rgba(37, 99, 235, 0.15)',
          fill: true,
          tension: 0.35,
          pointRadius: 3,
          pointHoverRadius: 5,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: (ctx) => `Rp ${formatRupiah(ctx.parsed.y || 0)}`,
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => `Rp ${formatRupiah(value)}`,
          },
        },
      },
    },
  })

  bookingChart = new Chart(bookingChartRef.value, {
    type: 'doughnut',
    data: {
      labels: bookingLabels,
      datasets: [
        {
          data: bookingValues,
          backgroundColor: ['#f59e0b', '#0ea5e9', '#22c55e', '#ef4444'],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  })

  pembayaranChart = new Chart(pembayaranChartRef.value, {
    type: 'doughnut',
    data: {
      labels: pembayaranLabels,
      datasets: [
        {
          data: pembayaranValues,
          backgroundColor: ['#f59e0b', '#16a34a', '#ef4444'],
          borderWidth: 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        },
      },
    },
  })
}

async function loadDashboard(isInitial = false) {
  if (isInitial) {
    loading.value = true
  } else {
    chartLoading.value = true
  }

  try {
    const res = await api.get('/admin/dashboard', {
      params: {
        periode: String(periodePendapatan.value),
      },
    })
    stats.value = res.data

    const apiPeriode = res.data?.charts?.pendapatan_harian?.periode_hari
    if (apiPeriode) {
      periodePendapatan.value = String(apiPeriode)
    }
  } finally {
    if (isInitial) {
      loading.value = false
    } else {
      chartLoading.value = false
    }
  }

  await nextTick()
  renderCharts()
}

async function gantiPeriodePendapatan() {
  await loadDashboard(false)
}

onMounted(async () => {
  await loadDashboard(true)
})

onBeforeUnmount(() => {
  destroyCharts()
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
        <div class="d-flex align-items-center gap-3 flex-wrap">
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
                  <i class="bi bi-hourglass-split"></i> {{ stats.pembayaran.menunggu }} transaksi masih pending
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

      <!-- Charts -->
      <div class="row g-3 mb-4">
        <div class="col-12">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <h6 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Tren Pendapatan Harian</h6>
                <div class="d-flex align-items-center gap-2">
                  <label class="small text-muted mb-0">Periode</label>
                  <select
                    v-model="periodePendapatan"
                    @change="gantiPeriodePendapatan"
                    :disabled="chartLoading"
                    class="form-select form-select-sm"
                    style="width:auto;min-width:120px;">
                    <option value="7">7 Hari</option>
                    <option value="14">14 Hari</option>
                    <option value="30">30 Hari</option>
                  </select>
                </div>
              </div>
              <div v-if="chartLoading" class="text-center py-5">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <div class="small text-muted mt-2">Memuat data chart...</div>
              </div>
              <div v-else style="height:280px;">
                <canvas ref="pendapatanChartRef"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-info me-2"></i>Distribusi Status Booking</h6>
              <div style="height:280px;">
                <canvas ref="bookingChartRef"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-success me-2"></i>Distribusi Status Pembayaran</h6>
              <div style="height:280px;">
                <canvas ref="pembayaranChartRef"></canvas>
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
              <div class="mt-3 d-flex gap-2 flex-wrap">
                <RouterLink v-if="stats.pembayaran.menunggu > 0" to="/admin/pembayaran" class="btn btn-sm btn-warning">
                  <i class="bi bi-clock-history me-1"></i>{{ stats.pembayaran.menunggu }} transaksi pending
                </RouterLink>
                <span v-else class="text-muted small"><i class="bi bi-check-circle-fill text-success me-1"></i>Semua pembayaran berstatus final</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Booking Terbaru -->
      <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
          <div class="px-4 py-3 fw-bold border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2"
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
