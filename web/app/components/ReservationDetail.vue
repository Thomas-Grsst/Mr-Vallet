<script setup lang="ts">
import type { Reservation, ReservationChanges } from '~/types/vallet'

const props = defineProps<{
  reservation: Reservation
  anomalies: string[]
  canCancel: boolean
  cancelBlockedReason: string | null
  canEdit: boolean
  isCancelling: boolean
  isSaving: boolean
  editErrors: string[]
}>()

const emit = defineEmits<{ close: [], cancel: [], save: [changes: ReservationChanges] }>()

const { formatDate } = useFormatDate()

const isEditing = ref(false)
const startsAt = ref('')
const endsAt = ref('')
const purchaseOrder = ref('')

const startEditing = () => {
  startsAt.value = props.reservation.starts_at
  endsAt.value = props.reservation.ends_at
  purchaseOrder.value = props.reservation.purchase_order ?? ''
  isEditing.value = true
}

watch(() => props.reservation, () => {
  isEditing.value = false
})

const save = () => emit('save', {
  starts_at: startsAt.value,
  ends_at: endsAt.value,
  purchase_order: purchaseOrder.value.trim() || null,
})
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
      <template v-if="reservation.modified_at">
        <dt>Modification</dt>
        <dd>modifiée le {{ formatDate(reservation.modified_at) }} par {{ reservation.modified_by }}</dd>
      </template>
      <dt>Annulation</dt>
      <dd v-if="reservation.cancellable">Annulable jusqu'au {{ formatDate(reservation.cancellable_until) }}</dd>
      <dd v-else-if="reservation.cancel_beyond_deadline" class="detail__beyond">Délai de 48 h dépassé : annulation possible par la Direction</dd>
      <dd v-else class="detail__locked">Plus annulable (moins de 48 h avant le début)</dd>
    </dl>

    <div v-if="anomalies.length" class="status-block status-block--ko">
      <span class="status-block__title">⚠ Anomalie</span>
      <span v-for="message in anomalies" :key="message" class="status-block__detail">{{ message }}</span>
    </div>

    <form v-if="isEditing" class="detail__edit" @submit.prevent="save">
      <p v-if="!reservation.cancellable" class="muted detail__hint">Moins de 48 h avant le début : la location peut seulement être prolongée.</p>
      <label>
        Du
        <input v-model="startsAt" type="date" :disabled="!reservation.cancellable">
      </label>
      <label>
        Au
        <input v-model="endsAt" type="date" :min="reservation.cancellable ? startsAt : reservation.ends_at">
      </label>
      <label>
        N° de bon de commande
        <input v-model="purchaseOrder" type="text" placeholder="ex. BC-2026-0412">
      </label>
      <div v-if="editErrors.length" class="alert alert--ko">
        <strong>Modification refusée</strong>
        <ul><li v-for="message in editErrors" :key="message">{{ message }}</li></ul>
      </div>
      <div class="detail__actions">
        <button type="submit" class="button" :disabled="isSaving">{{ isSaving ? 'Enregistrement…' : 'Enregistrer' }}</button>
        <button type="button" class="button button--ghost" @click="isEditing = false">Abandonner</button>
      </div>
    </form>

    <div v-else class="detail__actions">
      <button v-if="canEdit" type="button" class="button" @click="startEditing">Modifier</button>
      <button
        v-if="canCancel"
        type="button"
        class="button button--ghost"
        :disabled="isCancelling"
        @click="$emit('cancel')"
      >
        {{ isCancelling ? 'Annulation…' : 'Annuler la réservation' }}
      </button>
      <p v-if="cancelBlockedReason && reservation.cancellable" class="muted detail__hint">{{ cancelBlockedReason }}</p>
    </div>
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

.detail__beyond {
  color: #b45309;
  font-weight: 600;
}

.detail__locked {
  color: var(--color-ko);
  font-weight: 600;
}

.detail__edit {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.detail__edit label {
  display: flex;
  flex-direction: column;
  gap: 2px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
  color: var(--color-muted);
}

.detail__edit input {
  color: var(--color-text);
  text-transform: none;
}

.detail__hint {
  margin: 0;
  font-size: 0.85rem;
}

.detail__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
