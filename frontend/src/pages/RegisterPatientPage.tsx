import { useEffect, useMemo, useState } from 'react'
import { Alert, Box, Button, Divider, MenuItem, Paper, Stack, TextField, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { Link, useNavigate } from 'react-router-dom'
import { PatientCameraCapture } from '@/components/PatientCameraCapture'
import { PatientPhoto } from '@/components/PatientPhoto'
import { fetchPatients, findPatientDuplicates, registerPatient, uploadPatientPhoto } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import type { Patient, PatientContact } from '@/types/api'

const bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown'] as const
const genotypes = ['AA', 'AS', 'AC', 'SS', 'SC', 'CC', 'unknown'] as const
const maritalStatuses = ['single', 'married', 'divorced', 'widowed', 'separated', 'unknown'] as const
const relationLabels = ['Spouse', 'Parent', 'Child', 'Sibling', 'Guardian', 'Friend', 'Other'] as const

const emptyContact = (): PatientContact => ({
  type: 'next_of_kin',
  related_patient_id: null,
  full_name: '',
  relationship: 'Spouse',
  phone: '',
  email: null,
  address: null,
  is_primary: true,
})

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
  contacts: [] as PatientContact[],
  allergies: [],
  medical_histories: [],
  identifications: [],
} satisfies Omit<Patient, 'id' | 'mrn' | 'name' | 'hospital_id' | 'status' | 'branch_id' | 'photo_url'>

function displayName(form: typeof emptyPatient): string {
  return [form.first_name, form.middle_name, form.last_name].filter(Boolean).join(' ').trim() || 'New patient'
}

function ageFromDob(dateOfBirth: string): string | null {
  if (!dateOfBirth) return null
  const dob = new Date(dateOfBirth)
  if (Number.isNaN(dob.getTime())) return null
  const today = new Date()
  let age = today.getFullYear() - dob.getFullYear()
  const monthDiff = today.getMonth() - dob.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) age -= 1
  return age >= 0 ? `${age} yrs` : null
}

export function RegisterPatientPage() {
  const navigate = useNavigate()
  const [form, setForm] = useState(emptyPatient)
  const [photoFile, setPhotoFile] = useState<File | null>(null)
  const [photoPreview, setPhotoPreview] = useState<string | null>(null)
  const [duplicates, setDuplicates] = useState<Patient[]>([])
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)
  const [relatedSearch, setRelatedSearch] = useState('')

  const relatedPatientsQuery = useQuery({
    queryKey: ['patients', 'related-picker', relatedSearch],
    queryFn: () => fetchPatients(relatedSearch),
    enabled: form.contacts.length > 0,
  })

  const setValue = (name: keyof typeof form, value: string) => setForm((current) => ({ ...current, [name]: value || null }))

  const contact = form.contacts[0] ?? null

  const setContact = (patch: Partial<PatientContact>) => {
    setForm((current) => {
      const existing = current.contacts[0] ?? emptyContact()
      return { ...current, contacts: [{ ...existing, ...patch }] }
    })
  }

  const linkRelatedPatient = (patientId: string) => {
    const related = relatedPatientsQuery.data?.find((item) => item.id === patientId)
    if (!related) {
      setContact({ related_patient_id: patientId || null })
      return
    }
    setContact({
      related_patient_id: related.id,
      full_name: related.name,
      phone: related.phone ?? '',
      email: related.email,
      address: related.address,
    })
  }

  const preview = useMemo(() => ({
    name: displayName(form),
    age: ageFromDob(form.date_of_birth),
  }), [form])

  useEffect(() => {
    if (!photoFile) {
      setPhotoPreview(null)
      return
    }
    const url = URL.createObjectURL(photoFile)
    setPhotoPreview(url)
    return () => URL.revokeObjectURL(url)
  }, [photoFile])

  const checkDuplicates = async () => {
    if (!form.first_name || !form.last_name || !form.date_of_birth) return
    setDuplicates(await findPatientDuplicates({
      first_name: form.first_name,
      last_name: form.last_name,
      date_of_birth: form.date_of_birth,
      phone: form.phone,
    }))
  }

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Register patient</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Enter demographics, add a photo, and map next of kin if they are already registered.
        {' '}
        <Link to="/patients/register-family">Register a whole family instead</Link>.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}

      <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', lg: 'minmax(0, 1.6fr) minmax(280px, 0.9fr)' }, gap: 3, alignItems: 'start' }}>
        <Paper
          component="form"
          variant="outlined"
          sx={{ p: 3, display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 2 }}
          onSubmit={async (event) => {
            event.preventDefault()
            setSaving(true)
            setError(null)
            try {
              const payload = {
                ...form,
                contacts: form.contacts.filter((item) => item.related_patient_id || (item.full_name && item.phone)),
              }
              const patient = await registerPatient(payload)
              if (photoFile) {
                await uploadPatientPhoto(patient.id, photoFile)
              }
              navigate(`/patients/${patient.id}`)
            } catch (err) {
              setError(apiErrorMessage(err, 'Unable to register the patient.'))
            } finally {
              setSaving(false)
            }
          }}
        >
          <Typography variant="subtitle2" color="text.secondary" sx={{ gridColumn: '1 / -1' }}>Photo</Typography>
          <Box sx={{ gridColumn: '1 / -1', display: 'flex', alignItems: 'center', gap: 2, flexWrap: 'wrap' }}>
            <PatientPhoto previewUrl={photoPreview} name={preview.name} size={88} />
            <Button variant="outlined" component="label">
              {photoFile ? 'Change photo' : 'Upload photo'}
              <input
                hidden
                type="file"
                accept="image/jpeg,image/png,image/webp"
                onChange={(event) => {
                  const file = event.target.files?.[0] ?? null
                  if (file && file.size > 2 * 1024 * 1024) {
                    setError('Photo must be 2 MB or smaller.')
                    return
                  }
                  setError(null)
                  setPhotoFile(file)
                }}
              />
            </Button>
            <PatientCameraCapture
              onCapture={(photo) => {
                setError(null)
                setPhotoFile(photo)
              }}
            />
            {photoFile ? (
              <Button color="inherit" onClick={() => setPhotoFile(null)}>Remove</Button>
            ) : (
              <Typography variant="caption" color="text.secondary">JPEG, PNG, or WebP · max 2 MB</Typography>
            )}
          </Box>

          <Typography variant="subtitle2" color="text.secondary" sx={{ gridColumn: '1 / -1' }}>Identity</Typography>
          <TextField required label="First name" value={form.first_name} onBlur={checkDuplicates} onChange={(event) => setValue('first_name', event.target.value)} />
          <TextField label="Middle name" value={form.middle_name ?? ''} onChange={(event) => setValue('middle_name', event.target.value)} />
          <TextField required label="Last name" value={form.last_name} onBlur={checkDuplicates} onChange={(event) => setValue('last_name', event.target.value)} />
          <TextField required type="date" label="Date of birth" slotProps={{ inputLabel: { shrink: true } }} value={form.date_of_birth} onBlur={checkDuplicates} onChange={(event) => setValue('date_of_birth', event.target.value)} />
          <TextField required select label="Gender" value={form.gender} onChange={(event) => setValue('gender', event.target.value)}>
            {['male', 'female', 'other', 'unknown'].map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
          </TextField>
          <TextField select label="Marital status" value={form.marital_status ?? ''} onChange={(event) => setValue('marital_status', event.target.value)}>
            <MenuItem value="">—</MenuItem>
            {maritalStatuses.map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
          </TextField>
          <TextField label="Occupation" value={form.occupation ?? ''} onChange={(event) => setValue('occupation', event.target.value)} />

          <Typography variant="subtitle2" color="text.secondary" sx={{ gridColumn: '1 / -1', mt: 1 }}>Patient contact</Typography>
          <TextField label="Phone" value={form.phone ?? ''} onBlur={checkDuplicates} onChange={(event) => setValue('phone', event.target.value)} />
          <TextField label="Email" type="email" value={form.email ?? ''} onChange={(event) => setValue('email', event.target.value)} />
          <TextField label="Address" value={form.address ?? ''} onChange={(event) => setValue('address', event.target.value)} sx={{ gridColumn: { md: 'span 2' } }} />
          <TextField label="State" value={form.state ?? ''} onChange={(event) => setValue('state', event.target.value)} />
          <TextField label="Country" value={form.country} onChange={(event) => setValue('country', event.target.value)} />

          <Typography variant="subtitle2" color="text.secondary" sx={{ gridColumn: '1 / -1', mt: 1 }}>Clinical basics</Typography>
          <TextField select label="Blood group" value={form.blood_group ?? ''} onChange={(event) => setValue('blood_group', event.target.value)}>
            <MenuItem value="">—</MenuItem>
            {bloodGroups.map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
          </TextField>
          <TextField select label="Genotype" value={form.genotype ?? ''} onChange={(event) => setValue('genotype', event.target.value)}>
            <MenuItem value="">—</MenuItem>
            {genotypes.map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
          </TextField>

          <Box sx={{ gridColumn: '1 / -1', display: 'flex', alignItems: 'center', justifyContent: 'space-between', mt: 1 }}>
            <Typography variant="subtitle2" color="text.secondary">Related person / next of kin</Typography>
            {contact ? (
              <Button size="small" onClick={() => setForm((current) => ({ ...current, contacts: [] }))}>Remove</Button>
            ) : (
              <Button size="small" onClick={() => setForm((current) => ({ ...current, contacts: [emptyContact()] }))}>Add relation</Button>
            )}
          </Box>

          {contact ? (
            <>
              <TextField select label="Contact type" value={contact.type} onChange={(event) => setContact({ type: event.target.value as PatientContact['type'] })}>
                <MenuItem value="next_of_kin">Next of kin</MenuItem>
                <MenuItem value="emergency">Emergency</MenuItem>
                <MenuItem value="other">Other</MenuItem>
              </TextField>
              <TextField select label="Relationship" value={contact.relationship ?? ''} onChange={(event) => setContact({ relationship: event.target.value || null })}>
                {relationLabels.map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
              </TextField>
              <TextField
                label="Search registered patient"
                value={relatedSearch}
                onChange={(event) => setRelatedSearch(event.target.value)}
                helperText="Link if this relation is already a patient"
                sx={{ gridColumn: { md: 'span 2' } }}
              />
              <TextField
                select
                label="Map to registered patient"
                value={contact.related_patient_id ?? ''}
                onChange={(event) => {
                  const value = event.target.value
                  if (!value) {
                    setContact({ related_patient_id: null })
                    return
                  }
                  linkRelatedPatient(value)
                }}
                sx={{ gridColumn: '1 / -1' }}
              >
                <MenuItem value="">Not registered / enter manually</MenuItem>
                {(relatedPatientsQuery.data ?? []).map((patient) => (
                  <MenuItem key={patient.id} value={patient.id}>
                    {patient.mrn} · {patient.name}{patient.phone ? ` · ${patient.phone}` : ''}
                  </MenuItem>
                ))}
              </TextField>
              <TextField
                required={!contact.related_patient_id}
                label="Full name"
                value={contact.full_name}
                onChange={(event) => setContact({ full_name: event.target.value })}
                disabled={Boolean(contact.related_patient_id)}
              />
              <TextField
                required={!contact.related_patient_id}
                label="Phone"
                value={contact.phone}
                onChange={(event) => setContact({ phone: event.target.value })}
                disabled={Boolean(contact.related_patient_id)}
              />
            </>
          ) : null}

          <Box sx={{ gridColumn: '1 / -1', display: 'flex', justifyContent: 'flex-end', gap: 1, mt: 1 }}>
            <Button onClick={() => navigate('/patients')}>Cancel</Button>
            <Button type="submit" variant="contained" disabled={saving}>{saving ? 'Registering…' : 'Register patient'}</Button>
          </Box>
        </Paper>

        <Stack spacing={2}>
          <Paper variant="outlined" sx={{ p: 3 }}>
            <Box sx={{ display: 'flex', gap: 2, alignItems: 'center', mb: 2 }}>
              <PatientPhoto previewUrl={photoPreview} name={preview.name} size={72} />
              <Box>
                <Typography variant="overline" color="text.secondary">Patient look</Typography>
                <Typography variant="h5">{preview.name}</Typography>
                <Typography variant="body2" color="text.secondary">
                  {[form.gender !== 'unknown' ? form.gender : null, preview.age, form.date_of_birth || null]
                    .filter(Boolean)
                    .join(' · ') || 'Fill identity fields to preview'}
                </Typography>
              </Box>
            </Box>
            <Divider sx={{ mb: 2 }} />
            <Stack spacing={1}>
              <Typography variant="body2"><strong>Phone</strong> · {form.phone || '—'}</Typography>
              <Typography variant="body2"><strong>Email</strong> · {form.email || '—'}</Typography>
              <Typography variant="body2"><strong>Address</strong> · {[form.address, form.state, form.country].filter(Boolean).join(', ') || '—'}</Typography>
              <Typography variant="body2"><strong>Occupation</strong> · {form.occupation || '—'}</Typography>
              <Typography variant="body2"><strong>Marital status</strong> · {form.marital_status || '—'}</Typography>
              <Typography variant="body2"><strong>Blood / genotype</strong> · {form.blood_group || '—'} / {form.genotype || '—'}</Typography>
              {contact ? (
                <Typography variant="body2">
                  <strong>Relation</strong> · {contact.relationship || contact.type}
                  {contact.full_name ? ` · ${contact.full_name}` : ''}
                  {contact.related_patient_id ? ' (mapped patient)' : ''}
                </Typography>
              ) : null}
            </Stack>
            <Typography variant="caption" color="text.secondary" sx={{ display: 'block', mt: 2 }}>
              MRN is assigned automatically after registration.
            </Typography>
          </Paper>

          {duplicates.length > 0 ? (
            <Paper variant="outlined" sx={{ p: 2, borderColor: 'warning.main' }}>
              <Typography variant="subtitle2" sx={{ mb: 1 }}>Possible matches</Typography>
              <Stack spacing={1.5}>
                {duplicates.map((patient) => (
                  <Box key={patient.id} sx={{ display: 'flex', gap: 1.5, alignItems: 'center' }}>
                    <PatientPhoto patientId={patient.id} photoUrl={patient.photo_url} name={patient.name} size={40} />
                    <Box>
                      <Typography variant="body2" sx={{ fontWeight: 600 }}>
                        <Link to={`/patients/${patient.id}`}>{patient.name}</Link>
                        {' · '}{patient.mrn}
                      </Typography>
                      <Typography variant="caption" color="text.secondary" sx={{ display: 'block' }}>
                        {[patient.date_of_birth, patient.gender, patient.phone, patient.blood_group].filter(Boolean).join(' · ')}
                      </Typography>
                    </Box>
                  </Box>
                ))}
              </Stack>
              <Alert severity="warning" sx={{ mt: 2 }}>Review these records before registering a new one.</Alert>
            </Paper>
          ) : null}
        </Stack>
      </Box>
    </>
  )
}
