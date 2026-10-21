<script setup>
import { onMounted, reactive } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import api from '@/services/api'
import { clearSession, getToken } from '@/services/auth'
import { toastState } from '@/services/toast'
import Toast from '@/components/Toast.vue'

const router = useRouter()

// ---- Global toast state ----
const toasts = reactive([])

const addToast = (message, type = 'info', duration = 5000) => {
  const id = Date.now() + Math.random()
  toasts.push({ id, message, type, duration })
  toastState.toasts = [...toasts] // keep shared state in sync
  if (duration > 0) {
    setTimeout(() => removeToast(id), duration)
  }
}

const removeToast = (id) => {
  const index = toasts.findIndex((t) => t.id === id)
  if (index > -1) {
    toasts.splice(index, 1)
    toastState.toasts = [...toasts]
  }
}

// Register handlers so api.js and useToast() can trigger toasts globally
toastState.add = addToast
toastState.remove = removeToast

// ---- Auth bootstrap ----
onMounted(async () => {
  if (getToken()) {
    try {
      const { data } = await api.get('/me')
      localStorage.setItem('user', JSON.stringify(data.data))
    } catch {
      clearSession()
      router.push('/login')
    }
  }
})
</script>

<template>
  <Toast />
  <RouterView />
</template>
