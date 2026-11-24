export const INVENTORY_LAYOUTS = [
  { value: 'studio', label: 'Studio' },
  { value: '1_bhk', label: '1 BHK' },
  { value: '2_bhk', label: '2 BHK' },
  { value: '3_bhk', label: '3 BHK' },
  { value: '4_bhk', label: '4 BHK' },
  { value: '4+_bhk', label: '4+ BHK' },
]

export const layoutLabel = (value) =>
  INVENTORY_LAYOUTS.find((layout) => layout.value === value)?.label || '—'

export const INVENTORY_STATUSES = [
  { value: 'available', label: 'Available' },
  { value: 'hold', label: 'On hold' },
  { value: 'booked', label: 'Booked' },
  { value: 'sold', label: 'Sold' },
]

export const statusLabel = (value) =>
  INVENTORY_STATUSES.find((status) => status.value === value)?.label || '—'

export const UNIT_TRANSITIONS = {
  available: ['hold', 'booked'],
  hold: ['available', 'booked'],
  booked: ['available', 'sold'],
  sold: [],
}

export const nextUnitStatuses = (status) => UNIT_TRANSITIONS[status] || []

export const inventoryStatusTone = (status) =>
  ({ available: 'success', hold: 'warning', booked: 'info', sold: 'purple' })[status] || 'neutral'
