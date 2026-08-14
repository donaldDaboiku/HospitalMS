import { useState } from 'react'
import { Alert, Box, Button, MenuItem, Paper, TextField, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { addClinicalNote, addDiagnosis, closeEncounter, fetchEncounter, saveTriage } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'

export function EncounterDetailPage() {
  const { id = '' } = useParams()
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [triage, setTriage] = useState({
    temperature_c: '',
    pulse: '',
    systolic_bp: '',
    diastolic_bp: '',
    oxygen_saturation: '',
    weight_kg: '',
    height_cm: '',
    pain_score: '',
    chief_complaint: '',
    priority: 'NORMAL',
  })
  const [note, setNote] = useState({ chief_complaint: '', assessment: '', treatment_plan: '' })
  const [diagnosis, setDiagnosis] = useState({ icd10_code: '', description: '', type: 'primary' })

  const encounterQuery = useQuery({
    queryKey: ['encounter', id],
    queryFn: () => fetchEncounter(id),
    enabled: Boolean(id),
  })

  const refresh = async () => {
    await queryClient.invalidateQueries({ queryKey: ['encounter', id] })
    await queryClient.invalidateQueries({ queryKey: ['encounters'] })
    await queryClient.invalidateQueries({ queryKey: ['appointments'] })
    await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
  }

  const triageMutation = useMutation({
    mutationFn: () => saveTriage(id, {
      temperature_c: triage.temperature_c ? Number(triage.temperature_c) : null,
      pulse: triage.pulse ? Number(triage.pulse) : null,
      systolic_bp: triage.systolic_bp ? Number(triage.systolic_bp) : null,
      diastolic_bp: triage.diastolic_bp ? Number(triage.diastolic_bp) : null,
      oxygen_saturation: triage.oxygen_saturation ? Number(triage.oxygen_saturation) : null,
      weight_kg: triage.weight_kg ? Number(triage.weight_kg) : null,
      height_cm: triage.height_cm ? Number(triage.height_cm) : null,
      pain_score: triage.pain_score ? Number(triage.pain_score) : null,
      chief_complaint: triage.chief_complaint || null,
      priority: triage.priority,
    }),
    onSuccess: async () => { setError(null); await refresh() },
    onError: (err) => setError(apiErrorMessage(err, 'Unable to save triage.')),
  })

  if (encounterQuery.error) return <Alert severity="error">Unable to load this encounter.</Alert>
  if (!encounterQuery.data) return <Typography>Loading…</Typography>

  const encounter = encounterQuery.data

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>
        Encounter · {encounter.patient?.name}
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        {encounter.type} · {encounter.status} · Doctor: {encounter.doctor?.name ?? 'Unassigned'}
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}

      {can('triage.create') && encounter.status !== 'closed' ? (
        <Paper sx={{ p: 2, mb: 2 }} variant="outlined">
          <Typography variant="h6" sx={{ mb: 2 }}>Triage / vitals</Typography>
          <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))', gap: 2 }}>
            {([
              ['temperature_c', 'Temp °C'],
              ['pulse', 'Pulse'],
              ['systolic_bp', 'Systolic'],
              ['diastolic_bp', 'Diastolic'],
              ['oxygen_saturation', 'SpO2'],
              ['weight_kg', 'Weight kg'],
              ['height_cm', 'Height cm'],
              ['pain_score', 'Pain 0-10'],
            ] as const).map(([key, label]) => (
              <TextField key={key} label={label} value={triage[key]} onChange={(event) => setTriage((current) => ({ ...current, [key]: event.target.value }))} />
            ))}
            <TextField select label="Priority" value={triage.priority} onChange={(event) => setTriage((current) => ({ ...current, priority: event.target.value }))}>
              {['EMERGENCY', 'URGENT', 'NORMAL', 'LOW'].map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
            </TextField>
            <TextField label="Chief complaint" value={triage.chief_complaint} onChange={(event) => setTriage((current) => ({ ...current, chief_complaint: event.target.value }))} sx={{ gridColumn: '1 / -1' }} />
            <Button variant="contained" onClick={() => triageMutation.mutate()} disabled={triageMutation.isPending}>Save triage</Button>
          </Box>
        </Paper>
      ) : null}

      {encounter.triage ? (
        <Paper sx={{ p: 2, mb: 2 }} variant="outlined">
          <Typography variant="h6">Current triage</Typography>
          <Typography>Priority: {encounter.triage.priority}</Typography>
          <Typography>Complaint: {encounter.triage.chief_complaint ?? '—'}</Typography>
          <Typography>BMI: {encounter.triage.bmi ?? '—'}</Typography>
        </Paper>
      ) : null}

      {can('clinical.create') && encounter.status !== 'closed' ? (
        <>
          <Paper sx={{ p: 2, mb: 2 }} variant="outlined">
            <Typography variant="h6" sx={{ mb: 2 }}>Clinical note</Typography>
            <Box sx={{ display: 'grid', gap: 2 }}>
              <TextField label="Chief complaint" value={note.chief_complaint} onChange={(event) => setNote((current) => ({ ...current, chief_complaint: event.target.value }))} />
              <TextField label="Assessment" multiline minRows={2} value={note.assessment} onChange={(event) => setNote((current) => ({ ...current, assessment: event.target.value }))} />
              <TextField label="Treatment plan" multiline minRows={2} value={note.treatment_plan} onChange={(event) => setNote((current) => ({ ...current, treatment_plan: event.target.value }))} />
              <Button variant="contained" onClick={async () => {
                try {
                  await addClinicalNote(id, note)
                  setError(null)
                  await refresh()
                } catch (err) {
                  setError(apiErrorMessage(err, 'Unable to save note.'))
                }
              }}>Save note</Button>
            </Box>
          </Paper>

          <Paper sx={{ p: 2, mb: 2 }} variant="outlined">
            <Typography variant="h6" sx={{ mb: 2 }}>Diagnosis</Typography>
            <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 2 }}>
              <TextField label="ICD-10 code" value={diagnosis.icd10_code} onChange={(event) => setDiagnosis((current) => ({ ...current, icd10_code: event.target.value }))} />
              <TextField required label="Description" value={diagnosis.description} onChange={(event) => setDiagnosis((current) => ({ ...current, description: event.target.value }))} />
              <TextField select label="Type" value={diagnosis.type} onChange={(event) => setDiagnosis((current) => ({ ...current, type: event.target.value }))}>
                {['primary', 'secondary', 'differential'].map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
              </TextField>
              <Button variant="contained" onClick={async () => {
                try {
                  await addDiagnosis(id, diagnosis)
                  setError(null)
                  await refresh()
                } catch (err) {
                  setError(apiErrorMessage(err, 'Unable to save diagnosis.'))
                }
              }}>Save diagnosis</Button>
            </Box>
          </Paper>

          <Button color="warning" variant="outlined" onClick={async () => {
            try {
              await closeEncounter(id)
              setError(null)
              await refresh()
            } catch (err) {
              setError(apiErrorMessage(err, 'Unable to close encounter.'))
            }
          }}>Close encounter</Button>
        </>
      ) : null}

      <Typography component={Link} to="/clinical/encounters" sx={{ display: 'inline-block', mt: 2 }}>Back to encounters</Typography>
    </>
  )
}
