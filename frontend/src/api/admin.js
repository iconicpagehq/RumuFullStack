import { api } from './axios'

export const adminApi = {
  dashboard: () => api.get('/admin/dashboard'),

  leads: (params) => api.get('/admin/leads', { params }),

  users: {
    list: (params) => api.get('/admin/users', { params }),
    get: (id) => api.get(`/admin/users/${id}`),
  },

  plans: {
    list: () => api.get('/admin/plans'),
    create: (payload) => api.post('/admin/plans', payload),
    update: (id, payload) => api.put(`/admin/plans/${id}`, payload),
    reorder: (order) => api.post('/admin/plans/reorder', { order }),
    toggleStatus: (id) => api.patch(`/admin/plans/${id}/toggle-status`),
  },

  upgradeRequests: {
    list: (params) => api.get('/admin/upgrade-requests', { params }),
    approve: (id, payload = {}) => api.post(`/admin/upgrade-requests/${id}/approve`, payload),
    reject: (id, payload = {}) => api.post(`/admin/upgrade-requests/${id}/reject`, payload),
  },

  enterprises: {
    list: (params) => api.get('/admin/enterprises', { params }),
    get: (id) => api.get(`/admin/enterprises/${id}`),
    suspend: (id, payload = {}) => api.post(`/admin/enterprises/${id}/suspend`, payload),
    resume: (id) => api.post(`/admin/enterprises/${id}/resume`),
  },

  seatRequests: {
    list: (params) => api.get('/admin/seat-requests', { params }),
    approve: (id, payload = {}) => api.post(`/admin/seat-requests/${id}/approve`, payload),
    reject: (id, payload = {}) => api.post(`/admin/seat-requests/${id}/reject`, payload),
  },

  broadcasts: {
    list: (params) => api.get('/admin/broadcasts', { params }),
    preview: (payload) => api.post('/admin/broadcasts/preview', payload),
    create: (payload) => api.post('/admin/broadcasts', payload),
  },

  auditLogs: (params) => api.get('/admin/audit-logs', { params }),

  notifications: {
    list: (params) => api.get('/admin/notifications', { params }),
    unreadCount: () => api.get('/admin/notifications/unread-count'),
    markRead: (id) => api.patch(`/admin/notifications/${id}/read`),
    markAllRead: () => api.post('/admin/notifications/mark-all-read'),
  },

  settings: {
    get: () => api.get('/admin/settings'),
    update: (payload) => api.put('/admin/settings', payload),
    updateAppearance: (payload) => api.put('/admin/settings/appearance', payload),
    updateMetaCapi: (payload) => api.put('/admin/settings/meta-capi', payload),
  },

  billing: {
    subscriptions: {
      list: (params) => api.get('/admin/billing/subscriptions', { params }),
      get: (id) => api.get(`/admin/billing/subscriptions/${id}`),
      update: (id, payload) => api.put(`/admin/billing/subscriptions/${id}`, payload),
      extend: (id, payload) => api.post(`/admin/billing/subscriptions/${id}/extend`, payload),
      cancel: (id, payload = {}) => api.post(`/admin/billing/subscriptions/${id}/cancel`, payload),
    },
    payments: {
      list: (params) => api.get('/admin/billing/payments', { params }),
      get: (id) => api.get(`/admin/billing/payments/${id}`),
    },
    invoices: {
      list: (params) => api.get('/admin/billing/invoices', { params }),
      get: (id) => api.get(`/admin/billing/invoices/${id}`),
      download: (id) => api.get(`/admin/billing/invoices/${id}/download`, { responseType: 'blob' }),
      resend: (id) => api.post(`/admin/billing/invoices/${id}/resend`),
    },
    coupons: {
      list: (params) => api.get('/admin/billing/coupons', { params }),
      get: (id) => api.get(`/admin/billing/coupons/${id}`),
      create: (payload) => api.post('/admin/billing/coupons', payload),
      update: (id, payload) => api.put(`/admin/billing/coupons/${id}`, payload),
      remove: (id) => api.delete(`/admin/billing/coupons/${id}`),
    },
    referrals: (params) => api.get('/admin/billing/referrals', { params }),
    notificationTemplates: {
      list: (params) => api.get('/admin/billing/notification-templates', { params }),
      get: (id) => api.get(`/admin/billing/notification-templates/${id}`),
      create: (payload) => api.post('/admin/billing/notification-templates', payload),
      update: (id, payload) => api.put(`/admin/billing/notification-templates/${id}`, payload),
      remove: (id) => api.delete(`/admin/billing/notification-templates/${id}`),
    },
    notifications: {
      list: (params) => api.get('/admin/billing/notifications', { params }),
      resend: (id) => api.post(`/admin/billing/notifications/${id}/resend`),
    },
    settings: {
      get: () => api.get('/admin/billing/settings'),
      update: (payload) => api.post('/admin/billing/settings', payload),
    },
  },

  analytics: {
    dashboard: (params) => api.get('/admin/analytics/dashboard', { params }),
    revenue: (params) => api.get('/admin/analytics/revenue', { params }),
    payments: (params) => api.get('/admin/analytics/payments', { params }),
    customers: (params) => api.get('/admin/analytics/customers', { params }),
    enterprises: (params) => api.get('/admin/analytics/enterprises', { params }),
    reports: (params) => api.get('/admin/analytics/reports', { params }),
    export: (payload) => api.post('/admin/analytics/reports/export', payload, { responseType: 'blob' }),
  },

  realtors: {
    list: (params) => api.get('/admin/realtors', { params }),
    get: (id) => api.get(`/admin/realtors/${id}`),
    create: (payload) => api.post('/admin/realtors', payload),
    update: (id, payload) => api.put(`/admin/realtors/${id}`, payload),
    remove: (id) => api.delete(`/admin/realtors/${id}`),
    billing: (id) => api.get(`/admin/realtors/${id}/billing`),
    assignPlan: (id, payload) => api.post(`/admin/realtors/${id}/assign-plan`, payload),
    suspend: (id, payload = {}) => api.post(`/admin/realtors/${id}/suspend`, payload),
    resume: (id) => api.post(`/admin/realtors/${id}/resume`),
  },
}
