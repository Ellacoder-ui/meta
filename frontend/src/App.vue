<script setup>
import { ref } from 'vue'

const activeView = ref('roles')
const selectedRole = ref('user')

function openLogin(role) {
  selectedRole.value = role
  activeView.value = 'login'
}

function backToRoles() {
  activeView.value = 'roles'
}
</script>

<template>
  <main v-if="activeView === 'roles'" class="role-selection">
    <header class="role-selection__topbar">
      <div class="logo">
        <div class="logo__icon" aria-hidden="true">M</div>
        <div>
          <div class="logo__name">MetaVault</div>
          <div class="logo__sub">Secure access</div>
        </div>
      </div>
    </header>

    <section class="role-selection__hero" aria-labelledby="role-title">
      <div class="role-selection__badge">Welcome back</div>
      <h1 id="role-title" class="role-selection__title">Choose your <span>portal</span></h1>
      <p class="role-selection__subtitle">Select how you would like to continue.</p>
      <div class="role-selection__cards">
        <article class="role-card role-card--user">
          <div class="role-card__icon-wrap" aria-hidden="true">U</div>
          <h2 class="role-card__title">User portal</h2>
          <p class="role-card__desc">Access your account and manage your personal workspace.</p>
          <button class="btn btn--primary-lg" type="button" @click="openLogin('user')">Continue as user</button>
        </article>
        <article class="role-card role-card--admin">
          <div class="role-card__icon-wrap" aria-hidden="true">A</div>
          <h2 class="role-card__title">Admin portal</h2>
          <p class="role-card__desc">Manage users, settings, and organization-wide controls.</p>
          <button class="btn btn--admin-primary" type="button" @click="openLogin('admin')">Continue as admin</button>
        </article>
      </div>
    </section>
    <footer class="role-selection__footer"><span>Protected workspace access</span></footer>
  </main>

  <main v-else :class="['login-page', `login-page--${selectedRole}`]">
    <section :class="['login-panel-left', `login-panel-left--${selectedRole}`]">
      <div class="left-panel__content">
        <div class="logo logo--dark">
          <div class="logo__icon" aria-hidden="true">M</div>
          <div><div class="logo__name">MetaVault</div><div class="logo__sub">Secure access</div></div>
        </div>
      </div>
      <div class="left-panel__quote-card">
        <div class="left-panel__icon-box" aria-hidden="true">*</div>
        <p class="left-panel__quote">Everything you need, in one secure workspace.</p>
      </div>
      <p class="left-panel__copyright">© 2026 MetaVault</p>
    </section>

    <section class="login-panel-right">
      <form :class="['login-card', `login-card--${selectedRole}`]" @submit.prevent>
        <div :class="['login-card__portal-badge', `login-card__portal-badge--${selectedRole}`]">
          <span class="login-card__badge-dot" aria-hidden="true"></span>
          <span class="login-card__badge-text">{{ selectedRole }} portal</span>
        </div>
        <h1 class="login-card__title">Sign in</h1>
        <p class="login-card__subtitle">Enter your details to continue.</p>
        <div class="form-fields">
          <label class="form-group"><span class="form-label">Email address</span><input class="form-input" :class="`form-input--${selectedRole}`" type="email" placeholder="you@example.com" required></label>
          <label class="form-group"><span class="form-label">Password</span><input class="form-input" :class="`form-input--${selectedRole}`" type="password" placeholder="Enter your password" required></label>
        </div>
        <div class="btn-group">
          <button class="btn btn--primary-lg" type="submit">Sign in</button>
          <button class="btn btn--secondary" type="button" @click="backToRoles">Choose another portal</button>
        </div>
      </form>
    </section>
  </main>
</template>

<style src="../frontend.css"></style>