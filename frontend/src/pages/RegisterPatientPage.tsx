import { useState } from 'react'
import { Alert, Box, Button, MenuItem, Paper, TextField, Typography } from '@mui/material'
import { useNavigate } from 'react-router-dom'
import { findPatientDuplicates, registerPatient } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import type { Patient } from '@/types/api'

const emptyPatient = {
  first_name: '',
  middle_name: null,
  last_name: '',
  date_of_birth: '',
  gender: 'unknown',
  phone: null,
  email: null,
  address: null,
  state: null,
  country: 'NG',
  occupation: null,
  marital_status: null,
  blood_group: null,
  genotype: null,
  contacts: [],
  allergies: [],
  medical_histories: [],
  identifications: [],
} satisfies Omit<Patient, 'id' | 'mrn' | 'name' | 'hospital_id' | 'status' | 'branch_id'>

export function RegisterPatientPage() {
  const navigate = useNavigate()
  const [form, setForm] = useState(emptyPatient)
  const [duplicates, setDuplicates] = useState<Patient[]>([])
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)

  const setValue = (name: keyof typeof form, value: string) => setForm((current) => ({ ...current, [name]: value || null }))

  const checkDuplicates = async () => {
    if (!form.first_name || !form.last_name || !form.date_of_birth) return
    setDuplicates(await findPatientDuplicates(form))
  }

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Register patient</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Confirm possible matches before registering a new medical record.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}
      {duplicates.length > 0 ? (
        <Alert severity="warning" sx={{ mb: 2 }}>
          Possible duplicate: {duplicates.map((patient) => `${patient.name} (${patient.mrn})`).join(', ')}. Review before continuing.
        </Alert>
      ) : null}
      <Paper
        component="form"
        variant="outlined"
        sx={{ p: 3, display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(220px, 1fr))', gap: 2 }}
        onSubmit={async (event) => {
          event.preventDefault()
          setSaving(true)
          setError(null)
          try {
            const patient = await registerPatient(form)
            navigate(`/patients/${patient.id}`)
          } catch (err) {
            setError(apiErrorMessage(err, 'Unable to register the patient.'))
          } finally {
            setSaving(false)
          }
        }}
      >
        <TextField required label="First name" value={form.first_name} onBlur={checkDuplicates} onChange={(event) => setValue('first_name', event.target.value)} />
        <TextField label="Middle name" value={form.middle_name ?? ''} onChange={(event) => setValue('middle_name', event.target.value)} />
        <TextField required label="Last name" value={form.last_name} onBlur={checkDuplicates} onChange={(event) => setValue('last_name', event.target.value)} />
        <TextField required type="date" label="Date of birth" slotProps={{ inputLabel: { shrink: true } }} value={form.date_of_birth} onBlur={checkDuplicates} onChange={(event) => setValue('date_of_birth', event.target.value)} />
        <TextField required select label="Gender" value={form.gender} onChange={(event) => setValue('gender', event.target.value)}>
          {['male', 'female', 'other', 'unknown'].map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
        </TextField>
        <TextField label="Phone" value={form.phone ?? ''} onBlur={checkDuplicates} onChange={(event) => setValue('phone', event.target.value)} />
        <TextField label="Email" type="email" value={form.email ?? ''} onChange={(event) => setValue('email', event.target.value)} />
        <TextField label="Address" value={form.address ?? ''} onChange={(event) => setValue('address', event.target.value)} />
        <TextField label="State" value={form.state ?? ''} onChange={(event) => setValue('state', event.target.value)} />
        <TextField label="Blood group" value={form.blood_group ?? ''} onChange={(event) => setValue('blood_group', event.target.value)} />
        <TextField label="Genotype" value={form.genotype ?? ''} onChange={(event) => setValue('genotype', event.target.value)} />
        <Box sx={{ gridColumn: '1 / -1', display: 'flex', justifyContent: 'flex-end', gap: 1 }}>
          <Button onClick={() => navigate('/patients')}>Cancel</Button>
          <Button type="submit" variant="contained" disabled={saving}>{saving ? 'Registering…' : 'Register patient'}</Button>
        </Box>
      </Paper>
    </>
  )
}
