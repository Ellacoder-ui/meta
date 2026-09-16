<script setup>
import { ref } from 'vue'

const activeView = ref('signup')
const selectedRole = ref(null)

function goToChooseRole() {
  activeView.value = 'choose-role'
}

function goToLogin() {
  activeView.value = 'login'
}

function goToSignUp() {
  activeView.value = 'signup'
}

function selectRole(role) {
  selectedRole.value = role
}

function continueToPortal() {
  if (!selectedRole.value) return
  activeView.value = selectedRole.value === 'admin' ? 'admin-dashboard' : 'user-dashboard'
}

function loginAndRedirect() {
  activeView.value = selectedRole.value === 'admin' ? 'admin-dashboard' : 'user-dashboard'
}

function logout() {
  selectedRole.value = null
  activeView.value = 'signup'
}
</script>

<template>

  <!-- ═══════════ SIGN UP ═══════════ -->
  <main v-if="activeView === 'signup'" class="auth-page auth-page--light">
    <div class="auth-waves"></div>
    <aside class="auth-panel auth-panel--blue">
      <div class="logo">
        <div class="logo__icon" aria-hidden="true">T</div>
        <div>
          <div class="logo__name">TechVault</div>
          <div class="logo__sub">ICT Inventory</div>
        </div>
      </div>
      <div class="auth-features">
        <p class="auth-features__label">Platform Features</p>
        <div class="auth-feature-item">
          <div class="auth-feature-item__icon">🔒</div>
          <div>
            <div class="auth-feature-item__title">Secure Access</div>
            <div class="auth-feature-item__sub">Accounts are protected end-to-end</div>
          </div>
        </div>
        <div class="auth-feature-item">
          <div class="auth-feature-item__icon">📦</div>
          <div>
            <div class="auth-feature-item__title">Inventory Visibility</div>
            <div class="auth-feature-item__sub">Browse and track all ICT assets</div>
          </div>
        </div>
        <div class="auth-feature-item">
          <div class="auth-feature-item__icon">⚡</div>
          <div>
            <div class="auth-feature-item__title">Real-Time Updates</div>
            <div class="auth-feature-item__sub">Track request status instantly</div>
          </div>
        </div>
      </div>
      <p class="auth-panel__footer">© 2026 TechVault · Liceo de Cagayan University</p>
    </aside>

    <section class="auth-card-wrapper">
      <div class="auth-card">
        <div class="auth-card__logo">
          <div class="logo">
            <div class="logo__icon" aria-hidden="true">T</div>
            <div>
              <div class="logo__name logo__name--dark">TechVault</div>
              <div class="logo__sub">ICT Inventory</div>
            </div>
          </div>
        </div>
        <div class="auth-badge">
          <span class="auth-badge__dot"></span>
          Create Account
        </div>
        <h1 class="auth-card__title">Sign Up</h1>
        <p class="auth-card__subtitle">Create your TechVault account to get started.</p>
        <div class="auth-divider"></div>
        <form class="auth-form" @submit.prevent="goToChooseRole">
          <div class="form-field">
            <label class="form-field__label">Username <span class="required">*</span></label>
            <input class="form-field__input" type="text" placeholder="Choose a username" />
          </div>
          <div class="form-field">
            <label class="form-field__label">Password <span class="required">*</span></label>
            <input class="form-field__input" type="password" placeholder="Create a password" />
          </div>
          <div class="form-field">
            <label class="form-field__label">Confirm Password <span class="required">*</span></label>
            <input class="form-field__input" type="password" placeholder="Repeat your password" />
          </div>
          <button type="submit" class="btn btn--primary btn--full">Sign Up</button>
        </form>
        <p class="auth-card__switch">
          Already have an account?
          <button class="auth-link" @click="goToLogin">Log In</button>
        </p>
      </div>
    </section>
  </main>

  <!-- ═══════════ CHOOSE ROLE ═══════════ -->
  <main v-else-if="activeView === 'choose-role'" class="choose-role-page">
    <div class="auth-waves"></div>
    <div class="choose-role__inner">
      <div class="logo choose-role__logo">
        <div class="logo__icon" aria-hidden="true">T</div>
        <div>
          <div class="logo__name logo__name--dark">TechVault</div>
          <div class="logo__sub">ICT Inventory</div>
        </div>
      </div>
      <div class="auth-badge">
        <span class="auth-badge__dot"></span>
        Account Setup
      </div>
      <h1 class="choose-role__title">Choose Your Role</h1>
      <p class="choose-role__subtitle">Select the role that best describes how you will use TechVault.</p>

      <div class="role-cards">
        <div
          class="role-card"
          :class="{ 'role-card--active': selectedRole === 'admin' }"
          @click="selectRole('admin')"
        >
          <div class="role-card__icon">🛡️</div>
          <h2 class="role-card__title">Admin</h2>
          <p class="role-card__desc">Manage inventory, users, transactions, reports, and system settings.</p>
          <div class="role-card__radio" :class="{ 'role-card__radio--checked': selectedRole === 'admin' }">
            <span v-if="selectedRole === 'admin'" class="role-card__radio-dot"></span>
          </div>
        </div>
        <div
          class="role-card"
          :class="{ 'role-card--active': selectedRole === 'user' }"
          @click="selectRole('user')"
        >
          <div class="role-card__icon">👤</div>
          <h2 class="role-card__title">User</h2>
          <p class="role-card__desc">View inventory, submit requests, borrow equipment, and manage personal transactions.</p>
          <div class="role-card__radio" :class="{ 'role-card__radio--checked': selectedRole === 'user' }">
            <span v-if="selectedRole === 'user'" class="role-card__radio-dot"></span>
          </div>
        </div>
      </div>

      <button
        class="btn btn--primary btn--continue"
        :class="{ 'btn--disabled': !selectedRole }"
        :disabled="!selectedRole"
        @click="continueToPortal"
      >
        Continue →
      </button>
      <p class="choose-role__hint">You can contact the IT Department to change your role after registration.</p>
    </div>
  </main>

  <!-- ═══════════ LOGIN ═══════════ -->
  <main v-else-if="activeView === 'login'" class="auth-page auth-page--dark">
    <div class="auth-waves auth-waves--dark"></div>
    <aside class="auth-panel auth-panel--navy">
      <div class="logo">
        <div class="logo__icon" aria-hidden="true">T</div>
        <div>
          <div class="logo__name logo__name--white">TechVault</div>
          <div class="logo__sub logo__sub--white">ICT Inventory</div>
        </div>
      </div>
      <div class="auth-features">
        <p class="auth-features__label auth-features__label--light">Platform Features</p>
        <div class="auth-feature-item">
          <div class="auth-feature-item__icon">🛡️</div>
          <div>
            <div class="auth-feature-item__title auth-feature-item__title--white">Role-Based Access</div>
            <div class="auth-feature-item__sub auth-feature-item__sub--light">System identifies your role automatically</div>
          </div>
        </div>
        <div class="auth-feature-item">
          <div class="auth-feature-item__icon">🔍</div>
          <div>
            <div class="auth-feature-item__title auth-feature-item__title--white">Session Monitoring</div>
            <div class="auth-feature-item__sub auth-feature-item__sub--light">All sessions are securely tracked</div>
          </div>
        </div>
        <div class="auth-feature-item">
          <div class="auth-feature-item__icon">⚙️</div>
          <div>
            <div class="auth-feature-item__title auth-feature-item__title--white">Full Platform Access</div>
            <div class="auth-feature-item__sub auth-feature-item__sub--light">Admin or User dashboard upon login</div>
          </div>
        </div>
      </div>
      <p class="auth-panel__footer auth-panel__footer--light">© 2026 TechVault · Liceo de Cagayan University</p>
    </aside>

    <section class="auth-card-wrapper">
      <div class="auth-card auth-card--dark">
        <div class="auth-card__logo">
          <div class="logo">
            <div class="logo__icon" aria-hidden="true">T</div>
            <div>
              <div class="logo__name logo__name--white">TechVault</div>
              <div class="logo__sub logo__sub--white">ICT Inventory</div>
            </div>
          </div>
        </div>
        <div class="auth-badge auth-badge--sky">
          <span class="auth-badge__dot auth-badge__dot--sky"></span>
          Sign In
        </div>
        <h1 class="auth-card__title auth-card__title--white">Log In</h1>
        <p class="auth-card__subtitle auth-card__subtitle--dim">Your role will be identified automatically.</p>
        <div class="auth-divider auth-divider--dark"></div>
        <form class="auth-form" @submit.prevent="loginAndRedirect">
          <div class="form-field">
            <label class="form-field__label form-field__label--white">Username</label>
            <input class="form-field__input form-field__input--dark" type="text" placeholder="Enter your username" />
          </div>
          <div class="form-field">
            <label class="form-field__label form-field__label--white">Password</label>
            <input class="form-field__input form-field__input--dark" type="password" placeholder="Enter your password" />
          </div>

          <!-- Prototype role selector -->
          <div class="proto-selector">
            <p class="proto-selector__label">Prototype: Simulate saved account role</p>
            <div class="proto-selector__buttons">
              <button
                type="button"
                class="proto-btn"
                :class="{ 'proto-btn--active': selectedRole === 'user' || !selectedRole }"
                @click="selectRole('user')"
              >User</button>
              <button
                type="button"
                class="proto-btn"
                :class="{ 'proto-btn--active': selectedRole === 'admin' }"
                @click="selectRole('admin')"
              >Administrator</button>
            </div>
          </div>

          <button type="submit" class="btn btn--sky btn--full">Log In</button>
        </form>
        <p class="auth-card__switch auth-card__switch--dim">
          Don't have an account?
          <button class="auth-link auth-link--sky" @click="goToSignUp">Sign Up</button>
        </p>
      </div>
    </section>
  </main>

  <!-- ═══════════ ADMIN DASHBOARD ═══════════ -->
  <div v-else-if="activeView === 'admin-dashboard'" class="portal portal--admin">
    <aside class="sidebar sidebar--admin">
      <div class="sidebar__brand">
        <div class="logo">
          <div class="logo__icon" aria-hidden="true">T</div>
          <div>
            <div class="logo__name logo__name--white">TechVault</div>
            <div class="logo__sub logo__sub--white">ICT Inventory</div>
          </div>
        </div>
        <span class="sidebar__role-badge">Administrator</span>
      </div>
      <nav class="sidebar__nav">
        <span class="sidebar__section-label">Management</span>
        <a class="sidebar__link sidebar__link--active" href="#">🏠 Dashboard</a>
        <a class="sidebar__link" href="#">📦 Inventory</a>
        <a class="sidebar__link" href="#">⬇️ Stock In</a>
        <a class="sidebar__link" href="#">⬆️ Stock Out</a>
        <a class="sidebar__link" href="#">🤲 Borrowing</a>
        <a class="sidebar__link" href="#">↩️ Returns</a>
        <a class="sidebar__link" href="#">📋 Requests</a>
        <a class="sidebar__link" href="#">👥 Users</a>
        <a class="sidebar__link" href="#">🚚 Suppliers</a>
        <a class="sidebar__link" href="#">🏢 Departments</a>
        <div class="sidebar__divider"></div>
        <span class="sidebar__section-label">System</span>
        <a class="sidebar__link" href="#">🗂️ Categories</a>
        <a class="sidebar__link" href="#">📊 Reports</a>
        <a class="sidebar__link" href="#">📝 Audit Logs</a>
        <a class="sidebar__link" href="#">🔔 Notifications</a>
        <a class="sidebar__link" href="#">⚙️ Settings</a>
      </nav>
      <button class="sidebar__logout" @click="logout">↪ Logout</button>
    </aside>
    <main class="portal__content">
      <div class="page-header">
        <h1 class="page-header__title">Administrator Dashboard</h1>
        <p class="page-header__subtitle">TechVault ICT Inventory Management System</p>
      </div>
      <div class="stat-grid stat-grid--6">
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">Total Inventory Items</div><div class="stat-card__sub">No records yet</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">Available Items</div><div class="stat-card__sub">No records yet</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">Low Stock Items</div><div class="stat-card__sub">No records yet</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">Borrowed Items</div><div class="stat-card__sub">No records yet</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">Damaged Items</div><div class="stat-card__sub">No records yet</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">Pending Requests</div><div class="stat-card__sub">No records yet</div></div>
      </div>
      <div class="dashboard-grid">
        <div class="section-card">
          <div class="section-card__header"><span class="section-card__title">Recent Transactions</span></div>
          <div class="empty-state"><div class="empty-state__icon">📋</div><div class="empty-state__title">No recent transactions</div><p class="empty-state__desc">Transactions will appear here once the system is in use.</p></div>
        </div>
        <div class="section-card">
          <div class="section-card__header"><span class="section-card__title">Pending Requests</span></div>
          <div class="empty-state"><div class="empty-state__icon">📋</div><div class="empty-state__title">No pending requests</div><p class="empty-state__desc">Requests awaiting review will appear here.</p></div>
        </div>
      </div>
    </main>
  </div>

  <!-- ═══════════ USER DASHBOARD ═══════════ -->
  <div v-else-if="activeView === 'user-dashboard'" class="portal portal--user">
    <aside class="sidebar sidebar--user">
      <div class="sidebar__brand">
        <div class="logo">
          <div class="logo__icon" aria-hidden="true">T</div>
          <div>
            <div class="logo__name logo__name--dark">TechVault</div>
            <div class="logo__sub">ICT Inventory</div>
          </div>
        </div>
      </div>
      <nav class="sidebar__nav">
        <span class="sidebar__section-label sidebar__section-label--light">Navigation</span>
        <a class="sidebar__link sidebar__link--user-active" href="#">🏠 Dashboard</a>
        <a class="sidebar__link sidebar__link--user" href="#">📦 Inventory</a>
        <a class="sidebar__link sidebar__link--user" href="#">📋 Request Item</a>
        <a class="sidebar__link sidebar__link--user" href="#">🤲 Borrow Equipment</a>
        <a class="sidebar__link sidebar__link--user" href="#">📄 My Requests</a>
        <a class="sidebar__link sidebar__link--user" href="#">📦 My Borrowed Items</a>
        <a class="sidebar__link sidebar__link--user" href="#">↩️ Returns</a>
        <a class="sidebar__link sidebar__link--user" href="#">🔔 Notifications</a>
        <a class="sidebar__link sidebar__link--user" href="#">👤 Profile</a>
      </nav>
      <button class="sidebar__logout sidebar__logout--user" @click="logout">↪ Logout</button>
    </aside>
    <main class="portal__content portal__content--user">
      <div class="page-header">
        <h1 class="page-header__title">Dashboard</h1>
        <p class="page-header__subtitle">Welcome to TechVault ICT Inventory Platform</p>
      </div>
      <div class="stat-grid stat-grid--4">
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">Available Items</div><div class="stat-card__sub">Items ready to borrow</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">My Pending Requests</div><div class="stat-card__sub">Awaiting approval</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">My Approved Requests</div><div class="stat-card__sub">Ready for pickup</div></div>
        <div class="stat-card"><div class="stat-card__value">—</div><div class="stat-card__label">My Borrowed Equipment</div><div class="stat-card__sub">Currently checked out</div></div>
      </div>
      <div class="dashboard-grid">
        <div class="section-card">
          <div class="section-card__header"><span class="section-card__title">Recent Activity</span></div>
          <div class="empty-state"><div class="empty-state__icon">📋</div><div class="empty-state__title">No recent activity</div><p class="empty-state__desc">Your recent actions will appear here once you begin using the platform.</p></div>
        </div>
        <div class="section-card">
          <div class="section-card__header"><span class="section-card__title">System Status</span></div>
          <div class="status-list">
            <div class="status-item"><span class="status-item__name">Inventory System</span><span class="status-item__state status-item__state--ok">● Operational</span></div>
            <div class="status-item"><span class="status-item__name">Request Processing</span><span class="status-item__state status-item__state--ok">● Operational</span></div>
            <div class="status-item"><span class="status-item__name">Notification Service</span><span class="status-item__state status-item__state--ok">● Operational</span></div>
          </div>
        </div>
      </div>
    </main>
  </div>

</template>
