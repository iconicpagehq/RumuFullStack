const SCRIPT_SRC = 'https://checkout.razorpay.com/v1/checkout.js'

let loader = null

export const loadRazorpay = () => {
  if (typeof window === 'undefined') {
    return Promise.reject(new Error('The payment gateway is unavailable.'))
  }
  if (window.Razorpay) return Promise.resolve()

  if (!loader) {
    loader = new Promise((resolve, reject) => {
      const existing = document.querySelector(`script[src="${SCRIPT_SRC}"]`)

      const onLoad = () => {
        if (window.Razorpay) resolve()
        else reject(new Error('The payment gateway failed to initialise.'))
      }
      const onError = () => {
        loader = null
        reject(new Error('Could not load the payment gateway. Check your connection and try again.'))
      }

      if (existing) {
        existing.addEventListener('load', onLoad, { once: true })
        existing.addEventListener('error', onError, { once: true })
        return
      }

      const script = document.createElement('script')
      script.src = SCRIPT_SRC
      script.async = true
      script.addEventListener('load', onLoad, { once: true })
      script.addEventListener('error', onError, { once: true })
      document.body.appendChild(script)
    })
  }

  return loader
}
