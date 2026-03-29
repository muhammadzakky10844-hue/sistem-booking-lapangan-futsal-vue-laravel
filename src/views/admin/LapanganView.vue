<script setup>
import { ref, onMounted } from 'vue'
import api from '@/utils/api'

const lapangans  = ref([])
const loading    = ref(true)
const showModal  = ref(false)
const saving     = ref(false)
const editMode   = ref(false)
const error      = ref('')
const success    = ref('')
const filterStatus = ref('')
const filterSearch = ref('')
const apiBase    = (import.meta.env.VITE_API_URL || `${window.location.origin}/api`).replace(/\/+$/, '')

const form = ref({
  lapangan_id: null,
  nomor_lapangan: '',
  harga_per_jam: '',
  deskripsi: '',
  status: 'tersedia',
  gambar: null,
})

async function load() {
  loading.value = true
  try {
    const res = await api.get('/admin/lapangan', {
      params: { status: filterStatus.value, search: filterSearch.value }
    })
    lapangans.value = res.data
  } finally {
    loading.value = false
  }
}

onMounted(load)

function openAdd() {
  editMode.value = false
  form.value = { lapangan_id: null, nomor_lapangan: '', harga_per_jam: '', deskripsi: '', status: 'tersedia', gambar: null }
  error.value = ''
  showModal.value = true
}

function openEdit(lap) {
  editMode.value = true
  form.value = { ...lap, gambar: null }
  error.value = ''
  showModal.value = true
}

function onFileChange(e) {
  form.value.gambar = e.target.files[0]
}

async function save() {
  error.value = ''
  saving.value = true
  try {
    const fd = new FormData()
    fd.append('nomor_lapangan', form.value.nomor_lapangan)
    fd.append('harga_per_jam', form.value.harga_per_jam)
    fd.append('deskripsi', form.value.deskripsi || '')
    fd.append('status', form.value.status)
    if (form.value.gambar) fd.append('gambar', form.value.gambar)

    if (editMode.value) {
      fd.append('_method', 'PUT')
      await api.post(`/admin/lapangan/${form.value.lapangan_id}`, fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      success.value = 'Lapangan berhasil diupdate!'
    } else {
      await api.post('/admin/lapangan', fd, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      success.value = 'Lapangan berhasil ditambahkan!'
    }
    showModal.value = false
    load()
    setTimeout(() => success.value = '', 3000)
  } catch (err) {
    const data = err.response?.data
    if (data?.errors) {
      error.value = Object.values(data.errors).flat().join(' | ')
    } else {
      error.value = data?.message || 'Gagal menyimpan.'
    }
  } finally {
    saving.value = false
  }
}

async function hapus(id) {
  if (!confirm('Hapus lapangan ini?')) return
  try {
    await api.delete(`/admin/lapangan/${id}`)
    success.value = 'Lapangan berhasil dihapus!'
    load()
    setTimeout(() => success.value = '', 3000)
  } catch {
    alert('Gagal menghapus lapangan.')
  }
}

function formatRupiah(n) { return new Intl.NumberFormat('id-ID').format(n) }
</script>

<template>
  <div>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h5 class="fw-bold mb-0">Daftar Lapangan</h5>
        <small class="text-muted">Kelola data lapangan futsal</small>
      </div>
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-secondary fs-6">{{ lapangans.length }} data</span>
        <button @click="openAdd" class="btn btn-primary btn-sm">
          <i class="bi bi-plus-circle"></i> Tambah Lapangan
        </button>
      </div>
    </div>

    <div v-if="success" class="alert alert-success border-0">{{ success }}</div>

    <!-- Filter -->
    <div class="card border-0 shadow-sm rounded-3 p-3 mb-3">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Cari Nomor Lapangan</label>
          <input v-model="filterSearch" @input="load" type="text" class="form-control form-control-sm" placeholder="Contoh: A1, B2...">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">Status</label>
          <select v-model="filterStatus" @change="load" class="form-select form-select-sm">
            <option value="">Semua Status</option>
            <option value="tersedia">Tersedia</option>
            <option value="perbaikan">Perbaikan</option>
          </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
          <button @click="load" type="button" class="btn btn-primary btn-sm w-100">
            <i class="bi bi-funnel"></i> Filter
          </button>
          <button @click="filterSearch='';filterStatus='';load()" type="button" class="btn btn-outline-secondary btn-sm w-100">
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
              <th>Foto</th>
              <th>Nomor Lapangan</th>
              <th>Harga/Jam</th>
              <th>Deskripsi</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(l, i) in lapangans" :key="l.lapangan_id">
              <td class="ps-4">{{ i + 1 }}</td>
              <td>
                 <img v-if="l.gambar" :src="`${apiBase}/media/lapangan/${encodeURIComponent(l.gambar)}`"
                     :alt="l.nomor_lapangan"
                     style="width:60px;height:50px;object-fit:cover;border-radius:8px;">
                <div v-else class="d-flex align-items-center justify-content-center rounded"
                     style="width:60px;height:50px;background:#e9ecef;color:#adb5bd;">
                  <i class="bi bi-image"></i>
                </div>
              </td>
              <td class="fw-semibold">{{ l.nomor_lapangan }}</td>
              <td>Rp {{ formatRupiah(l.harga_per_jam) }}</td>
              <td>{{ l.deskripsi ?? '-' }}</td>
              <td>
                <span v-if="l.status === 'tersedia'" class="badge bg-success">Tersedia</span>
                <span v-else class="badge bg-danger">Perbaikan</span>
              </td>
              <td class="text-center">
                <button @click="openEdit(l)" class="btn btn-sm btn-warning">
                  <i class="bi bi-pencil"></i>
                </button>
                <button @click="hapus(l.lapangan_id)" class="btn btn-sm btn-danger ms-1">
                  <i class="bi bi-trash"></i>
                </button>
              </td>
            </tr>
            <tr v-if="lapangans.length === 0">
              <td colspan="7" class="text-center text-muted py-4">
                <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                Belum ada lapangan. <button @click="openAdd" class="btn btn-link p-0">Tambah sekarang</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div v-if="showModal" class="modal d-block" style="background:rgba(0,0,0,.5);" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
          <div class="modal-header">
            <h6 class="modal-title fw-bold">
              {{ editMode ? 'Edit Lapangan' : 'Tambah Lapangan' }}
            </h6>
            <button @click="showModal = false" class="btn-close"></button>
          </div>
          <div class="modal-body">
            <div v-if="error" class="alert alert-danger border-0 small">{{ error }}</div>
            <form @submit.prevent="save">
              <div class="mb-3">
                <label class="form-label fw-semibold small">Nomor Lapangan</label>
                <input v-model="form.nomor_lapangan" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Harga per Jam (Rp)</label>
                <input v-model="form.harga_per_jam" type="number" class="form-control" min="1" required>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Deskripsi</label>
                <textarea v-model="form.deskripsi" class="form-control" rows="2"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Status</label>
                <select v-model="form.status" class="form-select" required>
                  <option value="tersedia">Tersedia</option>
                  <option value="perbaikan">Perbaikan</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label fw-semibold small">Gambar</label>
                <input @change="onFileChange" type="file" class="form-control"
                       accept="image/jpg,image/jpeg,image/png">
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" @click="showModal = false" class="btn btn-outline-secondary btn-sm">Batal</button>
                <button type="submit" :disabled="saving" class="btn btn-warning btn-sm fw-bold">
                  <span v-if="saving" class="spinner-border spinner-border-sm me-1"></span>
                  {{ editMode ? 'Update' : 'Simpan' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
