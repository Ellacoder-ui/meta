<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { useToast } from '@/services/toast'

const router = useRouter()
const toast = useToast()

// ---- State ----
const categories = ref([])
const loading = ref(false)
const saving = ref(false)
const deletingId = ref(null)
const modalOpen = ref(false)
const editing = ref(false)
const currentCategoryId = ref(null)

const categoryForm = reactive({
  name: '',
  description: '',
})

// ---- Helpers ----
function resetForm() {
  categoryForm.name = ''
  categoryForm.description = ''
  editing.value = false
  currentCategoryId.value = null
}

// ---- API ----
async function fetchCategories() {
  loading.value = true
  try {
    const { data } = await api.get('/categories')
    categories.value = data.data || data
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors
  } finally {
    loading.value = false
  }
}

async function saveCategory() {
  saving.value = true

  try {
    if (editing.value && currentCategoryId.value) {
      await api.patch(`/categories/${currentCategoryId.value}`, {
        name: categoryForm.name,
        description: categoryForm.description || null,
      })
      toast.success('Category updated successfully.')
    } else {
      await api.post('/categories', {
        name: categoryForm.name,
        description: categoryForm.description || null,
      })
      toast.success('Category created successfully.')
    }

    modalOpen.value = false
    await fetchCategories()
  } catch (e) {
    // api.js interceptor already shows a toast for HTTP errors (including 422 validation)
  } finally {
    saving.value = false
  }
}

async function deleteCategory(id) {
  if (!confirm('Are you sure you want to delete this category? Categories with items assigned cannot be deleted.')) return

  deletingId.value = id
  try {
    await api.delete(`/categories/${id}`)
    toast.success('Category deleted successfully.')
    await fetchCategories()
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

function openEditModal(category) {
  resetForm()
  editing.value = true
  currentCategoryId.value = category.id
  categoryForm.name = category.name
  categoryForm.description = category.description || ''
  modalOpen.value = true
}

function closeModal() {
  modalOpen.value = false
  resetForm()
}

onMounted(() => {
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
        <router-link class="sidebar__link" to="/admin/items">📦 Items</router-link>
        <router-link class="sidebar__link sidebar__link--active" to="/admin/categories">📂 Categories</router-link>
      </nav>
      <button class="sidebar__logout" @click="router.push('/login')">↪ Logout</button>
    </aside>

    <main class="portal__content">
      <div class="page-header">
        <h1 class="page-header__title">Categories</h1>
        <p class="page-header__subtitle">Manage item categories in TechVault.</p>
      </div>

      <div class="section-card">
        <div class="section-card__header">
          <span class="section-card__title">Categories</span>
          <button class="btn btn--primary" @click="openCreateModal" :disabled="loading">
            + Add Category
          </button>
        </div>

        <table v-if="categories.length" style="width:100%;border-collapse:collapse;margin-top:12px">
          <thead>
            <tr>
              <th align="left">Name</th>
              <th align="left">Description</th>
              <th align="left">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="category in categories" :key="category.id">
              <td>{{ category.name }}</td>
              <td>{{ category.description || '—' }}</td>
              <td style="display:flex;gap:6px">
                <button class="btn btn--sky" style="padding:6px 10px;font-size:12px" @click="openEditModal(category)" :disabled="loading">Edit</button>
                <button class="btn btn--sky" style="padding:6px 10px;font-size:12px;opacity:0.6" :disabled="loading || deletingId === category.id">
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="empty-state"><div class="empty-state__title">No categories found</div></div>
      </div>

      <!-- Create / Edit Modal -->
      <div v-if="modalOpen" class="fixed inset-0 z-[60] flex items-center justify-center" style="background:rgba(0,0,0,0.45)">
        <div class="bg-white rounded-24 p-6 max-w-md w-full shadow-2xl border border-gray-200">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold text-gray-800">{{ editing ? 'Edit Category' : 'Create Category' }}</h2>
            <button @click="closeModal" class="text-gray-400 hover:text-gray-600">✕</button>
          </div>

          <form @submit.prevent="saveCategory" class="space-y-3">
            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Name <span class="text-red-500">*</span></label>
              <input v-model="categoryForm.name" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="e.g. Laptops" required />
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-600 mb-1">Description</label>
              <textarea v-model="categoryForm.description" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" rows="3" placeholder="e.g. Portable workstations for student labs and faculty."></textarea>
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
