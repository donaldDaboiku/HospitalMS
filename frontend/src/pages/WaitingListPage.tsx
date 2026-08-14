import { Alert, Button, Paper, Table, TableBody, TableCell, TableHead, TableRow, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { fetchAppointments } from '@/services/api'

export function WaitingListPage() {
  const { data, error, isFetching } = useQuery({
    queryKey: ['appointments', 'waiting'],
    queryFn: () => fetchAppointments({ status: 'checked_in' }),
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Waiting list</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Patients who have checked in and are waiting for triage or consultation.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>Unable to load the waiting list.</Alert> : null}
      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Checked in</TableCell>
              <TableCell>Patient</TableCell>
              <TableCell>Doctor</TableCell>
              <TableCell>Reason</TableCell>
              <TableCell></TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {isFetching ? (
              <TableRow><TableCell colSpan={5}>Loading…</TableCell></TableRow>
            ) : (
              data?.map((appointment) => (
                <TableRow key={appointment.id}>
                  <TableCell>{appointment.checked_in_at ? new Date(appointment.checked_in_at).toLocaleString() : '—'}</TableCell>
                  <TableCell>{appointment.patient?.name}</TableCell>
                  <TableCell>{appointment.doctor?.name}</TableCell>
                  <TableCell>{appointment.reason ?? '—'}</TableCell>
                  <TableCell>
                    <Button component={Link} to="/clinical/encounters" size="small">Encounters</Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </Paper>
    </>
  )
}
