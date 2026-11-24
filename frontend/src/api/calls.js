import { api } from './axios'

const createCallsApi = (base) => ({
  summary: () => api.get(`${base}/summary`),
  list: (params) => api.get(base, { params }),
  get: (id) => api.get(`${base}/${id}`),
  updateDisposition: (id, payload) => api.patch(`${base}/${id}/disposition`, payload),
  recordingBlob: (id) => api.get(`${base}/${id}/recording`, { responseType: 'blob' }),
  deleteRecording: (id) => api.delete(`${base}/${id}/recording`),
  importRecording: ({ file, leadId, recordedAt, durationSeconds, direction, disposition, notes }) => {
    const form = new FormData()
    form.append('lead_id', leadId)
    form.append('recording', file)
    form.append('recorded_at', recordedAt)
    form.append('duration_seconds', durationSeconds)
    if (direction) form.append('direction', direction)
    if (disposition) form.append('disposition', disposition)
    if (notes) form.append('notes', notes)
    return api.post(`${base}/import`, form)
  },
})

export const realtorCallsApi = createCallsApi('/realtor/calls')
export const enterpriseCallsApi = createCallsApi('/enterprise/calls')
