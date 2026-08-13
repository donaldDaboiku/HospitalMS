import {
  Alert,
  Chip,
  Paper,
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableRow,
  Typography,
} from '@mui/material'
import { useQuery } from '@tanstack/react-query'
import { fetchUsers } from '@/services/api'

export function UsersPage() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['users'],
    queryFn: fetchUsers,
  })

  return (
    <>
      <Typography variant="h5" sx={{ mb: 3 }}>
        Users
      </Typography>
      {error ? <Alert severity="error">You do not have permission to view users, or the request failed.</Alert> : null}
      <Paper variant="outlined">
        <Table>
          <TableHead>
            <TableRow>
              <TableCell>Name</TableCell>
              <TableCell>Email</TableCell>
              <TableCell>Roles</TableCell>
              <TableCell>Status</TableCell>
            </TableRow>
          </TableHead>
          <TableBody>
            {isLoading ? (
              <TableRow>
                <TableCell colSpan={4}>Loading…</TableCell>
              </TableRow>
            ) : (
              data?.map((user) => (
                <TableRow key={user.id}>
                  <TableCell>{user.name}</TableCell>
                  <TableCell>{user.email}</TableCell>
                  <TableCell>{user.roles.join(', ')}</TableCell>
                  <TableCell>
                    <Chip size="small" label={user.is_active ? 'Active' : 'Inactive'} color={user.is_active ? 'success' : 'default'} />
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
