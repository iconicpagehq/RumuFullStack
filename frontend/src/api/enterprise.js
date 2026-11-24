import { api } from './axios'

const base = '/enterprise'

export const enterpriseApi = {
  plans: () => api.get(`${base}/plans`),
  register: (payload) => api.post(`${base}/register`, payload),
  invitation: {
    show: (token) => api.get(`${base}/invitations/${token}`),
    accept: (token, payload) => api.post(`${base}/invitations/${token}/accept`, payload),
  },

  me: () => api.get(`${base}/me`),
  dashboard: () => api.get(`${base}/dashboard`),
  referenceData: () => api.get(`${base}/reference-data`),

  leads: {
    list: (params) => api.get(`${base}/leads`, { params }),
    get: (id) => api.get(`${base}/leads/${id}`),
    create: (payload) => api.post(`${base}/leads`, payload),
    update: (id, payload) => api.put(`${base}/leads/${id}`, payload),
    remove: (id) => api.delete(`${base}/leads/${id}`),
    updateStage: (id, payload) => api.patch(`${base}/leads/${id}/stage`, payload),
    addNote: (id, note) => api.post(`${base}/leads/${id}/notes`, { note }),
    assign: (id, payload) => api.post(`${base}/leads/${id}/assign`, payload),
    roundRobin: (id, payload = {}) => api.post(`${base}/leads/${id}/round-robin`, payload),
    distribute: (payload) => api.post(`${base}/leads/distribute`, payload),
    importCsv: ({ file, teamId, campaignId }) => {
      const form = new FormData()
      form.append('csv_file', file)
      if (teamId) form.append('team_id', teamId)
      if (campaignId) form.append('campaign_id', campaignId)
      return api.post(`${base}/leads/import`, form)
    },
    importSample: (params) => api.get(`${base}/leads/import/sample`, { params, responseType: 'blob' }),
  },

  tasks: {
    list: (params) => api.get(`${base}/tasks`, { params }),
    get: (id) => api.get(`${base}/tasks/${id}`),
    create: (payload) => api.post(`${base}/tasks`, payload),
    update: (id, payload) => api.put(`${base}/tasks/${id}`, payload),
    updateStatus: (id, payload) => api.patch(`${base}/tasks/${id}/status`, payload),
    remove: (id) => api.delete(`${base}/tasks/${id}`),
  },

  siteVisits: {
    list: (params) => api.get(`${base}/site-visits`, { params }),
    get: (id) => api.get(`${base}/site-visits/${id}`),
    create: (payload) => api.post(`${base}/site-visits`, payload),
    update: (id, payload) => api.put(`${base}/site-visits/${id}`, payload),
    updateStatus: (id, payload) => api.patch(`${base}/site-visits/${id}/status`, payload),
    remove: (id) => api.delete(`${base}/site-visits/${id}`),
  },

  inventory: {
    projects: () => api.get(`${base}/inventory/projects`),
    project: (id) => api.get(`${base}/inventory/projects/${id}`),
    createProject: (payload) => api.post(`${base}/inventory/projects`, payload),
    updateProject: (id, payload) => api.put(`${base}/inventory/projects/${id}`, payload),
    deleteProject: (id) => api.delete(`${base}/inventory/projects/${id}`),
    addTower: (projectId, payload) => api.post(`${base}/inventory/projects/${projectId}/towers`, payload),
    updateTower: (id, payload) => api.put(`${base}/inventory/towers/${id}`, payload),
    deleteTower: (id) => api.delete(`${base}/inventory/towers/${id}`),
    addWing: (towerId, payload) => api.post(`${base}/inventory/towers/${towerId}/wings`, payload),
    updateWing: (id, payload) => api.put(`${base}/inventory/wings/${id}`, payload),
    deleteWing: (id) => api.delete(`${base}/inventory/wings/${id}`),
    addFloor: (towerId, payload) => api.post(`${base}/inventory/towers/${towerId}/floors`, payload),
    updateFloor: (id, payload) => api.put(`${base}/inventory/floors/${id}`, payload),
    deleteFloor: (id) => api.delete(`${base}/inventory/floors/${id}`),
    addUnit: (floorId, payload) => api.post(`${base}/inventory/floors/${floorId}/units`, payload),
    bulkUnits: (floorId, payload) => api.post(`${base}/inventory/floors/${floorId}/units/bulk`, payload),
    updateUnit: (id, payload) => api.put(`${base}/inventory/units/${id}`, payload),
    updateUnitStatus: (id, payload) => api.patch(`${base}/inventory/units/${id}/status`, payload),
    deleteUnit: (id) => api.delete(`${base}/inventory/units/${id}`),
    searchUnits: (params) => api.get(`${base}/inventory/units/search`, { params }),
  },

  targets: {
    list: (params) => api.get(`${base}/targets`, { params }),
    create: (payload) => api.post(`${base}/targets`, payload),
    update: (id, payload) => api.put(`${base}/targets/${id}`, payload),
    remove: (id) => api.delete(`${base}/targets/${id}`),
  },

  customers: (params) => api.get(`${base}/customers`, { params }),

  campaigns: {
    list: (params) => api.get(`${base}/campaigns`, { params }),
    get: (id) => api.get(`${base}/campaigns/${id}`),
    create: (payload) => api.post(`${base}/campaigns`, payload),
    update: (id, payload) => api.put(`${base}/campaigns/${id}`, payload),
    remove: (id) => api.delete(`${base}/campaigns/${id}`),
  },

  members: {
    list: (params) => api.get(`${base}/users`, { params }),
    create: (payload) => api.post(`${base}/users`, payload),
    get: (id) => api.get(`${base}/users/${id}`),
    history: (id, params) => api.get(`${base}/users/${id}/history`, { params }),
    reports: (id, params) => api.get(`${base}/users/${id}/reports`, { params }),
    changeManager: (id, payload) => api.patch(`${base}/users/${id}/manager`, payload),
    update: (id, payload) => api.put(`${base}/users/${id}`, payload),
    suspend: (id) => api.post(`${base}/users/${id}/suspend`),
    resume: (id) => api.post(`${base}/users/${id}/resume`),
    transfer: (id, payload) => api.post(`${base}/users/${id}/transfer`, payload),
    remove: (id, payload) => api.post(`${base}/users/${id}/remove`, payload),
  },

  organization: {
    tree: () => api.get(`${base}/organization/tree`),
  },

  teams: {
    list: (params) => api.get(`${base}/teams`, { params }),
    get: (id) => api.get(`${base}/teams/${id}`),
    create: (payload) => api.post(`${base}/teams`, payload),
    update: (id, payload) => api.put(`${base}/teams/${id}`, payload),
    remove: (id) => api.delete(`${base}/teams/${id}`),
    addMember: (id, userId) => api.post(`${base}/teams/${id}/members`, { user_id: userId }),
    removeMember: (id, userId) => api.delete(`${base}/teams/${id}/members/${userId}`),
  },

  invitations: {
    list: (params) => api.get(`${base}/invitations`, { params }),
    create: (payload) => api.post(`${base}/invitations`, payload),
    resend: (id) => api.post(`${base}/invitations/${id}/resend`),
    revoke: (id) => api.post(`${base}/invitations/${id}/revoke`),
  },

  reports: {
    get: (params) => api.get(`${base}/reports`, { params }),
    targets: (params) => api.get(`${base}/reports/targets`, { params }),
    export: (params) => api.get(`${base}/reports/export`, { params, responseType: 'blob' }),
  },

  notifications: {
    list: (params) => api.get(`${base}/notifications`, { params }),
    unreadCount: () => api.get(`${base}/notifications/unread-count`),
    markRead: (id) => api.patch(`${base}/notifications/${id}/read`),
    markAllRead: () => api.post(`${base}/notifications/mark-all-read`),
  },

  settings: {
    get: () => api.get(`${base}/settings`),
    updateProfile: (payload) => api.put(`${base}/settings/profile`, payload),
    updatePassword: (payload) => api.put(`${base}/settings/password`, payload),
    update: (payload) => api.put(`${base}/settings`, payload),
    uploadLogo: (file) => {
      const form = new FormData()
      form.append('logo', file)
      return api.post(`${base}/settings/logo`, form)
    },
    removeLogo: () => api.delete(`${base}/settings/logo`),
  },

  billing: {
    get: () => api.get(`${base}/billing`),
    upgrade: (planId) => api.post(`${base}/billing/upgrade`, { plan_id: planId }),
    downloadInvoice: (id) =>
      api.get(`${base}/billing/invoices/${id}/download`, { responseType: 'blob' }),
    requestSeat: (payload) => api.post(`${base}/billing/seat-requests`, payload),
  },

  meta: {
    pages: () => api.get(`${base}/meta/pages`),
    connectUrl: (redirectUri) =>
      api.get(`${base}/meta/connect-url`, { params: { redirect_uri: redirectUri } }),
    connect: (payload) => api.post(`${base}/meta/connect`, payload),
    disconnect: () => api.post(`${base}/meta/disconnect`),
    integration: (campaignId) => api.get(`${base}/campaigns/${campaignId}/meta-integration`),
    forms: (campaignId, params) =>
      api.get(`${base}/campaigns/${campaignId}/meta/forms`, { params }),
    saveMapping: (campaignId, payload) =>
      api.post(`${base}/campaigns/${campaignId}/meta/mapping`, payload),
    disconnectForm: (campaignId) =>
      api.post(`${base}/campaigns/${campaignId}/meta-integration/disconnect-form`),
    saveCapi: (campaignId, payload) =>
      api.post(`${base}/campaigns/${campaignId}/meta-integration/capi`, payload),
    sync: (campaignId, payload) => api.post(`${base}/campaigns/${campaignId}/meta/sync`, payload),
  },

  auditLogs: (params) => api.get(`${base}/audit-logs`, { params }),
}
