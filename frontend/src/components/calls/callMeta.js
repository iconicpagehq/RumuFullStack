export const CALL_STATUS_OPTIONS = [
  { value: 'initiating', label: 'Calling' },
  { value: 'ringing', label: 'Ringing' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'completed', label: 'Completed' },
  { value: 'no_answer', label: 'No answer' },
  { value: 'failed', label: 'Failed' },
  { value: 'canceled', label: 'Canceled' },
]

export const CALL_STATUS_LABELS = Object.fromEntries(
  CALL_STATUS_OPTIONS.map((option) => [option.value, option.label]),
)

export const CALL_DISPOSITIONS = [
  { value: 'connected', label: 'Connected', tone: 'success' },
  { value: 'not_connected', label: 'Not connected', tone: 'neutral' },
  { value: 'busy', label: 'Busy', tone: 'warning' },
  { value: 'wrong_number', label: 'Wrong number', tone: 'danger' },
  { value: 'call_back', label: 'Call back later', tone: 'warning' },
  { value: 'interested', label: 'Interested', tone: 'success' },
  { value: 'not_interested', label: 'Not interested', tone: 'danger' },
  { value: 'converted', label: 'Converted', tone: 'success' },
  { value: 'other', label: 'Other', tone: 'neutral' },
]

export const dispositionMeta = (value) =>
  CALL_DISPOSITIONS.find((item) => item.value === value) || null

export const CALL_SOURCES = {
  app: 'Android app',
  import: 'Uploaded recording',
  web: 'Dashboard (legacy)',
  webhook: 'Provider (legacy)',
}

export const callSourceLabel = (value) =>
  value ? CALL_SOURCES[value] || String(value) : null
