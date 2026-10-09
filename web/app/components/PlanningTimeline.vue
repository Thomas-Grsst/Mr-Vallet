<script setup lang="ts">
import type { Machine, Reservation, WorkshopPeriod } from '~/types/vallet'

type Bar = {
  key: string
  kind: 'reservation' | 'workshop' | 'vgp'
  label: string
  title: string
  column: string
  row: number
  hasAnomaly: boolean
  reservation: Reservation | null
  workshopPeriod: WorkshopPeriod | null
  continuesBefore: boolean
  continuesAfter: boolean
}

const props = defineProps<{
  machines: Machine[]
  reservations: Reservation[]
  anomaliesByReservation: Map<number, Set<string>>
  rangeStart: string
  days: number
  today: string
  selectedId: number | null
  selectedWorkshopId: number | null
}>()

const emit = defineEmits<{ select: [reservation: Reservation], selectWorkshop: [machine: Machine, period: WorkshopPeriod] }>()

const { addDays, daysBetween, weekday } = useIsoDate()
const { formatDate } = useFormatDate()

const WEEKDAYS = ['dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam']

const dayColumns = computed(() => Array.from({ length: props.days }, (_, index) => {
  const date = addDays(props.rangeStart, index)
  const dayOfWeek = weekday(date)

  return {
    date,
    label: `${WEEKDAYS[dayOfWeek]} ${date.slice(8, 10)}`,
    isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
    isToday: date === props.today,
  }
}))

const rangeEnd = computed(() => addDays(props.rangeStart, props.days - 1))

const gridColumns = computed(() => `110px repeat(${props.days}, minmax(0, 1fr))`)

const placement = (startsAt: string, endsAt: string | null) => {
  const end = endsAt ?? rangeEnd.value

  if (end < props.rangeStart || startsAt > rangeEnd.value) {
    return null
  }

  const first = Math.max(0, daysBetween(props.rangeStart, startsAt))
  const last = Math.min(props.days - 1, daysBetween(props.rangeStart, end))

  return {
    column: `${first + 2} / ${last + 3}`,
    continuesBefore: startsAt < props.rangeStart,
    continuesAfter: end > rangeEnd.value,
  }
}

const rows = computed(() => [...props.machines].sort((first, second) => first.ref.localeCompare(second.ref)).map((machine) => {
  const background: Bar[] = []

  for (const period of machine.workshop_periods) {
    const place = placement(period.starts_at, period.ends_at)

    if (place) {
      background.push({
        key: `workshop-${period.id}`,
        kind: 'workshop',
        label: `Atelier${period.reason ? ` · ${period.reason}` : ''}`,
        title: `En atelier du ${formatDate(period.starts_at)} au ${formatDate(period.ends_at)}${period.reason ? ` (${period.reason})` : ''}`,
        row: 1,
        hasAnomaly: false,
        reservation: null,
        workshopPeriod: period,
        ...place,
      })
    }
  }

  if (machine.requires_vgp) {
    const place = placement(machine.vgp_expires_at ?? props.rangeStart, null)

    if (place) {
      background.push({
        key: 'vgp',
        kind: 'vgp',
        label: machine.vgp_expires_at ? 'VGP échue' : 'Aucune VGP',
        title: machine.vgp_expires_at ? `VGP échue à partir du ${formatDate(machine.vgp_expires_at)}` : 'Aucune VGP enregistrée',
        row: 1,
        hasAnomaly: false,
        reservation: null,
        workshopPeriod: null,
        ...place,
      })
    }
  }

  const laneEnds: string[] = []
  const firstReservationRow = background.length ? 2 : 1
  const reservationBars: Bar[] = []

  const machineReservations = props.reservations
    .filter((reservation) => reservation.machine_ref === machine.ref)
    .sort((first, second) => first.starts_at.localeCompare(second.starts_at))

  for (const reservation of machineReservations) {
    const place = placement(reservation.starts_at, reservation.ends_at)

    if (!place) {
      continue
    }

    let lane = laneEnds.findIndex((end) => end < reservation.starts_at)
    if (lane === -1) {
      lane = laneEnds.length
      laneEnds.push(reservation.ends_at)
    }
    else {
      laneEnds[lane] = reservation.ends_at
    }

    const anomalies = props.anomaliesByReservation.get(reservation.id)

    reservationBars.push({
      key: `reservation-${reservation.id}`,
      kind: 'reservation',
      label: `${anomalies ? '⚠ ' : ''}${reservation.client}`,
      title: `${reservation.client} · du ${formatDate(reservation.starts_at)} au ${formatDate(reservation.ends_at)}${anomalies ? ` · ${[...anomalies].join(', ')}` : ''}`,
      row: firstReservationRow + lane,
      hasAnomaly: !!anomalies,
      reservation,
      workshopPeriod: null,
      ...place,
    })
  }

  return {
    machine,
    bars: [...background, ...reservationBars],
    rowCount: Math.max(1, firstReservationRow - 1 + laneEnds.length),
  }
}))
</script>

<template>
  <div class="timeline-scroll">
    <div class="timeline">
      <div class="timeline__header" :style="{ gridTemplateColumns: gridColumns }">
        <div class="timeline__corner">Machine</div>
        <div
          v-for="day in dayColumns"
          :key="day.date"
          class="timeline__day"
          :class="{ 'timeline__day--weekend': day.isWeekend, 'timeline__day--today': day.isToday }"
        >
          {{ day.label }}
        </div>
      </div>

      <div
        v-for="row in rows"
        :key="row.machine.ref"
        class="timeline__row"
        :style="{ gridTemplateColumns: gridColumns, gridTemplateRows: `repeat(${row.rowCount}, 28px)` }"
      >
        <div class="timeline__machine" :style="{ gridRow: `1 / ${row.rowCount + 1}` }">
          <strong>{{ row.machine.ref }}</strong>
          <span class="muted">{{ row.machine.agency }}</span>
        </div>
        <div
          v-for="(day, index) in dayColumns"
          :key="day.date"
          class="timeline__cell"
          :class="{ 'timeline__cell--weekend': day.isWeekend, 'timeline__cell--today': day.isToday }"
          :style="{ gridColumn: `${index + 2}`, gridRow: `1 / ${row.rowCount + 1}` }"
        />
        <component
          :is="bar.reservation || bar.workshopPeriod ? 'button' : 'div'"
          v-for="bar in row.bars"
          :key="bar.key"
          :type="bar.reservation || bar.workshopPeriod ? 'button' : undefined"
          class="timeline__bar"
          :class="[
            `timeline__bar--${bar.kind}`,
            {
              'timeline__bar--anomaly': bar.hasAnomaly,
              'timeline__bar--selected': (bar.reservation && bar.reservation.id === selectedId)
                || (bar.workshopPeriod && bar.workshopPeriod.id === selectedWorkshopId),
              'timeline__bar--cut-before': bar.continuesBefore,
              'timeline__bar--cut-after': bar.continuesAfter,
            },
          ]"
          :style="{ gridColumn: bar.column, gridRow: `${bar.row}` }"
          :title="bar.title"
          @click="bar.reservation ? emit('select', bar.reservation) : bar.workshopPeriod && emit('selectWorkshop', row.machine, bar.workshopPeriod)"
        >
          {{ bar.label }}
        </component>
      </div>

      <p v-if="!rows.length" class="muted timeline__empty">Aucune réservation ne correspond aux filtres.</p>
    </div>
  </div>
</template>

<style scoped>
.timeline-scroll {
  overflow-x: auto;
}

.timeline {
  min-width: 720px;
  font-size: 0.8rem;
}

.timeline__header,
.timeline__row {
  display: grid;
  column-gap: 0;
}

.timeline__header {
  position: sticky;
  top: 0;
  background: #fff;
  border-bottom: 1px solid var(--color-border);
  z-index: 3;
}

.timeline__corner {
  padding: 6px 8px;
  font-weight: 600;
}

.timeline__day {
  padding: 6px 0;
  text-align: center;
  color: var(--color-muted);
  white-space: nowrap;
  overflow: hidden;
}

.timeline__day--weekend {
  background: var(--color-bg);
}

.timeline__day--today {
  color: var(--color-primary);
  font-weight: 700;
}

.timeline__row {
  row-gap: 2px;
  padding: 3px 0;
  border-bottom: 1px solid var(--color-border);
}

.timeline__machine {
  grid-column: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-width: 0;
  padding: 0 8px;
  line-height: 1.2;
  z-index: 1;
}

.timeline__machine span {
  font-size: 0.72rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.timeline__cell {
  border-left: 1px solid #f1f2f4;
  z-index: 0;
}

.timeline__cell--weekend {
  background: var(--color-bg);
}

.timeline__cell--today {
  border-left: 2px solid var(--color-primary);
}

.timeline__bar {
  z-index: 2;
  margin: 0 2px;
  padding: 0 6px;
  border: none;
  border-left: 3px solid;
  border-radius: 4px;
  font: inherit;
  font-size: 0.78rem;
  text-align: left;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 28px;
}

button.timeline__bar {
  cursor: pointer;
}

.timeline__bar--reservation {
  background: #eeedfe;
  color: #3c3489;
  border-left-color: #534ab7;
}

.timeline__bar--anomaly {
  background: #fcebeb;
  color: #791f1f;
  border-left-color: #a32d2d;
}

.timeline__bar--workshop {
  background: #faece7;
  color: #712b13;
  border-left-color: #993c1d;
}

.timeline__bar--vgp {
  background: repeating-linear-gradient(45deg, #f3f4f6 0 6px, #fff 6px 12px);
  color: var(--color-muted);
  border-left-color: #d1d5db;
}

.timeline__bar--cut-before {
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
  border-left-style: dashed;
}

.timeline__bar--cut-after {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}

.timeline__bar--selected {
  outline: 2px solid var(--color-primary);
  outline-offset: 1px;
}

.timeline__empty {
  padding: 16px 8px;
}
</style>
