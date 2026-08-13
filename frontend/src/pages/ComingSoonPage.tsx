import { Paper, Typography } from '@mui/material'
import { useSearchParams } from 'react-router-dom'

export function ComingSoonPage() {
  const [params] = useSearchParams()
  const moduleName = params.get('module') ?? 'This module'

  return (
    <Paper variant="outlined" sx={{ p: 4 }}>
      <Typography variant="h5" sx={{ mb: 1 }}>
        {moduleName}
      </Typography>
      <Typography color="text.secondary">
        This clinical or operational module is scheduled for a later development phase. The navigation
        is in place so the hospital workflow is visible, but the feature is not implemented yet.
      </Typography>
    </Paper>
  )
}
