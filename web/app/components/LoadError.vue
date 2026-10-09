<script setup lang="ts">
defineEmits<{ retry: [] }>()
</script>

<template>
  <div class="alert alert--ko load-error" role="alert">
    <span>Impossible de charger les données. Vérifiez la connexion puis réessayez.</span>
    <button type="button" class="button button--ghost" @click="$emit('retry')">Réessayer</button>
  </div>
</template>

<style scoped>
.load-error {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
</style>
