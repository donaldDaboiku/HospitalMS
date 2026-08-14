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

export type Patient = {
  id: string
  hospital_id: string
  branch_id: string | null
  mrn: string
  first_name: string
  middle_name: string | null
  last_name: string
  name: string
  date_of_birth: string
  gender: string
  phone: string | null
  email: string | null
  address: string | null
  state: string | null
  country: string
  occupation: string | null
  marital_status: string | null
  blood_group: string | null
  genotype: string | null
  photo_url: string | null
  status: string
  contacts: PatientContact[]
  allergies: PatientAllergy[]
  medical_histories: PatientMedicalHistory[]
  identifications: PatientIdentification[]
}

export type PatientContact = {
  id?: string
  type: 'emergency' | 'next_of_kin' | 'other'
  related_patient_id?: string | null
  full_name: string
  relationship: string | null
  phone: string
  email: string | null
  address: string | null
  is_primary: boolean
  related_patient?: {
    id: string
    mrn: string
    name: string
    phone: string | null
    photo_url: string | null
  } | null
}

export type PatientAllergy = {
  id?: string
  allergen: string
  reaction: string | null
  severity: string | null
}

export type PatientMedicalHistory = {
  id?: string
  condition_name: string
  status: string
  notes: string | null
}

export type PatientIdentification = {
  id?: string
  type: string
  number: string
  issuer: string | null
  expires_at: string | null
}

export type Doctor = {
  id: string
  hospital_id: string
  user_id: string
  specialty: string | null
  is_available: boolean
  user?: { id: string; first_name: string; last_name: string; email: string; name?: string }
  department?: { id: string; name: string; code: string } | null
}

export type Appointment = {
  id: string
  hospital_id: string
  patient_id: string
  doctor_user_id: string
  department_id: string | null
  scheduled_at: string
  status: string
  type: string
  reason: string | null
  notes: string | null
  checked_in_at: string | null
  patient?: { id: string; mrn: string; name: string; phone: string | null } | null
  doctor?: { id: string; name: string; email: string } | null
  department?: { id: string; name: string; code: string } | null
}

export type Encounter = {
  id: string
  hospital_id: string
  patient_id: string
  appointment_id: string | null
  doctor_user_id: string | null
  department_id: string | null
  type: string
  status: string
  started_at: string
  closed_at: string | null
  patient?: { id: string; mrn: string; name: string } | null
  doctor?: { id: string; name: string } | null
  triage?: TriageAssessment | null
  clinical_notes?: ClinicalNote[]
  diagnoses?: Diagnosis[]
}

export type TriageAssessment = {
  id: string
  temperature_c: number | null
  systolic_bp: number | null
  diastolic_bp: number | null
  pulse: number | null
  respiratory_rate: number | null
  oxygen_saturation: number | null
  weight_kg: number | null
  height_cm: number | null
  bmi: number | null
  pain_score: number | null
  consciousness_level: string | null
  allergies_noted: string | null
  chief_complaint: string | null
  priority: string
}

export type ClinicalNote = {
  id: string
  chief_complaint: string | null
  assessment: string | null
  treatment_plan: string | null
  notes: string | null
}

export type Diagnosis = {
  id: string
  icd10_code: string | null
  description: string
  type: string
}

export type Department = {
  id: string
  name: string
  code: string
}
