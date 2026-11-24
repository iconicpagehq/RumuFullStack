export const getErrorMessage = (error, fallback = 'Something went wrong. Please try again.') =>
  error?.userMessage || error?.response?.data?.message || error?.message || fallback

export const applyServerErrors = (error, setError, fields = []) => {
  const errors = error?.response?.data?.errors
  if (!errors || typeof errors !== 'object') return false

  let applied = false
  Object.entries(errors).forEach(([field, messages]) => {
    const key = field.includes('.') ? field.split('.').pop() : field
    if (!fields.includes(field) && !fields.includes(key)) return
    setError(key, {
      type: 'server',
      message: Array.isArray(messages) ? messages[0] : String(messages),
    })
    applied = true
  })

  return applied
}

export const serverFieldErrors = (error) => {
  const errors = error?.response?.data?.errors
  if (!errors || typeof errors !== 'object') return {}

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [
      field.includes('.') ? field.split('.').pop() : field,
      Array.isArray(messages) ? messages[0] : String(messages),
    ]),
  )
}
