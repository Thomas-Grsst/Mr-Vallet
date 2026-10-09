<script setup lang="ts">
import type { HistoryReservation, ReservationStatus } from '~/types/vallet'

const statuses: { value: ReservationStatus, label: string }[] = [
  { value: 'upcoming', label: 'À venir' },
  { value: 'ongoing', label: 'En cours' },
  { value: 'finished', label: 'Terminée' },
  { value: 'cancelled', label: 'Annulée' },
]

const { formatDate } = useFormatDate()

const { data: reservations, status: loadStatus, refresh } = useApiFetch<HistoryReservation[]>('/api/reservation-history', { default: () => [] })

const { filters, options, hasFilters, matches, resetFilters } = useReservationFilters(reservations)
const statusFilter = ref<ReservationStatus | ''>('')

const visibleReservations = computed(() =>
  reservations.value.filter((reservation) => matches(reservation) && (!statusFilter.value || reservation.status === statusFilter.value)),
)

const resetAllFilters = () => {
  resetFilters()
  statusFilter.value = ''
}
</script>

<template>
  <section class="card table-wrapper">
    <ReservationFilters
      v-model="filters"
      :options="options"
      :has-filters="hasFilters || !!statusFilter"
      @reset="resetAllFilters"
    >
      <label>
        Statut
        <select v-model="statusFilter">
          <option value="">Tous les statuts</option>
          <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
        </select>
      </label>
    </ReservationFilters>
    <LoadError v-if="loadStatus === 'error'" @retry="refresh()" />
    <LoadingMessage v-else-if="loadStatus !== 'success'" label="Chargement de l'historique…" />
    <table v-else>
      <thead>
        <tr>
          <th>Machine</th>
          <th>Agence de la machine</th>
          <th>Client</th>
          <th>Du</th>
          <th>Au</th>
          <th>Saisie par</th>
          <th>Statut</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="reservation in visibleReservations" :key="reservation.id">
          <td><strong>{{ reservation.machine_ref }}</strong> <span class="muted">{{ reservation.machine_type }}</span></td>
          <td>{{ reservation.machine_agency }}</td>
          <td>{{ reservation.client }}<div v-if="reservation.purchase_order" class="muted">BC {{ reservation.purchase_order }}</div></td>
          <td>{{ formatDate(reservation.starts_at) }}</td>
          <td>{{ formatDate(reservation.ends_at) }}</td>
          <td>{{ reservation.entered_by }}</td>
          <td>
            <span class="badge" :class="`history-status--${reservation.status}`">{{ reservation.status_label }}</span>
            <ol v-if="reservation.events.length" class="history-events">
              <li
                v-for="event in reservation.events"
                :key="`${event.type}-${event.description}`"
                :class="`history-events__item--${event.type}`"
              >
                {{ event.description }}
              </li>
            </ol>
          </td>
        </tr>
        <tr v-if="!visibleReservations.length">
          <td colspan="7" class="muted">Aucune réservation ne correspond aux filtres.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.history-events {
  margin: 6px 0 0;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 3px;
  font-size: 0.8rem;
  color: var(--color-muted);
}

.history-events li {
  padding-left: 8px;
  border-left: 3px solid var(--color-border);
}

.history-events__item--cancelled {
  border-left-color: var(--color-ko-bg) !important;
}

.history-status--upcoming {
  background: #dbeafe;
  color: #1d4ed8;
}

.history-status--ongoing {
  background: var(--color-ok-bg);
  color: var(--color-ok);
}

.history-status--finished {
  background: var(--color-border);
  color: var(--color-muted);
}

.history-status--cancelled {
  background: var(--color-ko-bg);
  color: var(--color-ko);
}
</style>
