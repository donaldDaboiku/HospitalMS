import { Alert, Box, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { StatCard } from '@/components/StatCard'
import { fetchDashboard } from '@/services/api'

export function DashboardPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['dashboard'],
    queryFn: fetchDashboard,
  })

  const cards = [
    { label: 'Total Patients', value: data?.total_patients ?? 0 },
    { label: "Today's Appointments", value: data?.todays_appointments ?? 0 },
    { label: 'Waiting Patients', value: data?.waiting_patients ?? 0 },
    { label: 'Doctors Available', value: data?.doctors_available ?? 0 },
    { label: 'Admissions', value: data?.admissions ?? 0 },
    { label: 'Discharges', value: data?.discharges ?? 0 },
    { label: 'Bed Occupancy', value: data?.bed_occupancy ?? 0 },
    { label: 'Pending Lab Results', value: data?.pending_lab_results ?? 0 },
    { label: 'Pending Prescriptions', value: data?.pending_prescriptions ?? 0 },
    { label: "Today's Revenue", value: data?.todays_revenue ?? 0 },
    { label: 'Outstanding Bills', value: data?.outstanding_bills ?? 0 },
    { label: 'Low Stock', value: data?.low_stock ?? 0 },
    { label: 'Emergency Cases', value: data?.emergency_cases ?? 0 },
    { label: 'Active Users', value: data?.active_users ?? 0 },
    { label: 'Audit Events Today', value: data?.audit_events_today ?? 0 },
  ]

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>
        Dashboard
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Operational overview. Clinical and financial modules will populate these figures as they are built.
      </Typography>
      {error ? (
        <Alert severity="error" sx={{ mb: 2 }}>
          Unable to load dashboard metrics.
        </Alert>
      ) : null}
      <Box
        sx={{
          display: 'grid',
          gridTemplateColumns: 'repeat(auto-fill, minmax(240px, 1fr))',
          gap: 2,
        }}
      >
        {cards.map((card) => (
          <StatCard key={card.label} label={card.label} value={isLoading ? '—' : card.value} />
        ))}
      </Box>
    </>
  )
}
