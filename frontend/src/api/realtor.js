import { api } from './axios'

export const realtorApi = {
  dashboard: () => api.get('/realtor/dashboard'),

  campaigns: {
    list: () => api.get('/realtor/campaigns'),
    get: (id) => api.get(`/realtor/campaigns/${id}`),
    create: (payload) => api.post('/realtor/campaigns', payload),
    update: (id, payload) => api.put(`/realtor/campaigns/${id}`, payload),
    remove: (id) => api.delete(`/realtor/campaigns/${id}`),
  },

  leads: {
    all: (params) => api.get('/realtor/leads', { params }),
    export: (params) => api.get('/realtor/leads/export', { params, responseType: 'blob' }),
    list: (campaignId, params) => api.get(`/realtor/campaigns/${campaignId}/leads`, { params }),
    get: (id) => api.get(`/realtor/leads/${id}`),
    create: (campaignId, payload) => api.post(`/realtor/campaigns/${campaignId}/leads`, payload),
    update: (id, payload) => api.put(`/realtor/leads/${id}`, payload),
    updateStatus: (id, status) => api.patch(`/realtor/leads/${id}/status`, { status }),
    addNote: (id, note) => api.post(`/realtor/leads/${id}/notes`, { note }),
    resendMetaCapi: (id) => api.post(`/realtor/leads/${id}/meta-capi/resend`),
  },

  tasks: {
    list: (params) => api.get('/realtor/tasks', { params }),
    get: (id) => api.get(`/realtor/tasks/${id}`),
    create: (payload) => api.post('/realtor/tasks', payload),
    update: (id, payload) => api.put(`/realtor/tasks/${id}`, payload),
    updateStatus: (id, payload) => api.patch(`/realtor/tasks/${id}/status`, payload),
    remove: (id) => api.delete(`/realtor/tasks/${id}`),
  },

  siteVisits: {
    list: (params) => api.get('/realtor/site-visits', { params }),
    get: (id) => api.get(`/realtor/site-visits/${id}`),
    create: (payload) => api.post('/realtor/site-visits', payload),
    update: (id, payload) => api.put(`/realtor/site-visits/${id}`, payload),
    updateStatus: (id, payload) => api.patch(`/realtor/site-visits/${id}/status`, payload),
    remove: (id) => api.delete(`/realtor/site-visits/${id}`),
  },

  imports: {
    dynamic: (campaignId, payload) =>
      api.post(`/realtor/campaigns/${campaignId}/import/dynamic`, payload),
    csv: (campaignId, file) => {
      const form = new FormData()
      form.append('csv_file', file)
      return api.post(`/realtor/campaigns/${campaignId}/import/csv`, form)
    },
    metaCsv: (campaignId, file) => {
      const form = new FormData()
      form.append('csv_file', file)
      return api.post(`/realtor/campaigns/${campaignId}/import/meta/csv`, form)
    },
    sample: (campaignId) =>
      api.get(`/realtor/campaigns/${campaignId}/import/sample`, { responseType: 'blob' }),
    metaSample: (campaignId) =>
      api.get(`/realtor/campaigns/${campaignId}/import/meta/sample`, { responseType: 'blob' }),
    metaForms: (campaignId, params) => api.get(`/realtor/campaigns/${campaignId}/meta/forms`, { params }),
    syncMeta: (campaignId, payload) =>
      api.post(`/realtor/campaigns/${campaignId}/meta/sync`, payload),
    scanGeminiVision: (campaignId, formData) =>
      api.post(`/realtor/campaigns/${campaignId}/ocr/gemini`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      }),
  },

  customers: (params) => api.get('/realtor/customers', { params }),

  inventory: {
    projects: () => api.get('/realtor/inventory/projects'),
    project: (id) => api.get(`/realtor/inventory/projects/${id}`),
    createProject: (payload) => api.post('/realtor/inventory/projects', payload),
    updateProject: (id, payload) => api.put(`/realtor/inventory/projects/${id}`, payload),
    deleteProject: (id) => api.delete(`/realtor/inventory/projects/${id}`),

    addTower: (projectId, payload) => api.post(`/realtor/inventory/projects/${projectId}/towers`, payload),
    updateTower: (id, payload) => api.put(`/realtor/inventory/towers/${id}`, payload),
    deleteTower: (id) => api.delete(`/realtor/inventory/towers/${id}`),

    addWing: (towerId, payload) => api.post(`/realtor/inventory/towers/${towerId}/wings`, payload),
    updateWing: (id, payload) => api.put(`/realtor/inventory/wings/${id}`, payload),
    deleteWing: (id) => api.delete(`/realtor/inventory/wings/${id}`),

    addFloor: (towerId, payload) => {
      const form = payload instanceof FormData ? payload : objectToFormData(payload)
      return api.post(`/realtor/inventory/towers/${towerId}/floors`, form)
    },
    addFloorToWing: (wingId, payload) => {
      const form = payload instanceof FormData ? payload : objectToFormData(payload)
      return api.post(`/realtor/inventory/wings/${wingId}/floors`, form)
    },
    updateFloor: (id, payload) => {
      const form = payload instanceof FormData ? payload : objectToFormData(payload)
      return api.post(`/realtor/inventory/floors/${id}?_method=PUT`, form)
    },
    deleteFloor: (id) => api.delete(`/realtor/inventory/floors/${id}`),

    floorUnits: (floorId, params) => api.get(`/realtor/inventory/floors/${floorId}/units`, { params }),
    addUnit: (floorId, payload) => {
      const form = payload instanceof FormData ? payload : objectToFormData(payload)
      return api.post(`/realtor/inventory/floors/${floorId}/units`, form)
    },
    bulkUnits: (floorId, payload) => api.post(`/realtor/inventory/floors/${floorId}/units/bulk`, payload),
    updateUnit: (id, payload) => {
      const form = payload instanceof FormData ? payload : objectToFormData(payload)
      return api.post(`/realtor/inventory/units/${id}?_method=PUT`, form)
    },
    updateUnitStatus: (id, payload) => api.patch(`/realtor/inventory/units/${id}/status`, payload),
    assignUnitLead: (id, payload) => api.patch(`/realtor/inventory/units/${id}/assign-lead`, payload),
    deleteUnit: (id) => api.delete(`/realtor/inventory/units/${id}`),
    searchUnits: (params) => api.get('/realtor/inventory/units/search', { params }),
  },

  plans: {
    list: () => api.get('/realtor/plans'),
    requestUpgrade: (planId) => api.post('/realtor/plans/upgrade', { plan_id: planId }),
  },

  profile: {
    get: () => api.get('/realtor/profile'),
    update: (payload) => api.put('/realtor/profile', payload),
    uploadAvatar: (file) => {
      const form = new FormData()
      form.append('avatar', file)
      return api.post('/realtor/profile/avatar', form)
    },
    updatePassword: (payload) => api.put('/realtor/profile/password', payload),
  },

  settings: {
    get: () => api.get('/realtor/settings'),
    updateNotifications: (payload) => api.put('/realtor/settings/notifications', payload),
    updatePrivacy: (payload) => api.put('/realtor/settings/privacy', payload),
    updateAppearance: (payload) => api.put('/realtor/settings/appearance', payload),
    deleteAccount: (payload) => api.delete('/realtor/settings/account', { data: payload }),
  },

  billing: {
    summary: (params) => api.get('/realtor/billing', { params }),
    invoice: (id) => api.get(`/realtor/billing/invoices/${id}`),
    downloadInvoice: (id) =>
      api.get(`/realtor/billing/invoices/${id}/download`, { responseType: 'blob' }),
    notifications: (params) => api.get('/realtor/billing/notifications', { params }),
    unreadCount: () => api.get('/realtor/billing/notifications/unread-count'),
    markRead: (id) => api.post(`/realtor/billing/notifications/${id}/read`),
    markAllRead: () => api.post('/realtor/billing/notifications/mark-all-read'),
  },

  meta: {
    integration: (campaignId) => api.get(`/realtor/campaigns/${campaignId}/meta-integration`),
    disconnectForm: (campaignId) =>
      api.post(`/realtor/campaigns/${campaignId}/meta-integration/disconnect-form`),
    saveCapi: (campaignId, payload) =>
      api.post(`/realtor/campaigns/${campaignId}/meta-integration/capi`, payload),
    pages: () => api.get('/realtor/meta/pages'),
    connectUrl: (redirectUri) =>
      api.get('/realtor/meta/connect-url', { params: { redirect_uri: redirectUri } }),
    connect: (payload) => api.post('/realtor/meta/connect', payload),
    disconnect: () => api.post('/realtor/meta/disconnect'),
  },
}

function objectToFormData(payload = {}) {
  const form = new FormData()
  Object.entries(payload).forEach(([key, value]) => {
    if (value === undefined || value === null) return
    if (value instanceof File) form.append(key, value)
    else if (Array.isArray(value)) value.forEach((item) => form.append(`${key}[]`, item))
    else form.append(key, value)
  })
  return form
}
