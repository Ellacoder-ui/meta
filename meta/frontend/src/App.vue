<script setup>
import { onMounted } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import api from '@/services/api'
import { clearSession, getToken } from '@/services/auth'

const router = useRouter()

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
  <RouterView />
</template>
