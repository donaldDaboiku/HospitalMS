import { http } from '@/services/http'
import type { ApiSuccess, AuditLog, DashboardSummary, Patient, RoleRecord, StaffUser } from '@/types/api'

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
