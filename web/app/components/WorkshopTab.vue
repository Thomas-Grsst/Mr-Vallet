<script setup lang="ts">
import type { Machine } from '~/types/vallet'

const today = useRuntimeConfig().public.today
const { formatDate } = useFormatDate()
const { toMessages } = useApiErrors()

const errors = ref<string[]>([])
const workshopUntil = ref<Record<string, string>>({})
const workshopNote = ref<Record<string, string>>({})
const vgpDate = ref<Record<string, string>>({})

const { data: machines, refresh } = await useFetch<Machine[]>('/api/machines', { default: () => [] })

const save = async (url: string, body: Record<string, string | null>) => {
  errors.value = []
  try {
    await $fetch(url, { method: 'PATCH', body })
    await refresh()
  }
  catch (error) {
    errors.value = toMessages(error)
  }
}

const putInWorkshop = (machine: Machine) => save(`/api/machines/${machine.ref}/workshop`, {
  until: workshopUntil.value[machine.ref] || null,
  note: workshopNote.value[machine.ref] || null,
})

const backInService = (machine: Machine) => save(`/api/machines/${machine.ref}/workshop`, { until: null })

const recordVgp = (machine: Machine) => save(`/api/machines/${machine.ref}/vgp`, {
  last_vgp_at: vgpDate.value[machine.ref] || today,
})
</script>

<template>
  <section class="card table-wrapper">
    <div v-if="errors.length" class="alert alert--ko">
      <ul><li v-for="message in errors" :key="message">{{ message }}</li></ul>
    </div>
    <table>
      <thead>
        <tr>
          <th>Machine</th>
          <th>Agence</th>
          <th>Atelier</th>
          <th>VGP</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="machine in machines" :key="machine.ref">
          <td><strong>{{ machine.ref }}</strong><br><span class="muted">{{ machine.type }}</span></td>
          <td>{{ machine.agency }}</td>
          <td>
            <template v-if="machine.workshop_until">
              <span class="badge badge--ko">En atelier jusqu'au {{ formatDate(machine.workshop_until) }}</span>
              <span v-if="machine.workshop_note" class="muted"> {{ machine.workshop_note }}</span>
              <br>
              <button type="button" class="button button--ghost" @click="backInService(machine)">Remettre en service</button>
            </template>
            <div v-else class="form-row">
              <input v-model="workshopUntil[machine.ref]" type="date" :min="today" aria-label="En atelier jusqu'au">
              <input v-model="workshopNote[machine.ref]" type="text" placeholder="Motif" aria-label="Motif">
              <button type="button" class="button button--ghost" :disabled="!workshopUntil[machine.ref]" @click="putInWorkshop(machine)">Passer en atelier</button>
            </div>
          </td>
          <td>
            <template v-if="machine.requires_vgp">
              <span :class="machine.vgp_ok_today ? 'badge badge--ok' : 'badge badge--ko'">
                {{ machine.last_vgp_at ? `Dernière VGP ${formatDate(machine.last_vgp_at)}, valable jusqu'au ${formatDate(machine.vgp_expires_at)}` : 'Aucune VGP' }}
              </span>
              <div class="form-row">
                <input v-model="vgpDate[machine.ref]" type="date" :max="today" aria-label="Date de la nouvelle VGP">
                <button type="button" class="button button--ghost" @click="recordVgp(machine)">Enregistrer une VGP</button>
              </div>
            </template>
            <span v-else class="muted">Non soumise</span>
          </td>
        </tr>
      </tbody>
    </table>
  </section>
</template>
