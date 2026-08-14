import { useState } from 'react'
import { Alert, Button, Paper, Table, TableBody, TableCell, TableHead, TableRow, TextField, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { fetchPatients } from '@/services/api'

export function PatientsPage() {
  const [search, setSearch] = useState('')
  const { data, error, isFetching } = useQuery({
    queryKey: ['patients', search],
    queryFn: () => fetchPatients(search),
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Patients</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Search by MRN, name, phone, or date of birth.
      </Typography>
      <Paper variant="outlined" sx={{ p: 2, mb: 2, display: 'flex', gap: 2 }}>
        <TextField
          label="Search patients"
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          fullWidth
        />
        <Button component={Link} to="/patients/register" variant="contained">Register patient</Button>
      </Paper>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>Unable to load patients.</Alert> : null}
      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow><TableCell>MRN</TableCell><TableCell>Name</TableCell><TableCell>Date of birth</TableCell><TableCell>Phone</TableCell><TableCell>Status</TableCell></TableRow>
          </TableHead>
          <TableBody>
            {isFetching ? <TableRow><TableCell colSpan={5}>Loading…</TableCell></TableRow> : null}
            {data?.map((patient) => (
              <TableRow key={patient.id} hover component={Link} to={`/patients/${patient.id}`} sx={{ textDecoration: 'none', cursor: 'pointer' }}>
                <TableCell>{patient.mrn}</TableCell><TableCell>{patient.name}</TableCell><TableCell>{patient.date_of_birth}</TableCell><TableCell>{patient.phone ?? '—'}</TableCell><TableCell>{patient.status}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Paper>
    </>
  )
}
