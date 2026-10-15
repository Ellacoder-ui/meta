<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { clearSession, getUser } from '@/services/auth'

const router = useRouter()
const user = ref(getUser())
const stats = reactive({ total_items: '—', available_items: '—', borrowed_items: '—', pending_requests: '—' })
const items = ref([])
const categories = ref([])
const transactions = ref([])
const users = ref([])
const filterCategory = ref('')
const filterStatus = ref('')
const loading = ref(true)
const error = ref('')

async function fetchAll() {
  try {
    const [statsRes, itemsRes, categoriesRes, txRes, usersRes] = await Promise.all([
      api.get('/dashboard/stats'),
      api.get('/items', { params: { category_id: filterCategory.value || undefined, status: filterStatus.value || undefined } }),
      api.get('/categories'),
      api.get('/transactions'),
      api.get('/users'),
    ])
    Object.assign(stats, statsRes.data.data)
    items.value = itemsRes.data.data
    categories.value = categoriesRes.data.data
    transactions.value = txRes.data.data
    users.value = usersRes.data.data
  } catch (e) {
    error.value = e.response?.data?.message || 'Failed to load dashboard data.'
  } finally {
    loading.value = false
  }
}

async function setStatus(id, status) {
  loading.value = true
  try {
    await api.patch(`/transactions/${id}/status`, { status })
    await fetchAll()
  } finally {
    loading.value = false
  }
}

async function promote(id) {
  loading.value = true
  try {
    await api.patch(`/users/${id}/role`, { role: 'admin' })
    await fetchAll()
  } finally {
    loading.value = false
  }
}

function applyFilters() {
  fetchAll()
}

async function logout() {
  try {
    await api.post('/logout')
  } catch {
    // token may already be invalid; proceed to clear
  }
  clearSession()
  router.push('/login')
}

onMounted(fetchAll)
</script>

<template>
  <div class="portal portal--admin">
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
        <router-link class="sidebar__link sidebar__link--active" to="/admin">🏠 Dashboard</router-link>
      </nav>
      <button class="sidebar__logout" @click="logout">↪ Logout</button>
    </aside>

    <main class="portal__content">
      <div class="page-header">
        <h1 class="page-header__title">Administrator Dashboard</h1>
        <p class="page-header__subtitle">Welcome, {{ user?.name }}</p>
      </div>

      <div class="stat-grid stat-grid--6">
        <div class="stat-card"><div class="stat-card__value">{{ stats.total_items }}</div><div class="stat-card__label">Total Inventory Items</div></div>
        <div class="stat-card"><div class="stat-card__value">{{ stats.available_items }}</div><div class="stat-card__label">Available Items</div></div>
        <div class="stat-card"><div class="stat-card__value">{{ stats.borrowed_items }}</div><div class="stat-card__label">Borrowed Items</div></div>
        <div class="stat-card"><div class="stat-card__value">{{ stats.pending_requests }}</div><div class="stat-card__label">Pending Requests</div></div>
      </div>

      <div class="section-card">
        <div class="section-card__header"><span class="section-card__title">Inventory Items</span></div>
        <div class="filters" style="display:flex;gap:8px;margin-bottom:12px">
          <select v-model="filterCategory" @change="applyFilters">
            <option value="">All Categories</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
          <select v-model="filterStatus" @change="applyFilters">
            <option value="">All Statuses</option>
            <option value="available">Available</option>
            <option value="assigned">Assigned</option>
            <option value="under_repair">Under Repair</option>
            <option value="disposed">Disposed</option>
          </select>
        </div>
        <table v-if="items.length" style="width:100%;border-collapse:collapse">
          <thead>
            <tr><th align="left">Asset Tag</th><th align="left">Name</th><th align="left">Category</th><th align="left">Status</th><th align="left">Location</th></tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.asset_tag }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.category?.name }}</td>
              <td>{{ item.status }}</td>
              <td>{{ item.location }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state"><div class="empty-state__title">No items found</div></div>
      </div>

      <div class="section-card">
        <div class="section-card__header"><span class="section-card__title">Users</span></div>
        <table v-if="users.length" style="width:100%;border-collapse:collapse">
          <thead><tr><th align="left">Name</th><th align="left">Email</th><th align="left">Department</th><th align="left">Role</th><th align="left">Actions</th></tr></thead>
          <tbody>
            <tr v-for="u in users" :key="u.id">
              <td>{{ u.name }}</td><td>{{ u.email }}</td><td>{{ u.department }}</td><td>{{ u.role }}</td>
              <td><button :disabled="loading" v-if="u.role === 'user'" @click="promote(u.id)">Promote to Admin</button></td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state"><div class="empty-state__title">No users found</div></div>
      </div>

      <div class="section-card">
        <div class="section-card__header"><span class="section-card__title">Transactions</span></div>
        <table v-if="transactions.length" style="width:100%;border-collapse:collapse">
          <thead>
            <tr><th align="left">User</th><th align="left">Item</th><th align="left">Status</th><th align="left">Expected Return</th><th align="left">Actions</th></tr>
          </thead>
          <tbody>
            <tr v-for="tx in transactions" :key="tx.id">
              <td>{{ tx.user?.name }}</td>
              <td>{{ tx.item?.name }}</td>
              <td>{{ tx.status }}</td>
              <td>{{ tx.expected_return_at }}</td>
              <td>
<button :disabled="loading" v-if="tx.status === 'pending'" @click="setStatus(tx.id, 'approved')">Approve</button>
                <button :disabled="loading" v-if="tx.status === 'pending'" @click="setStatus(tx.id, 'rejected')">Reject</button>
                <button :disabled="loading" v-if="tx.status === 'approved'" @click="setStatus(tx.id, 'active')">Mark Active</button>
                <button :disabled="loading" v-if="tx.status === 'active'" @click="setStatus(tx.id, 'returned')">Mark Returned</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state"><div class="empty-state__title">No transactions</div></div>
      </div>
    </main>
  </div>
</template>
