<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { clearSession, getUser } from '@/services/auth'
import { useToast } from '@/services/toast'

const router = useRouter()
const toast = useToast()
const user = ref(getUser())
const stats = reactive({ total_items: '—', available_items: '—', borrowed_items: '—', pending_requests: '—' })
const items = ref([])
const categories = ref([])
const myTransactions = ref([])
const borrowItemId = ref('')
const expectedReturnAt = ref('')
const search = ref('')
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
})
const loading = ref(true)

async function fetchAll() {
  loading.value = true
  try {
    const [statsRes, itemsRes, categoriesRes, txRes] = await Promise.all([
      api.get('/dashboard/stats'),
      api.get('/items', {
        params: {
          search: search.value || undefined,
          status: 'available',
          page: pagination.current_page,
          per_page: pagination.per_page,
        },
      }),
      api.get('/categories'),
      api.get('/transactions', { params: { user_id: user.value?.id } }),
    ])
    Object.assign(stats, statsRes.data.data)
    items.value = itemsRes.data.data || []
    Object.assign(pagination, itemsRes.data.meta || {})
    categories.value = categoriesRes.data.data || []
    myTransactions.value = txRes.data.data || []
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors
  } finally {
    loading.value = false
  }
}

async function borrow() {
  if (!borrowItemId.value || !expectedReturnAt.value) return
  loading.value = true
  try {
    await api.post('/transactions/borrow', {
      item_id: Number(borrowItemId.value),
      expected_return_at: expectedReturnAt.value,
    })
    toast.success('Borrow request submitted successfully.')
    borrowItemId.value = ''
    expectedReturnAt.value = ''
    await fetchAll()
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors (including 422 validation)
  } finally {
    loading.value = false
  }
}

function setPage(page) {
  if (page < 1 || page > pagination.last_page) return
  pagination.current_page = page
  fetchAll()
}

function applyFilters() {
  pagination.current_page = 1
  fetchAll()
}

async function logout() {
  try {
    await api.post('/logout')
  } catch {
    // ignore
  }
  clearSession()
  router.push('/login')
}

onMounted(fetchAll)
</script>

<template>
  <div class="portal portal--user">
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
        <router-link class="sidebar__link sidebar__link--user-active" to="/user">🏠 Dashboard</router-link>
      </nav>
      <button class="sidebar__logout sidebar__logout--user" @click="logout">↪ Logout</button>
    </aside>

    <main class="portal__content portal__content--user">
      <div class="page-header">
        <h1 class="page-header__title">Dashboard</h1>
        <p class="page-header__subtitle">Welcome, {{ user?.name }}</p>
      </div>

      <div class="stat-grid stat-grid--4">
        <div class="stat-card"><div class="stat-card__value">{{ stats.available_items }}</div><div class="stat-card__label">Available Items</div></div>
        <div class="stat-card"><div class="stat-card__value">{{ stats.pending_requests }}</div><div class="stat-card__label">My Pending Requests</div></div>
        <div class="stat-card"><div class="stat-card__value">{{ stats.borrowed_items }}</div><div class="stat-card__label">My Borrowed Equipment</div></div>
        <div class="stat-card"><div class="stat-card__value">{{ stats.total_items }}</div><div class="stat-card__label">Total Items</div></div>
      </div>

      <div class="section-card">
        <div class="section-card__header"><span class="section-card__title">Borrow Equipment</span></div>
        <form @submit.prevent="borrow" style="display:flex;gap:8px;flex-wrap:wrap">
          <select v-model="borrowItemId" required>
            <option value="" disabled>Select an available item</option>
            <option v-for="item in items" :key="item.id" :value="item.id">{{ item.name }} ({{ item.asset_tag }})</option>
          </select>
          <input v-model="expectedReturnAt" type="datetime-local" required />
          <button type="submit" class="btn btn--primary" :disabled="loading">
            {{ loading ? 'Requesting...' : 'Request' }}
          </button>
        </form>
      </div>

      <div class="section-card">
        <div class="section-card__header" style="display:flex;justify-content:space-between;align-items:center">
          <span class="section-card__title">Available Items</span>
          <input
            v-model="search"
            @change="applyFilters"
            placeholder="Search by name or asset tag..."
            class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
            style="min-width:220px;font-size:12px"
          />
        </div>
        <table v-if="items.length" style="width:100%;border-collapse:collapse">
          <thead><tr><th align="left">Asset Tag</th><th align="left">Name</th><th align="left">Category</th><th align="left">Location</th></tr></thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.asset_tag }}</td><td>{{ item.name }}</td><td>{{ item.category?.name }}</td><td>{{ item.location }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state"><div class="empty-state__title">No available items</div></div>

        <div v-if="pagination.total > 0" style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;gap:8px">
          <div style="font-size:12px;color:var(--c-text-sub)">
            Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }}
          </div>
          <div style="display:flex;gap:6px;align-items:center">
            <button
              :disabled="pagination.current_page === 1"
              @click="setPage(pagination.current_page - 1)"
              class="btn btn--sky"
              style="padding:6px 12px;font-size:12px"
            >
              Prev
            </button>
            <span style="font-size:12px;color:var(--c-text-sub)">
              Page {{ pagination.current_page }} of {{ pagination.last_page }}
            </span>
            <button
              :disabled="pagination.current_page === pagination.last_page"
              @click="setPage(pagination.current_page + 1)"
              class="btn btn--sky"
              style="padding:6px 12px;font-size:12px"
            >
              Next
            </button>
          </div>
        </div>
      </div>

      <div class="section-card">
        <div class="section-card__header"><span class="section-card__title">My Requests</span></div>
        <table v-if="myTransactions.length" style="width:100%;border-collapse:collapse">
          <thead><tr><th align="left">Item</th><th align="left">Status</th><th align="left">Expected Return</th></tr></thead>
          <tbody>
            <tr v-for="tx in myTransactions" :key="tx.id">
              <td>{{ tx.item?.name }}</td><td>{{ tx.status }}</td><td>{{ tx.expected_return_at }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state"><div class="empty-state__title">No requests yet</div></div>
      </div>
    </main>
  </div>
</template>
