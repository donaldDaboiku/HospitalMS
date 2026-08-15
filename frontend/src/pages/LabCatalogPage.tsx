import { Alert, Paper, Table, TableBody, TableCell, TableHead, TableRow, Typography } from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { fetchLabTests } from '@/services/api'

export function LabCatalogPage() {
  const { data, error, isFetching } = useQuery({ queryKey: ['lab-tests'], queryFn: fetchLabTests })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>Lab test catalog</Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Active tests available for ordering in this hospital.
      </Typography>
      {error ? <Alert severity="error" sx={{ mb: 2 }}>Unable to load the lab catalog.</Alert> : null}
      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Code</TableCell>
              <TableCell>Name</TableCell>
              <TableCell>Category</TableCell>
              <TableCell>Specimen</TableCell>
              <TableCell>Unit / range</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {isFetching ? (
              <TableRow><TableCell colSpan={5}>Loading…</TableCell></TableRow>
            ) : (
              data?.map((test) => (
                <TableRow key={test.id}>
                  <TableCell>{test.code}</TableCell>
                  <TableCell>{test.name}</TableCell>
                  <TableCell>{test.category ?? '—'}</TableCell>
                  <TableCell>{test.specimen_type ?? '—'}</TableCell>
                  <TableCell>{[test.unit, test.reference_range].filter(Boolean).join(' · ') || '—'}</TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </Paper>
    </>
  )
}
