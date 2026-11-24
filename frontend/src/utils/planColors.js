const LEGACY_PLAN_HEADER = '#6b7280'
const BRAND_PLAN_GRADIENT = 'linear-gradient(135deg, #650052 0%, #8a006b 100%)'

export const planHeaderStyle = (color) =>
  !color || color.toLowerCase() === LEGACY_PLAN_HEADER
    ? { backgroundImage: BRAND_PLAN_GRADIENT }
    : { backgroundColor: color }

export const planAccentColor = (color) =>
  !color || color.toLowerCase() === LEGACY_PLAN_HEADER ? '#8a006b' : color
