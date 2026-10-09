<script setup lang="ts">
import type { Machine, WorkshopPeriod } from '~/types/vallet'

type PeriodDraft = { startsAt: string, endsAt: string, reason: string }

type StoreResponse = { impacted_reservations: string[] }

const today = useRuntimeConfig().public.today
const { $api } = useNuxtApp()
const { formatDate } = useFormatDate()
const { toMessages } = useApiErrors()

const errors = ref<string[]>([])
const notice = ref<{ machine: string, impacted: string[] } | null>(null)
const drafts = ref<Record<string, PeriodDraft>>({})
const vgpDate = ref<Record<string, string>>({})
const savingRef = ref<string | null>(null)

const { data: machines, status, refresh } = useApiFetch<Machine[]>('/api/machines', { default: () => [] })

const nameFilter = ref('')
const typeFilter = ref('')

const machineTypes = computed(() => [...new Set(machines.value.map((machine) => machine.type))].sort((first, second) => first.localeCompare(second, 'fr')))

const visibleMachines = computed(() => {
  const name = nameFilter.value.trim().toLowerCase()

  return machines.value.filter((machine) =>
    (!name || machine.ref.toLowerCase().includes(name))
    && (!typeFilter.value || machine.type === typeFilter.value),
  )
})

const emptyDraft = (): PeriodDraft => ({ startsAt: today, endsAt: today, reason: '' })

watch(machines, (list) => {
  for (const machine of list) {
    drafts.value[machine.ref] ??= emptyDraft()
  }
}, { immediate: true })

const run = async (machineRef: string, action: () => Promise<void>) => {
  errors.value = []
  notice.value = null
  savingRef.value = machineRef
  try {
    await action()
    await refresh()
  }
  catch (error) {
    errors.value = toMessages(error)
  }
  finally {
    savingRef.value = null
  }
}

const planPeriod = (machine: Machine) => run(machine.ref, async () => {
  const draft = drafts.value[machine.ref]!
  const response = await $api<StoreResponse>(`/api/machines/${machine.ref}/workshop-periods`, {
    method: 'POST',
    body: { starts_at: draft.startsAt, ends_at: draft.endsAt, reason: draft.reason || null },
  })
  drafts.value[machine.ref] = emptyDraft()
  notice.value = { machine: machine.ref, impacted: response.impacted_reservations }
})

const endPeriod = (machine: Machine, period: WorkshopPeriod) => {
  const question = period.status === 'current'
    ? `Remettre ${machine.ref} en service dès aujourd'hui ?`
    : `Annuler le passage en atelier de ${machine.ref} du ${formatDate(period.starts_at)} au ${formatDate(period.ends_at)} ?`

  if (!confirm(question)) {
    return
  }

  return run(machine.ref, () => $api(`/api/workshop-periods/${period.id}`, { method: 'DELETE' }))
}

const recordVgp = (machine: Machine) => run(machine.ref, () => $api(`/api/machines/${machine.ref}/vgp`, {
  method: 'PATCH',
  body: { last_vgp_at: vgpDate.value[machine.ref] || today },
}))
</script>

<template>
  <section class="card table-wrapper">
    <div v-if="errors.length" class="alert alert--ko">
      <ul><li v-for="message in errors" :key="message">{{ message }}</li></ul>
    </div>
    <div v-if="notice" :class="notice.impacted.length ? 'alert alert--ko' : 'alert alert--ok'">
      <template v-if="notice.impacted.length">
        <strong>Passage en atelier enregistré pour {{ notice.machine }}. Réservation(s) concernée(s), à traiter avec les agences :</strong>
        <ul><li v-for="line in notice.impacted" :key="line">{{ line }}</li></ul>
      </template>
      <template v-else>Passage en atelier enregistré pour {{ notice.machine }}. Aucune réservation concernée.</template>
    </div>
    <div class="form-row workshop-filters">
      <label>
        Nom de la machine
        <input v-model="nameFilter" type="search" placeholder="ex. NAC112">
      </label>
      <label>
        Type de machine
        <select v-model="typeFilter">
          <option value="">Tous les types</option>
          <option v-for="machineType in machineTypes" :key="machineType" :value="machineType">{{ machineType }}</option>
        </select>
      </label>
    </div>
    <LoadError v-if="status === 'error'" @retry="refresh()" />
    <LoadingMessage v-else-if="status !== 'success'" label="Chargement des machines…" />
    <table v-else>
      <thead>
        <tr>
          <th>Machine</th>
          <th>Agence</th>
          <th>Passages en atelier</th>
          <th>VGP</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="machine in visibleMachines" :key="machine.ref">
          <td><strong>{{ machine.ref }}</strong><br><span class="muted">{{ machine.type }}</span></td>
          <td>{{ machine.agency }}</td>
          <td>
            <ul v-if="machine.workshop_periods.length" class="workshop-periods">
              <li v-for="period in machine.workshop_periods" :key="period.id">
                <span :class="period.status === 'current' ? 'badge badge--ko' : 'badge workshop-planned'">
                  {{ period.status === 'current' ? 'En atelier' : 'Prévu' }} du {{ formatDate(period.starts_at) }} au {{ formatDate(period.ends_at) }}
                </span>
                <span v-if="period.reason" class="muted"> {{ period.reason }}</span>
                <button
                  type="button"
                  class="button button--ghost workshop-action"
                  :disabled="savingRef === machine.ref"
                  @click="endPeriod(machine, period)"
                >
                  {{ period.status === 'current' ? 'Remettre en service' : 'Annuler ce passage' }}
                </button>
              </li>
            </ul>
            <div v-if="drafts[machine.ref]" class="form-row">
              <label>
                Début
                <input v-model="drafts[machine.ref].startsAt" type="date" :min="today">
              </label>
              <label>
                Fin
                <input v-model="drafts[machine.ref].endsAt" type="date" :min="drafts[machine.ref].startsAt">
              </label>
              <label>
                Motif
                <input v-model="drafts[machine.ref].reason" type="text" placeholder="ex. VGP, vérin cassé">
              </label>
              <button type="button" class="button button--ghost" :disabled="savingRef === machine.ref" @click="planPeriod(machine)">
                {{ savingRef === machine.ref ? 'Enregistrement…' : 'Prévoir le passage en atelier' }}
              </button>
            </div>
          </td>
          <td>
            <template v-if="machine.requires_vgp">
              <span :class="machine.vgp_ok_today ? 'badge badge--ok' : 'badge badge--ko'">
                <strong>{{ machine.vgp_ok_today ? 'VGP à jour' : 'VGP en retard' }}</strong>
                · {{ machine.last_vgp_at ? `dernière VGP ${formatDate(machine.last_vgp_at)}, valable jusqu'au ${formatDate(machine.vgp_expires_at)}` : 'aucune VGP enregistrée' }}
              </span>
              <div class="form-row">
                <label>
                  VGP réalisée le
                  <input v-model="vgpDate[machine.ref]" type="date" :max="today">
                </label>
                <button type="button" class="button button--ghost" :disabled="savingRef === machine.ref" @click="recordVgp(machine)">Enregistrer la VGP</button>
              </div>
            </template>
            <span v-else class="muted">Non soumise</span>
          </td>
        </tr>
        <tr v-if="!visibleMachines.length">
          <td colspan="4" class="muted">Aucune machine ne correspond.</td>
        </tr>
      </tbody>
    </table>
  </section>
</template>

<style scoped>
.workshop-filters {
  margin-bottom: 12px;
}

.workshop-periods {
  list-style: none;
  margin: 0 0 8px;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.workshop-planned {
  background: #dbeafe;
  color: #1d4ed8;
}

.workshop-action {
  margin-left: 8px;
  padding: 2px 8px;
  font-size: 0.85rem;
}
</style>
