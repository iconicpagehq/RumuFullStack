import { useEffect, useRef, useState } from 'react'
import { Link, useNavigate, useSearchParams } from 'react-router-dom'
import toast from 'react-hot-toast'
import { enterpriseApi } from '@/api/enterprise'
import { Alert } from '@/components/ui/Alert'
import { Button } from '@/components/ui/Button'
import { Card } from '@/components/ui/Card'
import { Spinner } from '@/components/ui/Spinner'
import {
  ENTERPRISE_META_CALLBACK_PATH,
  readEnterpriseMetaConnectReturn,
} from '@/utils/enterpriseMetaConnect'
import { getErrorMessage } from '@/utils/errors'

export const EnterpriseMetaConnectCallbackPage = () => {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [state, setState] = useState({ status: 'working', message: '' })
  const startedRef = useRef(false)
  const [returnTo] = useState(() => readEnterpriseMetaConnectReturn() || '/enterprise/campaigns')

  useEffect(() => {
    if (startedRef.current) return
    startedRef.current = true

    const error = searchParams.get('error_description') || searchParams.get('error')
    const code = searchParams.get('code')

    if (error) {
      setState({
        status: 'error',
        message: error === 'access_denied' ? 'Facebook authorization was cancelled.' : error,
      })
      return
    }

    if (!code) {
      setState({ status: 'error', message: 'Facebook did not return an authorization code.' })
      return
    }

    const redirectUri = `${window.location.origin}${ENTERPRISE_META_CALLBACK_PATH}`

    enterpriseApi.meta
      .connect({ code, state: searchParams.get('state') || undefined, redirect_uri: redirectUri })
      .then(() => {
        toast.success('Facebook Pages connected')
        navigate(returnTo, { replace: true })
      })
      .catch((err) => {
        setState({
          status: 'error',
          message: getErrorMessage(err, 'Could not connect the Facebook Pages.'),
        })
      })
  }, [navigate, returnTo, searchParams])

  return (
    <div className="mx-auto flex max-w-md flex-col items-center py-16">
      <Card className="w-full p-8 text-center">
        {state.status === 'working' ? (
          <>
            <Spinner className="mx-auto h-8 w-8" />
            <h1 className="mt-5 text-lg font-semibold text-foreground">Connecting Facebook…</h1>
            <p className="mt-1 text-sm text-muted-foreground">
              We are linking your company Pages and preparing lead form syncing. This only takes a
              moment.
            </p>
          </>
        ) : (
          <>
            <Alert variant="danger" title="Connection failed">
              {state.message}
            </Alert>
            <div className="mt-5 flex flex-col justify-center gap-2 sm:flex-row">
              <Link to={returnTo}>
                <Button variant="outline">Go back</Button>
              </Link>
              <Link to="/enterprise/campaigns">
                <Button>All campaigns</Button>
              </Link>
            </div>
          </>
        )}
      </Card>
    </div>
  )
}
