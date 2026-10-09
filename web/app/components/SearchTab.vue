<script setup lang="ts">
import type { Agency, KeyAccount, Machine, Reservation } from '~/types/vallet'

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
const reservationForm = ref<HTMLFormElement | null>(null)
const reservationFrom = ref(today)
const reservationTo = ref(today)
const client = ref('')
const enteringAgencyId = ref<number | null>(null)
const purchaseOrder = ref('')
const purchaseOrderRequiredByServer = ref(false)

const { data: keyAccounts, refresh: refreshKeyAccounts } = useApiFetch<KeyAccount[]>('/api/key-accounts', {
  default: () => [],
  immediate: !!user.value?.can_book,
})

const { data: knownClients, refresh: refreshKnownClients } = useApiFetch<string[]>('/api/clients', {
  default: () => [],
  immediate: !!user.value?.can_book,
})

const keyAccountNames = computed(() => keyAccounts.value.map((account) => account.name))

const keyAccount = computed(() => {
  const typed = client.value.trim().toLowerCase()
  return keyAccountNames.value.find((name) => name.toLowerCase() === typed) ?? null
})

const clientSuggestions = computed(() => {
  const keyNames = new Set(keyAccountNames.value.map((name) => name.toLowerCase()))
  const others = knownClients.value.filter((name) => !keyNames.has(name.toLowerCase()))

  return [
    ...keyAccountNames.value.map((name) => ({ name, isKeyAccount: true })),
    ...others.map((name) => ({ name, isKeyAccount: false })),
  ].sort((first, second) => first.name.localeCompare(second.name, 'fr'))
})

const { data: agencies } = useApiFetch<Agency[]>('/api/agencies', {
  default: () => [],
  immediate: !!user.value?.chooses_entering_agency,
})
const reservationErrors = ref<string[]>([])
const confirmation = ref<string | null>(null)
const isSubmitting = ref(false)

const { data: types, status: typesStatus, refresh: refreshTypes } = useApiFetch<string[]>('/api/machine-types', { default: () => [] })

const isSearching = ref(false)
const searchFailed = ref(false)

const isValidationError = (error: unknown) => (error as { statusCode?: number })?.statusCode === 422

const search = async () => {
  searchErrors.value = []
  searchFailed.value = false
  isSearching.value = true
  try {
    machines.value = await $api<Machine[]>('/api/machines', {
      query: { type: type.value || undefined, from: from.value, to: to.value },
    })
    hasSearched.value = true
  }
  catch (error) {
    if (isValidationError(error)) {
      searchErrors.value = toMessages(error)
    }
    else {
      searchFailed.value = true
    }
  }
  finally {
    isSearching.value = false
  }
}

onMounted(search)

const selectMachine = async (machine: Machine) => {
  selectedMachine.value = machine
  reservationFrom.value = from.value
  reservationTo.value = to.value
  reservationErrors.value = []
  confirmation.value = null
  purchaseOrderRequiredByServer.value = false
  refreshKeyAccounts()
  await nextTick()
  reservationForm.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
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
        starts_at: reservationFrom.value,
        ends_at: reservationTo.value,
        agency_id: user.value?.chooses_entering_agency ? enteringAgencyId.value : undefined,
        purchase_order: purchaseOrder.value || null,
      },
    })
    confirmation.value = `Réservation enregistrée : ${reservation.machine_ref} (${reservation.machine_agency}) pour ${reservation.client} du ${formatDate(reservation.starts_at)} au ${formatDate(reservation.ends_at)}.`
    selectedMachine.value = null
    client.value = ''
    purchaseOrder.value = ''
    purchaseOrderRequiredByServer.value = false
    refreshKnownClients()
    await search()
  }
  catch (error) {
    const violationCodes = ((error as { data?: { violations?: { code: string }[] } })?.data?.violations ?? []).map((violation) => violation.code)
    purchaseOrderRequiredByServer.value = violationCodes.includes('missing_purchase_order')
    reservationErrors.value = isValidationError(error)
      ? toMessages(error)
      : ['Le serveur ne répond pas : la réservation n\'a pas été enregistrée. Réessayez.']
  }
  finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section>
    <form class="filter-bar" @submit.prevent="search">
      <label>
        Type de machine
        <select v-model="type" :disabled="typesStatus !== 'success'">
          <option value="">{{ typesStatus === 'success' ? 'Tous les types' : 'Chargement des types…' }}</option>
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
      <button class="button" type="submit" :disabled="isSearching">{{ isSearching ? 'Recherche…' : 'Rechercher dans les 7 agences' }}</button>
    </form>

    <LoadError v-if="typesStatus === 'error'" @retry="refreshTypes()" />

    <div v-if="searchErrors.length" class="alert alert--ko">
      <ul><li v-for="message in searchErrors" :key="message">{{ message }}</li></ul>
    </div>

    <div v-if="confirmation" class="alert alert--ok">{{ confirmation }}</div>

    <LoadError v-if="searchFailed" @retry="search()" />

    <div v-if="isSearching && !hasSearched" class="card">
      <LoadingMessage label="Recherche dans les 7 agences…" />
    </div>

    <div v-else-if="hasSearched && !searchFailed" class="card table-wrapper" :class="{ 'search-results--refreshing': isSearching }">
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
                <ul class="reasons">
                  <li v-for="reason in machine.reasons" :key="reason">{{ reason }}</li>
                </ul>
              </template>
              <MachineTimeline v-if="machine.timeline" :timeline="machine.timeline" />
            </td>
            <td v-if="user?.can_book" class="cell-action">
              <button
                type="button"
                :class="machine.available ? 'button' : 'button button--ghost'"
                @click="selectMachine(machine)"
              >
                {{ machine.available ? 'Réserver' : 'Réserver à d\'autres dates' }}
              </button>
            </td>
          </tr>
          <tr v-if="!machines.length">
            <td colspan="5" class="muted">Aucune machine de ce type.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <form v-if="selectedMachine" ref="reservationForm" class="card" @submit.prevent="reserve">
      <h2>Réserver {{ selectedMachine.ref }} ({{ selectedMachine.type }}, {{ selectedMachine.agency }})</h2>
      <p v-if="!user?.chooses_entering_agency" class="muted">Saisie par l'agence {{ user?.agency }}</p>
      <div class="form-row">
        <label>
          Du
          <input v-model="reservationFrom" type="date" :min="today" required>
        </label>
        <label>
          Au
          <input v-model="reservationTo" type="date" :min="reservationFrom" required>
        </label>
        <label>
          Client
          <input v-model="client" type="text" placeholder="Nom du client" list="client-suggestions" autocomplete="off">
          <datalist id="client-suggestions">
            <option v-for="suggestion in clientSuggestions" :key="suggestion.name" :value="suggestion.name">
              {{ suggestion.isKeyAccount ? `${suggestion.name} (grand compte)` : suggestion.name }}
            </option>
          </datalist>
        </label>
        <label v-if="keyAccount || purchaseOrderRequiredByServer">
          N° de bon de commande
          <input v-model="purchaseOrder" type="text" placeholder="ex. BC-2026-0412">
          <small class="muted">{{ keyAccount ?? client.trim() }} est un grand compte : obligatoire</small>
        </label>
        <label v-if="user?.chooses_entering_agency">
          Agence de saisie
          <select v-model="enteringAgencyId">
            <option :value="null" disabled>Choisir l'agence</option>
            <option v-for="agency in agencies" :key="agency.id" :value="agency.id">{{ agency.name }}</option>
          </select>
        </label>
        <button class="button" type="submit" :disabled="isSubmitting">{{ isSubmitting ? 'Enregistrement…' : 'Confirmer la réservation' }}</button>
        <button class="button button--ghost" type="button" @click="selectedMachine = null">Annuler</button>
      </div>
      <div v-if="reservationErrors.length" class="alert alert--ko">
        <strong>Réservation refusée</strong>
        <ul><li v-for="message in reservationErrors" :key="message">{{ message }}</li></ul>
      </div>
    </form>
  </section>
</template>
