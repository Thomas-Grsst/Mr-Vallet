<script setup lang="ts">
import type { Reservation } from '~/types/vallet'

defineProps<{
  reservation: Reservation
  anomalies: string[]
  canCancel: boolean
  isCancelling: boolean
}>()

defineEmits<{ close: [], cancel: [] }>()

const { formatDate } = useFormatDate()
</script>

<template>
  <aside class="detail" aria-label="Détail de la réservation">
    <div class="detail__head">
      <span class="detail__title">{{ reservation.machine_ref }} · {{ reservation.machine_type }} · {{ reservation.machine_agency }}</span>
      <button type="button" class="button button--ghost button--small" @click="$emit('close')">Fermer</button>
    </div>

    <dl class="detail__list">
      <dt>Client</dt>
      <dd>{{ reservation.client }}</dd>
      <template v-if="reservation.purchase_order">
        <dt>Bon de commande</dt>
        <dd>{{ reservation.purchase_order }}</dd>
      </template>
      <dt>Période</dt>
      <dd>du {{ formatDate(reservation.starts_at) }} au {{ formatDate(reservation.ends_at) }}</dd>
      <dt>Agence de saisie</dt>
      <dd>saisie par {{ reservation.entered_by }}</dd>
      <dt>Annulation</dt>
      <dd :class="{ 'detail__locked': !reservation.cancellable }">
        {{ reservation.cancellable ? `Annulable jusqu'au ${formatDate(reservation.cancellable_until)}` : 'Plus annulable (moins de 48 h avant le début)' }}
      </dd>
    </dl>

    <div v-if="anomalies.length" class="status-block status-block--ko">
      <span class="status-block__title">⚠ Anomalie</span>
      <span v-for="message in anomalies" :key="message" class="status-block__detail">{{ message }}</span>
    </div>

    <button
      v-if="canCancel && reservation.cancellable"
      type="button"
      class="button button--ghost detail__cancel"
      :disabled="isCancelling"
      @click="$emit('cancel')"
    >
      {{ isCancelling ? 'Annulation…' : 'Annuler la réservation' }}
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

.detail__list {
  display: grid;
  grid-template-columns: auto 1fr;
  gap: 6px 12px;
  margin: 0;
  font-size: 0.9rem;
}

.detail__list dt {
  color: var(--color-muted);
}

.detail__list dd {
  margin: 0;
}

.detail__locked {
  color: var(--color-ko);
  font-weight: 600;
}

.detail__cancel {
  align-self: flex-start;
}
</style>
