<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/api'

const pembayarans = ref([])
const loading     = ref(true)
const exporting   = ref(false)

const filters = ref({
  search: '',
  uploaded_date: '',
  page: 1,
  per_page: 10,
})

const pagination = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
})

async function load(page = filters.value.page) {
  filters.value.page = page
  loading.value = true
  try {
    const res = await api.get('/admin/pembayaran', { params: filters.value })

    // Support both paginated and non-paginated response format.
    if (Array.isArray(res.data)) {
      pembayarans.value = res.data
      pagination.value = {
        current_page: 1,
        last_page: 1,
        per_page: res.data.length,
        total: res.data.length,
        from: res.data.length ? 1 : 0,
        to: res.data.length,
      }
    } else {
      pembayarans.value = res.data.data || []
      pagination.value = {
        current_page: res.data.current_page || 1,
        last_page: res.data.last_page || 1,
        per_page: res.data.per_page || 10,
        total: res.data.total || 0,
        from: res.data.from || 0,
        to: res.data.to || 0,
      }
    }
  } finally {
    loading.value = false
  }
}

onMounted(load)

function formatJam(t) { return t ? t.substring(0, 5) : '-' }

function formatTanggalWaktu(v) {
  if (!v) return '-'
  return new Date(v).toLocaleString('id-ID', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function normalizeStatus(s) {
  return String(s || '').toLowerCase()
}

function statusTransaksi(p) {
  const statusVerifikasi = normalizeStatus(p.status_verifikasi)
  if (statusVerifikasi === 'diterima') return 'berhasil'
  if (statusVerifikasi === 'ditolak') return 'gagal'

  if (normalizeStatus(p.payment_gateway) === 'midtrans') {
    return normalizeStatus(p.gateway_transaction_status) || 'pending'
  }

  return 'pending'
}

function labelStatusTransaksi(p) {
  const s = statusTransaksi(p)

  if (s === 'settlement' || s === 'capture' || s === 'berhasil') return 'Berhasil'
  if (s === 'pending') return 'Pending'
  if (s === 'deny' || s === 'cancel' || s === 'expire' || s === 'failure' || s === 'gagal') return 'Gagal'

  return s ? s.charAt(0).toUpperCase() + s.slice(1) : '-'
}

function classStatusTransaksi(p) {
  const s = statusTransaksi(p)

  if (s === 'settlement' || s === 'capture' || s === 'berhasil') return 'bg-success'
  if (s === 'pending') return 'bg-warning text-dark'
  if (s === 'deny' || s === 'cancel' || s === 'expire' || s === 'failure' || s === 'gagal') return 'bg-danger'

  return 'bg-secondary'
}

function waktuTransaksi(p) {
  return p.paid_at || p.updated_at || p.created_at
}

function referensiTransaksi(p) {
  if (p.gateway_order_id) return p.gateway_order_id
  if (p.gateway_transaction_id) return p.gateway_transaction_id
  return '-'
}

function labelMetode(p) {
  if (p.gateway_payment_type) return `Midtrans - ${String(p.gateway_payment_type).replaceAll('_', ' ').toUpperCase()}`
  return p.metode_pembayaran || '-'
}

function terapkanFilter() {
  load(1)
}

function resetFilter() {
  filters.value = {
    search: '',
    uploaded_date: '',
    page: 1,
    per_page: 10,
  }
  load(1)
}

function gantiHalaman(page) {
  if (page < 1 || page > pagination.value.last_page || page === pagination.value.current_page) return
  load(page)
}

async function exportData(format) {
  if (exporting.value) return

  exporting.value = true
  try {
    const params = {
      search: filters.value.search,
      uploaded_date: filters.value.uploaded_date,
    }

    const res = await api.get(`/admin/pembayaran/export/${format}`, {
      params,
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
    link.download = `laporan-pembayaran-${new Date().toISOString().slice(0, 10)}.${ext}`
    document.body.appendChild(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  } catch {
    alert('Gagal mengunduh laporan pembayaran.')
  } finally {
    exporting.value = false
  }
}
</script>

<template>
  <div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
      <div>
        <h5 class="fw-bold mb-0">Monitoring Transaksi Pembayaran</h5>
        <small class="text-muted">Pantau status transaksi Midtrans dan data pembayaran penyewa</small>
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
        <span class="badge bg-secondary fs-6">{{ pagination.total }} data</span>
      </div>
    </div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Cari Penyewa</label>
          <input v-model="filters.search" type="text" class="form-control form-control-sm" placeholder="Nama penyewa...">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">Tanggal Transaksi</label>
          <input v-model="filters.uploaded_date" type="date" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Per Halaman</label>
          <select v-model.number="filters.per_page" class="form-select form-select-sm">
            <option :value="5">5</option>
            <option :value="10">10</option>
            <option :value="20">20</option>
            <option :value="50">50</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-1 flex-wrap">
          <button @click="terapkanFilter" type="button" class="btn btn-primary btn-sm flex-fill">
            <i class="bi bi-funnel"></i> Filter
          </button>
          <button @click="resetFilter" type="button" class="btn btn-outline-secondary btn-sm flex-fill">
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
              <th>Metode</th>
              <th>Status Transaksi</th>
              <th>Waktu Transaksi</th>
              <th>Referensi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(p, i) in pembayarans" :key="p.pembayaran_id">
              <td class="ps-4">{{ (pagination.from || 1) + i }}</td>
              <td>
                <div class="fw-semibold">{{ p.booking?.nama_penyewa }}</div>
                <small class="text-muted">{{ p.booking?.no_hp }}</small>
              </td>
              <td>
                Lapangan {{ p.booking?.lapangan?.nomor_lapangan ?? '-' }}<br>
                <small class="text-muted">
                  {{ p.booking?.tanggal_booking }}
                  {{ formatJam(p.booking?.jam_mulai) }} - {{ formatJam(p.booking?.jam_selesai) }}
                </small>
              </td>
              <td>{{ labelMetode(p) }}</td>
              <td>
                <span class="badge" :class="classStatusTransaksi(p)">
                  {{ labelStatusTransaksi(p) }}
                </span>
              </td>
              <td>{{ formatTanggalWaktu(waktuTransaksi(p)) }}</td>
              <td>
                <small class="text-muted">{{ referensiTransaksi(p) }}</small>
              </td>
            </tr>
            <tr v-if="pembayarans.length === 0">
              <td colspan="8" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                Belum ada data pembayaran.
              </td>
            </tr>
          </tbody>
        </table>
        </div>
      </div>

      <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <small class="text-muted">
          Menampilkan {{ pagination.from || 0 }} - {{ pagination.to || 0 }} dari {{ pagination.total }} data
        </small>
        <div class="btn-group btn-group-sm pagination-controls" role="group" aria-label="Pagination">
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="pagination.current_page <= 1"
            @click="gantiHalaman(pagination.current_page - 1)">
            Sebelumnya
          </button>
          <button type="button" class="btn btn-outline-primary" disabled>
            {{ pagination.current_page }}/{{ pagination.last_page }}
          </button>
          <button
            type="button"
            class="btn btn-outline-secondary"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="gantiHalaman(pagination.current_page + 1)">
            Selanjutnya
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media (max-width: 575.98px) {
  .pagination-controls .btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
  }
}
</style>
