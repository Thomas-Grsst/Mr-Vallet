<script setup lang="ts">
import type { Machine, WorkshopPeriod } from '~/types/vallet'

defineProps<{
  machine: Machine
  period: WorkshopPeriod
  isEnding: boolean
}>()

defineEmits<{ close: [], end: [] }>()

const { user } = useAuth()
const { formatDate } = useFormatDate()
</script>

<template>
  <aside class="detail" aria-label="Détail du passage en atelier">
    <div class="detail__head">
      <span class="detail__title">{{ machine.ref }} · {{ machine.type }} · {{ machine.agency }}</span>
      <button type="button" class="button button--ghost button--small" @click="$emit('close')">Fermer</button>
    </div>

    <div class="status-block" :class="period.status === 'current' ? 'status-block--ko' : 'status-block--planned'">
      <span class="status-block__title">
        {{ period.status === 'current' ? 'En atelier' : 'Prévu' }} du {{ formatDate(period.starts_at) }} au {{ formatDate(period.ends_at) }}
      </span>
      <span class="status-block__detail">Motif : {{ period.reason || 'non précisé' }}</span>
    </div>

    <p class="muted detail__hint">La machine n'est pas réservable pendant ce passage.</p>

    <button
      v-if="user?.can_maintain"
      type="button"
      class="button button--ghost detail__action"
      :disabled="isEnding"
      @click="$emit('end')"
    >
      {{ period.status === 'current' ? 'Remettre en service' : 'Annuler ce passage' }}
    </button>
  </aside>
</template>

<style scoped>
.detail {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 12px;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: #fff;
  align-self: start;
  position: sticky;
  top: 12px;
}

.detail__head {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 8px;
}

.detail__title {
  font-weight: 700;
}

.detail__hint {
  margin: 0;
  font-size: 0.85rem;
}

.detail__action {
  align-self: flex-start;
}
</style>
