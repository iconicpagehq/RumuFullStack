import { enterpriseApi } from '@/api/enterprise'

export const ENTERPRISE_META_CALLBACK_PATH = '/enterprise/meta/callback'
const RETURN_KEY = 'rumu-enterprise-meta-connect-return'

export const startEnterpriseMetaConnect = async (returnTo = '/enterprise/campaigns') => {
  sessionStorage.setItem(RETURN_KEY, returnTo)
  try {
    const redirectUri = `${window.location.origin}${ENTERPRISE_META_CALLBACK_PATH}`
    const response = await enterpriseApi.meta.connectUrl(redirectUri)
    const url = response.data?.data?.url
    if (!url) throw new Error('Meta did not return an authorization URL.')
    window.location.assign(url)
  } catch (error) {
    sessionStorage.removeItem(RETURN_KEY)
    throw error
  }
}

export const readEnterpriseMetaConnectReturn = () => {
  const value = sessionStorage.getItem(RETURN_KEY)
  sessionStorage.removeItem(RETURN_KEY)
  return value
}
