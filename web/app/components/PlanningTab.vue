<script setup lang="ts">
import type { Anomaly, Machine, Reservation, ReservationChanges, WorkshopPeriod } from '~/types/vallet'

type View = 'timeline' | 'list'

const anomalyLabels: Record<string, string> = {
  overlap: 'double réservation',
  vgp_expired: 'VGP non à jour',
  workshop: 'en atelier',
  missing_purchase_order: 'grand compte sans bon de commande',
}

const durations: { days: number, label: string, previous: string, next: string }[] = [
  { days: 7, label: '1 semaine', previous: 'Semaine précédente', next: 'Semaine suivante' },
  { days: 14, label: '2 semaines', previous: '2 semaines avant', next: '2 semaines après' },
  { days: 30, label: '1 mois', previous: 'Mois précédent', next: 'Mois suivant' },
]

const today = useRuntimeConfig().public.today
const { $api } = useNuxtApp()
const { formatDate } = useFormatDate()
const { toMessages } = useApiErrors()
const { addDays } = useIsoDate()
const { notice, notify } = useActionNotice()

const errors = ref<string[]>([])
const view = ref<View>('timeline')
const days = ref(14)
const rangeStart = ref(today)
const selectedId = ref<number | null>(null)
const selectedWorkshop = ref<{ machineRef: string, periodId: number } | null>(null)
const isCancelling = ref(false)
const isSaving = ref(false)
const editErrors = ref<string[]>([])

const { data: reservations, status: reservationsStatus, refresh } = useApiFetch<Reservation[]>('/api/reservations', { default: () => [] })
const { data: anomalies, status: anomaliesStatus, refresh: refreshAnomalies } = useApiFetch<Anomaly[]>('/api/anomalies', { default: () => [] })
const { data: machines, status: machinesStatus, refresh: refreshMachines } = useApiFetch<Machine[]>('/api/machines', { default: () => [] })

const { isFirstLoading, hasError, hasLoaded } = useFirstLoad([reservationsStatus, anomaliesStatus, machinesStatus])

const reload = () => Promise.all([refresh(), refreshAnomalies(), refreshMachines()])

const { filters, options, hasFilters, matches, resetFilters } = useReservationFilters(reservations)

const visibleReservations = computed(() => reservations.value.filter(matches))

const timelineMachines = computed(() => {
  if (!hasFilters.value) {
    return machines.value
  }

  const refs = new Set(visibleReservations.value.map((reservation) => reservation.machine_ref))
  return machines.value.filter((machine) => refs.has(machine.ref))
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

const selected = computed(() => reservations.value.find((reservation) => reservation.id === selectedId.value) ?? null)

const selectedWorkshopMachine = computed(() => machines.value.find((machine) => machine.ref === selectedWorkshop.value?.machineRef) ?? null)

const selectedWorkshopPeriod = computed(() => selectedWorkshopMachine.value?.workshop_periods
  .find((period) => period.id === selectedWorkshop.value?.periodId) ?? null)

const hasDetail = computed(() => !!selected.value || !!selectedWorkshopPeriod.value)

const selectedAnomalies = computed(() => anomalies.value
  .filter((anomaly) => selectedId.value !== null && anomaly.reservation_ids.includes(selectedId.value))
  .map((anomaly) => anomaly.message))

const duration = computed(() => durations.find((option) => option.days === days.value) ?? durations[1]!)

const rangeLabel = computed(() => `du ${formatDate(rangeStart.value)} au ${formatDate(addDays(rangeStart.value, days.value - 1))}`)

const move = (direction: number) => {
  rangeStart.value = addDays(rangeStart.value, direction * days.value)
}

const select = (reservation: Reservation) => {
  selectedWorkshop.value = null
  selectedId.value = reservation.id
  editErrors.value = []
}

const selectWorkshop = (machine: Machine, period: WorkshopPeriod) => {
  selectedId.value = null
  selectedWorkshop.value = { machineRef: machine.ref, periodId: period.id }
  errors.value = []
}

const closeDetail = () => {
  selectedId.value = null
  selectedWorkshop.value = null
}

const saveSelected = async (changes: ReservationChanges) => {
  if (!selected.value) {
    return
  }

  editErrors.value = []
  isSaving.value = true
  try {
    await $api(`/api/reservations/${selected.value.id}`, { method: 'PATCH', body: changes })
    await reload()
    notify(`Réservation modifiée : ${selected.value?.machine_ref} du ${formatDate(changes.starts_at)} au ${formatDate(changes.ends_at)}`)
  }
  catch (error) {
    editErrors.value = toMessages(error)
  }
  finally {
    isSaving.value = false
  }
}

const cancelSelected = async () => {
  const reservation = selected.value

  if (!reservation || !confirm(`Annuler la réservation de ${reservation.machine_ref} pour ${reservation.client} ?`)) {
    return
  }

  errors.value = []
  isCancelling.value = true
  try {
    await $api(`/api/reservations/${reservation.id}`, { method: 'DELETE' })
    selectedId.value = null
    await reload()
    notify(`Réservation annulée : ${reservation.machine_ref} pour ${reservation.client}`)
  }
  catch (error) {
    errors.value = toMessages(error)
  }
  finally {
    isCancelling.value = false
  }
}

const endSelectedWorkshop = async () => {
  const machine = selectedWorkshopMachine.value
  const period = selectedWorkshopPeriod.value

  if (!machine || !period) {
    return
  }

  const isCurrent = period.status === 'current'
  const question = isCurrent
    ? `Remettre ${machine.ref} en service dès aujourd'hui ?`
    : `Annuler le passage en atelier de ${machine.ref} du ${formatDate(period.starts_at)} au ${formatDate(period.ends_at)} ?`

  if (!confirm(question)) {
    return
  }

  errors.value = []
  isCancelling.value = true
  try {
    await $api(`/api/workshop-periods/${period.id}`, { method: 'DELETE' })
    selectedWorkshop.value = null
    await reload()
    notify(isCurrent ? `${machine.ref} remise en service` : `Passage en atelier de ${machine.ref} annulé`)
  }
  catch (error) {
    errors.value = toMessages(error)
  }
  finally {
    isCancelling.value = false
  }
}
</script>

<template>
  <section class="card">
    <ActionNotice :message="notice" />

    <div v-if="errors.length" class="alert alert--ko">
      <ul><li v-for="message in errors" :key="message">{{ message }}</li></ul>
    </div>

    <ReservationFilters v-model="filters" :options="options" :has-filters="hasFilters" @reset="resetFilters" />

    <div class="planning-toolbar">
      <div class="planning-toolbar__group" role="group" aria-label="Affichage">
        <button type="button" class="button button--small" :class="{ 'button--ghost': view !== 'timeline' }" @click="view = 'timeline'">Frise</button>
        <button type="button" class="button button--small" :class="{ 'button--ghost': view !== 'list' }" @click="view = 'list'">Liste</button>
      </div>
      <template v-if="view === 'timeline'">
        <div class="planning-toolbar__group">
          <button type="button" class="button button--ghost button--small" @click="move(-1)">‹ {{ duration.previous }}</button>
          <button type="button" class="button button--ghost button--small" @click="rangeStart = today">Aujourd'hui</button>
          <button type="button" class="button button--ghost button--small" @click="move(1)">{{ duration.next }} ›</button>
        </div>
        <span class="planning-toolbar__range">{{ rangeLabel }}</span>
        <select v-model.number="days" class="planning-toolbar__duration" aria-label="Durée affichée">
          <option v-for="option in durations" :key="option.days" :value="option.days">{{ option.label }}</option>
        </select>
      </template>
    </div>

    <LoadError v-if="hasError" @retry="reload()" />
    <LoadingMessage v-if="isFirstLoading" label="Chargement du planning…" />

    <div v-if="hasLoaded" class="planning-body" :class="{ 'planning-body--with-detail': hasDetail }">
      <div class="planning-main">
        <PlanningTimeline
          v-if="view === 'timeline'"
          :machines="timelineMachines"
          :reservations="visibleReservations"
          :anomalies-by-reservation="anomaliesByReservation"
          :range-start="rangeStart"
          :days="days"
          :today="today"
          :selected-id="selectedId"
          :selected-workshop-id="selectedWorkshop?.periodId ?? null"
          @select="select"
          @select-workshop="selectWorkshop"
        />

        <div v-else class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Machine</th>
                <th>Agence de la machine</th>
                <th>Client</th>
                <th>Du</th>
                <th>Au</th>
                <th>Saisie par</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="reservation in visibleReservations"
                :key="reservation.id"
                class="planning-list__row"
                :class="{ 'planning-list__row--selected': reservation.id === selectedId }"
                @click="select(reservation)"
              >
                <td>
                  <strong>{{ reservation.machine_ref }}</strong> <span class="muted">{{ reservation.machine_type }}</span>
                  <div v-if="anomaliesByReservation.has(reservation.id)" class="planning-anomaly">
                    ⚠ Attention : anomalie ({{ [...anomaliesByReservation.get(reservation.id)!].join(', ') }})
                  </div>
                </td>
                <td>{{ reservation.machine_agency }}</td>
                <td>{{ reservation.client }}<div v-if="reservation.purchase_order" class="muted">BC {{ reservation.purchase_order }}</div></td>
                <td>{{ formatDate(reservation.starts_at) }}</td>
                <td>{{ formatDate(reservation.ends_at) }}</td>
                <td>{{ reservation.entered_by }}</td>
              </tr>
              <tr v-if="!visibleReservations.length">
                <td colspan="6" class="muted">Aucune réservation ne correspond aux filtres.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <ReservationDetail
        v-if="selected"
        :reservation="selected"
        :anomalies="selectedAnomalies"
        :can-cancel="selected.can_cancel"
        :cancel-blocked-reason="selected.cancel_denied_reason"
        :can-edit="selected.can_modify"
        :is-cancelling="isCancelling"
        :is-saving="isSaving"
        :edit-errors="editErrors"
        @close="closeDetail"
        @cancel="cancelSelected"
        @save="saveSelected"
      />

      <WorkshopPeriodDetail
        v-else-if="selectedWorkshopMachine && selectedWorkshopPeriod"
        :machine="selectedWorkshopMachine"
        :period="selectedWorkshopPeriod"
        :is-ending="isCancelling"
        @close="closeDetail"
        @end="endSelectedWorkshop"
      />
    </div>
  </section>
</template>

<style scoped>
.planning-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.planning-toolbar__group {
  display: flex;
  gap: 4px;
}

.planning-toolbar__range {
  font-weight: 600;
}

.planning-toolbar__duration {
  margin-left: auto;
}

.planning-body {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 16px;
}

.planning-body--with-detail {
  grid-template-columns: minmax(0, 1fr) 300px;
}

@media (max-width: 900px) {
  .planning-body--with-detail {
    grid-template-columns: minmax(0, 1fr);
  }
}

.planning-list__row {
  cursor: pointer;
}

.planning-list__row:hover {
  background: var(--color-bg);
}

.planning-list__row--selected {
  background: #fff7ed;
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
