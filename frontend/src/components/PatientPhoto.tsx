import { useEffect, useState } from 'react'
import { Avatar, Box } from '@mui/material'
import { http } from '@/services/http'

type PatientPhotoProps = {
  patientId?: string | null
  photoUrl?: string | null
  previewUrl?: string | null
  name?: string
  size?: number
}

export function PatientPhoto({ patientId, photoUrl, previewUrl, name = 'Patient', size = 96 }: PatientPhotoProps) {
  const [src, setSrc] = useState<string | null>(previewUrl ?? null)

  useEffect(() => {
    if (previewUrl) {
      setSrc(previewUrl)
      return
    }

    if (!photoUrl || !patientId) {
      setSrc(null)
      return
    }

    let objectUrl: string | null = null
    let cancelled = false

    http.get(`/patients/${patientId}/photo`, { responseType: 'blob' })
      .then((response) => {
        if (cancelled) return
        objectUrl = URL.createObjectURL(response.data)
        setSrc(objectUrl)
      })
      .catch(() => {
        if (!cancelled) setSrc(null)
      })

    return () => {
      cancelled = true
      if (objectUrl) URL.revokeObjectURL(objectUrl)
    }
  }, [patientId, photoUrl, previewUrl])

  const initials = name
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase() ?? '')
    .join('')

  return (
    <Box sx={{ width: size, height: size, flexShrink: 0 }}>
      <Avatar
        src={src ?? undefined}
        alt={name}
        sx={{ width: size, height: size, fontSize: size * 0.32, bgcolor: 'primary.main' }}
      >
        {initials || '?'}
      </Avatar>
    </Box>
  )
}
