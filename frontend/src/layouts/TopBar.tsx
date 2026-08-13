import { AppBar, Box, Button, Toolbar, Typography } from '@mui/material'
import { useNavigate } from 'react-router-dom'
import { useAuth } from '@/hooks/useAuth'

export function TopBar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  return (
    <AppBar position="fixed" color="inherit" sx={{ zIndex: (theme) => theme.zIndex.drawer + 1 }}>
      <Toolbar>
        <Typography variant="subtitle1" sx={{ fontWeight: 700 }}>
          {user?.hospital?.name ?? 'Hospital Management System'}
        </Typography>
        <Box sx={{ flexGrow: 1 }} />
        <Box sx={{ display: 'flex', flexDirection: 'row', gap: 2, alignItems: 'center' }}>
          <Box sx={{ textAlign: 'right' }}>
            <Typography variant="body2" sx={{ fontWeight: 600 }}>
              {user?.name}
            </Typography>
            <Typography variant="caption" color="text.secondary">
              {user?.roles.join(', ')}
            </Typography>
          </Box>
          <Button
            variant="outlined"
            size="small"
            onClick={async () => {
              await logout()
              navigate('/login')
            }}
          >
            Logout
          </Button>
        </Box>
      </Toolbar>
    </AppBar>
  )
}
