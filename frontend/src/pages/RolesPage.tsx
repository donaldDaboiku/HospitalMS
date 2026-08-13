import { Alert, Box, Chip, Paper, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { fetchRoles } from '@/services/api'

export function RolesPage() {
  const { data, error } = useQuery({
    queryKey: ['roles'],
    queryFn: fetchRoles,
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 3 }}>
        Roles & permissions
      </Typography>
      {error ? <Alert severity="error">Unable to load roles.</Alert> : null}
      <Box sx={{ display: 'flex', flexDirection: 'column', gap: 2 }}>
        {data?.map((role) => (
          <Paper key={role.id} variant="outlined" sx={{ p: 2 }}>
            <Typography variant="subtitle1" sx={{ mb: 1 }}>
              {role.name}
            </Typography>
            <Box sx={{ display: 'flex', flexDirection: 'row', gap: 1, flexWrap: 'wrap' }}>
              {role.permissions.length === 0 ? (
                <Typography variant="body2" color="text.secondary">
                  No staff permissions
                </Typography>
              ) : (
                role.permissions.map((permission) => <Chip key={permission} size="small" label={permission} />)
              )}
            </Box>
          </Paper>
        ))}
      </Box>
    </>
  )
}
