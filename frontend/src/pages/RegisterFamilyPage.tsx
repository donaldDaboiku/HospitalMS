import { useState } from 'react'
import { Alert, Box, Button, Divider, MenuItem, Paper, Stack, TextField, Typography } from '@mui/material'
import { Link, useNavigate } from 'react-router-dom'
import { registerFamily } from '@/services/api'
import { apiErrorMessage } from '@/services/http'

const relationLabels = ['Spouse', 'Parent', 'Child', 'Sibling', 'Guardian', 'Dependent', 'Other'] as const

type FamilyPerson = {
  first_name: string
  middle_name: string | null
  last_name: string
  date_of_birth: string
  gender: string
  phone: string | null
  email: string | null
  address: string | null
  state: string | null
  country: string
  occupation: string | null
  marital_status: string | null
  blood_group: string | null
  genotype: string | null
}

type FamilyMember = FamilyPerson & { relationship_to_primary: string }

const emptyPerson = (): FamilyPerson => ({
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
})

const emptyMember = (): FamilyMember => ({
  ...emptyPerson(),
  relationship_to_primary: 'Spouse',
})

function PersonFields({
  value,
  onChange,
  title,
}: {
  value: FamilyPerson
  onChange: (patch: Partial<FamilyPerson>) => void
  title: string
}) {
  return (
    <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 2 }}>
      <Typography variant="subtitle2" color="text.secondary" sx={{ gridColumn: '1 / -1' }}>{title}</Typography>
      <TextField required label="First name" value={value.first_name} onChange={(event) => onChange({ first_name: event.target.value })} />
      <TextField label="Middle name" value={value.middle_name ?? ''} onChange={(event) => onChange({ middle_name: event.target.value || null })} />
      <TextField required label="Last name" value={value.last_name} onChange={(event) => onChange({ last_name: event.target.value })} />
      <TextField required type="date" label="Date of birth" slotProps={{ inputLabel: { shrink: true } }} value={value.date_of_birth} onChange={(event) => onChange({ date_of_birth: event.target.value })} />
      <TextField required select label="Gender" value={value.gender} onChange={(event) => onChange({ gender: event.target.value })}>
        {['male', 'female', 'other', 'unknown'].map((item) => <MenuItem key={item} value={item}>{item}</MenuItem>)}
      </TextField>
      <TextField label="Phone" value={value.phone ?? ''} onChange={(event) => onChange({ phone: event.target.value || null })} />
      <TextField label="Email" type="email" value={value.email ?? ''} onChange={(event) => onChange({ email: event.target.value || null })} />
      <TextField label="Address" value={value.address ?? ''} onChange={(event) => onChange({ address: event.target.value || null })} sx={{ gridColumn: { md: 'span 2' } }} />
    </Box>
  )
}

export function RegisterFamilyPage() {
  const navigate = useNavigate()
  const [primary, setPrimary] = useState<FamilyPerson>(emptyPerson)
  const [members, setMembers] = useState<FamilyMember[]>([emptyMember()])
  const [error, setError] = useState<string | null>(null)
  const [saving, setSaving] = useState(false)

  const updateMember = (index: number, patch: Partial<FamilyMember>) => {
    setMembers((current) => current.map((member, i) => (i === index ? { ...member, ...patch } : member)))
  }

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Register family</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Register a primary patient and one or more family members. Relationships are mapped both ways automatically.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}

      <Paper
        component="form"
        variant="outlined"
        sx={{ p: 3 }}
        onSubmit={async (event) => {
          event.preventDefault()
          setSaving(true)
          setError(null)
          try {
            const family = await registerFamily({ primary, members })
            navigate(`/patients/${family.primary.id}`)
          } catch (err) {
            setError(apiErrorMessage(err, 'Unable to register the family.'))
          } finally {
            setSaving(false)
          }
        }}
      >
        <PersonFields value={primary} title="Primary patient" onChange={(patch) => setPrimary((current) => ({ ...current, ...patch }))} />

        <Divider sx={{ my: 3 }} />

        <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
          <Typography variant="h6">Family members</Typography>
          <Button
            size="small"
            disabled={members.length >= 10}
            onClick={() => setMembers((current) => [
              ...current,
              {
                ...emptyMember(),
                last_name: primary.last_name || '',
                address: primary.address,
                state: primary.state,
                country: primary.country,
              },
            ])}
          >
            Add member
          </Button>
        </Box>

        <Stack spacing={3}>
          {members.map((member, index) => (
            <Paper key={index} variant="outlined" sx={{ p: 2 }}>
              <Box sx={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', mb: 2 }}>
                <Typography variant="subtitle1">Member {index + 1}</Typography>
                {members.length > 1 ? (
                  <Button size="small" color="inherit" onClick={() => setMembers((current) => current.filter((_, i) => i !== index))}>
                    Remove
                  </Button>
                ) : null}
              </Box>
              <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 2, mb: 2 }}>
                <TextField
                  select
                  required
                  label="Relationship to primary"
                  value={member.relationship_to_primary}
                  onChange={(event) => updateMember(index, { relationship_to_primary: event.target.value })}
                >
                  {relationLabels.map((item) => <MenuItem key={item} value={item}>{item}</MenuItem>)}
                </TextField>
              </Box>
              <PersonFields value={member} title="Details" onChange={(patch) => updateMember(index, patch)} />
            </Paper>
          ))}
        </Stack>

        <Box sx={{ display: 'flex', justifyContent: 'flex-end', gap: 1, mt: 3 }}>
          <Button component={Link} to="/patients/register">Single patient</Button>
          <Button onClick={() => navigate('/patients')}>Cancel</Button>
          <Button type="submit" variant="contained" disabled={saving}>
            {saving ? 'Registering family…' : 'Register family'}
          </Button>
        </Box>
      </Paper>
    </>
  )
}
