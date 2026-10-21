<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { saveSession } from '@/services/auth'
import { useToast } from '@/services/toast'

const router = useRouter()
const toast = useToast()
const name = ref('')
const email = ref('')
const department = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)

async function register() {
  if (password.value !== passwordConfirmation.value) {
    toast.error('Passwords do not match.')
    return
  }
  loading.value = true
  try {
    const { data } = await api.post('/register', {
      name: name.value,
      email: email.value,
      password: password.value,
      password_confirmation: passwordConfirmation.value,
      department: department.value || null,
    })
    saveSession(data.data.token, data.data.user)
    toast.success('Account created successfully.')
    router.push('/user')
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <main class="auth-page auth-page--light">
    <div class="auth-waves"></div>
    <aside class="auth-panel auth-panel--blue">
      <div class="logo">
        <div class="logo__icon" aria-hidden="true">T</div>
        <div>
          <div class="logo__name">TechVault</div>
          <div class="logo__sub">ICT Inventory</div>
        </div>
      </div>
      <p class="auth-panel__footer">© 2026 TechVault · Liceo de Cagayan University</p>
    </aside>

    <section class="auth-card-wrapper">
      <div class="auth-card">
        <div class="auth-badge">
          <span class="auth-badge__dot"></span>
          Create Account
        </div>
        <h1 class="auth-card__title">Sign Up</h1>
        <p class="auth-card__subtitle">Create your TechVault account to get started.</p>
        <div class="auth-divider"></div>

        <form class="auth-form" @submit.prevent="register">
          <div class="form-field">
            <label class="form-field__label">Name <span class="required">*</span></label>
            <input v-model="name" class="form-field__input" type="text" placeholder="Full name" required />
          </div>
          <div class="form-field">
            <label class="form-field__label">Email <span class="required">*</span></label>
            <input v-model="email" class="form-field__input" type="email" placeholder="you@liceo.edu.ph" required />
          </div>
          <div class="form-field">
            <label class="form-field__label">Department</label>
            <input v-model="department" class="form-field__input" type="text" placeholder="e.g. Computer Science" />
          </div>
          <div class="form-field">
            <label class="form-field__label">Password <span class="required">*</span></label>
            <input v-model="password" class="form-field__input" type="password" placeholder="Min 8 characters" required />
          </div>
          <div class="form-field">
            <label class="form-field__label">Confirm Password <span class="required">*</span></label>
            <input v-model="passwordConfirmation" class="form-field__input" type="password" placeholder="Repeat your password" required />
          </div>
          <button type="submit" class="btn btn--primary btn--full" :disabled="loading">
            {{ loading ? 'Creating...' : 'Sign Up' }}
          </button>
        </form>
        <p class="auth-card__switch">
          Already have an account?
          <router-link class="auth-link" to="/login">Log In</router-link>
        </p>
      </div>
    </section>
  </main>
</template>
