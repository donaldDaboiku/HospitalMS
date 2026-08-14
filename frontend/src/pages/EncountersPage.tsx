import { Alert, Button, Paper, Table, TableBody, TableCell, TableHead, TableRow, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { fetchEncounters } from '@/services/api'

export function EncountersPage() {
  const { data, error, isFetching } = useQuery({
    queryKey: ['encounters'],
    queryFn: () => fetchEncounters({}),
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Encounters</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Open clinical visits, triage, notes, and diagnoses.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>Unable to load encounters.</Alert> : null}
      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Started</TableCell>
              <TableCell>Patient</TableCell>
              <TableCell>Doctor</TableCell>
              <TableCell>Type</TableCell>
              <TableCell>Status</TableCell>
              <TableCell></TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {isFetching ? (
              <TableRow><TableCell colSpan={6}>Loading…</TableCell></TableRow>
            ) : (
              data?.map((encounter) => (
                <TableRow key={encounter.id}>
                  <TableCell>{new Date(encounter.started_at).toLocaleString()}</TableCell>
                  <TableCell>{encounter.patient?.name}</TableCell>
                  <TableCell>{encounter.doctor?.name ?? '—'}</TableCell>
                  <TableCell>{encounter.type}</TableCell>
                  <TableCell>{encounter.status}</TableCell>
                  <TableCell>
                    <Button component={Link} to={`/clinical/encounters/${encounter.id}`} size="small">Open</Button>
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
