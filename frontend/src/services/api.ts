import { http } from '@/services/http'
import type {
  ApiSuccess,
  Appointment,
  AuditLog,
  DashboardSummary,
  Department,
  Doctor,
  Encounter,
  Patient,
  RoleRecord,
  StaffUser,
} from '@/types/api'

export async function fetchDashboard(): Promise<DashboardSummary> {
  const { data } = await http.get<ApiSuccess<DashboardSummary>>('/dashboard/summary')
  return data.data
}

export async function fetchUsers(): Promise<StaffUser[]> {
  const { data } = await http.get<ApiSuccess<StaffUser[]>>('/users')
  return data.data
}

export async function fetchRoles(): Promise<RoleRecord[]> {
  const { data } = await http.get<ApiSuccess<RoleRecord[]>>('/roles')
  return data.data
}

export async function fetchAuditLogs(): Promise<AuditLog[]> {
  const { data } = await http.get<ApiSuccess<AuditLog[]>>('/audit-logs')
  return data.data
}

export async function fetchPatients(search = ''): Promise<Patient[]> {
  const { data } = await http.get<ApiSuccess<Patient[]>>('/patients', { params: { search } })
  return data.data
}

export async function fetchPatient(id: string): Promise<Patient> {
  const { data } = await http.get<ApiSuccess<Patient>>(`/patients/${id}`)
  return data.data
}

export async function findPatientDuplicates(payload: Pick<Patient, 'first_name' | 'last_name' | 'date_of_birth' | 'phone'>): Promise<Patient[]> {
  const { data } = await http.post<ApiSuccess<Patient[]>>('/patients/duplicates', payload)
  return data.data
}

export async function registerPatient(payload: Omit<Patient, 'id' | 'mrn' | 'name' | 'hospital_id' | 'status' | 'branch_id'>): Promise<Patient> {
  const { data } = await http.post<ApiSuccess<Patient>>('/patients', payload)
  return data.data
}

export async function fetchDoctors(): Promise<Doctor[]> {
  const { data } = await http.get<ApiSuccess<Doctor[]>>('/doctors')
  return data.data
}

export async function fetchDepartments(): Promise<Department[]> {
  const { data } = await http.get<ApiSuccess<Department[]>>('/departments')
  return data.data
}

export async function fetchAppointments(params: Record<string, string> = {}): Promise<Appointment[]> {
  const { data } = await http.get<ApiSuccess<Appointment[]>>('/appointments', { params })
  return data.data
}

export async function createAppointment(payload: {
  patient_id: string
  doctor_user_id: string
  department_id?: string | null
  scheduled_at: string
  reason?: string
}): Promise<Appointment> {
  const { data } = await http.post<ApiSuccess<Appointment>>('/appointments', payload)
  return data.data
}

export async function cancelAppointment(id: string, cancellation_reason?: string): Promise<Appointment> {
  const { data } = await http.post<ApiSuccess<Appointment>>(`/appointments/${id}/cancel`, { cancellation_reason })
  return data.data
}

export async function checkInAppointment(id: string): Promise<{ appointment: Appointment; encounter: Encounter }> {
  const { data } = await http.post<ApiSuccess<{ appointment: Appointment; encounter: Encounter }>>(`/appointments/${id}/check-in`)
  return data.data
}

export async function fetchEncounters(params: Record<string, string> = {}): Promise<Encounter[]> {
  const { data } = await http.get<ApiSuccess<Encounter[]>>('/encounters', { params })
  return data.data
}

export async function fetchEncounter(id: string): Promise<Encounter> {
  const { data } = await http.get<ApiSuccess<Encounter>>(`/encounters/${id}`)
  return data.data
}

export async function saveTriage(encounterId: string, payload: Record<string, unknown>): Promise<unknown> {
  const { data } = await http.post(`/encounters/${encounterId}/triage`, payload)
  return data.data
}

export async function addClinicalNote(encounterId: string, payload: Record<string, unknown>): Promise<unknown> {
  const { data } = await http.post(`/encounters/${encounterId}/notes`, payload)
  return data.data
}

export async function addDiagnosis(encounterId: string, payload: Record<string, unknown>): Promise<unknown> {
  const { data } = await http.post(`/encounters/${encounterId}/diagnoses`, payload)
  return data.data
}

export async function closeEncounter(encounterId: string): Promise<Encounter> {
  const { data } = await http.post<ApiSuccess<Encounter>>(`/encounters/${encounterId}/close`)
  return data.data
}
