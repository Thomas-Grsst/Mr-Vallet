<script setup lang="ts">
import type { Anomaly, Reservation } from '~/types/vallet'

const anomalyLabels: Record<string, string> = {
  overlap: 'double réservation',
  vgp_expired: 'VGP non à jour',
}

const { $api } = useNuxtApp()
const { user } = useAuth()
const { formatDate } = useFormatDate()
const { toMessages } = useApiErrors()
const errors = ref<string[]>([])

const { data: reservations, status: reservationsStatus, refresh } = useApiFetch<Reservation[]>('/api/reservations', { default: () => [] })
const { data: anomalies, status: anomaliesStatus, refresh: refreshAnomalies } = useApiFetch<Anomaly[]>('/api/anomalies', { default: () => [] })

const hasLoadError = computed(() => reservationsStatus.value === 'error' || anomaliesStatus.value === 'error')
const isLoaded = computed(() => reservationsStatus.value === 'success' && anomaliesStatus.value === 'success')

const reload = () => Promise.all([refresh(), refreshAnomalies()])

const { filters, options, hasFilters, matches, resetFilters } = useReservationFilters(reservations)

const visibleReservations = computed(() => reservations.value.filter(matches))

const anomaliesByReservation = computed(() => {
  const labels = new Map<number, Set<string>>()

  for (const anomaly of anomalies.value) {
    for (const reservationId of anomaly.reservation_ids) {
      const reservationLabels = labels.get(reservationId) ?? new Set<string>()
      reservationLabels.add(anomalyLabels[anomaly.code] ?? anomaly.code)
      labels.set(reservationId, reservationLabels)
    }
  }

  return labels
})

const cancel = async (reservation: Reservation) => {
  if (!confirm(`Annuler la réservation de ${reservation.machine_ref} pour ${reservation.client} ?`)) {
    return
  }

  errors.value = []
  try {
    await $api(`/api/reservations/${reservation.id}`, { method: 'DELETE' })
    await reload()
  }
  catch (error) {
    errors.value = toMessages(error)
  }
}
</script>

<template>
  <section class="card table-wrapper">
    <div v-if="errors.length" class="alert alert--ko">
      <ul><li v-for="message in errors" :key="message">{{ message }}</li></ul>
    </div>
    <ReservationFilters v-model="filters" :options="options" :has-filters="hasFilters" @reset="resetFilters" />
    <LoadError v-if="hasLoadError" @retry="reload()" />
    <LoadingMessage v-else-if="!isLoaded" label="Chargement du planning…" />
    <table v-else>
      <thead>
        <tr>
          <th>Machine</th>
          <th>Agence de la machine</th>
          <th>Client</th>
          <th>Du</th>
          <th>Au</th>
          <th>Saisie par</th>
          <th v-if="user?.can_book" />
        </tr>
      </thead>
      <tbody>
        <tr v-for="reservation in visibleReservations" :key="reservation.id">
          <td>
            <strong>{{ reservation.machine_ref }}</strong> <span class="muted">{{ reservation.machine_type }}</span>
            <div v-if="anomaliesByReservation.has(reservation.id)" class="planning-anomaly">
              ⚠ Attention : anomalie ({{ [...anomaliesByReservation.get(reservation.id)!].join(', ') }})
            </div>
          </td>
          <td>{{ reservation.machine_agency }}</td>
          <td>{{ reservation.client }}</td>
          <td>{{ formatDate(reservation.starts_at) }}</td>
          <td>{{ formatDate(reservation.ends_at) }}</td>
          <td>{{ reservation.entered_by }}</td>
          <td v-if="user?.can_book"><button type="button" class="button button--ghost" @click="cancel(reservation)">Annuler</button></td>
        </tr>
        <tr v-if="!visibleReservations.length">
          <td colspan="7" class="muted">Aucune réservation ne correspond aux filtres.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.planning-anomaly {
  display: inline-block;
  margin-top: 6px;
  padding: 2px 8px;
  border-radius: 6px;
  background: var(--color-ko-bg);
  color: var(--color-ko);
  font-size: 0.85rem;
  font-weight: 600;
}
</style>
