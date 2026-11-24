import { z } from 'zod'

const optionalEmail = z
  .string()
  .trim()
  .email('Enter a valid email')
  .optional()
  .or(z.literal(''))

const optionalPhone = z
  .string()
  .trim()
  .regex(/^[+]?[\d\s-]{7,16}$/, 'Enter a valid phone number')
  .optional()
  .or(z.literal(''))

const emptyToUndefined = (value) => (value === '' || value === undefined ? undefined : value)
const optionalNumber = (schema) =>
  z.preprocess(emptyToUndefined, schema.nullable().optional())

export const loginSchema = z.object({
  email: z.string().trim().email('Enter a valid email address'),
  password: z.string().min(1, 'Password is required'),
})

export const registerSchema = z
  .object({
    name: z.string().trim().min(2, 'Name must be at least 2 characters').max(255),
    email: z.string().trim().email('Enter a valid email address'),
    password: z.string().min(8, 'Use at least 8 characters'),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match',
  })

export const profileSchema = z.object({
  name: z.string().trim().min(2, 'Name is required').max(255),
  email: z.string().trim().email('Enter a valid email address'),
  phone: optionalPhone,
  company: z.string().trim().max(255).optional().or(z.literal('')),
  bio: z.string().trim().max(1000).optional().or(z.literal('')),
})

export const passwordSchema = z
  .object({
    current_password: z.string().min(1, 'Current password is required'),
    password: z.string().min(8, 'Use at least 8 characters'),
    password_confirmation: z.string(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    path: ['password_confirmation'],
    message: 'Passwords do not match',
  })

export const campaignSchema = z.object({
  name: z.string().trim().min(2, 'Campaign name is required').max(255),
  description: z.string().trim().max(2000).optional().or(z.literal('')),
  status: z.enum(['active', 'paused', 'completed']),
})

export const leadSchema = z.object({
  name: z.string().trim().min(2, 'Lead name is required').max(255),
  email: optionalEmail,
  phone: optionalPhone,
  company: z.string().trim().max(255).optional().or(z.literal('')),
  status: z.enum(['new', 'contacted', 'qualified', 'proposal', 'negotiation', 'won', 'lost']),
  note: z.string().trim().max(2000).optional().or(z.literal('')),
  custom_fields: z.record(z.string().optional()).optional(),
})

export const realtorSchema = z.object({
  name: z.string().trim().min(2, 'Name is required').max(255),
  email: z.string().trim().email('Enter a valid email'),
  phone: z
    .string()
    .trim()
    .max(20, 'Phone number is too long')
    .optional()
    .or(z.literal('')),
  company: z.string().trim().max(255).optional().or(z.literal('')),
  password: z.string().min(8, 'Use at least 8 characters').optional().or(z.literal('')),
  password_confirmation: z.string().optional().or(z.literal('')),
  plan_id: z.coerce.number().int().positive('Choose a plan'),
})

export const planSchema = z.object({
  name: z.string().trim().min(2, 'Plan name is required').max(80),
  price: z.coerce.number().min(0, 'Price cannot be negative'),
  max_campaigns: optionalNumber(z.coerce.number().int().min(0)),
  max_leads_per_campaign: optionalNumber(z.coerce.number().int().min(0)),
  header_color: z
    .string()
    .trim()
    .regex(/^#[0-9a-fA-F]{6}$/, 'Use a hex colour like #8a006b')
    .optional()
    .or(z.literal('')),
  is_active: z.boolean().optional(),
  is_default: z.boolean().optional(),
})

export const couponSchema = z
  .object({
    code: z
      .string()
      .trim()
      .min(2, 'Coupon code is required')
      .max(50)
      .regex(/^[A-Za-z0-9_-]+$/, 'Use letters, numbers, hyphens or underscores'),
    name: z.string().trim().min(2, 'Name is required').max(255),
    description: z.string().trim().max(1000).optional().or(z.literal('')),
    discount_type: z.enum(['percentage', 'fixed']),
    discount_value: z.coerce.number().positive('Discount must be greater than zero'),
    max_discount_amount: optionalNumber(z.coerce.number().min(0)),
    min_purchase_amount: optionalNumber(z.coerce.number().min(0)),
    applicable_plans: z.string().trim().optional().or(z.literal('')),
    start_date: z.string().optional().or(z.literal('')),
    expiration_date: z.string().optional().or(z.literal('')),
    usage_limit: optionalNumber(z.coerce.number().int().min(1)),
    customer_usage_limit: z.coerce.number().int().min(1).optional(),
    is_active: z.boolean().optional(),
    notes: z.string().trim().max(1000).optional().or(z.literal('')),
  })
  .refine(
    (data) =>
      !data.start_date || !data.expiration_date || data.expiration_date >= data.start_date,
    { path: ['expiration_date'], message: 'Expiry must be after the start date' },
  )
  .refine(
    (data) =>
      data.discount_type !== 'percentage' || data.discount_value <= 100,
    { path: ['discount_value'], message: 'Percentage cannot exceed 100' },
  )

export const capiSchema = z.object({
  pixel_id: z.string().trim().min(3, 'Pixel ID is required'),
  access_token: z.string().trim().optional().or(z.literal('')),
  test_event_code: z.string().trim().optional().or(z.literal('')),
  is_active: z.boolean().optional(),
})

export const inventoryProjectSchema = z.object({
  name: z.string().trim().min(2, 'Project name is required').max(255),
  location: z.string().trim().max(255).optional().or(z.literal('')),
  city: z.string().trim().max(120).optional().or(z.literal('')),
  description: z.string().trim().max(2000).optional().or(z.literal('')),
  status: z.enum(['active', 'inactive']),
})

export const batchSchema = z.object({
  prefix: z.string().trim().max(20).optional().or(z.literal('')),
  start_number: z.coerce.number().int().min(1),
  end_number: z.coerce.number().int().min(1),
  layout: z.string().optional().or(z.literal('')),
  carpet_area: optionalNumber(z.coerce.number().min(0)),
  built_up_area: optionalNumber(z.coerce.number().min(0)),
  base_price: optionalNumber(z.coerce.number().min(0)),
}).refine((data) => data.end_number >= data.start_number, {
  path: ['end_number'],
  message: 'End number must be greater than or equal to start',
})
