import { format, formatDistanceToNow, isValid, parseISO } from 'date-fns'

const inr = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: 'INR',
  maximumFractionDigits: 0,
})

const inrPrecise = new Intl.NumberFormat('en-IN', {
  style: 'currency',
  currency: 'INR',
  minimumFractionDigits: 2,
  maximumFractionDigits: 2,
})

const numberFormat = new Intl.NumberFormat('en-IN')

export const formatCurrency = (value, { precise = false } = {}) => {
  const amount = Number(value)
  if (Number.isFinite(amount) === false || value === null || value === undefined) return '—'
  return precise ? inrPrecise.format(amount) : inr.format(amount)
}

export const formatCompactCurrency = (value) => {
  const amount = Number(value) || 0
  if (Math.abs(amount) >= 10000000) return `₹${(amount / 10000000).toFixed(2)} Cr`
  if (Math.abs(amount) >= 100000) return `₹${(amount / 100000).toFixed(2)} L`
  if (Math.abs(amount) >= 1000) return `₹${(amount / 1000).toFixed(1)}K`
  return formatCurrency(amount)
}

export const formatNumber = (value) => {
  const amount = Number(value)
  return Number.isFinite(amount) ? numberFormat.format(amount) : '—'
}

export const formatPercent = (value, digits = 1) => {
  const amount = Number(value)
  if (!Number.isFinite(amount)) return '—'
  return `${amount.toFixed(digits)}%`
}

const toDate = (value) => {
  if (!value) return null
  if (value instanceof Date) return value
  const parsed = typeof value === 'string' ? parseISO(value) : new Date(value)
  return isValid(parsed) ? parsed : null
}

export const formatDate = (value, pattern = 'dd MMM yyyy') => {
  const date = toDate(value)
  return date ? format(date, pattern) : '—'
}

export const formatDateTime = (value) => formatDate(value, 'dd MMM yyyy, h:mm a')

export const formatTime = (value) => formatDate(value, 'h:mm a')

export const toUtcDateTime = (value) => {
  if (!value) return null
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? null : date.toISOString()
}

export const timeAgo = (value) => {
  const date = toDate(value)
  return date ? formatDistanceToNow(date, { addSuffix: true }) : '—'
}

export const initials = (name = '') =>
  name
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0]?.toUpperCase())
    .join('') || '?'

export const formatPhone = (phone) => {
  if (!phone) return '—'
  const digits = String(phone).replace(/\D/g, '')
  if (digits.length === 10) return `${digits.slice(0, 5)} ${digits.slice(5)}`
  if (digits.length === 12 && digits.startsWith('91')) {
    return `+91 ${digits.slice(2, 7)} ${digits.slice(7)}`
  }
  return phone
}

export const formatDuration = (seconds) => {
  const total = Math.max(0, Math.round(Number(seconds) || 0))
  if (!total) return '0:00'
  const hours = Math.floor(total / 3600)
  const minutes = Math.floor((total % 3600) / 60)
  const rest = total % 60
  const pad = (value) => String(value).padStart(2, '0')
  return hours ? `${hours}:${pad(minutes)}:${pad(rest)}` : `${minutes}:${pad(rest)}`
}

export const formatFileSize = (bytes) => {
  if (!Number.isFinite(Number(bytes))) return '—'
  const units = ['B', 'KB', 'MB', 'GB']
  let size = Number(bytes)
  let index = 0
  while (size >= 1024 && index < units.length - 1) {
    size /= 1024
    index += 1
  }
  return `${size.toFixed(index === 0 ? 0 : 1)} ${units[index]}`
}

export const titleCase = (value = '') =>
  String(value)
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())

export const maskEmail = (email) => {
  if (!email || !email.includes('@')) return email || '—'
  const [name, domain] = email.split('@')
  const visible = name.slice(0, 2)
  return `${visible}${'*'.repeat(Math.max(2, name.length - 2))}@${domain}`
}

export const saveBlob = (blob, filename) => {
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  link.remove()
  URL.revokeObjectURL(url)
}

export const filenameFromResponse = (response, fallback = 'download') => {
  const header = response.headers?.['content-disposition'] || ''
  const match = /filename\*?=(?:UTF-8'')?"?([^";]+)"?/i.exec(header)
  return match ? decodeURIComponent(match[1]) : fallback
}
