import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '@/pages/LoginPage.vue'
import RegisterPage from '@/pages/RegisterPage.vue'
import AdminDashboard from '@/pages/AdminDashboard.vue'
import UserDashboard from '@/pages/UserDashboard.vue'
import { getToken, getUser } from '@/services/auth'

const routes = [
  { path: '/', redirect: '/login' },
  { path: '/login', name: 'login', component: LoginPage },
  { path: '/register', name: 'register', component: RegisterPage },
  {
    path: '/admin',
    name: 'admin',
    component: AdminDashboard,
    meta: { requiresAuth: true, role: 'admin' },
  },
  {
    path: '/user',
    name: 'user',
    component: UserDashboard,
    meta: { requiresAuth: true },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})

router.beforeEach((to) => {
  const token = getToken()
  const user = getUser()

  if (to.path === '/') {
    if (!token) return '/login'
    return user?.role === 'admin' ? '/admin' : '/user'
  }

  if (to.meta.requiresAuth && !token) {
    return '/login'
  }

  if (to.meta.role === 'admin' && user?.role !== 'admin') {
    return '/user'
  }

  if ((to.path === '/login' || to.path === '/register') && token) {
    return user?.role === 'admin' ? '/admin' : '/user'
  }

  return true
})

export default router
