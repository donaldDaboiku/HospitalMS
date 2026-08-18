import { useState } from 'react'
import { Alert, Box, Button, Chip, Dialog, DialogActions, DialogContent, DialogTitle, MenuItem, Paper, Table, TableBody, TableCell, TableHead, TableRow, TextField, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createInvoice, fetchInvoices, fetchPatients, issueInvoice, recordPayment } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'
import type { Invoice } from '@/types/api'

const statusColor: Record<string, 'default' | 'warning' | 'success' | 'error' | 'info'> = {
  draft: 'default', issued: 'info', partial: 'warning', paid: 'success', cancelled: 'error', refunded: 'error',
}

export function BillingPage() {
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [payDialog, setPayDialog] = useState<Invoice | null>(null)
  const [payForm, setPayForm] = useState({ amount: '', method: 'cash', reference: '' })
  const [form, setForm] = useState({ patient_id: '', category: 'consultation', description: '', quantity: '1', unit_price: '' })

  const patients = useQuery({ queryKey: ['patients', ''], queryFn: () => fetchPatients(''), enabled: can('billing.create') })
  const invoices = useQuery({ queryKey: ['invoices'], queryFn: () => fetchInvoices({}) })

  const create = useMutation({
    mutationFn: () => createInvoice({
      patient_id: form.patient_id,
      items: [{ category: form.category, description: form.description, quantity: Number(form.quantity), unit_price: Number(form.unit_price) }],
    }),
    onSuccess: async () => { setError(null); setForm((v) => ({ ...v, description: '', unit_price: '' })); await queryClient.invalidateQueries({ queryKey: ['invoices'] }) },
    onError: (err) => setError(apiErrorMessage(err, 'Failed to create invoice.')),
  })

  const pay = useMutation({
    mutationFn: () => recordPayment(payDialog!.id, { amount: Number(payForm.amount), method: payForm.method, reference: payForm.reference || undefined }),
    onSuccess: async () => { setPayDialog(null); await queryClient.invalidateQueries({ queryKey: ['invoices'] }) },
    onError: (err) => setError(apiErrorMessage(err, 'Failed to record payment.')),
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Billing</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Create invoices, issue them, and record payments.</Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }} onClose={() => setError(null)}>{error}</Alert> : null}

      {can('billing.create') ? (
        <Paper component="form" variant="outlined" sx={{ p: 2, mb: 3, display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 2 }} onSubmit={(event) => { event.preventDefault(); create.mutate() }}>
          <TextField select required label="Patient" value={form.patient_id} onChange={(event) => setForm((v) => ({ ...v, patient_id: event.target.value }))}>
            {(patients.data ?? []).map((patient) => <MenuItem key={patient.id} value={patient.id}>{patient.mrn} · {patient.name}</MenuItem>)}
          </TextField>
          <TextField select label="Category" value={form.category} onChange={(event) => setForm((v) => ({ ...v, category: event.target.value }))}>
            {['consultation', 'lab', 'radiology', 'pharmacy', 'procedure', 'ward', 'other'].map((cat) => <MenuItem key={cat} value={cat}>{cat}</MenuItem>)}
          </TextField>
          <TextField required label="Description" value={form.description} onChange={(event) => setForm((v) => ({ ...v, description: event.target.value }))} />
          <TextField required type="number" label="Qty" slotProps={{ htmlInput: { min: 1 } }} value={form.quantity} onChange={(event) => setForm((v) => ({ ...v, quantity: event.target.value }))} />
          <TextField required type="number" label="Unit price" slotProps={{ htmlInput: { min: 0, step: 0.01 } }} value={form.unit_price} onChange={(event) => setForm((v) => ({ ...v, unit_price: event.target.value }))} />
          <Box><Button type="submit" variant="contained" disabled={create.isPending}>Create invoice</Button></Box>
        </Paper>
      ) : null}

      <Paper variant="outlined">
        <Table size="small">
          <TableHead><TableRow><TableCell>#</TableCell><TableCell>Patient</TableCell><TableCell>Total</TableCell><TableCell>Paid</TableCell><TableCell>Status</TableCell><TableCell>Actions</TableCell></TableRow></TableHead>
          <TableBody>
            {invoices.isFetching ? <TableRow><TableCell colSpan={6}>Loading…</TableCell></TableRow> : invoices.data?.map((invoice) => (
              <TableRow key={invoice.id}>
                <TableCell>{invoice.invoice_number}</TableCell>
                <TableCell>{invoice.patient?.name}</TableCell>
                <TableCell>{invoice.total}</TableCell>
                <TableCell>{invoice.amount_paid}</TableCell>
                <TableCell><Chip size="small" label={invoice.status} color={statusColor[invoice.status] ?? 'default'} /></TableCell>
                <TableCell>
                  {invoice.status === 'draft' && can('billing.create') ? <Button size="small" onClick={async () => { try { await issueInvoice(invoice.id); await queryClient.invalidateQueries({ queryKey: ['invoices'] }) } catch (err) { setError(apiErrorMessage(err, 'Unable to issue.')) } }}>Issue</Button> : null}
                  {['issued', 'partial'].includes(invoice.status) && can('payment.create') ? <Button size="small" onClick={() => { setPayForm({ amount: String(Number(invoice.total) - Number(invoice.amount_paid)), method: 'cash', reference: '' }); setPayDialog(invoice) }}>Pay</Button> : null}
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Paper>

      <Dialog open={!!payDialog} onClose={() => setPayDialog(null)} maxWidth="xs" fullWidth>
        <DialogTitle>Record payment — {payDialog?.invoice_number}</DialogTitle>
        <DialogContent sx={{ display: 'flex', flexDirection: 'column', gap: 2, pt: '16px !important' }}>
          <TextField required type="number" label="Amount" slotProps={{ htmlInput: { min: 0.01, step: 0.01 } }} value={payForm.amount} onChange={(event) => setPayForm((v) => ({ ...v, amount: event.target.value }))} />
          <TextField select label="Method" value={payForm.method} onChange={(event) => setPayForm((v) => ({ ...v, method: event.target.value }))}>
            {['cash', 'card', 'transfer', 'mobile', 'cheque'].map((m) => <MenuItem key={m} value={m}>{m}</MenuItem>)}
          </TextField>
          <TextField label="Reference" value={payForm.reference} onChange={(event) => setPayForm((v) => ({ ...v, reference: event.target.value }))} />
        </DialogContent>
        <DialogActions>
          <Button onClick={() => setPayDialog(null)}>Cancel</Button>
          <Button variant="contained" disabled={pay.isPending} onClick={() => pay.mutate()}>Record payment</Button>
        </DialogActions>
      </Dialog>
    </>
  )
}
