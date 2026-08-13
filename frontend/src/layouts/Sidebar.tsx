import { useMemo, useState } from 'react'
import {
  Box,
  Collapse,
  Drawer,
  List,
  ListItemButton,
  ListItemText,
  Toolbar,
  Typography,
} from '@mui/material'
import { ExpandLess, ExpandMore } from '@mui/icons-material'
import { useLocation, useNavigate } from 'react-router-dom'
import { navigation } from '@/routes/nav'
import { useAuth } from '@/hooks/useAuth'

const DRAWER_WIDTH = 280

export function Sidebar() {
  const { can } = useAuth()
  const location = useLocation()
  const navigate = useNavigate()
  const [openMenus, setOpenMenus] = useState<Record<string, boolean>>({})

  const items = useMemo(
    () => navigation.filter((item) => !item.permission || can(item.permission)),
    [can],
  )

  return (
    <Drawer
      variant="permanent"
      sx={{
        width: DRAWER_WIDTH,
        flexShrink: 0,
        '& .MuiDrawer-paper': {
          width: DRAWER_WIDTH,
          boxSizing: 'border-box',
          bgcolor: '#0F4C5C',
          color: '#fff',
        },
      }}
    >
      <Toolbar sx={{ px: 2 }}>
        <Box>
          <Typography variant="h6" sx={{ fontWeight: 800, letterSpacing: 0.4 }}>
            HMS
          </Typography>
          <Typography variant="caption" sx={{ opacity: 0.8 }}>
            Hospital Management System
          </Typography>
        </Box>
      </Toolbar>
      <List sx={{ px: 1, pb: 4 }}>
        {items.map((item) => {
          const selected = location.pathname === item.to || location.pathname.startsWith(`${item.to}/`)
          const open = openMenus[item.label] ?? selected
          const children = item.children?.filter((child) => !child.permission || can(child.permission)) ?? []

          return (
            <Box key={item.label}>
              <ListItemButton
                selected={selected && children.length === 0}
                onClick={() => {
                  if (children.length > 0) {
                    setOpenMenus((current) => ({ ...current, [item.label]: !open }))
                    return
                  }
                  if (item.enabled === false) {
                    navigate(`/coming-soon?module=${encodeURIComponent(item.label)}`)
                    return
                  }
                  navigate(item.to)
                }}
                sx={{
                  borderRadius: 1,
                  mb: 0.5,
                  '&.Mui-selected': { bgcolor: 'rgba(255,255,255,0.12)' },
                  '&:hover': { bgcolor: 'rgba(255,255,255,0.08)' },
                }}
              >
                <ListItemText primary={item.label} />
                {children.length > 0 ? open ? <ExpandLess /> : <ExpandMore /> : null}
              </ListItemButton>
              {children.length > 0 ? (
                <Collapse in={open} timeout="auto" unmountOnExit>
                  <List disablePadding>
                    {children.map((child) => (
                      <ListItemButton
                        key={child.to}
                        sx={{ pl: 4, borderRadius: 1, mb: 0.5 }}
                        selected={location.pathname === child.to}
                        onClick={() => {
                          if (child.enabled === false) {
                            navigate(`/coming-soon?module=${encodeURIComponent(child.label)}`)
                            return
                          }
                          navigate(child.to)
                        }}
                      >
                        <ListItemText
                          primary={child.label}
                          slotProps={{ primary: { variant: 'body2' } }}
                        />
                      </ListItemButton>
                    ))}
                  </List>
                </Collapse>
              ) : null}
            </Box>
          )
        })}
      </List>
    </Drawer>
  )
}
