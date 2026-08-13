import { http, setToken } from '@/services/http'
import type { ApiSuccess, AuthUser, LoginResponse } from '@/types/api'

export async function login(email: string, password: string): Promise<LoginResponse> {
  const { data } = await http.post<ApiSuccess<LoginResponse>>('/auth/login', { email, password })
  setToken(data.data.token)
  return data.data
}

export async function fetchMe(): Promise<AuthUser> {
  const { data } = await http.get<ApiSuccess<AuthUser>>('/auth/me')
  return data.data
}

export async function logout(): Promise<void> {
  try {
    await http.post('/auth/logout')
  } finally {
    setToken(null)
  }
}
