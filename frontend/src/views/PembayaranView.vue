<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/utils/api'

const route  = useRoute()
const router = useRouter()

const booking      = ref(null)
const loading      = ref(true)
const submitting   = ref(false)
const uploaded     = ref(false)
const error        = ref('')
const refreshing   = ref(false)
let   pollInterval = null
const apiBase      = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`).replace(/\/+$/, '')

const form = ref({
  booking_id:        '',
  metode_pembayaran: '',
  bukti_pembayaran:  null,
})

async function fetchBooking() {
  try {
    const res = await api.get(`/booking/${route.params.id}/detail`)
    booking.value         = res.data
    form.value.booking_id = res.data.booking_id
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

function onFileChange(e) {
  form.value.bukti_pembayaran = e.target.files[0]
}

async function submit() {
  error.value      = ''
  submitting.value = true
  try {
    const fd = new FormData()
    fd.append('booking_id', form.value.booking_id)
    fd.append('metode_pembayaran', form.value.metode_pembayaran)
    fd.append('bukti_pembayaran', form.value.bukti_pembayaran)

    await api.post('/pembayaran/upload', fd, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    await fetchBooking()
    uploaded.value = true
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join(' | ')
    } else {
      error.value = data?.message || 'Gagal upload.'
    }
  } finally {
    submitting.value = false
  }
}

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n) }

function formatTanggal(t) {
  if (!t) return '-'
  return new Date(t).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' })
}

function formatJam(t) {
  if (!t) return ''
  return t.substring(0, 5)
}

function bukaApp(deeplink, fallback) {
  window.location = deeplink
  setTimeout(() => { window.open(fallback, '_blank') }, 1500)
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
              <small class="opacity-75">Upload bukti pembayaran Anda untuk konfirmasi</small>
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

            <!-- Info Pembayaran -->
            <div class="card border-0 shadow-sm mb-4">
              <div class="card-body p-0">
                <div class="px-4 py-3 fw-bold border-bottom" style="background:#f8f9fa;border-radius:14px 14px 0 0;">
                  <i class="bi bi-wallet2 text-success me-2"></i>Informasi Pembayaran
                </div>
                <div class="p-4">
                  <div class="row align-items-center g-4">
                    <!-- QR Code -->
                    <div class="col-md-5 text-center">
                      <div class="border rounded-3 p-3 d-inline-block shadow-sm">
                            <img :src="`${apiBase}/media/image/${encodeURIComponent('qr dana.jpeg')}`" alt="QR Code Pembayaran"
                             style="width:200px;height:200px;object-fit:contain;"
                             @error="$event.target.style.display='none'">
                        <div style="display:none;width:200px;height:200px;background:#f8f9fa;align-items:center;justify-content:center;flex-direction:column;border-radius:8px;">
                          <i class="bi bi-qr-code" style="font-size:3rem;color:#adb5bd;"></i>
                          <small class="text-muted mt-2">QR Code</small>
                        </div>
                      </div>
                      <div class="mt-2 text-muted small"><i class="bi bi-info-circle me-1"></i>Scan QR untuk bayar</div>
                    </div>
                    <!-- Detail Rekening -->
                    <div class="col-md-7">
                      <h6 class="fw-bold mb-3">Transfer / Scan ke:</h6>
                      <div class="d-flex align-items-center gap-3 mb-3 p-3 rounded-3" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                        <div style="font-size:1.8rem;"><i class="bi bi-telephone-fill text-success"></i></div>
                        <div>
                          <div class="text-muted small">Nomor Telepon / E-Wallet</div>
                          <div class="fw-bold fs-5">0857-7529-4657</div>
                        </div>
                      </div>
                      <div class="mb-3">
                        <div class="text-muted small mb-2 fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px;">Buka Aplikasi E-Wallet</div>
                        <div class="d-flex flex-wrap gap-2">
                          <a href="dana://" @click.prevent="bukaApp('dana://', 'https://play.google.com/store/apps/details?id=id.dana')"
                             class="badge px-3 py-2 rounded-pill text-decoration-none"
                             style="background:#118EEA;font-size:.85rem;cursor:pointer;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Dana
                          </a>
                          <a href="gojek://gopay" @click.prevent="bukaApp('gojek://gopay', 'https://play.google.com/store/apps/details?id=com.gojek.app')"
                             class="badge px-3 py-2 rounded-pill text-decoration-none"
                             style="background:#00AED6;font-size:.85rem;cursor:pointer;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> GoPay
                          </a>
                          <a href="ovo://" @click.prevent="bukaApp('ovo://', 'https://play.google.com/store/apps/details?id=ovo.id')"
                             class="badge px-3 py-2 rounded-pill text-decoration-none"
                             style="background:#4C279F;font-size:.85rem;cursor:pointer;">
                            <i class="bi bi-box-arrow-up-right me-1"></i> OVO
                          </a>
                        </div>
                        <div class="text-muted mt-2" style="font-size:.75rem;"><i class="bi bi-info-circle me-1"></i>Klik untuk membuka aplikasi (khusus HP)</div>
                      </div>
                      <div class="alert alert-warning border-0 py-2 mb-0" style="font-size:.85rem;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Setelah transfer, upload bukti pembayaran di bawah ini.
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Upload -->
            <div class="card border-0 shadow-sm">
              <div class="card-body p-0">
                <div class="px-4 py-3 fw-bold border-bottom" style="background:#f8f9fa;border-radius:14px 14px 0 0;">
                  <i class="bi bi-credit-card text-primary me-2"></i>Upload Bukti Pembayaran
                </div>
                <div class="p-4">
                  <div v-if="error" class="alert alert-danger border-0">
                    <i class="bi bi-x-circle me-2"></i>{{ error }}
                  </div>

                  <!-- Upload sukses -->
                  <div v-if="uploaded" class="text-center py-4">
                    <div style="font-size:4rem;"><i class="bi bi-check-circle-fill text-success"></i></div>
                    <h5 class="fw-bold mt-3 text-success">Pembayaran Berhasil Diupload!</h5>
                    <p class="text-muted mb-1">Bukti pembayaran kamu sudah kami terima.</p>
                    <p class="text-muted mb-4">Silakan tunggu konfirmasi dari admin. Proses verifikasi biasanya membutuhkan beberapa saat.</p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                      <button @click="router.push({ name: 'home' })" class="btn fw-semibold px-4"
                              style="background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;border:none;border-radius:10px;">
                        <i class="bi bi-house me-2"></i>Kembali ke Beranda
                      </button>
                      <button @click="router.push({ name: 'booking' })" class="btn btn-outline-secondary fw-semibold px-4" style="border-radius:10px;">
                        <i class="bi bi-calendar-plus me-2"></i>Booking Lagi
                      </button>
                    </div>
                  </div>

                  <!-- Already uploaded (sebelum submit) -->
                  <div v-else-if="booking.pembayaran?.bukti_pembayaran" class="text-center py-3">
                    <div style="font-size:3rem;"><i class="bi bi-check-circle-fill text-success"></i></div>
                    <h6 class="fw-bold mt-2">Bukti Pembayaran Sudah Diupload</h6>
                    <p class="text-muted small mb-3">Status verifikasi admin:</p>
                    <span class="badge px-3 py-2 fs-6 mb-3"
                      :class="{
                        'bg-warning text-dark': booking.pembayaran.status_verifikasi === 'menunggu',
                        'bg-success': booking.pembayaran.status_verifikasi === 'diterima',
                        'bg-danger': booking.pembayaran.status_verifikasi === 'ditolak',
                      }">
                      {{ booking.pembayaran.status_verifikasi.charAt(0).toUpperCase() + booking.pembayaran.status_verifikasi.slice(1) }}
                    </span>
                    <div v-if="booking.pembayaran.status_verifikasi === 'menunggu'" class="text-muted small mb-3">
                      <i class="bi bi-arrow-clockwise me-1"></i>Halaman otomatis cek status setiap 10 detik
                    </div>
                    <div v-else-if="booking.pembayaran.status_verifikasi === 'diterima'" class="alert alert-success border-0 text-start mb-3" style="font-size:.9rem;">
                      <i class="bi bi-check-circle-fill me-2"></i>Pembayaran kamu telah <strong>diterima</strong>. Booking kamu sudah terkonfirmasi!
                    </div>
                    <div v-else-if="booking.pembayaran.status_verifikasi === 'ditolak'" class="alert alert-danger border-0 text-start mb-3" style="font-size:.9rem;">
                      <i class="bi bi-x-circle-fill me-2"></i>Pembayaran kamu <strong>ditolak</strong>. Silakan hubungi admin atau upload ulang bukti yang valid.
                    </div>
                    <button @click="refreshStatus" :disabled="refreshing" class="btn btn-outline-secondary btn-sm px-4" style="border-radius:8px;">
                      <span v-if="refreshing" class="spinner-border spinner-border-sm me-1"></span>
                      <i v-else class="bi bi-arrow-clockwise me-1"></i>
                      {{ refreshing ? 'Mengecek...' : 'Cek Status Sekarang' }}
                    </button>
                  </div>

                  <!-- Upload form -->
                  <form v-else @submit.prevent="submit">
                    <div class="mb-3">
                      <label class="form-label fw-semibold">Metode Pembayaran <span class="text-danger">*</span></label>
                      <select v-model="form.metode_pembayaran" class="form-select" required>
                        <option value="" disabled>-- Pilih Metode Pembayaran --</option>
                        <option>Transfer Bank</option>
                        <option>QRIS</option>
                        <option>GoPay</option>
                        <option>OVO</option>
                        <option>Dana</option>
                      </select>
                    </div>
                    <div class="mb-4">
                      <label class="form-label fw-semibold">Bukti Pembayaran <span class="text-danger">*</span></label>
                      <input @change="onFileChange" type="file" class="form-control"
                             accept="image/jpg,image/jpeg,image/png,application/pdf" required>
                      <div class="form-text text-muted">Format: JPG, PNG, PDF. Maks 2MB.</div>
                    </div>
                    <button type="submit" :disabled="submitting" class="btn fw-bold w-100 py-2"
                            style="background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;border:none;border-radius:10px;">
                      <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                      <i v-else class="bi bi-upload me-2"></i>
                      {{ submitting ? 'Mengupload...' : 'Upload Bukti Bayar' }}
                    </button>
                  </form>
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
