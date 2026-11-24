import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom'
import toast from 'react-hot-toast'
import { authApi } from '@/api/auth'
import { Brand } from '@/components/shared/Brand'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Spinner } from '@/components/ui/Spinner'
import { useAuthStore } from '@/stores/authStore'
import { getErrorMessage } from '@/utils/errors'

export const SocialCallbackPage = () => {
  const { provider } = useParams()
  const [searchParams] = useSearchParams()
  const navigate = useNavigate()
  const setAuth = useAuthStore((state) => state.setAuth)
  const [error, setError] = useState('')
  const started = useRef(false)

  useEffect(() => {
    if (started.current) return
    started.current = true

    const oauthError = searchParams.get('error_description') || searchParams.get('error')
    if (oauthError) {
      setError(oauthError)
      return
    }

    const code = searchParams.get('code')
    if (!code) {
      setError('The sign-in provider did not return an authorization code.')
      return
    }

    const exchange = async () => {
      try {
        const response = await authApi.socialLogin(provider, {
          code,
          redirect_uri: `${window.location.origin}/auth/callback/${provider}`,
        })
        const payload = response.data?.data
        if (!payload?.token || !payload?.user) {
          throw new Error('The server returned an unexpected response.')
        }
        setAuth({ token: payload.token, user: payload.user })
        toast.success(`Welcome, ${payload.user.name?.split(' ')[0] || 'there'}`)
        navigate(payload.user.role === 'admin' ? '/admin/dashboard' : '/realtor/dashboard', {
          replace: true,
        })
      } catch (err) {
        setError(getErrorMessage(err, 'Social sign-in failed. Please try again.'))
      }
    }

    exchange()
  }, [navigate, provider, searchParams, setAuth])

  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background px-5 py-10">
      <Brand to="/" className="mb-8" />
      <div className="w-full max-w-md rounded-2xl border border-border bg-card p-7 shadow-card">
        {error ? (
          <div className="space-y-5">
            <div>
              <h1 className="text-lg font-semibold text-foreground">Sign-in could not be completed</h1>
              <p className="mt-1 text-sm text-muted-foreground">
                We could not verify your social account.
              </p>
            </div>
            <Alert variant="danger">{error}</Alert>
            <div className="flex gap-3">
              <Button className="flex-1" onClick={() => navigate('/login', { replace: true })}>
                Back to sign in
              </Button>
              <Link
                to="/register"
                className="inline-flex flex-1 items-center justify-center rounded-lg border border-border px-4 text-sm font-semibold text-foreground transition hover:bg-muted"
              >
                Create account
              </Link>
            </div>
          </div>
        ) : (
          <div className="flex flex-col items-center py-6 text-center">
            <Spinner className="h-8 w-8" />
            <h1 className="mt-4 text-base font-semibold text-foreground">
              Signing you in with {provider === 'google' ? 'Google' : 'Facebook'}…
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">This only takes a moment.</p>
          </div>
        )}
      </div>
    </div>
  )
}
