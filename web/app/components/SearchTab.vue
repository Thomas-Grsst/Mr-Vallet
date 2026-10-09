<script setup lang="ts">
import type { Machine, Reservation } from '~/types/vallet'

const today = useRuntimeConfig().public.today
const { $api } = useNuxtApp()
const { user } = useAuth()
const { formatDate } = useFormatDate()
const { toMessages } = useApiErrors()

const type = ref('')
const from = ref(today)
const to = ref(today)

const machines = ref<Machine[]>([])
const hasSearched = ref(false)
const searchErrors = ref<string[]>([])

const selectedMachine = ref<Machine | null>(null)
const client = ref('')
const reservationErrors = ref<string[]>([])
const confirmation = ref<string | null>(null)
const isSubmitting = ref(false)

const { data: types } = await useApiFetch<string[]>('/api/machine-types', { default: () => [] })

const search = async () => {
  searchErrors.value = []
  try {
    machines.value = await $api<Machine[]>('/api/machines', {
      query: { type: type.value || undefined, from: from.value, to: to.value },
    })
    hasSearched.value = true
  }
  catch (error) {
    searchErrors.value = toMessages(error)
  }
}

const selectMachine = (machine: Machine) => {
  selectedMachine.value = machine
  reservationErrors.value = []
  confirmation.value = null
}

const reserve = async () => {
  if (!selectedMachine.value) {
    return
  }

  reservationErrors.value = []
  confirmation.value = null

  isSubmitting.value = true
  try {
    const reservation = await $api<Reservation>('/api/reservations', {
      method: 'POST',
      body: {
        machine_ref: selectedMachine.value.ref,
        client: client.value,
        starts_at: from.value,
        ends_at: to.value,
      },
    })
    confirmation.value = `Réservation enregistrée : ${reservation.machine_ref} (${reservation.machine_agency}) pour ${reservation.client} du ${formatDate(reservation.starts_at)} au ${formatDate(reservation.ends_at)}.`
    selectedMachine.value = null
    client.value = ''
    await search()
  }
  catch (error) {
    reservationErrors.value = toMessages(error)
  }
  finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section>
    <form class="card form-row" @submit.prevent="search">
      <label>
        Type de machine
        <select v-model="type">
          <option value="">Tous les types</option>
          <option v-for="machineType in types" :key="machineType" :value="machineType">{{ machineType }}</option>
        </select>
      </label>
      <label>
        Du
        <input v-model="from" type="date" required>
      </label>
      <label>
        Au
        <input v-model="to" type="date" required>
      </label>
      <button class="button" type="submit">Rechercher dans les 7 agences</button>
    </form>

    <div v-if="searchErrors.length" class="alert alert--ko">
      <ul><li v-for="message in searchErrors" :key="message">{{ message }}</li></ul>
    </div>

    <div v-if="confirmation" class="alert alert--ok">{{ confirmation }}</div>

    <div v-if="hasSearched" class="card table-wrapper">
      <p class="muted">Du {{ formatDate(from) }} au {{ formatDate(to) }} · {{ machines.filter((machine) => machine.available).length }} disponible(s) sur {{ machines.length }}</p>
      <table>
        <thead>
          <tr>
            <th>Machine</th>
            <th>Type</th>
            <th>Agence</th>
            <th>Disponibilité</th>
            <th v-if="user?.can_book" />
          </tr>
        </thead>
        <tbody>
          <tr v-for="machine in machines" :key="machine.ref">
            <td><strong>{{ machine.ref }}</strong></td>
            <td>{{ machine.type }}</td>
            <td>{{ machine.agency }}</td>
            <td>
              <span v-if="machine.available" class="badge badge--ok">Disponible</span>
              <template v-else>
                <span class="badge badge--ko">Indisponible</span>
                <ul class="muted">
                  <li v-for="reason in machine.reasons" :key="reason">{{ reason }}</li>
                </ul>
              </template>
            </td>
            <td v-if="user?.can_book">
              <button
                type="button"
                class="button"
                :disabled="!machine.available"
                :title="machine.available ? undefined : 'Machine indisponible sur cette période'"
                @click="selectMachine(machine)"
              >
                Réserver
              </button>
            </td>
          </tr>
          <tr v-if="!machines.length">
            <td colspan="5" class="muted">Aucune machine de ce type.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <form v-if="selectedMachine" class="card" @submit.prevent="reserve">
      <h2>Réserver {{ selectedMachine.ref }} ({{ selectedMachine.agency }}) du {{ formatDate(from) }} au {{ formatDate(to) }}</h2>
      <p class="muted">Saisie par l'agence {{ user?.agency }}</p>
      <div class="form-row">
        <label>
          Client
          <input v-model="client" type="text" placeholder="Nom du client">
        </label>
        <button class="button" type="submit" :disabled="isSubmitting">Confirmer la réservation</button>
        <button class="button button--ghost" type="button" @click="selectedMachine = null">Annuler</button>
      </div>
      <div v-if="reservationErrors.length" class="alert alert--ko">
        <strong>Réservation refusée</strong>
        <ul><li v-for="message in reservationErrors" :key="message">{{ message }}</li></ul>
      </div>
    </form>
  </section>
</template>
