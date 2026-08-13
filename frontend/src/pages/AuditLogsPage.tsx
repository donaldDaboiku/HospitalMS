import {
  Alert,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { fetchAuditLogs } from '@/services/api'

export function AuditLogsPage() {
  const { data, error } = useQuery({
    queryKey: ['audit-logs'],
    queryFn: fetchAuditLogs,
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 1 }}>
        Audit logs
      </Typography>
      <Typography variant="body2" color="text.secondary" sx={{ mb: 3 }}>
        Sensitive actions are recorded and cannot be deleted by ordinary users.
      </Typography>
      {error ? <Alert severity="error">Unable to load audit logs.</Alert> : null}
      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Time</TableCell>
              <TableCell>User</TableCell>
              <TableCell>Module</TableCell>
              <TableCell>Action</TableCell>
              <TableCell>IP</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {data?.map((log) => (
              <TableRow key={log.id}>
                <TableCell>{new Date(log.created_at).toLocaleString()}</TableCell>
                <TableCell>{log.user ? `${log.user.first_name} ${log.user.last_name}` : 'System'}</TableCell>
                <TableCell>{log.module}</TableCell>
                <TableCell>{log.action}</TableCell>
                <TableCell>{log.ip_address ?? '—'}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </Paper>
    </>
  )
}
