import { http } from '@/services/http'
import type { ApiSuccess, AuditLog, DashboardSummary, RoleRecord, StaffUser } from '@/types/api'

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
