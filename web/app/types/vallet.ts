export type User = {
  name: string
  email: string
  role: 'agency' | 'workshop' | 'sales'
  role_label: string
  agency: string | null
  can_book: boolean
  can_maintain: boolean
}

export type Agency = {
  id: number
  name: string
}

export type Machine = {
  ref: string
  type: string
  agency: string
  requires_vgp: boolean
  last_vgp_at: string | null
  vgp_expires_at: string | null
  vgp_ok_today: boolean
  workshop_until: string | null
  workshop_note: string | null
  available: boolean | null
  reasons: string[]
}

export type Reservation = {
  id: number
  machine_ref: string
  machine_type: string
  machine_agency: string
  client: string
  starts_at: string
  ends_at: string
  entered_by: string
}

export type Anomaly = {
  code: string
  machine_ref: string
  reservation_ids: number[]
  message: string
}
