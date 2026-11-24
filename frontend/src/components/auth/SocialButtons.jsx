import { useState } from 'react'
import toast from 'react-hot-toast'
import { authApi } from '@/api/auth'
import { getErrorMessage } from '@/utils/errors'

const GoogleMark = () => (
  <svg viewBox="0 0 24 24" className="h-4 w-4" aria-hidden="true">
    <path
      fill="#4285F4"
      d="M21.6 12.23c0-.71-.06-1.4-.18-2.05H12v3.88h5.38a4.6 4.6 0 0 1-2 3.02v2.52h3.24c1.9-1.75 2.98-4.33 2.98-7.37Z"
    />
    <path
      fill="#34A853"
      d="M12 22c2.7 0 4.96-.9 6.62-2.4l-3.24-2.52c-.9.6-2.05.96-3.38.96-2.6 0-4.8-1.76-5.59-4.12H3.06v2.6A10 10 0 0 0 12 22Z"
    />
    <path fill="#FBBC05" d="M6.41 13.92a6 6 0 0 1 0-3.84v-2.6H3.06a10 10 0 0 0 0 9.04l3.35-2.6Z" />
    <path
      fill="#EA4335"
      d="M12 5.96c1.47 0 2.79.5 3.83 1.5l2.87-2.87A9.62 9.62 0 0 0 12 2a10 10 0 0 0-8.94 5.48l3.35 2.6C7.2 7.72 9.4 5.96 12 5.96Z"
    />
  </svg>
)

const FacebookMark = () => (
  <svg viewBox="0 0 24 24" className="h-4 w-4" aria-hidden="true">
    <path
      fill="#1877F2"
      d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.52 1.5-3.91 3.78-3.91 1.1 0 2.24.2 2.24.2v2.47h-1.26c-1.24 0-1.63.77-1.63 1.57v1.88h2.78l-.45 2.91h-2.33V22c4.78-.76 8.43-4.92 8.43-9.94Z"
    />
  </svg>
)

export const SocialButtons = ({ disabled = false, label = 'Continue' }) => {
  const [loading, setLoading] = useState(null)

  const start = async (provider) => {
    setLoading(provider)
    try {
      const redirectUri = `${window.location.origin}/auth/callback/${provider}`
      const response = await authApi.socialUrl(provider, redirectUri)
      const url = response.data?.data?.url
      if (!url) throw new Error('Social sign-in is not available right now.')
      window.location.assign(url)
    } catch (error) {
      toast.error(getErrorMessage(error, 'Could not start social sign-in.'))
      setLoading(null)
    }
  }

  return (
    <div className="grid grid-cols-2 gap-3">
      <button
        type="button"
        onClick={() => start('google')}
        disabled={disabled || Boolean(loading)}
        className="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card text-sm font-medium text-foreground shadow-sm transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
      >
        {loading === 'google' ? (
          <span className="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground/30 border-t-muted-foreground" />
        ) : (
          <GoogleMark />
        )}
        Google
      </button>
      <button
        type="button"
        onClick={() => start('facebook')}
        disabled={disabled || Boolean(loading)}
        className="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card text-sm font-medium text-foreground shadow-sm transition hover:bg-muted disabled:cursor-not-allowed disabled:opacity-60"
      >
        {loading === 'facebook' ? (
          <span className="h-4 w-4 animate-spin rounded-full border-2 border-muted-foreground/30 border-t-muted-foreground" />
        ) : (
          <FacebookMark />
        )}
        Facebook
      </button>
      <span className="sr-only">{label}</span>
    </div>
  )
}
