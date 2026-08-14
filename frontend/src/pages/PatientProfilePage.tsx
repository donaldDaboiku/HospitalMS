import { Alert, Box, Button, Chip, Paper, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { useRef, useState } from 'react'
import { PatientCameraCapture } from '@/components/PatientCameraCapture'
import { PatientPhoto } from '@/components/PatientPhoto'
import { fetchPatient, uploadPatientPhoto } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'

export function PatientProfilePage() {
  const { id = '' } = useParams()
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [error, setError] = useState<string | null>(null)
  const { data: patient, error: loadError } = useQuery({ queryKey: ['patient', id], queryFn: () => fetchPatient(id) })

  const photoMutation = useMutation({
    mutationFn: (file: File) => uploadPatientPhoto(id, file),
    onSuccess: async () => {
      setError(null)
      await queryClient.invalidateQueries({ queryKey: ['patient', id] })
    },
    onError: (err) => setError(apiErrorMessage(err, 'Unable to save photo.')),
  })

  if (loadError) return <Alert severity="error">Unable to load this patient.</Alert>
  if (!patient) return <Typography>Loading…</Typography>

  return (
    <>
      <Box sx={{ display: 'flex', gap: 2, alignItems: 'center', mb: 1, flexWrap: 'wrap' }}>
        <PatientPhoto patientId={patient.id} photoUrl={patient.photo_url} name={patient.name} size={88} />
        <Box>
          <Typography variant="h5">{patient.name}</Typography>
          <Typography variant="body2" color="text.secondary">
            {patient.mrn} · {patient.date_of_birth} · {patient.gender}
          </Typography>
          {can('patient.edit') ? (
            <>
              <Button size="small" sx={{ mt: 1 }} onClick={() => fileInputRef.current?.click()} disabled={photoMutation.isPending}>
                {patient.photo_url ? 'Change photo' : 'Add photo'}
              </Button>
              <PatientCameraCapture
                disabled={photoMutation.isPending}
                onCapture={(photo) => photoMutation.mutate(photo)}
              />
              <input
                ref={fileInputRef}
                hidden
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={(event) => {
                  const file = event.target.files?.[0]
                  if (!file) return
                  if (file.size > 2 * 1024 * 1024) {
                    setError('Photo must be 2 MB or smaller.')
                    return
                  }
                  photoMutation.mutate(file)
                  event.target.value = ''
                }}
              />
            </>
          ) : null}
        </Box>
      </Box>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}
      <Paper variant="outlined" sx={{ p: 3, mb: 2 }}>
        <Typography variant="h6" sx={{ mb: 1 }}>Registration details</Typography>
        <Typography>Phone: {patient.phone ?? '—'}</Typography>
        <Typography>Email: {patient.email ?? '—'}</Typography>
        <Typography>Address: {patient.address ?? '—'}</Typography>
        <Typography>Blood group / genotype: {patient.blood_group ?? '—'} / {patient.genotype ?? '—'}</Typography>
      </Paper>
      <Paper variant="outlined" sx={{ p: 3, mb: 2 }}>
        <Typography variant="h6" sx={{ mb: 1 }}>Related persons</Typography>
        {patient.contacts.length ? patient.contacts.map((contact) => (
          <Box key={contact.id ?? `${contact.type}-${contact.full_name}`} sx={{ display: 'flex', gap: 1.5, alignItems: 'center', mb: 1.5 }}>
            <PatientPhoto
              patientId={contact.related_patient?.id}
              photoUrl={contact.related_patient?.photo_url}
              name={contact.related_patient?.name ?? contact.full_name}
              size={40}
            />
            <Box>
              <Typography variant="body2" sx={{ fontWeight: 600 }}>
                {contact.related_patient ? (
                  <Link to={`/patients/${contact.related_patient.id}`}>{contact.related_patient.name}</Link>
                ) : contact.full_name}
                {' · '}{contact.relationship ?? contact.type}
              </Typography>
              <Typography variant="caption" color="text.secondary">
                {[contact.type.replaceAll('_', ' '), contact.phone, contact.related_patient?.mrn].filter(Boolean).join(' · ')}
                {contact.related_patient ? ' · mapped patient' : ''}
              </Typography>
            </Box>
          </Box>
        )) : 'No related persons recorded'}
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
