import { Box, Toolbar } from '@mui/material'
import { Outlet } from 'react-router-dom'
import { Sidebar } from '@/layouts/Sidebar'
import { TopBar } from '@/layouts/TopBar'

export function AppLayout() {
  return (
    <Box sx={{ display: 'flex', minHeight: '100vh', bgcolor: 'background.default' }}>
      <TopBar />
      <Sidebar />
      <Box component="main" sx={{ flexGrow: 1, p: { xs: 2, md: 3 } }}>
        <Toolbar />
        <Outlet />
      </Box>
    </Box>
  )
}
