import { useState } from 'react'
import { Alert, Box, Button, MenuItem, Paper, TextField, Typography } from '@mui/material'
import { useQuery, useQueryClient } from '@tanstack/react-query'
import { Link, useParams } from 'react-router-dom'
import { collectLabSpecimen, enterLabResult, fetchLabOrder, verifyLabResult } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'

export function LabOrderDetailPage() {
  const { id = '' } = useParams()
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [specimenType, setSpecimenType] = useState('Serum')
  const [results, setResults] = useState<Record<string, { value: string; flag: string }>>({})

  const orderQuery = useQuery({
    queryKey: ['lab-order', id],
    queryFn: () => fetchLabOrder(id),
    enabled: Boolean(id),
  })

  const refresh = async () => {
    await queryClient.invalidateQueries({ queryKey: ['lab-order', id] })
    await queryClient.invalidateQueries({ queryKey: ['lab-orders'] })
    await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
  }

  if (orderQuery.error) return <Alert severity="error">Unable to load this lab order.</Alert>
  if (!orderQuery.data) return <Typography>Loading…</Typography>

  const order = orderQuery.data

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Lab order · {order.patient?.name}</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        {order.status} · {order.priority} · Ordered {new Date(order.ordered_at).toLocaleString()}
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}

      {can('lab.collect') && order.status === 'ordered' ? (
        <Paper sx={{ p: 2, mb: 2 }} variant="outlined">
          <Typography variant="h6" sx={{ mb: 2 }}>Collect specimen</Typography>
          <Box sx={{ display: 'flex', gap: 2, flexWrap: 'wrap' }}>
            <TextField label="Specimen type" value={specimenType} onChange={(event) => setSpecimenType(event.target.value)} />
            <Button
              variant="contained"
              onClick={async () => {
                try {
                  await collectLabSpecimen(order.id, { specimen_type: specimenType })
                  setError(null)
                  await refresh()
                } catch (err) {
                  setError(apiErrorMessage(err, 'Unable to collect specimen.'))
                }
              }}
            >
              Mark collected
            </Button>
          </Box>
        </Paper>
      ) : null}

      {(order.items ?? []).map((item) => (
        <Paper key={item.id} sx={{ p: 2, mb: 2 }} variant="outlined">
          <Typography variant="h6">{item.test?.code} · {item.test?.name}</Typography>
          <Typography variant="body2" color="text.secondary" sx={{ mb: 1 }}>
            Item status: {item.status}
            {item.result ? ` · Result ${item.result.value}${item.result.unit ? ` ${item.result.unit}` : ''} (${item.result.status})` : ''}
          </Typography>

          {can('lab.result') && ['collected', 'in_progress', 'completed'].includes(order.status) && item.result?.status !== 'final' ? (
            <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(140px, 1fr))', gap: 2, mt: 1 }}>
              <TextField
                label="Value"
                value={results[item.id]?.value ?? item.result?.value ?? ''}
                onChange={(event) => setResults((current) => ({
                  ...current,
                  [item.id]: { value: event.target.value, flag: current[item.id]?.flag ?? item.result?.flag ?? 'normal' },
                }))}
              />
              <TextField
                select
                label="Flag"
                value={results[item.id]?.flag ?? item.result?.flag ?? 'normal'}
                onChange={(event) => setResults((current) => ({
                  ...current,
                  [item.id]: { value: current[item.id]?.value ?? item.result?.value ?? '', flag: event.target.value },
                }))}
              >
                {['normal', 'low', 'high', 'critical', 'abnormal'].map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
              </TextField>
              <Button
                variant="contained"
                onClick={async () => {
                  try {
                    const payload = results[item.id] ?? { value: item.result?.value ?? '', flag: item.result?.flag ?? 'normal' }
                    await enterLabResult(item.id, payload)
                    setError(null)
                    await refresh()
                  } catch (err) {
                    setError(apiErrorMessage(err, 'Unable to save result.'))
                  }
                }}
              >
                Save result
              </Button>
            </Box>
          ) : null}

          {can('lab.verify') && item.result && item.result.status === 'preliminary' ? (
            <Button
              sx={{ mt: 1 }}
              variant="outlined"
              onClick={async () => {
                try {
                  await verifyLabResult(item.result!.id)
                  setError(null)
                  await refresh()
                } catch (err) {
                  setError(apiErrorMessage(err, 'Unable to verify result.'))
                }
              }}
            >
              Verify result
            </Button>
          ) : null}
        </Paper>
      ))}

      <Typography component={Link} to="/laboratory/orders">Back to lab orders</Typography>
    </>
  )
}
