import { reactive } from 'vue'

/**
 * Shared reactive toast state. App.vue registers add/remove handlers;
 * pages and api.js call toastState.add(...) to surface notifications.
 */
export const toastState = reactive({
  toasts: [],
  add: null,
  remove: null,
})

/**
 * Composable for components. Provides typed toast shortcuts.
 */
export function useToast() {
  return {
    success: (message, duration = 5000) =>
      toastState.add?.(message, 'success', duration),
    error: (message, duration = 5000) =>
      toastState.add?.(message, 'error', duration),
    info: (message, duration = 5000) =>
      toastState.add?.(message, 'info', duration),
    warning: (message, duration = 5000) =>
      toastState.add?.(message, 'warning', duration),
    add: (message, type = 'info', duration = 5000) =>
      toastState.add?.(message, type, duration),
    remove: (id) => toastState.remove?.(id),
  }
}
