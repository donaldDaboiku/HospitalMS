import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react'
import { fetchMe, login as loginRequest, logout as logoutRequest } from '@/services/auth'
import { getToken } from '@/services/http'
import type { AuthUser } from '@/types/api'

type AuthContextValue = {
  user: AuthUser | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
  can: (permission: string) => boolean
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = getToken()
    if (!token) {
      setLoading(false)
      return
    }

    fetchMe()
      .then(setUser)
      .catch(() => setUser(null))
      .finally(() => setLoading(false))
  }, [])

  const value = useMemo<AuthContextValue>(
    () => ({
      user,
      loading,
      login: async (email, password) => {
        const result = await loginRequest(email, password)
        setUser(result.user)
      },
      logout: async () => {
        await logoutRequest()
        setUser(null)
      },
      can: (permission) => Boolean(user?.permissions.includes(permission) || user?.roles.includes('SUPER_ADMIN')),
    }),
    [user, loading],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext)
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider')
  }
  return context
}
