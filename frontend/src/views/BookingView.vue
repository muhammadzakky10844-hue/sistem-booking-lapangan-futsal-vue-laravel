<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/utils/api'
import flatpickr from 'flatpickr'
import { Indonesian } from 'flatpickr/dist/l10n/id.js'
import 'flatpickr/dist/flatpickr.min.css'

const route  = useRoute()
const router = useRouter()

const lapangans        = ref([])
const selectedLapangan = ref(null)
const bookedSlots      = ref([])
const slots            = ref([])
const loadingSlots     = ref(false)
const submitting       = ref(false)
const error            = ref('')

const elTanggal = ref(null)
const elMulai   = ref(null)
const elSelesai = ref(null)
let fpTanggal = null, fpMulai = null, fpSelesai = null

const form = ref({
  nama_penyewa:    '',
  no_hp:           '',
  alamat:          '',
  lapangan_id:     route.query.lapangan_id || '',
  tanggal_booking: '',
  jam_mulai:       '',
  jam_selesai:     '',
})

onMounted(async () => {
  const res = await api.get('/lapangan')
  lapangans.value = res.data

  if (form.value.lapangan_id) {
    selectedLapangan.value = lapangans.value.find(l => l.lapangan_id == form.value.lapangan_id) || null
  }

  fpTanggal = flatpickr(elTanggal.value, {
    locale: Indonesian,
    dateFormat: 'Y-m-d',
    altInput: true,
    altFormat: 'd F Y',
    minDate: 'today',
    disableMobile: true,
    onChange: (_, dateStr) => {
      form.value.tanggal_booking = dateStr
      loadSlots()
    },
  })

  fpMulai = flatpickr(elMulai.value, {
    enableTime: true,
    noCalendar: true,
    dateFormat: 'H:i',
    time_24hr: true,
    minuteIncrement: 30,
    disableMobile: true,
    onChange: (_, timeStr) => { form.value.jam_mulai = timeStr },
  })

  fpSelesai = flatpickr(elSelesai.value, {
    enableTime: true,
    noCalendar: true,
    dateFormat: 'H:i',
    time_24hr: true,
    minuteIncrement: 30,
    disableMobile: true,
    onChange: (_, timeStr) => { form.value.jam_selesai = timeStr },
  })
})

onUnmounted(() => {
  fpTanggal?.destroy()
  fpMulai?.destroy()
  fpSelesai?.destroy()
})

function onLapanganChange() {
  selectedLapangan.value = lapangans.value.find(l => l.lapangan_id == form.value.lapangan_id) || null
  loadSlots()
}

async function loadSlots() {
  if (!form.value.lapangan_id || !form.value.tanggal_booking) {
    slots.value = []
    return
  }
  loadingSlots.value = true
  try {
    const res = await api.get('/booking/jadwal', {
      params: { lapangan_id: form.value.lapangan_id, tanggal: form.value.tanggal_booking }
    })
    bookedSlots.value = res.data
    buildSlots()
  } finally {
    loadingSlots.value = false
  }
}

function buildSlots() {
  const result = []
  for (let h = 7; h < 22; h++) {
    const startMin = h * 60
    const endMin   = (h + 1) * 60
    const startStr = pad(h) + ':00'
    const endStr   = pad(h + 1) + ':00'
    const booked   = bookedSlots.value.some(b => {
      const bStart = toMin(b.jam_mulai)
      const bEnd   = toMin(b.jam_selesai)
      return startMin < bEnd && endMin > bStart
    })
    result.push({ startStr, endStr, booked })
  }
  slots.value = result
}

function selectSlot(s) {
  if (s.booked) return
  form.value.jam_mulai   = s.startStr
  form.value.jam_selesai = s.endStr
  fpMulai?.setDate(s.startStr, false)
  fpSelesai?.setDate(s.endStr, false)
}

function toMin(t) {
  const p = t.split(':')
  return parseInt(p[0]) * 60 + parseInt(p[1])
}
function pad(n) { return n < 10 ? '0' + n : '' + n }

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n) }

const totalHarga = computed(() => {
  if (!form.value.jam_mulai || !form.value.jam_selesai || !selectedLapangan.value) return 0
  const lama = (toMin(form.value.jam_selesai) - toMin(form.value.jam_mulai)) / 60
  return lama > 0 ? lama * selectedLapangan.value.harga_per_jam : 0
})

async function submit() {
  error.value  = ''
  submitting.value = true
  try {
    const res = await api.post('/booking', form.value)
    router.push({ name: 'pembayaran', params: { id: res.data.booking.booking_id } })
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join(' | ')
    } else {
      error.value = data?.message || 'Terjadi kesalahan.'
    }
  } finally {
    submitting.value = false
  }
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
      <!-- Hero Banner -->
      <div class="rounded-3 mb-4 px-4 py-4 text-white" style="background:linear-gradient(135deg,#0f2027,#2c5364);">
        <div class="d-flex align-items-center gap-3">
          <RouterLink to="/" class="text-white me-1" title="Kembali"><i class="bi bi-arrow-left fs-5"></i></RouterLink>
          <div style="font-size:2.5rem;"><i class="fas fa-futbol text-warning"></i></div>
          <div>
            <h4 class="mb-0 fw-bold">Form Booking Lapangan</h4>
            <small class="opacity-75">Lengkapi data pemesan dan pilih jadwal</small>
          </div>
        </div>
      </div>
      <div class="row g-4">
        <!-- Form -->
        <div class="col-lg-8">
          <div class="card shadow-sm border-0">
            <div class="card-body p-4">
              <h6 class="fw-bold mb-4 pb-2 border-bottom">
                <i class="bi bi-person-fill text-primary me-2"></i>Data Pemesan
              </h6>

              <div v-if="error" class="alert alert-danger border-0 rounded-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ error }}
              </div>

              <form @submit.prevent="submit">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">
                      Nama Penyewa <span class="text-danger">*</span>
                    </label>
                    <input v-model="form.nama_penyewa" type="text" class="form-control form-control-lg"
                           placeholder="Nama lengkap" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">
                      No. HP <span class="text-danger">*</span>
                    </label>
                    <input v-model="form.no_hp" type="text" class="form-control form-control-lg"
                           placeholder="08xxxxxxxxxx" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">Alamat</label>
                    <textarea v-model="form.alamat" class="form-control" rows="2"
                              placeholder="Alamat (opsional)"></textarea>
                  </div>
                </div>

                <h6 class="fw-bold my-4 pb-2 border-bottom">
                  <i class="bi bi-calendar3 text-warning me-2"></i>Detail Pemesanan
                </h6>

                <div class="row g-3">
                  <!-- Lapangan -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">
                      Lapangan <span class="text-danger">*</span>
                    </label>
                    <template v-if="selectedLapangan && route.query.lapangan_id">
                      <input type="hidden" v-model="form.lapangan_id">
                      <div class="form-control form-control-lg d-flex align-items-center gap-2"
                           style="background:#f8f9fa;cursor:default;">
                        <i class="bi bi-geo-alt-fill text-warning"></i>
                        <span class="fw-semibold">Lapangan {{ selectedLapangan.nomor_lapangan }}</span>
                        <span class="ms-auto text-muted small">
                          Rp {{ formatRupiah(selectedLapangan.harga_per_jam) }}/jam
                        </span>
                      </div>
                    </template>
                    <template v-else>
                      <select v-model="form.lapangan_id" @change="onLapanganChange" class="form-select form-select-lg" required>
                        <option value="">-- Pilih Lapangan --</option>
                        <option v-for="l in lapangans" :key="l.lapangan_id" :value="l.lapangan_id">
                          Lap. {{ l.nomor_lapangan }} — Rp {{ formatRupiah(l.harga_per_jam) }}/jam
                        </option>
                      </select>
                    </template>
                  </div>

                  <!-- Tanggal -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">
                      Tanggal Booking <span class="text-danger">*</span>
                    </label>
                    <input ref="elTanggal" type="text" class="form-control form-control-lg"
                           placeholder="Pilih tanggal" readonly required>
                  </div>

                  <!-- Jam Mulai -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">
                      Jam Mulai <span class="text-danger">*</span>
                    </label>
                    <input ref="elMulai" type="text" class="form-control form-control-lg"
                           placeholder="07:00" readonly required>
                    <div class="form-text text-muted" style="font-size:.75rem;">
                      <i class="bi bi-info-circle me-1"></i>Ketik manual atau klik slot jam di bawah
                    </div>
                  </div>

                  <!-- Jam Selesai -->
                  <div class="col-md-6">
                    <label class="form-label fw-semibold text-secondary small text-uppercase">
                      Jam Selesai <span class="text-danger">*</span>
                    </label>
                    <input ref="elSelesai" type="text" class="form-control form-control-lg"
                           placeholder="00:00" readonly required>
                    <div class="form-text text-muted" style="font-size:.75rem;">
                      <i class="bi bi-info-circle me-1"></i>Ketik manual atau klik slot jam di bawah
                    </div>
                  </div>
                </div>

                <!-- Slot grid -->
                <div v-if="form.lapangan_id && form.tanggal_booking" class="mt-3">
                  <label class="form-label fw-semibold text-secondary small text-uppercase">
                    <i class="bi bi-clock-history me-1 text-primary"></i>Slot Jam Tersedia
                  </label>
                  <div v-if="loadingSlots" class="text-muted small">
                    <span class="spinner-border spinner-border-sm me-1"></span> Memuat jadwal...
                  </div>
                  <div v-else class="d-flex flex-wrap gap-2 mt-2">
                    <button
                      v-for="s in slots" :key="s.startStr"
                      type="button"
                      :disabled="s.booked"
                      @click="selectSlot(s)"
                      class="btn btn-sm"
                      :style="s.booked
                        ? 'min-width:72px;border-radius:10px;padding:6px 8px;background:#fee2e2;color:#dc2626;border:1.5px solid #fca5a5;cursor:not-allowed;'
                        : (form.jam_mulai === s.startStr
                            ? 'min-width:72px;border-radius:10px;padding:6px 8px;background:#fef3c7;color:#92400e;border:1.5px solid #f59e0b;'
                            : 'min-width:72px;border-radius:10px;padding:6px 8px;background:#dcfce7;color:#16a34a;border:1.5px solid #86efac;cursor:pointer;')"
                    >
                      <span class="fw-semibold">{{ s.startStr }}</span><br>
                      <small style="font-size:10px;">- {{ s.endStr }}</small>
                    </button>
                  </div>
                  <div class="d-flex gap-3 mt-2 small text-muted">
                    <span>
                      <span style="display:inline-block;width:12px;height:12px;background:#22c55e;border-radius:3px;margin-right:4px;"></span>
                      Tersedia
                    </span>
                    <span>
                      <span style="display:inline-block;width:12px;height:12px;background:#ef4444;border-radius:3px;margin-right:4px;"></span>
                      Terisi
                    </span>
                  </div>
                </div>

                <!-- Total -->
                <div v-if="totalHarga > 0" class="alert border-0 mt-4" style="background:#fef3c7;">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-cash me-2 text-warning"></i>Total Harga</span>
                    <span class="fw-bold fs-5 text-warning">Rp {{ formatRupiah(totalHarga) }}</span>
                  </div>
                </div>

                <div class="d-grid mt-4">
                  <button type="submit" :disabled="submitting" class="btn btn-lg fw-bold py-3"
                          style="background:linear-gradient(135deg,#f59e0b,#f97316);color:#fff;border:none;border-radius:12px;">
                    <span v-if="submitting" class="spinner-border spinner-border-sm me-2"></span>
                    <i v-else class="bi bi-check-circle-fill me-2"></i>
                    {{ submitting ? 'Memproses...' : 'Booking Sekarang' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Info -->
        <div class="col-lg-4">
          <div class="card shadow-sm border-0">
            <div class="card-body p-0">
              <div class="px-4 py-3 fw-bold border-bottom" style="background:#f8f9fa;border-radius:14px 14px 0 0;">
                <i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i>Lapangan Tersedia
              </div>
              <div v-for="l in lapangans" :key="l.lapangan_id" class="px-4 py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-start">
                  <div>
                    <div class="fw-bold">Lapangan {{ l.nomor_lapangan }}</div>
                    <small v-if="l.deskripsi" class="text-muted">{{ l.deskripsi }}</small>
                  </div>
                  <span class="badge rounded-pill" style="background:#f59e0b;color:#fff;">
                    Rp {{ formatRupiah(l.harga_per_jam) }}/jam
                  </span>
                </div>
              </div>
              <div v-if="lapangans.length === 0" class="p-4 text-muted text-center">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>Tidak ada lapangan.
              </div>
            </div>
          </div>

          <div class="card shadow-sm border-0 mt-3">
            <div class="card-body py-3 px-4">
              <h6 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Informasi</h6>
              <ul class="list-unstyled mb-0 small text-muted">
                <li class="mb-2"><i class="bi bi-dot text-warning fs-5 me-1"></i>Booking berlaku setelah pembayaran dikonfirmasi</li>
                <li class="mb-2"><i class="bi bi-dot text-warning fs-5 me-1"></i>Lanjutkan pembayaran online via Midtrans setelah booking berhasil</li>
                <li><i class="bi bi-dot text-warning fs-5 me-1"></i>Hubungi admin jika ada kendala</li>
              </ul>
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
