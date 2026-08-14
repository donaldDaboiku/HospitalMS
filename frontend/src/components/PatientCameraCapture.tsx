import { useEffect, useRef, useState } from 'react'
import { Alert, Box, Button, Dialog, DialogActions, DialogContent, DialogTitle, Stack, Typography } from '@mui/material'

type PatientCameraCaptureProps = {
  onCapture: (photo: File) => void
  disabled?: boolean
}

function stopStream(stream: MediaStream | null): void {
  stream?.getTracks().forEach((track) => track.stop())
}

export function PatientCameraCapture({ onCapture, disabled = false }: PatientCameraCaptureProps) {
  const videoRef = useRef<HTMLVideoElement>(null)
  const streamRef = useRef<MediaStream | null>(null)
  const [open, setOpen] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const close = () => {
    stopStream(streamRef.current)
    streamRef.current = null
    setOpen(false)
  }

  useEffect(() => () => stopStream(streamRef.current), [])

  const startCamera = async () => {
    setError(null)

    if (!navigator.mediaDevices?.getUserMedia) {
      setError('Camera capture is unavailable in this browser. Upload a photo instead.')
      return
    }

    setOpen(true)

    try {
      const stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
        audio: false,
      })
      streamRef.current = stream

      if (videoRef.current) {
        videoRef.current.srcObject = stream
        await videoRef.current.play()
      }
    } catch {
      setError('Camera access was denied or no camera is available. Upload a photo instead.')
    }
  }

  const capture = () => {
    const video = videoRef.current
    if (!video || video.videoWidth === 0 || video.videoHeight === 0) {
      setError('Camera is still starting. Try again in a moment.')
      return
    }

    const canvas = document.createElement('canvas')
    canvas.width = video.videoWidth
    canvas.height = video.videoHeight
    canvas.getContext('2d')?.drawImage(video, 0, 0, canvas.width, canvas.height)
    canvas.toBlob((blob) => {
      if (!blob) {
        setError('Unable to capture the photo. Please try again.')
        return
      }

      onCapture(new File([blob], `patient-camera-${Date.now()}.jpg`, { type: 'image/jpeg' }))
      close()
    }, 'image/jpeg', 0.9)
  }

  return (
    <>
      <Button variant="outlined" onClick={startCamera} disabled={disabled}>Use camera</Button>
      {error && !open ? <Alert severity="warning" sx={{ width: '100%' }}>{error}</Alert> : null}
      <Dialog open={open} onClose={close} fullWidth maxWidth="sm">
        <DialogTitle>Capture patient photo</DialogTitle>
        <DialogContent>
          <Stack spacing={2}>
            <Typography variant="body2" color="text.secondary">
              Centre the patient’s face, ensure good lighting, then capture. This camera stream is used only for this photo.
            </Typography>
            {error ? <Alert severity="warning">{error}</Alert> : null}
            <Box sx={{ bgcolor: 'grey.900', borderRadius: 1, overflow: 'hidden', aspectRatio: '4 / 3' }}>
              <video ref={videoRef} autoPlay muted playsInline style={{ width: '100%', height: '100%', objectFit: 'cover' }} />
            </Box>
          </Stack>
        </DialogContent>
        <DialogActions>
          <Button onClick={close}>Cancel</Button>
          <Button variant="contained" onClick={capture}>Capture photo</Button>
        </DialogActions>
      </Dialog>
    </>
  )
}
