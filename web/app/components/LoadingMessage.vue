<script setup lang="ts">
defineProps<{ label: string }>()
</script>

<template>
  <div class="loading" role="status" aria-live="polite">
    <span class="loading__spinner" aria-hidden="true" />
    <span>{{ label }}</span>
  </div>
</template>

<style scoped>
.loading {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 16px;
  color: var(--color-muted);
  font-weight: 600;
}

.loading__spinner {
  width: 18px;
  height: 18px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: loading-spin 0.8s linear infinite;
}

@keyframes loading-spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
