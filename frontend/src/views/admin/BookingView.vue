<script setup>


import { ref, onMounted } from 'vue'
import api from '@/utils/api'

const bookings  = ref([])
const lapangans = ref([])
const loading   = ref(true)
const exporting = ref(false)
const success   = ref('')

const filters = ref({ status: '', lapangan_id: '', tanggal: '', search: '' })

async function load() {
  loading.value = true
  try {
    const [b, l] = await Promise.all([
      api.get('/admin/booking', { params: filters.value }),
      api.get('/admin/lapangan'),
    ])
    bookings.value  = b.data
    lapangans.value = l.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function updateStatus(endpoint, bookingId) {
  try {
    await api.post(`/admin/booking/${endpoint}`, { booking_id: bookingId })
    success.value = 'Status berhasil diubah!'
    load()
    setTimeout(() => success.value = '', 3000)
  } catch {
    alert('Gagal mengubah status.')
  }
}

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n) }

function statusBadge(s) {
  return {
    'bg-warning text-dark': s === 'menunggu',
    'bg-success': s === 'terkonfirmasi',
    'bg-primary': s === 'selesai',
    'bg-danger':  s === 'batal',
  }
}

function formatJam(t) { return t ? t.substring(0, 5) : '-' }

async function exportData(format) {
  if (exporting.value) return

  exporting.value = true
  try {
    const res = await api.get(`/admin/booking/export/${format}`, {
      params: filters.value,
      responseType: 'blob',
    })

    const type = format === 'excel'
      ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      : 'application/pdf'
    const ext = format === 'excel' ? 'xlsx' : 'pdf'
    const blob = new Blob([res.data], { type })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')

    link.href = url
    link.download = `laporan-booking-${new Date().toISOString().slice(0, 10)}.${ext}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    alert('Gagal mengunduh laporan booking.')
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div>
        <h5 class="fw-bold mb-0">Daftar Booking</h5>
        <small class="text-muted">Kelola semua data pemesanan lapangan</small>
      </div>
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <button
          type="button"
          class="btn btn-sm btn-outline-success"
          :disabled="exporting"
          @click="exportData('excel')">
          <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
        </button>
        <button
          type="button"
          class="btn btn-sm btn-outline-danger"
          :disabled="exporting"
          @click="exportData('pdf')">
          <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
        </button>
        <span class="badge bg-secondary fs-6">{{ bookings.length }} data</span>
      </div>
    </div>

    <div v-if="success" class="alert alert-success border-0">{{ success }}</div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">Cari Penyewa</label>
          <input v-model="filters.search" @input="load" type="text" class="form-control form-control-sm" placeholder="Nama penyewa...">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Status</label>
          <select v-model="filters.status" @change="load" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="menunggu">Menunggu</option>
            <option value="terkonfirmasi">Terkonfirmasi</option>
            <option value="selesai">Selesai</option>
            <option value="batal">Batal</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">Lapangan</label>
          <select v-model="filters.lapangan_id" @change="load" class="form-select form-select-sm">
            <option value="">Semua Lapangan</option>
            <option v-for="l in lapangans" :key="l.lapangan_id" :value="l.lapangan_id">
              Lapangan {{ l.nomor_lapangan }}
            </option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Tanggal</label>
          <input v-model="filters.tanggal" @change="load" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-2 d-flex gap-1 flex-wrap">
          <button @click="load" type="button" class="btn btn-primary btn-sm flex-fill">
            <i class="bi bi-funnel"></i> Filter
          </button>
          <button @click="filters={status:'',lapangan_id:'',tanggal:'',search:''};load()" type="button" class="btn btn-outline-secondary btn-sm flex-fill">
            <i class="bi bi-arrow-counterclockwise"></i>
          </button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border text-warning"></div>
    </div>

    <div v-else class="card border-0 shadow-sm rounded-3">
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">#</th>
              <th>Penyewa</th>
              <th>Lapangan</th>
              <th>Tanggal</th>
              <th>Jam</th>
              <th>Total</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(b, i) in bookings" :key="b.booking_id">
              <td class="ps-4">{{ i + 1 }}</td>
              <td>
                <div class="fw-semibold">{{ b.nama_penyewa }}</div>
                <small class="text-muted">{{ b.no_hp }}</small>
              </td>
              <td>Lapangan {{ b.lapangan?.nomor_lapangan }}</td>
              <td>{{ b.tanggal_booking }}</td>
              <td>{{ formatJam(b.jam_mulai) }} - {{ formatJam(b.jam_selesai) }}</td>
              <td>Rp {{ formatRupiah(b.total_harga) }}</td>
              <td>
                <span class="badge" :class="statusBadge(b.status)">{{ b.status.charAt(0).toUpperCase() + b.status.slice(1) }}</span>
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 flex-wrap justify-content-center">
                  <button v-if="b.status === 'menunggu'"
                          @click="updateStatus('konfirmasi', b.booking_id)"
                          class="btn btn-sm btn-primary" title="Konfirmasi">
                    <i class="bi bi-check-lg"></i>
                  </button>
                  <button v-if="b.status === 'terkonfirmasi'"
                          @click="updateStatus('selesai', b.booking_id)"
                          class="btn btn-sm btn-success" title="Selesai">
                    <i class="bi bi-check-circle"></i>
                  </button>
                  <button v-if="b.status === 'menunggu' || b.status === 'terkonfirmasi'"
                          @click="updateStatus('batal', b.booking_id)"
                          class="btn btn-sm btn-danger" title="Batalkan">
                    <i class="bi bi-x-lg"></i>
                  </button>
                  <span v-if="b.status === 'selesai' || b.status === 'batal'" class="text-muted small">-</span>
                </div>
              </td>
            </tr>
            <tr v-if="bookings.length === 0">
              <td colspan="8" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                Belum ada data booking.
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</template>
