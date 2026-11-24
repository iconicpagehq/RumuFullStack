import { realtorApi } from '@/api/realtor'

export const META_CALLBACK_PATH = '/realtor/meta/callback'
const RETURN_KEY = 'rumu-meta-connect-return'

export const startMetaConnect = async (returnTo = '/realtor/campaigns') => {
  sessionStorage.setItem(RETURN_KEY, returnTo)
  try {
    const redirectUri = `${window.location.origin}${META_CALLBACK_PATH}`
    const response = await realtorApi.meta.connectUrl(redirectUri)
    const url = response.data?.data?.url
    if (!url) throw new Error('Meta did not return an authorization URL.')
    window.location.assign(url)
  } catch (error) {
    sessionStorage.removeItem(RETURN_KEY)
    throw error
  }
}

export const readMetaConnectReturn = () => {
  const value = sessionStorage.getItem(RETURN_KEY)
  sessionStorage.removeItem(RETURN_KEY)
  return value
}
