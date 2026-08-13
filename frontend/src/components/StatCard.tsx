import { Card, CardContent, Typography } from '@mui/material'

type StatCardProps = {
  label: string
  value: string | number
  hint?: string
}

export function StatCard({ label, value, hint }: StatCardProps) {
  return (
    <Card variant="outlined" sx={{ height: '100%' }}>
      <CardContent>
        <Typography variant="body2" color="text.secondary">
          {label}
        </Typography>
        <Typography variant="h5" sx={{ mt: 1, mb: 0.5 }}>
          {value}
        </Typography>
        {hint ? (
          <Typography variant="caption" color="text.secondary">
            {hint}
          </Typography>
        ) : null}
      </CardContent>
    </Card>
  )
}
