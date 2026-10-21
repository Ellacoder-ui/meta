<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from '@/services/toast'

const router = useRouter()
const toast = useToast()

// ---- State ----
const items = ref([])
const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const modalOpen = ref(false)
const editing = ref(false)
const currentItemId = ref(null)
const search = ref('')
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
  from: null,
  to: null,
})

const itemForm = reactive({
  asset_tag: '',
  name: '',
  category_id: '',
  serial_number: '',
  status: 'available',
  location: '',
})

const statuses = ['available', 'assigned', 'under_repair', 'disposed']

// ---- Helpers ----
function resetForm() {
  itemForm.asset_tag = ''
  itemForm.name = ''
  itemForm.category_id = ''
  itemForm.serial_number = ''
  itemForm.status = 'available'
  itemForm.location = ''
  editing.value = false
  currentItemId.value = null
}

// ---- API ----
async function fetchItems() {
  loading.value = true
  try {
    const { data } = await api.get('/items', {
      params: {
        search: search.value || undefined,
        page: pagination.current_page,
        per_page: pagination.per_page,
      },
    })
    items.value = data.data || []
    Object.assign(pagination, data.meta || {})
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors
  } finally {
    loading.value = false
  }
}

async function fetchCategories() {
  try {
    const { data } = await api.get('/categories')
    categories.value = data.data || data
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors
  }
}

function setPage(page) {
  if (page < 1 || page > pagination.last_page) return
  pagination.current_page = page
  fetchItems()
}

function applyFilters() {
  pagination.current_page = 1
  fetchItems()
}

async function saveItem() {
  saving.value = true

  try {
    if (editing.value && currentItemId.value) {
      await api.patch(`/items/${currentItemId.value}`, {
        asset_tag: itemForm.asset_tag,
        name: itemForm.name,
        category_id: Number(itemForm.category_id),
        serial_number: itemForm.serial_number || null,
        status: itemForm.status,
        location: itemForm.location || null,
      })
      toast.success('Item updated successfully.')
    } else {
      await api.post('/items', {
        asset_tag: itemForm.asset_tag,
        name: itemForm.name,
        category_id: Number(itemForm.category_id),
        serial_number: itemForm.serial_number || null,
        status: itemForm.status,
        location: itemForm.location || null,
      })
      toast.success('Item created successfully.')
    }

    modalOpen.value = false
    await fetchItems()
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors (including 422 validation)
  } finally {
    saving.value = false
  }
}

async function deleteItem(id) {
  if (!confirm('Are you sure you want to delete this item? Items with transaction history cannot be deleted.')) return

  deletingId.value = id
  try {
    await api.delete(`/items/${id}`)
    toast.success('Item deleted successfully.')
    await fetchItems()
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors
  } finally {
    deletingId.value = null
  }
}

function openCreateModal() {
  resetForm()
  modalOpen.value = true
}

function openEditModal(item) {
  resetForm()
  editing.value = true
  currentItemId.value = item.id
  itemForm.asset_tag = item.asset_tag
  itemForm.name = item.name
  itemForm.category_id = item.category_id
  itemForm.serial_number = item.serial_number || ''
  itemForm.status = item.status
  itemForm.location = item.location || ''
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
  resetForm()
}

onMounted(() => {
  pagination.current_page = 1
  fetchItems()
  fetchCategories()
})
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
        <router-link class="sidebar__link" to="/admin">🏠 Dashboard</router-link>
        <router-link class="sidebar__link sidebar__link--active" to="/admin/items">📦 Items</router-link>
        <router-link class="sidebar__link" to="/admin/categories">📂 Categories</router-link>
      </nav>
      <button class="sidebar__logout" @click="router.push('/login')">↪ Logout</button>
    </aside>

    <main class="portal__content">
      <div class="page-header">
        <h1 class="page-header__title">Inventory Items</h1>
        <p class="page-header__subtitle">Manage all ICT assets in TechVault.</p>
      </div>

      <div class="section-card">
        <div class="section-card__header" style="display:flex;justify-content:space-between;align-items:center">
          <span class="section-card__title">Items</span>
          <div style="display:flex;gap:8px;align-items:center">
            <input
              v-model="search"
              @change="applyFilters"
              placeholder="Search by name or asset tag..."
              class="border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
              style="min-width:220px;font-size:12px"
            />
            <button class="btn btn--primary" @click="openCreateModal" :disabled="loading">
              + Add Item
            </button>
          </div>
        </div>

        <table v-if="items.length" style="width:100%;border-collapse:collapse;margin-top:12px">
          <thead>
            <tr>
              <th align="left">Asset Tag</th>
              <th align="left">Name</th>
              <th align="left">Category</th>
              <th align="left">Status</th>
              <th align="left">Location</th>
              <th align="left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id">
              <td>{{ item.asset_tag }}</td>
              <td>{{ item.name }}</td>
              <td>{{ item.category?.name || '—' }}</td>
              <td>{{ item.status }}</td>
              <td>{{ item.location || '—' }}</td>
              <td style="display:flex;gap:6px">
                <button class="btn btn--sky" style="padding:6px 10px;font-size:12px" @click="openEditModal(item)" :disabled="loading">Edit</button>
                <button class="btn btn--sky" style="padding:6px 10px;font-size:12px;opacity:0.6" :disabled="loading || deletingId === item.id">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state"><div class="empty-state__title">No items found</div></div>

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

      <!-- Create / Edit Modal -->
      <div v-if="modalOpen" class="fixed inset-0 z-[60] flex items-center justify-center" style="background:rgba(0,0,0,0.45)">
        <div class="bg-white rounded-24 p-6 max-w-md w-full shadow-2xl border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">{{ editing ? 'Edit Item' : 'Create Item' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>

          <form @submit.prevent="saveItem" class="space-y-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Asset Tag <span class="text-red-500">*</span></label>
              <input v-model="itemForm.asset_tag" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. LDCU-LAP-001" required />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
              <input v-model="itemForm.name" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Dell Latitude 5420 Laptop" required />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Category <span class="text-red-500">*</span></label>
              <select v-model="itemForm.category_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                <option value="">Select a category</option>
                <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Serial Number</label>
              <input v-model="itemForm.serial_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. DL5420-001" />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
              <select v-model="itemForm.status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Location</label>
              <input v-model="itemForm.location" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. ICT Lab 1" />
            </div>

            <div class="flex justify-end gap-3 mt-6">
              <button type="button" @click="closeModal" class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
              <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed" :disabled="saving">
                {{ saving ? 'Saving...' : (editing ? 'Update' : 'Create') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>

<style scoped>
.fixed { position: fixed; }
.inset-0 { inset: 0; }
.z-\[60\] { z-index: 60; }
.flex { display: flex; }
.items-center { align-items: center; }
.justify-center { justify-content: center; }
.bg-white { background-color: #fff; }
.rounded-24 { border-radius: 24px; }
.p-6 { padding: 1.5rem; }
.max-w-md { max-width: 28rem; }
.w-full { width: 100%; }
.shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
.border { border-width: 1px; }
.border-gray-200 { border-color: #e5e7eb; }
.text-xl { font-size: 1.25rem; }
.font-bold { font-weight: 700; }
.text-gray-800 { color: #1f2937; }
.mb-4 { margin-bottom: 1rem; }
.text-gray-400 { color: #9ca3af; }
.hover\:text-gray-600:hover { color: #4b5563; }
.text-sm { font-size: 0.875rem; }
.text-red-600 { color: #dc2626; }
.mb-3 { margin-bottom: 0.75rem; }
.text-green-600 { color: #16a34a; }
.space-y-3 > * + * { margin-top: 0.75rem; }
.block { display: block; }
.text-xs { font-size: 0.75rem; }
.font-semibold { font-weight: 600; }
.text-gray-600 { color: #4b5563; }
.mb-1 { margin-bottom: 0.25rem; }
.text-red-500 { color: #ef4444; }
.border-gray-300 { border-color: #d1d5db; }
.rounded-lg { border-radius: 0.5rem; }
.px-3 { padding-left: 0.75rem; padding-right: 0.75rem; }
.py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
.focus\:outline-none:focus { outline: 2px solid transparent; outline-offset: 2px; }
.focus\:ring-2:focus { box-shadow: var(--tw-ring-offset-shadow), var(--tw-ring-shadow), var(--tw-shadow, 0 0 #0000); }
.focus\:ring-blue-500:focus { --tw-ring-offset-shadow: var(--tw-ring-inset) 0 0 0 var(--tw-ring-offset-width) var(--tw-ring-offset-color); --tw-ring-shadow: var(--tw-ring-inset) 0 0 0 calc(2px + var(--tw-ring-offset-width)) var(--tw-ring-color); ring-color: #3b82f6; }
.mt-6 { margin-top: 1.5rem; }
.gap-3 { gap: 0.75rem; }
.justify-end { justify-content: flex-end; }
.px-4 { padding-left: 1rem; padding-right: 1rem; }
.text-white { color: #fff; }
.bg-blue-600 { background-color: #2563eb; }
.hover\:bg-blue-700:hover { background-color: #1d4ed8; }
.text-blue-500 { color: #3b82f6; }
</style>
