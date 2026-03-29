<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth   = useAuthStore()

const email    = ref('')
const password = ref('')
const error    = ref('')

async function submit() {
  error.value = ''
  const result = await auth.login(email.value, password.value)
  if (result.success) {
    router.push('/admin/dashboard')
  } else {
    error.value = result.message
  }
}
</script>

<template>
  <div style="background:linear-gradient(135deg,#1a1a2e,#16213e,#0f3460);min-height:100vh;display:flex;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:2.5rem;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.3);">

      <div style="width:70px;height:70px;background:#0d6efd;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:2rem;color:#fff;">
        <i class="bi bi-person-lock"></i>
      </div>

      <h4 class="text-center fw-bold mb-1">Admin Login</h4>
      <p class="text-center text-muted small mb-4">Sistem Booking Lapangan Futsal</p>

      <div v-if="error" class="alert alert-danger">
        <i class="bi bi-x-circle"></i> {{ error }}
      </div>

      <form @submit.prevent="submit">
        <div class="mb-3">
          <label class="form-label fw-semibold">Email</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input v-model="email" type="email" class="form-control"
                   placeholder="admin@admin.com" required autofocus>
          </div>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input v-model="password" type="password" class="form-control"
                   placeholder="••••••••" required>
          </div>
        </div>
        <button type="submit" :disabled="auth.loading" class="btn btn-primary w-100 py-2 fw-semibold">
          <span v-if="auth.loading" class="spinner-border spinner-border-sm me-1"></span>
          <i v-else class="bi bi-box-arrow-in-right"></i>
          {{ auth.loading ? 'Login...' : 'Login' }}
        </button>
      </form>

    </div>
  </div>
</template>
