import { useState } from 'react'
import { Alert, Box, Button, MenuItem, Paper, Table, TableBody, TableCell, TableHead, TableRow, TextField, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createRadiologyOrder, fetchPatients, fetchRadiologyOrders, saveRadiologyReport } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'

export function RadiologyOrdersPage() {
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [form, setForm] = useState({ patient_id: '', modality: 'XRAY', study_name: '', clinical_indication: '', priority: 'routine' })
  const [reportDrafts, setReportDrafts] = useState<Record<string, { findings: string; impression: string }>>({})

  const ordersQuery = useQuery({ queryKey: ['radiology-orders'], queryFn: () => fetchRadiologyOrders({}) })
  const patientsQuery = useQuery({ queryKey: ['patients', ''], queryFn: () => fetchPatients(''), enabled: can('radiology.order') })

  const createMutation = useMutation({
    mutationFn: () => createRadiologyOrder(form),
    onSuccess: async () => {
      setError(null)
      setForm({ patient_id: '', modality: 'XRAY', study_name: '', clinical_indication: '', priority: 'routine' })
      await queryClient.invalidateQueries({ queryKey: ['radiology-orders'] })
    },
    onError: (err) => setError(apiErrorMessage(err, 'Unable to create radiology order.')),
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Radiology orders</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Order imaging studies and attach reports.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}

      {can('radiology.order') ? (
        <Paper
          component="form"
          variant="outlined"
          sx={{ p: 2, mb: 3, display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 2 }}
          onSubmit={(event) => {
            event.preventDefault()
            createMutation.mutate()
          }}
        >
          <TextField select required label="Patient" value={form.patient_id} onChange={(event) => setForm((current) => ({ ...current, patient_id: event.target.value }))}>
            {(patientsQuery.data ?? []).map((patient) => (
              <MenuItem key={patient.id} value={patient.id}>{patient.mrn} · {patient.name}</MenuItem>
            ))}
          </TextField>
          <TextField select label="Modality" value={form.modality} onChange={(event) => setForm((current) => ({ ...current, modality: event.target.value }))}>
            {['XRAY', 'CT', 'MRI', 'US', 'MG', 'FLUORO', 'OTHER'].map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
          </TextField>
          <TextField required label="Study name" value={form.study_name} onChange={(event) => setForm((current) => ({ ...current, study_name: event.target.value }))} />
          <TextField label="Indication" value={form.clinical_indication} onChange={(event) => setForm((current) => ({ ...current, clinical_indication: event.target.value }))} />
          <Box sx={{ display: 'flex', alignItems: 'center' }}>
            <Button type="submit" variant="contained" disabled={createMutation.isPending}>Create order</Button>
          </Box>
        </Paper>
      ) : null}

      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Ordered</TableCell>
              <TableCell>Patient</TableCell>
              <TableCell>Study</TableCell>
              <TableCell>Status</TableCell>
              <TableCell>Report</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {ordersQuery.isFetching ? (
              <TableRow><TableCell colSpan={5}>Loading…</TableCell></TableRow>
            ) : (
              ordersQuery.data?.map((order) => (
                <TableRow key={order.id}>
                  <TableCell>{new Date(order.ordered_at).toLocaleString()}</TableCell>
                  <TableCell>{order.patient?.name}</TableCell>
                  <TableCell>{order.modality} · {order.study_name}</TableCell>
                  <TableCell>{order.status}</TableCell>
                  <TableCell>
                    {order.report ? (
                      <Typography variant="body2">{order.report.impression ?? order.report.findings}</Typography>
                    ) : can('radiology.report') ? (
                      <Box sx={{ display: 'grid', gap: 1, minWidth: 220 }}>
                        <TextField
                          size="small"
                          label="Findings"
                          value={reportDrafts[order.id]?.findings ?? ''}
                          onChange={(event) => setReportDrafts((current) => ({
                            ...current,
                            [order.id]: { findings: event.target.value, impression: current[order.id]?.impression ?? '' },
                          }))}
                        />
                        <TextField
                          size="small"
                          label="Impression"
                          value={reportDrafts[order.id]?.impression ?? ''}
                          onChange={(event) => setReportDrafts((current) => ({
                            ...current,
                            [order.id]: { findings: current[order.id]?.findings ?? '', impression: event.target.value },
                          }))}
                        />
                        <Button
                          size="small"
                          variant="outlined"
                          onClick={async () => {
                            try {
                              const draft = reportDrafts[order.id]
                              if (!draft?.findings) {
                                setError('Findings are required.')
                                return
                              }
                              await saveRadiologyReport(order.id, draft)
                              setError(null)
                              await queryClient.invalidateQueries({ queryKey: ['radiology-orders'] })
                            } catch (err) {
                              setError(apiErrorMessage(err, 'Unable to save report.'))
                            }
                          }}
                        >
                          Save report
                        </Button>
                      </Box>
                    ) : '—'}
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </Paper>
    </>
  )
}
