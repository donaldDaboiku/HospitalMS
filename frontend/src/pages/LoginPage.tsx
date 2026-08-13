import { useState } from 'react'
import { Alert, Box, Button, Paper, TextField, Typography } from '@mui/material'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'
import { apiErrorMessage } from '@/services/http'

const schema = z.object({
  email: z.string().email(),
  password: z.string().min(1, 'Password is required'),
})

type FormValues = z.infer<typeof schema>

export function LoginPage() {
  const { login } = useAuth()
  const navigate = useNavigate()
  const [error, setError] = useState<string | null>(null)
  const {
    register,
    handleSubmit,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  return (
    <Box
      sx={{
        minHeight: '100vh',
        display: 'grid',
        placeItems: 'center',
        background: 'linear-gradient(135deg, #0F4C5C 0%, #1B7A6E 100%)',
        p: 2,
      }}
    >
      <Paper sx={{ width: '100%', maxWidth: 420, p: 4 }}>
        <Typography variant="h5" sx={{ mb: 0.5 }}>
          Sign in
        </Typography>
        <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
          Hospital Management System
        </Typography>
        {error ? (
          <Alert severity="error" sx={{ mb: 2 }}>
            {error}
          </Alert>
        ) : null}
        <Box
          component="form"
          sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}
          onSubmit={handleSubmit(async (values) => {
          setError(null)
          try {
            await login(values.email, values.password)
            navigate('/')
          } catch (err) {
            setError(apiErrorMessage(err, 'Unable to sign in.'))
          }
        })}>
          <TextField
            label="Email"
            autoComplete="email"
            error={Boolean(errors.email)}
            helperText={errors.email?.message}
            {...register('email')}
          />
          <TextField
            label="Password"
            type="password"
            autoComplete="current-password"
            error={Boolean(errors.password)}
            helperText={errors.password?.message}
            {...register('password')}
          />
          <Button type="submit" variant="contained" size="large" disabled={isSubmitting}>
            {isSubmitting ? 'Signing in…' : 'Sign in'}
          </Button>
        </Box>
      </Paper>
    </Box>
  )
}
