import axios, { type AxiosError } from 'axios'
import type { ApiError } from '@/types/api'

const TOKEN_KEY = 'hms.token'

export function getToken(): string | null {
  return sessionStorage.getItem(TOKEN_KEY)
}

export function setToken(token: string | null): void {
  if (token) {
    sessionStorage.setItem(TOKEN_KEY, token)
  } else {
    sessionStorage.removeItem(TOKEN_KEY)
  }
}

export const http = axios.create({
  baseURL: '/api/v1',
  withCredentials: true,
  headers: {
    Accept: 'application/json',
  },
})

http.interceptors.request.use((config) => {
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => response,
  (error: AxiosError<ApiError>) => {
    if (error.response?.status === 401) {
      setToken(null)
      if (!window.location.pathname.startsWith('/login')) {
        window.location.assign('/login')
      }
    }
    return Promise.reject(error)
  },
)

export function apiErrorMessage(error: unknown, fallback = 'Request failed'): string {
  const axiosError = error as AxiosError<ApiError>
  return axiosError.response?.data?.message ?? fallback
}
