import { useState } from 'react'
import { Alert, Box, Button, Checkbox, FormControlLabel, MenuItem, Paper, Table, TableBody, TableCell, TableHead, TableRow, TextField, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { createLabOrder, fetchLabOrders, fetchLabTests, fetchPatients } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'

export function LabOrdersPage() {
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [form, setForm] = useState({ patient_id: '', priority: 'routine', clinical_notes: '', lab_test_ids: [] as string[] })

  const ordersQuery = useQuery({ queryKey: ['lab-orders'], queryFn: () => fetchLabOrders({}) })
  const testsQuery = useQuery({ queryKey: ['lab-tests'], queryFn: fetchLabTests, enabled: can('lab.order') })
  const patientsQuery = useQuery({ queryKey: ['patients', ''], queryFn: () => fetchPatients(''), enabled: can('lab.order') })

  const createMutation = useMutation({
    mutationFn: () => createLabOrder(form),
    onSuccess: async () => {
      setError(null)
      setForm({ patient_id: '', priority: 'routine', clinical_notes: '', lab_test_ids: [] })
      await queryClient.invalidateQueries({ queryKey: ['lab-orders'] })
      await queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
    onError: (err) => setError(apiErrorMessage(err, 'Unable to create lab order.')),
  })

  const toggleTest = (id: string) => {
    setForm((current) => ({
      ...current,
      lab_test_ids: current.lab_test_ids.includes(id)
        ? current.lab_test_ids.filter((item) => item !== id)
        : [...current.lab_test_ids, id],
    }))
  }

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Lab orders</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Order tests, then open an order to collect specimens and enter results.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}

      {can('lab.order') ? (
        <Paper
          component="form"
          variant="outlined"
          sx={{ p: 2, mb: 3, display: 'grid', gap: 2 }}
          onSubmit={(event) => {
            event.preventDefault()
            if (!form.lab_test_ids.length) {
              setError('Select at least one lab test.')
              return
            }
            createMutation.mutate()
          }}
        >
          <Box sx={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))', gap: 2 }}>
            <TextField select required label="Patient" value={form.patient_id} onChange={(event) => setForm((current) => ({ ...current, patient_id: event.target.value }))}>
              {(patientsQuery.data ?? []).map((patient) => (
                <MenuItem key={patient.id} value={patient.id}>{patient.mrn} · {patient.name}</MenuItem>
              ))}
            </TextField>
            <TextField select label="Priority" value={form.priority} onChange={(event) => setForm((current) => ({ ...current, priority: event.target.value }))}>
              {['routine', 'urgent', 'stat'].map((value) => <MenuItem key={value} value={value}>{value}</MenuItem>)}
            </TextField>
            <TextField label="Clinical notes" value={form.clinical_notes} onChange={(event) => setForm((current) => ({ ...current, clinical_notes: event.target.value }))} />
          </Box>
          <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 1 }}>
            {(testsQuery.data ?? []).map((test) => (
              <FormControlLabel
                key={test.id}
                control={<Checkbox checked={form.lab_test_ids.includes(test.id)} onChange={() => toggleTest(test.id)} />}
                label={`${test.code} · ${test.name}`}
              />
            ))}
          </Box>
          <Box><Button type="submit" variant="contained" disabled={createMutation.isPending}>Create lab order</Button></Box>
        </Paper>
      ) : null}

      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Ordered</TableCell>
              <TableCell>Patient</TableCell>
              <TableCell>Priority</TableCell>
              <TableCell>Status</TableCell>
              <TableCell></TableCell>
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
                  <TableCell>{order.priority}</TableCell>
                  <TableCell>{order.status}</TableCell>
                  <TableCell><Button component={Link} to={`/laboratory/orders/${order.id}`} size="small">Open</Button></TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </Paper>
    </>
  )
}
