import { CssBaseline, ThemeProvider } from '@mui/material'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from '@/hooks/useAuth'
import { GuestRoute, ProtectedRoute } from '@/components/ProtectedRoute'
import { AppLayout } from '@/layouts/AppLayout'
import { LoginPage } from '@/pages/LoginPage'
import { DashboardPage } from '@/pages/DashboardPage'
import { UsersPage } from '@/pages/UsersPage'
import { RolesPage } from '@/pages/RolesPage'
import { AuditLogsPage } from '@/pages/AuditLogsPage'
import { ComingSoonPage } from '@/pages/ComingSoonPage'
import { PatientProfilePage } from '@/pages/PatientProfilePage'
import { PatientsPage } from '@/pages/PatientsPage'
import { RegisterPatientPage } from '@/pages/RegisterPatientPage'
import { RegisterFamilyPage } from '@/pages/RegisterFamilyPage'
import { TodaysAppointmentsPage } from '@/pages/TodaysAppointmentsPage'
import { WaitingListPage } from '@/pages/WaitingListPage'
import { EncountersPage } from '@/pages/EncountersPage'
import { EncounterDetailPage } from '@/pages/EncounterDetailPage'
import { LabCatalogPage } from '@/pages/LabCatalogPage'
import { LabOrdersPage } from '@/pages/LabOrdersPage'
import { LabOrderDetailPage } from '@/pages/LabOrderDetailPage'
import { RadiologyOrdersPage } from '@/pages/RadiologyOrdersPage'
import { PharmacyPage } from '@/pages/PharmacyPage'
import { theme } from '@/theme'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
})

export default function App() {
  return (
    <ThemeProvider theme={theme}>
      <CssBaseline />
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <BrowserRouter>
            <Routes>
              <Route element={<GuestRoute />}>
                <Route path="/login" element={<LoginPage />} />
              </Route>
              <Route element={<ProtectedRoute />}>
                <Route element={<AppLayout />}>
                  <Route path="/" element={<DashboardPage />} />
                  <Route path="/patients" element={<PatientsPage />} />
                  <Route path="/patients/register" element={<RegisterPatientPage />} />
                  <Route path="/patients/register-family" element={<RegisterFamilyPage />} />
                  <Route path="/patients/:id" element={<PatientProfilePage />} />
                  <Route path="/appointments/today" element={<TodaysAppointmentsPage />} />
                  <Route path="/appointments/waiting" element={<WaitingListPage />} />
                  <Route path="/clinical/encounters" element={<EncountersPage />} />
                  <Route path="/clinical/encounters/:id" element={<EncounterDetailPage />} />
                  <Route path="/laboratory/catalog" element={<LabCatalogPage />} />
                  <Route path="/laboratory/orders" element={<LabOrdersPage />} />
                  <Route path="/laboratory/orders/:id" element={<LabOrderDetailPage />} />
                  <Route path="/radiology/orders" element={<RadiologyOrdersPage />} />
                  <Route path="/pharmacy" element={<PharmacyPage />} />
                  <Route path="/admin/users" element={<UsersPage />} />
                  <Route path="/admin/roles" element={<RolesPage />} />
                  <Route path="/admin/audit-logs" element={<AuditLogsPage />} />
                  <Route path="/coming-soon" element={<ComingSoonPage />} />
                </Route>
              </Route>
              <Route path="*" element={<Navigate to="/" replace />} />
            </Routes>
          </BrowserRouter>
        </AuthProvider>
      </QueryClientProvider>
    </ThemeProvider>
  )
}
