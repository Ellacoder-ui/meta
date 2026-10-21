import axios from 'axios'
import { clearSession } from '@/services/auth'
import { toastState } from '@/services/toast'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api/v1',
  timeout: 10000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

/**
 * Extract a human-readable message from an Axios error.
 * Handles Laravel 422 validation errors (field-level) and standard messages.
 */
function extractErrorMessage(error) {
  if (!error.response) {
    return error.message || 'Network error. Please try again.'
  }

  const { status, data } = error.response

  // Laravel 422 validation errors
  if (status === 422 && data && data.errors) {
    const fieldMessages = Object.values(data.errors).flat()
    if (fieldMessages.length) {
      return fieldMessages.join('. ')
    }
    return data.message || 'Validation failed. Please check your inputs.'
  }

  if (data && data.message) {
    return data.message
  }

  const statusMessages = {
    400: 'Bad request.',
    401: 'Your session has expired. Please log in again.',
    403: 'You do not have permission to perform this action.',
    404: 'The requested resource was not found.',
    409: 'Conflict. The resource already exists.',
    422: 'Validation failed. Please check your inputs.',
    500: 'Internal server error. Please try again later.',
    502: 'Bad gateway. The server is temporarily unavailable.',
    503: 'Service unavailable. Please try again later.',
  }

  return statusMessages[status] || 'An unexpected error occurred.'
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response ? error.response.status : null
    const message = extractErrorMessage(error)

    // 401 Unauthorized — clear session and redirect
    if (status === 401) {
      clearSession()
      import('@/router').then((m) => m.default.push('/login'))
    }

    // Surface all non-401 errors via the global toast (401 handled by redirect)
    if (status && status !== 401 && toastState.add) {
      const type = status === 422 ? 'error' : status === 403 ? 'error' : 'error'
      toastState.add(message, 'error', 6000)
    }

    return Promise.reject(error)
  }
)

export default api
