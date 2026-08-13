export type ApiSuccess<T> = {
  success: true
  message: string
  data: T
  meta?: {
    current_page: number
    per_page: number
    total: number
    last_page: number
  }
}

export type ApiError = {
  success: false
  message: string
  errors?: Record<string, string[]>
}

export type AuthUser = {
  id: string
  hospital_id: string | null
  branch_id: string | null
  first_name: string
  middle_name: string | null
  last_name: string
  name: string
  email: string
  phone: string | null
  is_active: boolean
  roles: string[]
  permissions: string[]
  hospital?: {
    id: string
    name: string
    code: string
  } | null
}

export type LoginResponse = {
  token: string
  token_type: string
  expires_at: string
  user: AuthUser
}

export type DashboardSummary = {
  total_users: number
  active_users: number
  audit_events_today: number
  total_patients: number
  todays_appointments: number
  waiting_patients: number
  doctors_available: number
  admissions: number
  discharges: number
  bed_occupancy: number
  pending_lab_results: number
  pending_prescriptions: number
  todays_revenue: number
  outstanding_bills: number
  low_stock: number
  emergency_cases: number
}

export type StaffUser = {
  id: string
  hospital_id: string | null
  first_name: string
  last_name: string
  name: string
  email: string
  phone: string | null
  is_active: boolean
  last_login_at: string | null
  roles: string[]
  hospital?: { id: string; name: string; code: string } | null
}

export type RoleRecord = {
  id: string
  name: string
  permissions: string[]
}

export type AuditLog = {
  id: string
  action: string
  module: string
  ip_address: string | null
  created_at: string
  user?: { id: string; first_name: string; last_name: string; email: string } | null
}
