<script setup>
import { computed } from 'vue'
import { toastState } from '@/services/toast'

const toasts = computed(() => toastState.toasts)

const typeConfig = {
  success: {
    bg: '#16a34a',
    border: '#15803d',
    text: '#ffffff',
    icon: '✅',
  },
  error: {
    bg: '#dc2626',
    border: '#b91c1c',
    text: '#ffffff',
    icon: '❌',
  },
  info: {
    bg: '#2563eb',
    border: '#1d4ed8',
    text: '#ffffff',
    icon: 'ℹ️',
  },
  warning: {
    bg: '#d97706',
    border: '#b45309',
    text: '#ffffff',
    icon: '⚠️',
  },
}

function styleFor(type) {
  return typeConfig[type] || typeConfig.info
}

function removeToast(id) {
  const index = toastState.toasts.findIndex((t) => t.id === id)
  if (index > -1) {
    toastState.toasts.splice(index, 1)
  }
}
</script>

<template>
  <div
    class="toast-container"
    style="
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 8px;
      pointer-events: none;
    "
  >
    <transition-group
      name="toast"
      tag="div"
      class="toast-list"
      style="display: flex; flex-direction: column; gap: 8px; pointer-events: auto;"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast-item"
        :style="
          {
            background: styleFor(toast.type).bg,
            color: styleFor(toast.type).text,
            border: `1px solid ${styleFor(toast.type).border}`,
            padding: '12px 16px',
            borderRadius: '10px',
            boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.2)',
            minWidth: '300px',
            maxWidth: '420px',
            animation: 'toastSlideIn 0.3s ease-out forwards',
          }
        "
      >
        <div
          class="toast-content"
          style="display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 500;"
        >
          <span class="toast-icon" style="font-size: 18px;">
            {{ styleFor(toast.type).icon }}
          </span>
          <span class="toast-message">{{ toast.message }}</span>
          <button
            class="toast-close"
            @click="removeToast(toast.id)"
            style="
              margin-left: auto;
              background: none;
              border: none;
              color: inherit;
              opacity: 0.8;
              cursor: pointer;
              font-size: 18px;
              line-height: 1;
              padding: 0;
            "
          >
            ✕
          </button>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<style scoped>
.toast-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.toast-item {
  animation: toastSlideIn 0.3s ease-out forwards;
  will-change: transform, opacity;
}

@keyframes toastSlideIn {
  from {
    transform: translateX(120%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.toast-item.leave-active,
.toast-item.leave-to {
  animation: toastSlideOut 0.3s ease-in forwards;
}

@keyframes toastSlideOut {
  to {
    transform: translateX(120%);
    opacity: 0;
  }
}
</style>
