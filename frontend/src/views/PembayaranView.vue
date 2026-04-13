<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import api from '@/utils/api'

const route  = useRoute()
const router = useRouter()

const booking      = ref(null)
const loading      = ref(true)
const midtransLoading = ref(false)
const error        = ref('')
const midtransError = ref('')
const midtransNotice = ref('')
const refreshing   = ref(false)
const successPopupShown = ref(false)
let   pollInterval = null
const midtransClientKey = (import.meta.env.VITE_MIDTRANS_CLIENT_KEY || '').trim()
const midtransIsProduction = String(import.meta.env.VITE_MIDTRANS_IS_PRODUCTION || 'false').toLowerCase() === 'true'
const midtransSnapScriptSrc = midtransIsProduction
  ? 'https://app.midtrans.com/snap/snap.js'
  : 'https://app.sandbox.midtrans.com/snap/snap.js'

function canStartMidtransPayment() {
  if (!booking.value) return false
  if (!midtransClientKey) return false
  if (booking.value.status === 'batal' || booking.value.status === 'selesai') return false

  const status = booking.value?.pembayaran?.status_verifikasi
  return status !== 'diterima'
}

function midtransStatusLabel(status) {
  const s = String(status || '').toLowerCase()
  if (!s) return '-'
  if (s === 'settlement' || s === 'capture') return 'Berhasil'
  if (s === 'pending') return 'Pending'
  if (s === 'deny' || s === 'cancel' || s === 'expire' || s === 'failure') return 'Gagal'
  return s.charAt(0).toUpperCase() + s.slice(1)
}

function showSuccessPopup() {
  if (successPopupShown.value) return

  successPopupShown.value = true
  Swal.fire({
    icon: 'success',
    title: 'Pembayaran Berhasil',
    text: 'Pembayaran sudah diterima. Booking kamu terkonfirmasi.',
    confirmButtonText: 'Oke',
    confirmButtonColor: '#2563eb',
  })
}

async function loadMidtransSnapScript() {
  if (window.snap) return

  const existingScript = document.getElementById('midtrans-snap-script')
  if (existingScript) {
    await new Promise((resolve, reject) => {
      existingScript.addEventListener('load', resolve, { once: true })
      existingScript.addEventListener('error', () => reject(new Error('Gagal memuat Midtrans Snap.')), { once: true })
    })
    return
  }

  await new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.id = 'midtrans-snap-script'
    script.src = midtransSnapScriptSrc
    script.setAttribute('data-client-key', midtransClientKey)
    script.onload = resolve
    script.onerror = () => reject(new Error('Gagal memuat Midtrans Snap.'))
    document.body.appendChild(script)
  })
}

async function bayarDenganMidtrans() {
  midtransError.value = ''
  midtransNotice.value = ''

  if (!midtransClientKey) {
    midtransError.value = 'Client key Midtrans belum diisi di frontend.'
    return
  }

  if (!booking.value?.booking_id) {
    midtransError.value = 'Data booking tidak valid untuk pembayaran Midtrans.'
    return
  }

  midtransLoading.value = true
  try {
    await loadMidtransSnapScript()

    const res = await api.post('/pembayaran/midtrans/token', {
      booking_id: booking.value.booking_id,
      return_url: `${window.location.origin}/pembayaran/${booking.value.booking_id}`,
    })

    const snapToken = res.data?.snap_token
    const redirectUrl = res.data?.redirect_url

    // Paling stabil: gunakan redirect URL dari backend agar environment Midtrans pasti sinkron.
    if (redirectUrl) {
      window.location.href = redirectUrl
      return
    }

    if (!snapToken || !window.snap) {
      throw new Error('Snap token Midtrans tidak tersedia.')
    }

    window.snap.pay(snapToken, {
      onSuccess: async (result) => {
        await sinkronkanStatusMidtrans({
          ...result,
          transaction_status: 'settlement',
        })
        midtransNotice.value = 'Pembayaran berhasil. Status booking akan diperbarui otomatis.'
        await fetchBooking()
        showSuccessPopup()
      },
      onPending: async (result) => {
        await sinkronkanStatusMidtrans(result)
        midtransNotice.value = 'Pembayaran masih pending. Selesaikan pembayaran lalu cek status.'
        await fetchBooking()
      },
      onError: async (result) => {
        await sinkronkanStatusMidtrans(result)
        midtransError.value = 'Transaksi Midtrans gagal diproses.'
        await fetchBooking()
      },
      onClose: () => {
        midtransNotice.value = 'Popup Midtrans ditutup. Kamu bisa lanjutkan pembayaran kapan saja.'
      },
    })
  } catch (err) {
    const data = err.response?.data
    midtransError.value = data?.message || err.message || 'Gagal memulai pembayaran Midtrans.'
  } finally {
    midtransLoading.value = false
  }
}

async function sinkronkanStatusMidtrans(result = null) {
  if (!booking.value?.booking_id) return

  try {
    const orderId = String(result?.order_id || '')

    await api.post('/pembayaran/midtrans/sync', {
      booking_id: booking.value.booking_id,
      order_id: orderId || undefined,
      transaction_status: result?.transaction_status || undefined,
      transaction_id: result?.transaction_id || undefined,
      payment_type: result?.payment_type || undefined,
      fraud_status: result?.fraud_status || undefined,
    })
  } catch (err) {
    // Sinkronisasi status bersifat best effort, tidak perlu memblokir UX pembayaran.
  }
}

function applyMidtransReturnNoticeFromQuery() {
  const query = route.query || {}
  const returnFlag = String(query.midtrans_return || '').toLowerCase()
  const txStatus = String(query.transaction_status || '').toLowerCase()

  if (txStatus === 'settlement' || txStatus === 'capture') {
    midtransNotice.value = 'Pembayaran berhasil. Status booking akan diperbarui otomatis.'
    showSuccessPopup()
  } else if (txStatus === 'pending' || returnFlag === 'pending') {
    midtransNotice.value = 'Pembayaran masih pending. Selesaikan pembayaran lalu cek status.'
  } else if (['deny', 'cancel', 'expire', 'failure'].includes(txStatus) || returnFlag === 'error') {
    midtransError.value = 'Pembayaran gagal atau dibatalkan. Silakan coba lagi.'
  } else if (returnFlag === 'success') {
    midtransNotice.value = 'Pembayaran selesai. Status booking akan diperbarui otomatis.'
    showSuccessPopup()
  }

  const hasMidtransQuery = Boolean(
    query.midtrans_return || query.transaction_status || query.order_id || query.status_code
  )

  if (hasMidtransQuery) {
    const cleanedQuery = { ...query }
    delete cleanedQuery.midtrans_return
    delete cleanedQuery.transaction_status
    delete cleanedQuery.order_id
    delete cleanedQuery.status_code

    router.replace({ path: route.path, query: cleanedQuery }).catch(() => {})
  }
}

async function fetchBooking() {
  try {
    const res = await api.get(`/booking/${route.params.id}/detail`)
    booking.value = res.data
  } catch {
    error.value = 'Booking tidak ditemukan.'
  }
}

async function refreshStatus() {
  refreshing.value = true
  await fetchBooking()
  refreshing.value = false
}

onMounted(async () => {
  await fetchBooking()
  applyMidtransReturnNoticeFromQuery()
  const hasMidtransReturn = Boolean(route.query?.midtrans_return || route.query?.transaction_status || route.query?.order_id)
  if (hasMidtransReturn) {
    const returnFlag = String(route.query?.midtrans_return || '').toLowerCase()
    const txStatusFromQuery = String(route.query?.transaction_status || '').toLowerCase()

    await sinkronkanStatusMidtrans({
      order_id: String(route.query?.order_id || ''),
      transaction_status: txStatusFromQuery || (returnFlag === 'success' ? 'settlement' : ''),
    })
    await fetchBooking()
  }
  loading.value = false

  // Auto-polling setiap 10 detik selama status masih menunggu
  pollInterval = setInterval(async () => {
    const status = booking.value?.pembayaran?.status_verifikasi
    if (status === 'menunggu') {
      await fetchBooking()
    } else {
      clearInterval(pollInterval)
    }
  }, 10000)
})

onUnmounted(() => {
  clearInterval(pollInterval)
})

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n) }

function formatTanggal(t) {
  if (!t) return '-'
  return new Date(t).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

function formatJam(t) {
  if (!t) return ''
  return t.substring(0, 5)
}
</script>

<template>
  <div style="background:#f5f6fa;font-family:'Segoe UI',sans-serif;min-height:100vh;">
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

    <div class="container my-4">

      <div v-if="loading" class="text-center py-5">
        <div class="spinner-border text-warning"></div>
      </div>

      <template v-else-if="booking">
        <!-- Header -->
        <div class="rounded-3 mb-4 px-4 py-4 text-white" style="background:linear-gradient(135deg,#1e3a5f,#2563eb);">
          <div class="d-flex align-items-center gap-3">
            <div style="font-size:2.5rem;"><i class="bi bi-receipt-cutoff"></i></div>
            <div>
              <h4 class="mb-0 fw-bold">Pembayaran Booking</h4>
              <small class="opacity-75">Selesaikan pembayaran online via Midtrans</small>
            </div>
          </div>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-7">

            <!-- Detail Booking -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body p-0">
                <div class="px-4 py-3 fw-bold border-bottom" style="background:#f8f9fa;border-radius:14px 14px 0 0;">
                  <i class="bi bi-receipt text-primary me-2"></i>Detail Booking
                </div>
                <div class="p-4">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <small class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.5px;">Nama Penyewa</small>
                      <div class="fw-semibold mt-1">{{ booking.nama_penyewa }}</div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.5px;">No. HP</small>
                      <div class="fw-semibold mt-1">{{ booking.no_hp }}</div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.5px;">Lapangan</small>
                      <div class="fw-semibold mt-1">Lapangan {{ booking.lapangan?.nomor_lapangan ?? '-' }}</div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.5px;">Tanggal</small>
                      <div class="fw-semibold mt-1">{{ formatTanggal(booking.tanggal_booking) }}</div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.5px;">Jam</small>
                      <div class="fw-semibold mt-1">{{ formatJam(booking.jam_mulai) }} &ndash; {{ formatJam(booking.jam_selesai) }} WIB</div>
                    </div>
                    <div class="col-md-6">
                      <small class="text-muted text-uppercase" style="font-size:.75rem;letter-spacing:.5px;">Total Harga</small>
                      <div class="fw-bold mt-1 fs-5" style="color:#f59e0b;">Rp {{ formatRupiah(booking.total_harga) }}</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Midtrans -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body p-0">
                <div class="px-4 py-3 fw-bold border-bottom" style="background:#f8f9fa;border-radius:14px 14px 0 0;">
                  <i class="bi bi-lightning-charge-fill text-warning me-2"></i>Pembayaran Online (Midtrans)
                </div>
                <div class="p-4">
                  <div v-if="midtransError" class="alert alert-danger border-0 py-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>{{ midtransError }}
                  </div>
                  <div v-if="midtransNotice" class="alert alert-info border-0 py-2 mb-3">
                    <i class="bi bi-info-circle-fill me-1"></i>{{ midtransNotice }}
                  </div>

                  <div v-if="!midtransClientKey" class="alert alert-warning border-0 py-2 mb-3" style="font-size:.9rem;">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Midtrans belum aktif di frontend (VITE_MIDTRANS_CLIENT_KEY kosong).
                  </div>

                  <div v-if="booking.pembayaran?.gateway_transaction_status" class="mb-3">
                    <small class="text-muted d-block mb-1">Status Transaksi Midtrans</small>
                    <span class="badge px-3 py-2"
                      :class="{
                        'bg-warning text-dark': booking.pembayaran.gateway_transaction_status === 'pending',
                        'bg-success': booking.pembayaran.gateway_transaction_status === 'settlement' || booking.pembayaran.gateway_transaction_status === 'capture',
                        'bg-danger': ['deny','cancel','expire','failure'].includes(booking.pembayaran.gateway_transaction_status),
                      }">
                      {{ midtransStatusLabel(booking.pembayaran.gateway_transaction_status) }}
                    </span>
                  </div>

                  <button
                    v-if="canStartMidtransPayment()"
                    @click="bayarDenganMidtrans"
                    :disabled="midtransLoading"
                    class="btn fw-bold w-100 py-2"
                    style="background:linear-gradient(135deg,#2563eb,#1d4ed8);color:#fff;border:none;border-radius:10px;">
                    <span v-if="midtransLoading" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-credit-card-2-front me-2"></i>
                    {{ midtransLoading ? 'Menyiapkan Midtrans...' : 'Bayar Sekarang via Midtrans' }}
                  </button>

                  <div class="text-muted small mt-2">
                    <i class="bi bi-shield-check me-1"></i>
                    Aman dibayar lewat VA, e-wallet, QRIS, dan metode lain di Midtrans.
                  </div>

                  <div v-if="booking.pembayaran?.status_verifikasi" class="mt-3">
                    <small class="text-muted d-block mb-1">Status Verifikasi Booking</small>
                    <span class="badge px-3 py-2"
                      :class="{
                        'bg-warning text-dark': booking.pembayaran.status_verifikasi === 'menunggu',
                        'bg-success': booking.pembayaran.status_verifikasi === 'diterima',
                        'bg-danger': booking.pembayaran.status_verifikasi === 'ditolak',
                      }">
                      {{ booking.pembayaran.status_verifikasi.charAt(0).toUpperCase() + booking.pembayaran.status_verifikasi.slice(1) }}
                    </span>
                  </div>

                  <div v-if="booking.pembayaran?.status_verifikasi === 'diterima'" class="alert alert-success border-0 py-2 mt-3 mb-0" style="font-size:.9rem;">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Pembayaran sudah diterima. Booking kamu terkonfirmasi.
                  </div>

                  <div class="mt-3">
                    <button @click="refreshStatus" :disabled="refreshing" class="btn btn-outline-secondary btn-sm px-4" style="border-radius:8px;">
                      <span v-if="refreshing" class="spinner-border spinner-border-sm me-1"></span>
                      <i v-else class="bi bi-arrow-clockwise me-1"></i>
                      {{ refreshing ? 'Mengecek...' : 'Cek Status Sekarang' }}
                    </button>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      </template>

      <div v-else class="text-center py-5 text-muted">
        <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
        {{ error || 'Booking tidak ditemukan.' }}
      </div>

    </div>

    <!-- Footer -->
    <footer class="text-center text-muted py-4 mt-4" style="font-size:.85rem;border-top:1px solid #e9ecef;">
      &copy; {{ new Date().getFullYear() }} Mhmmdzakky &mdash; Sistem Booking Lapangan Futsal
    </footer>
  </div>
</template>
