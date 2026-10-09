import type { Ref } from 'vue'
import type { Reservation } from '~/types/vallet'

type Filters = {
  machine: string
  machineAgency: string
  client: string
  enteredBy: string
  from: string
  to: string
}

const emptyFilters = (): Filters => ({ machine: '', machineAgency: '', client: '', enteredBy: '', from: '', to: '' })

const distinctSorted = (values: string[]) => [...new Set(values)].sort((first, second) => first.localeCompare(second, 'fr'))

export const useReservationFilters = <T extends Reservation>(reservations: Ref<T[]>) => {
  const filters = ref<Filters>(emptyFilters())

  const options = computed(() => ({
    machineRefs: distinctSorted(reservations.value.map((reservation) => reservation.machine_ref)),
    machineAgencies: distinctSorted(reservations.value.map((reservation) => reservation.machine_agency)),
    clients: distinctSorted(reservations.value.map((reservation) => reservation.client)),
    enteringAgencies: distinctSorted(reservations.value.map((reservation) => reservation.entered_by)),
  }))

  const hasFilters = computed(() => Object.values(filters.value).some(Boolean))

  const matches = (reservation: T) => {
    const { machine, machineAgency, client, enteredBy, from, to } = filters.value

    return (!machine || reservation.machine_ref.toLowerCase().includes(machine.trim().toLowerCase()))
      && (!machineAgency || reservation.machine_agency === machineAgency)
      && (!client || reservation.client === client)
      && (!enteredBy || reservation.entered_by === enteredBy)
      && (!from || reservation.ends_at >= from)
      && (!to || reservation.starts_at <= to)
  }

  const resetFilters = () => {
    filters.value = emptyFilters()
  }

  watch(options, ({ clients }) => {
    if (filters.value.client && !clients.includes(filters.value.client)) {
      filters.value.client = ''
    }
  })

  return { filters, options, hasFilters, matches, resetFilters }
}
