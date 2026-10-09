export type User = {
  name: string
  email: string
  role: 'agency' | 'agency_manager' | 'workshop' | 'sales' | 'director'
  role_label: string
  agency: string | null
  can_book: boolean
  can_maintain: boolean
  chooses_entering_agency: boolean
  can_manage_key_accounts: boolean
}

export type Agency = {
  id: number
  name: string
}

export type WorkshopPeriod = {
  id: number
  starts_at: string
  ends_at: string
  reason: string | null
  status: 'current' | 'planned'
}

export type Machine = {
  ref: string
  type: string
  agency: string
  requires_vgp: boolean
  last_vgp_at: string | null
  vgp_expires_at: string | null
  vgp_ok_today: boolean
  workshop_periods: WorkshopPeriod[]
  available: boolean | null
  reasons: string[]
  timeline?: MachineTimeline | null
}

export type Occupation = {
  kind: 'reservation' | 'workshop' | 'vgp'
  starts_at: string | null
  ends_at: string | null
  label: string | null
}

export type MachineTimeline = {
  occupations: Occupation[]
  free_windows: { starts_at: string, ends_at: string | null }[]
}

export type Reservation = {
  id: number
  machine_ref: string
  machine_type: string
  machine_agency: string
  client: string
  purchase_order: string | null
  starts_at: string
  ends_at: string
  entered_by: string
  cancellable_until: string
  cancellable: boolean
  modified_at: string | null
  modified_by: string | null
  can_cancel: boolean
  can_modify: boolean
  cancel_denied_reason: string | null
  cancel_beyond_deadline: boolean
}

export type ReservationStatus = 'upcoming' | 'ongoing' | 'finished' | 'cancelled'

export type HistoryReservation = Reservation & {
  status: ReservationStatus
  status_label: string
  cancelled_at: string | null
  cancelled_by: string | null
  events: { type: 'modified' | 'cancelled', occurred_on: string, description: string }[]
}

export type Anomaly = {
  code: string
  machine_ref: string
  reservation_ids: number[]
  message: string
}

export type KeyAccount = {
  id: number
  name: string
}

export type ReservationChanges = {
  starts_at: string
  ends_at: string
  purchase_order: string | null
}
