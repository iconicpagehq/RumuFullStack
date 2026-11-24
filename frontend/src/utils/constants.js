export const LEAD_STATUSES = [
  { value: 'new', label: 'New', color: 'blue' },
  { value: 'contacted', label: 'Contacted', color: 'yellow' },
  { value: 'qualified', label: 'Qualified', color: 'purple' },
  { value: 'proposal', label: 'Proposal', color: 'indigo' },
  { value: 'negotiation', label: 'Negotiation', color: 'orange' },
  { value: 'won', label: 'Won', color: 'green' },
  { value: 'lost', label: 'Lost', color: 'red' },
]

export const LEAD_SOURCES = [
  { value: 'manual', label: 'Manual' },
  { value: 'csv', label: 'CSV import' },
  { value: 'meta_ads', label: 'Meta ads' },
]

export const CAMPAIGN_STATUSES = [
  { value: 'active', label: 'Active' },
  { value: 'paused', label: 'Paused' },
  { value: 'completed', label: 'Completed' },
]

export const PROJECT_STATUSES = [
  { value: 'active', label: 'Active' },
  { value: 'inactive', label: 'Inactive' },
]

export const UNIT_STATUSES = [
  { value: 'available', label: 'Available', color: 'green' },
  { value: 'hold', label: 'On hold', color: 'yellow' },
  { value: 'booked', label: 'Booked', color: 'blue' },
  { value: 'sold', label: 'Sold', color: 'red' },
]

export const UNIT_LAYOUTS = [
  { value: 'studio', label: 'Studio' },
  { value: '1_bhk', label: '1 BHK' },
  { value: '2_bhk', label: '2 BHK' },
  { value: '3_bhk', label: '3 BHK' },
  { value: '4_bhk', label: '4 BHK' },
  { value: '4+_bhk', label: '4+ BHK' },
]

export const DURATION_OPTIONS = [
  { value: 1, label: '1 month' },
  { value: 3, label: '3 months' },
  { value: 6, label: '6 months' },
  { value: 12, label: '12 months' },
]

export const SUBSCRIPTION_STATUSES = [
  { value: 'active', label: 'Active' },
  { value: 'pending', label: 'Pending' },
  { value: 'expired', label: 'Expired' },
  { value: 'cancelled', label: 'Cancelled' },
]

export const PAYMENT_STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'completed', label: 'Completed' },
  { value: 'failed', label: 'Failed' },
  { value: 'refunded', label: 'Refunded' },
]

export const INVOICE_STATUSES = [
  { value: 'draft', label: 'Draft' },
  { value: 'paid', label: 'Paid' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'refunded', label: 'Refunded' },
]

export const NOTIFICATION_CHANNELS = [
  { value: 'email', label: 'Email' },
  { value: 'in_app', label: 'In-app' },
  { value: 'both', label: 'Email + in-app' },
]

export const NOTIFICATION_STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'sent', label: 'Sent' },
  { value: 'failed', label: 'Failed' },
]

export const UPGRADE_STATUSES = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
]

export const BILLING_NOTIFICATION_TYPES = [
  { value: 'subscription_activated', label: 'Subscription activated' },
  { value: 'subscription_renewed', label: 'Subscription renewed' },
  { value: 'subscription_expiring', label: 'Subscription expiring' },
  { value: 'subscription_expired', label: 'Subscription expired' },
  { value: 'payment_successful', label: 'Payment successful' },
  { value: 'payment_failed', label: 'Payment failed' },
  { value: 'invoice_generated', label: 'Invoice generated' },
  { value: 'invoice_emailed', label: 'Invoice emailed' },
]

export const META_EVENTS = [
  'Contact',
  'Schedule',
  'SubmitApplication',
  'Purchase',
  'Lead',
  'ViewContent',
]

export const CUSTOM_FIELD_OPTIONS = {
  client_type: ['buyer', 'seller', 'investor', 'tenant'],
  property_type: ['apartment', 'villa', 'plot', 'commercial', 'office', 'shop'],
  configuration: ['studio', '1_bhk', '2_bhk', '3_bhk', '4_bhk', '4+_bhk'],
  budget_range: [
    'below_50l',
    '50l_75l',
    '75l_1cr',
    '1cr_1.5cr',
    '1.5cr_2cr',
    '2cr_3cr',
    '3cr_5cr',
    'above_5cr',
  ],
  purpose: ['self_use', 'investment', 'rental'],
  family_size: ['1', '2', '3', '4', '5', '5+'],
  construction_status: ['ready_to_move', 'under_construction', 'both'],
  site_visit_required: ['yes', 'no'],
  lead_source: ['facebook', 'google', 'portal', 'walk_in', 'referral', 'other'],
  lead_priority: ['hot', 'warm', 'cold'],
  buying_timeline: ['immediate', '1_3_months', '3_6_months', '6_12_months', 'just_browsing'],
  funding_type: ['self', 'loan', 'bank_approved'],
}

export const CUSTOM_FIELD_LABELS = {
  client_type: 'Client type',
  budget_range: 'Budget range',
  purpose: 'Purpose',
  family_size: 'Family size',
  property_type: 'Property type',
  configuration: 'Configuration',
  preferred_location: 'Preferred location',
  project_name: 'Project name',
  construction_status: 'Construction status',
  city: 'City',
  site_visit_required: 'Site visit required',
  preferred_visit_date: 'Preferred visit date',
  landmarks: 'Landmarks',
  lead_source: 'Lead source',
  lead_priority: 'Lead priority',
  buying_timeline: 'Buying timeline',
  funding_type: 'Funding type',
}

export const PAGE_SIZE = 20
