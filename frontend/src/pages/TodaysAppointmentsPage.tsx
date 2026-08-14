import { useMemo, useState } from 'react'
import { Alert, Box, Button, MenuItem, Paper, Table, TableBody, TableCell, TableHead, TableRow, TextField, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import {
  cancelAppointment,
  checkInAppointment,
  createAppointment,
  fetchAppointments,
  fetchDoctors,
  fetchDepartments,
  fetchPatients,
} from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'

export function TodaysAppointmentsPage() {
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [form, setForm] = useState({
    patient_id: '',
    doctor_user_id: '',
    department_id: '',
    scheduled_at: new Date().toISOString().slice(0, 16),
    reason: '',
  })

  const appointmentsQuery = useQuery({
    queryKey: ['appointments', 'today'],
    queryFn: () => fetchAppointments({ scope: 'today' }),
  })
  const doctorsQuery = useQuery({ queryKey: ['doctors'], queryFn: fetchDoctors })
  const departmentsQuery = useQuery({ queryKey: ['departments'], queryFn: fetchDepartments })
  const patientsQuery = useQuery({ queryKey: ['patients', ''], queryFn: () => fetchPatients('') })

  const bookMutation = useMutation({
    mutationFn: () => createAppointment({
      patient_id: form.patient_id,
      doctor_user_id: form.doctor_user_id,
      department_id: form.department_id || null,
      scheduled_at: new Date(form.scheduled_at).toISOString(),
      reason: form.reason || undefined,
    }),
    onSuccess: async () => {
      setError(null)
      await queryClient.invalidateQueries({ queryKey: ['appointments'] })
      await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
    onError: (err) => setError(apiErrorMessage(err, 'Unable to book appointment.')),
  })

  const doctors = useMemo(() => doctorsQuery.data ?? [], [doctorsQuery.data])

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Today&apos;s appointments</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Book visits, check patients in, and cancel appointments.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}

      {can('appointment.create') ? (
        <Paper
          component="form"
          variant="outlined"
          sx={{ p: 2, mb: 3, display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 2 }}
          onSubmit={(event) => {
            event.preventDefault()
            bookMutation.mutate()
          }}
        >
          <TextField select required label="Patient" value={form.patient_id} onChange={(event) => setForm((current) => ({ ...current, patient_id: event.target.value }))}>
            {(patientsQuery.data ?? []).map((patient) => (
              <MenuItem key={patient.id} value={patient.id}>{patient.mrn} · {patient.name}</MenuItem>
            ))}
          </TextField>
          <TextField select required label="Doctor" value={form.doctor_user_id} onChange={(event) => setForm((current) => ({ ...current, doctor_user_id: event.target.value }))}>
            {doctors.map((doctor) => (
              <MenuItem key={doctor.user_id} value={doctor.user_id}>
                {doctor.user ? `${doctor.user.first_name} ${doctor.user.last_name}` : doctor.user_id}
                {doctor.specialty ? ` · ${doctor.specialty}` : ''}
              </MenuItem>
            ))}
          </TextField>
          <TextField select label="Department" value={form.department_id} onChange={(event) => setForm((current) => ({ ...current, department_id: event.target.value }))}>
            <MenuItem value="">None</MenuItem>
            {(departmentsQuery.data ?? []).map((department) => (
              <MenuItem key={department.id} value={department.id}>{department.name}</MenuItem>
            ))}
          </TextField>
          <TextField required type="datetime-local" label="Scheduled at" slotProps={{ inputLabel: { shrink: true } }} value={form.scheduled_at} onChange={(event) => setForm((current) => ({ ...current, scheduled_at: event.target.value }))} />
          <TextField label="Reason" value={form.reason} onChange={(event) => setForm((current) => ({ ...current, reason: event.target.value }))} />
          <Box sx={{ display: 'flex', alignItems: 'center' }}>
            <Button type="submit" variant="contained" disabled={bookMutation.isPending}>Book appointment</Button>
          </Box>
        </Paper>
      ) : null}

      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Time</TableCell>
              <TableCell>Patient</TableCell>
              <TableCell>Doctor</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Actions</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {appointmentsQuery.isFetching ? (
              <TableRow><TableCell colSpan={5}>Loading…</TableCell></TableRow>
            ) : (
              appointmentsQuery.data?.map((appointment) => (
                <TableRow key={appointment.id}>
                  <TableCell>{new Date(appointment.scheduled_at).toLocaleString()}</TableCell>
                  <TableCell>{appointment.patient?.name ?? appointment.patient_id}</TableCell>
                  <TableCell>{appointment.doctor?.name ?? appointment.doctor_user_id}</TableCell>
                  <TableCell>{appointment.status}</TableCell>
                  <TableCell>
                    <Box sx={{ display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                      {can('appointment.edit') && ['scheduled', 'confirmed'].includes(appointment.status) ? (
                        <Button size="small" onClick={async () => {
                          try {
                            await checkInAppointment(appointment.id)
                            await queryClient.invalidateQueries({ queryKey: ['appointments'] })
                            await queryClient.invalidateQueries({ queryKey: ['encounters'] })
                            await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
                          } catch (err) {
                            setError(apiErrorMessage(err, 'Check-in failed.'))
                          }
                        }}>Check in</Button>
                      ) : null}
                      {can('appointment.cancel') && !['cancelled', 'completed'].includes(appointment.status) ? (
                        <Button size="small" color="warning" onClick={async () => {
                          try {
                            await cancelAppointment(appointment.id, 'Cancelled from today list')
                            await queryClient.invalidateQueries({ queryKey: ['appointments'] })
                            await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
                          } catch (err) {
                            setError(apiErrorMessage(err, 'Cancel failed.'))
                          }
                        }}>Cancel</Button>
                      ) : null}
                    </Box>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </Paper>
      <Typography component={Link} to="/appointments/waiting" sx={{ display: 'inline-block', mt: 2 }}>Open waiting list</Typography>
    </>
  )
}
