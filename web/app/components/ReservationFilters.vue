<script setup lang="ts">
type Filters = {
  machine: string
  machineAgency: string
  client: string
  enteredBy: string
  from: string
  to: string
}

type Options = {
  machineRefs: string[]
  machineAgencies: string[]
  clients: string[]
  enteringAgencies: string[]
}

defineProps<{ options: Options, hasFilters: boolean, machineSuggestions?: string[] }>()

const suggestionsId = useId()
defineEmits<{ reset: [] }>()

const filters = defineModel<Filters>({ required: true })
</script>

<template>
  <div class="filter-bar">
    <label>
      Nom de la machine
      <input v-model="filters.machine" type="search" placeholder="ex. NAC112" :list="suggestionsId" autocomplete="off">
      <datalist :id="suggestionsId">
        <option v-for="machineRef in machineSuggestions ?? options.machineRefs" :key="machineRef" :value="machineRef" />
      </datalist>
    </label>
    <label>
      Agence de la machine
      <select v-model="filters.machineAgency">
        <option value="">Toutes les agences</option>
        <option v-for="agency in options.machineAgencies" :key="agency" :value="agency">{{ agency }}</option>
      </select>
    </label>
    <label>
      Client
      <select v-model="filters.client">
        <option value="">Tous les clients</option>
        <option v-for="client in options.clients" :key="client" :value="client">{{ client }}</option>
      </select>
    </label>
    <label>
      Du
      <input v-model="filters.from" type="date">
    </label>
    <label>
      Au
      <input v-model="filters.to" type="date" :min="filters.from || undefined">
    </label>
    <label>
      Saisie par
      <select v-model="filters.enteredBy">
        <option value="">Toutes les agences</option>
        <option v-for="agency in options.enteringAgencies" :key="agency" :value="agency">{{ agency }}</option>
      </select>
    </label>
    <slot />
    <button type="button" class="button button--ghost" :disabled="!hasFilters" @click="$emit('reset')">Réinitialiser les filtres</button>
  </div>
</template>

