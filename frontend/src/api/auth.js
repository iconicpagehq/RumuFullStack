import { api } from './axios'

export const authApi = {
  login: (payload) => api.post('/auth/login', payload),
  register: (payload) => api.post('/auth/register', payload),
  me: () => api.get('/auth/me'),
  logout: () => api.post('/auth/logout'),
  logoutAll: () => api.post('/auth/logout-all'),
  sendResetOtp: (payload) => api.post('/auth/forgot-password/send-otp', payload),
  verifyResetOtp: (payload) => api.post('/auth/forgot-password/verify-otp', payload),
  resetPasswordWithOtp: (payload) => api.post('/auth/forgot-password/reset', payload),
  socialUrl: (provider, redirectUri) =>
    api.get(`/auth/social/${provider}/url`, { params: { redirect_uri: redirectUri } }),
  socialLogin: (provider, payload) => api.post(`/auth/social/${provider}`, payload),
  linkSocial: (provider, payload) => api.post(`/auth/social/${provider}/link`, payload),
  unlinkSocial: (provider) => api.delete(`/auth/social/${provider}/unlink`),
}
