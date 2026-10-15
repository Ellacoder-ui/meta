<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { saveSession } from '@/services/auth'

const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)

async function login() {
  error.value = ''
  loading.value = true
  try {
    const { data } = await api.post('/login', { email: email.value, password: password.value })
    saveSession(data.data.token, data.data.user)
    router.push(data.data.user.role === 'admin' ? '/admin' : '/user')
  } catch (e) {
    error.value = e.response?.data?.message || 'Login failed. Please try again.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-page auth-page--dark">
    <div class="auth-waves auth-waves--dark"></div>
    <aside class="auth-panel auth-panel--navy">
      <div class="logo">
        <div class="logo__icon" aria-hidden="true">T</div>
        <div>
          <div class="logo__name logo__name--white">TechVault</div>
          <div class="logo__sub logo__sub--white">ICT Inventory</div>
        </div>
      </div>
      <p class="auth-panel__footer auth-panel__footer--light">© 2026 TechVault · Liceo de Cagayan University</p>
    </aside>

    <section class="auth-card-wrapper">
      <div class="auth-card auth-card--dark">
        <div class="logo">
          <div class="logo__icon" aria-hidden="true">T</div>
          <div>
            <div class="logo__name logo__name--white">TechVault</div>
            <div class="logo__sub logo__sub--white">ICT Inventory</div>
          </div>
        </div>
        <div class="auth-badge auth-badge--sky">
          <span class="auth-badge__dot auth-badge__dot--sky"></span>
          Sign In
        </div>
        <h1 class="auth-card__title auth-card__title--white">Log In</h1>
        <p class="auth-card__subtitle auth-card__subtitle--dim">Your role will be identified automatically.</p>
        <div class="auth-divider auth-divider--dark"></div>

        <p v-if="error" class="auth-error">{{ error }}</p>

        <form class="auth-form" @submit.prevent="login">
          <div class="form-field">
            <label class="form-field__label form-field__label--white">Email</label>
            <input v-model="email" class="form-field__input form-field__input--dark" type="email" placeholder="Enter your email" required />
          </div>
          <div class="form-field">
            <label class="form-field__label form-field__label--white">Password</label>
            <input v-model="password" class="form-field__input form-field__input--dark" type="password" placeholder="Enter your password" required />
          </div>
          <button type="submit" class="btn btn--sky btn--full" :disabled="loading">
            {{ loading ? 'Signing in...' : 'Log In' }}
          </button>
        </form>
        <p class="auth-card__switch auth-card__switch--dim">
          Don't have an account?
          <router-link class="auth-link auth-link--sky" to="/register">Sign Up</router-link>
        </p>
      </div>
    </section>
  </main>
</template>
