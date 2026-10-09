<script setup lang="ts">
defineProps<{ message: string | null }>()
</script>

<template>
  <div v-if="message" class="alert alert--ok action-notice" role="status" aria-live="polite">✓ {{ message }}</div>
</template>

<style scoped>
.action-notice {
  position: sticky;
  top: 8px;
  z-index: 5;
  margin: 0 0 12px;
}
</style>
