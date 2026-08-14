export type NavItem = {
  label: string
  to: string
  permission?: string
  children?: { label: string; to: string; permission?: string; enabled?: boolean }[]
  enabled?: boolean
}

export const navigation: NavItem[] = [
  { label: 'Dashboard', to: '/', permission: 'dashboard.view', enabled: true },
  {
    label: 'Patients',
    to: '/patients',
    permission: 'patient.view',
    enabled: true,
    children: [
      { label: 'All Patients', to: '/patients', enabled: true },
      { label: 'Register Patient', to: '/patients/register', permission: 'patient.create', enabled: true },
      { label: 'Register Family', to: '/patients/register-family', permission: 'patient.create', enabled: true },
      { label: 'Patient History', to: '/patients/history', enabled: false },
    ],
  },
  {
    label: 'Appointments',
    to: '/appointments/today',
    permission: 'appointment.view',
    enabled: true,
    children: [
      { label: 'Calendar', to: '/appointments/calendar', enabled: false },
      { label: "Today's Appointments", to: '/appointments/today', enabled: true },
      { label: 'Waiting List', to: '/appointments/waiting', enabled: true },
    ],
  },
  {
    label: 'Clinical',
    to: '/clinical/encounters',
    permission: 'clinical.view',
    enabled: true,
    children: [
      { label: 'Encounters', to: '/clinical/encounters', enabled: true },
      { label: 'Triage', to: '/clinical/triage', enabled: false },
      { label: 'Consultations', to: '/clinical/consultations', enabled: false },
      { label: 'Diagnoses', to: '/clinical/diagnoses', enabled: false },
      { label: 'Procedures', to: '/clinical/procedures', enabled: false },
    ],
  },
  {
    label: 'Laboratory',
    to: '/laboratory',
    permission: 'lab.order',
    enabled: false,
    children: [
      { label: 'Test Catalog', to: '/laboratory/catalog', enabled: false },
      { label: 'Orders', to: '/laboratory/orders', enabled: false },
      { label: 'Specimens', to: '/laboratory/specimens', enabled: false },
      { label: 'Results', to: '/laboratory/results', enabled: false },
    ],
  },
  {
    label: 'Radiology',
    to: '/radiology',
    permission: 'radiology.order',
    enabled: false,
    children: [
      { label: 'Orders', to: '/radiology/orders', enabled: false },
      { label: 'Reports', to: '/radiology/reports', enabled: false },
    ],
  },
  {
    label: 'Pharmacy',
    to: '/pharmacy',
    permission: 'pharmacy.dispense',
    enabled: false,
    children: [
      { label: 'Prescriptions', to: '/pharmacy/prescriptions', enabled: false },
      { label: 'Dispensing', to: '/pharmacy/dispensing', enabled: false },
      { label: 'Inventory', to: '/pharmacy/inventory', enabled: false },
    ],
  },
  {
    label: 'Inventory',
    to: '/inventory',
    permission: 'inventory.view',
    enabled: false,
    children: [
      { label: 'Products', to: '/inventory/products', enabled: false },
      { label: 'Stock', to: '/inventory/stock', enabled: false },
      { label: 'Suppliers', to: '/inventory/suppliers', enabled: false },
      { label: 'Purchase Orders', to: '/inventory/purchase-orders', enabled: false },
    ],
  },
  {
    label: 'Wards',
    to: '/wards',
    permission: 'ward.view',
    enabled: false,
    children: [
      { label: 'Wards', to: '/wards', enabled: false },
      { label: 'Beds', to: '/wards/beds', enabled: false },
      { label: 'Admissions', to: '/wards/admissions', enabled: false },
      { label: 'Transfers', to: '/wards/transfers', enabled: false },
    ],
  },
  {
    label: 'Billing',
    to: '/billing',
    permission: 'billing.view',
    enabled: false,
    children: [
      { label: 'Invoices', to: '/billing/invoices', enabled: false },
      { label: 'Payments', to: '/billing/payments', enabled: false },
      { label: 'Refunds', to: '/billing/refunds', enabled: false },
    ],
  },
  {
    label: 'Insurance',
    to: '/insurance',
    permission: 'insurance.view',
    enabled: false,
    children: [
      { label: 'Providers', to: '/insurance/providers', enabled: false },
      { label: 'Plans', to: '/insurance/plans', enabled: false },
      { label: 'Claims', to: '/insurance/claims', enabled: false },
    ],
  },
  { label: 'Reports', to: '/reports', permission: 'reports.view', enabled: false },
  {
    label: 'Administration',
    to: '/admin/users',
    permission: 'user.view',
    enabled: true,
    children: [
      { label: 'Users', to: '/admin/users', permission: 'user.view', enabled: true },
      { label: 'Roles', to: '/admin/roles', permission: 'role.view', enabled: true },
      { label: 'Departments', to: '/admin/departments', permission: 'department.view', enabled: false },
      { label: 'Settings', to: '/admin/settings', permission: 'settings.manage', enabled: false },
    ],
  },
  { label: 'Audit Logs', to: '/admin/audit-logs', permission: 'audit.view', enabled: true },
]
