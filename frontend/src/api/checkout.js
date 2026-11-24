import { api } from './axios'

export const checkoutApi = {
  plans: () => api.get('/checkout/plans'),
  summary: (params) => api.get('/checkout/summary', { params }),
  applyCoupon: (payload) => api.post('/checkout/coupon', payload),
  removeCoupon: (payload) => api.delete('/checkout/coupon', { data: payload }),
  createOrder: (payload) => api.post('/checkout/order', payload),
  verify: (payload) => api.post('/checkout/verify', payload),
}
