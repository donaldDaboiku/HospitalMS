import { useState } from 'react'
import { Alert, Box, Button, MenuItem, Paper, Table, TableBody, TableCell, TableHead, TableRow, TextField, Typography } from '@mui/material'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { createPrescription, dispensePrescriptionItem, fetchPatients, fetchPrescriptions, fetchProducts } from '@/services/api'
import { apiErrorMessage } from '@/services/http'
import { useAuth } from '@/hooks/useAuth'

export function PharmacyPage() {
  const { can } = useAuth()
  const queryClient = useQueryClient()
  const [error, setError] = useState<string | null>(null)
  const [form, setForm] = useState({ patient_id: '', product_id: '', quantity: '1', dose: '', frequency: '' })
  const products = useQuery({ queryKey: ['products'], queryFn: () => fetchProducts({}) })
  const patients = useQuery({ queryKey: ['patients', ''], queryFn: () => fetchPatients(''), enabled: can('pharmacy.prescribe') })
  const prescriptions = useQuery({ queryKey: ['prescriptions'], queryFn: () => fetchPrescriptions({}) })
  const prescribe = useMutation({
    mutationFn: () => createPrescription({ patient_id: form.patient_id, items: [{ product_id: form.product_id, quantity_prescribed: Number(form.quantity), dose: form.dose || undefined, frequency: form.frequency || undefined }] }),
    onSuccess: async () => { setError(null); await queryClient.invalidateQueries({ queryKey: ['prescriptions'] }) },
    onError: (err) => setError(apiErrorMessage(err, 'Unable to create prescription.')),
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Pharmacy</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>Prescribe medicines and dispense only from non-expired available stock.</Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>{error}</Alert> : null}
      {can('pharmacy.prescribe') ? (
        <Paper component="form" variant="outlined" sx={{ p: 2, mb: 3, display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(180px, 1fr))', gap: 2 }} onSubmit={(event) => { event.preventDefault(); prescribe.mutate() }}>
          <TextField select required label="Patient" value={form.patient_id} onChange={(event) => setForm((v) => ({ ...v, patient_id: event.target.value }))}>
            {(patients.data ?? []).map((patient) => <MenuItem key={patient.id} value={patient.id}>{patient.mrn} · {patient.name}</MenuItem>)}
          </TextField>
          <TextField select required label="Medicine" value={form.product_id} onChange={(event) => setForm((v) => ({ ...v, product_id: event.target.value }))}>
            {(products.data ?? []).map((product) => <MenuItem key={product.id} value={product.id}>{product.name} · stock {product.stock_available ?? 0}</MenuItem>)}
          </TextField>
          <TextField required type="number" label="Quantity" slotProps={{ htmlInput: { min: 1 } }} value={form.quantity} onChange={(event) => setForm((v) => ({ ...v, quantity: event.target.value }))} />
          <TextField label="Dose" value={form.dose} onChange={(event) => setForm((v) => ({ ...v, dose: event.target.value }))} />
          <TextField label="Frequency" value={form.frequency} onChange={(event) => setForm((v) => ({ ...v, frequency: event.target.value }))} />
          <Box><Button type="submit" variant="contained" disabled={prescribe.isPending}>Prescribe</Button></Box>
        </Paper>
      ) : null}
      <Paper variant="outlined">
        <Table><TableHead><TableRow><TableCell>Patient</TableCell><TableCell>Medicines</TableCell><TableCell>Status</TableCell><TableCell>Dispense</TableCell></TableRow></TableHead>
          <TableBody>{prescriptions.isFetching ? <TableRow><TableCell colSpan={4}>Loading…</TableCell></TableRow> : prescriptions.data?.map((prescription) => (
            <TableRow key={prescription.id}><TableCell>{prescription.patient?.name}</TableCell><TableCell>{prescription.items?.map((item) => `${item.product?.name} (${item.quantity_dispensed}/${item.quantity_prescribed})`).join(', ')}</TableCell><TableCell>{prescription.status}</TableCell><TableCell>{can('pharmacy.dispense') ? prescription.items?.filter((item) => item.status !== 'dispensed').map((item) => <Button key={item.id} size="small" onClick={async () => { try { await dispensePrescriptionItem(item.id, item.quantity_prescribed - item.quantity_dispensed); await queryClient.invalidateQueries({ queryKey: ['prescriptions'] }); await queryClient.invalidateQueries({ queryKey: ['products'] }) } catch (err) { setError(apiErrorMessage(err, 'Unable to dispense medicine.')) } }}>Dispense {item.product?.name}</Button>) : '—'}</TableCell></TableRow>
          ))}</TableBody>
        </Table>
      </Paper>
    </>
  )
}
