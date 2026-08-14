import { Alert, Chip, Paper, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { fetchPatient } from '@/services/api'

export function PatientProfilePage() {
  const { id = '' } = useParams()
  const { data: patient, error } = useQuery({ queryKey: ['patient', id], queryFn: () => fetchPatient(id) })

  if (error) return <Alert severity="error">Unable to load this patient.</Alert>
  if (!patient) return <Typography>Loading…</Typography>

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>{patient.name}</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        {patient.mrn} · {patient.date_of_birth} · {patient.gender}
      </Typography>
      <Paper variant="outlined" sx={{ p: 3, mb: 2 }}>
        <Typography variant="h6" sx={{ mb: 1 }}>Registration details</Typography>
        <Typography>Phone: {patient.phone ?? '—'}</Typography>
        <Typography>Email: {patient.email ?? '—'}</Typography>
        <Typography>Address: {patient.address ?? '—'}</Typography>
        <Typography>Blood group / genotype: {patient.blood_group ?? '—'} / {patient.genotype ?? '—'}</Typography>
      </Paper>
      <Paper variant="outlined" sx={{ p: 3, mb: 2 }}>
        <Typography variant="h6" sx={{ mb: 1 }}>Allergies</Typography>
        {patient.allergies.length ? patient.allergies.map((allergy) => <Chip key={allergy.id ?? allergy.allergen} label={`${allergy.allergen}${allergy.severity ? ` (${allergy.severity})` : ''}`} sx={{ mr: 1 }} />) : 'No allergies recorded'}
      </Paper>
      <Paper variant="outlined" sx={{ p: 3 }}>
        <Typography variant="h6" sx={{ mb: 1 }}>Medical history</Typography>
        {patient.medical_histories.length ? patient.medical_histories.map((item) => <Chip key={item.id ?? item.condition_name} label={`${item.condition_name} (${item.status})`} sx={{ mr: 1 }} />) : 'No medical history recorded'}
      </Paper>
      <Typography component={Link} to="/patients" sx={{ display: 'inline-block', mt: 2 }}>Back to patients</Typography>
    </>
  )
}
