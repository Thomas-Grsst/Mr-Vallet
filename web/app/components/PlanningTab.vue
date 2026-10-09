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

const { data: reservations, refresh } = useApiFetch<Reservation[]>('/api/reservations', { default: () => [] })
const { data: anomalies, refresh: refreshAnomalies } = useApiFetch<Anomaly[]>('/api/anomalies', { default: () => [] })

const clientFilter = ref('')

const clients = computed(() =>
  [...new Set(reservations.value.map((reservation) => reservation.client))].sort((first, second) => first.localeCompare(second, 'fr')),
)

const visibleReservations = computed(() =>
  clientFilter.value
    ? reservations.value.filter((reservation) => reservation.client === clientFilter.value)
    : reservations.value,
)

watch(clients, (available) => {
  if (clientFilter.value && !available.includes(clientFilter.value)) {
    clientFilter.value = ''
  }
})

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
    await Promise.all([refresh(), refreshAnomalies()])
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
    <div class="form-row planning-filter">
      <label>
        Client
        <select v-model="clientFilter">
          <option value="">Tous les clients</option>
          <option v-for="client in clients" :key="client" :value="client">{{ client }}</option>
        </select>
      </label>
    </div>
    <table>
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
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.planning-filter {
  margin-bottom: 12px;
}

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
