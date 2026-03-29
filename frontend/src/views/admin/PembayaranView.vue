<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/api'

const pembayarans = ref([])
const loading     = ref(true)
const success     = ref('')
const apiBase     = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`).replace(/\/+$/, '')

const filters = ref({ status: '', search: '' })

async function load() {
  loading.value = true
  try {
    const res = await api.get('/admin/pembayaran', { params: filters.value })
    pembayarans.value = res.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

async function aksi(endpoint, id) {
  try {
    await api.post(`/admin/pembayaran/${endpoint}`, { pembayaran_id: id })
    success.value = endpoint === 'terima' ? 'Pembayaran diterima!' : 'Pembayaran ditolak!'
    load()
    setTimeout(() => success.value = '', 3000)
  } catch {
    alert('Gagal memperbarui status.')
  }
}

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n) }
function formatJam(t) { return t ? t.substring(0, 5) : '-' }
</script>

<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="fw-bold mb-0">Daftar Pembayaran</h5>
        <small class="text-muted">Verifikasi bukti pembayaran dari penyewa</small>
      </div>
      <span class="badge bg-secondary fs-6">{{ pembayarans.length }} data</span>
    </div>

    <div v-if="success" class="alert alert-success border-0">{{ success }}</div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Cari Penyewa</label>
          <input v-model="filters.search" @input="load" type="text" class="form-control form-control-sm" placeholder="Nama penyewa...">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">Status Verifikasi</label>
          <select v-model="filters.status" @change="load" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="menunggu">Menunggu</option>
            <option value="diterima">Diterima</option>
            <option value="ditolak">Ditolak</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
          <button @click="load" type="button" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-funnel"></i> Filter
          </button>
          <button @click="filters={status:'',search:''};load()" type="button" class="btn btn-outline-secondary btn-sm w-100">
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
        <table class="table table-hover mb-0">
          <thead class="table-light">
            <tr>
              <th class="ps-4">#</th>
              <th>Penyewa</th>
              <th>Lapangan</th>
              <th>Metode</th>
              <th>Bukti</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(p, i) in pembayarans" :key="p.pembayaran_id">
              <td class="ps-4">{{ i + 1 }}</td>
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
              <td>{{ p.metode_pembayaran }}</td>
              <td>
                <a v-if="p.bukti_pembayaran"
                   :href="`${apiBase}/media/bukti/${encodeURIComponent(p.bukti_pembayaran)}`"
                   target="_blank" class="btn btn-sm btn-outline-secondary">
                  <i class="bi bi-file-earmark-image"></i> Lihat
                </a>
                <span v-else class="text-muted">-</span>
              </td>
              <td>
                <span class="badge"
                  :class="{
                    'bg-warning text-dark': p.status_verifikasi === 'menunggu',
                    'bg-success': p.status_verifikasi === 'diterima',
                    'bg-danger': p.status_verifikasi === 'ditolak',
                  }">{{ p.status_verifikasi.charAt(0).toUpperCase() + p.status_verifikasi.slice(1) }}</span>
              </td>
              <td class="text-center">
                <div class="d-flex gap-1 justify-content-center" v-if="p.status_verifikasi === 'menunggu'">
                  <button @click="aksi('terima', p.pembayaran_id)" class="btn btn-sm btn-success" title="Terima">
                    <i class="bi bi-check-lg"></i> Terima
                  </button>
                  <button @click="aksi('tolak', p.pembayaran_id)" class="btn btn-sm btn-danger" title="Tolak">
                    <i class="bi bi-x-lg"></i> Tolak
                  </button>
                </div>
                <span v-else class="text-muted small">-</span>
              </td>
            </tr>
            <tr v-if="pembayarans.length === 0">
              <td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                Belum ada data pembayaran.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
