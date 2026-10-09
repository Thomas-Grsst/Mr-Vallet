<script setup lang="ts">
import type { MachineTimeline, Occupation } from '~/types/vallet'

const props = defineProps<{ timeline: MachineTimeline }>()

const { formatDate } = useFormatDate()

const describeOccupation = (occupation: Occupation) => {
  if (occupation.kind === 'vgp') {
    return occupation.starts_at ? `VGP échue à partir du ${formatDate(occupation.starts_at)}` : 'Aucune VGP enregistrée'
  }

  const prefix = occupation.kind === 'reservation' ? 'Réservée' : 'En atelier'
  const label = occupation.label ? ` · ${occupation.label}` : ''

  return `${prefix} du ${formatDate(occupation.starts_at)} au ${formatDate(occupation.ends_at)}${label}`
}

const items = computed(() => [
  ...props.timeline.occupations.map((occupation) => ({
    key: `${occupation.kind}-${occupation.starts_at}`,
    sortDate: occupation.starts_at ?? '',
    text: describeOccupation(occupation),
    free: false,
  })),
  ...props.timeline.free_windows.map((window) => ({
    key: `free-${window.starts_at}`,
    sortDate: window.starts_at,
    text: window.ends_at
      ? `Libre du ${formatDate(window.starts_at)} au ${formatDate(window.ends_at)}`
      : `Libre à partir du ${formatDate(window.starts_at)}`,
    free: true,
  })),
].sort((first, second) => first.sortDate.localeCompare(second.sortDate)))
</script>

<template>
  <div class="timeline">
    <span class="timeline__title">À venir</span>
    <ul class="timeline__items">
      <li
        v-for="item in items"
        :key="item.key"
        class="timeline__item"
        :class="item.free ? 'timeline__item--free' : 'timeline__item--busy'"
      >
        {{ item.text }}
      </li>
    </ul>
  </div>
</template>

<style scoped>
.timeline {
  margin-top: 8px;
}

.timeline__title {
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  color: var(--color-muted);
}

.timeline__items {
  list-style: none;
  margin: 4px 0 0;
  padding: 0;
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.timeline__item {
  padding: 2px 8px;
  border-radius: 6px;
  font-size: 0.8rem;
  border: 1px solid;
}

.timeline__item--free {
  background: var(--color-ok-bg);
  border-color: #bbf7d0;
  color: var(--color-ok);
}

.timeline__item--busy {
  background: #fff;
  border-color: var(--color-border);
  color: var(--color-text);
}
</style>
