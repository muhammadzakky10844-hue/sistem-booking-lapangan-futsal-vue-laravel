import { defineStore } from 'pinia'
import api from '@/utils/api'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    admin: null,
    isAuthenticated: false,
    loading: false,
  }),

  actions: {
    async checkAuth() {
      const token = localStorage.getItem('admin_token')
      if (!token) {
        this.admin = null
        this.isAuthenticated = false
        return
      }
      try {
        const res = await api.get('/me')
        if (res.data.authenticated) {
          this.admin = res.data.admin
          this.isAuthenticated = true
        }
      } catch {
        localStorage.removeItem('admin_token')
        this.admin = null
        this.isAuthenticated = false
      }
    },

    async login(email, password) {
      this.loading = true
      try {
        const res = await api.post('/login', { email, password })
        localStorage.setItem('admin_token', res.data.token)
        this.admin = res.data.admin
        this.isAuthenticated = true
        return { success: true }
      } catch (err) {
        return { success: false, message: err.response?.data?.message || 'Login gagal.' }
      } finally {
        this.loading = false
      }
    },

    async logout() {
      try {
        await api.post('/logout')
      } finally {
        localStorage.removeItem('admin_token')
        this.admin = null
        this.isAuthenticated = false
      }
    },
  },
})
