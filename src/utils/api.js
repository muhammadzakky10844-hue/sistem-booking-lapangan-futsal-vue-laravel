import axios from 'axios'

const envApiUrl = (import.meta.env.VITE_API_URL || '').trim().replace(/\/+$/, '')
const fallbackApiUrl = `${window.location.origin}/api`

const ensureApiSuffix = (url) => {
  const cleanUrl = (url || '').trim().replace(/\/+$/, '')
  if (!cleanUrl) return fallbackApiUrl
  return cleanUrl.endsWith('/api') ? cleanUrl : `${cleanUrl}/api`
}

// Guard against misconfigured env URL on shared hosting.
const baseURL = ensureApiSuffix(
  envApiUrl.includes('infinityfr.app.com') || !envApiUrl
    ? fallbackApiUrl
    : envApiUrl
)

const normalizedLocationOrigin = window.location.origin.replace(/\/+$/, '')

const getSameOriginFallbackBaseURLs = () => {
  return [
    `${normalizedLocationOrigin}/index.php/api`,
  ].map((url) => (url || '').trim().replace(/\/+$/, ''))
}

const getInfinityApiSubdomainBaseURLs = () => {
  try {
    const hostname = (window.location.hostname || '').toLowerCase()
    if (!hostname.endsWith('.free.nf')) return []
    if (hostname.startsWith('api.')) return []

    return [
      `https://api.${hostname}/api`,
      `https://api.${hostname}/index.php/api`,
    ].map((url) => (url || '').trim().replace(/\/+$/, ''))
  } catch {
    return []
  }
}

const isSameOriginAbsoluteUrl = (url) => {
  try {
    return new URL(url).origin === window.location.origin
  } catch {
    return false
  }
}

const uniqueBaseURLs = (urls) => {
  const seen = new Set()
  const result = []

  for (const url of urls) {
    const cleanUrl = (url || '').trim().replace(/\/+$/, '')
    if (!cleanUrl || seen.has(cleanUrl)) continue
    seen.add(cleanUrl)
    result.push(cleanUrl)
  }

  return result
}

const getInfinityChallengeParam = () => {
  try {
    const hostname = (window.location.hostname || '').toLowerCase()
    const isInfinityFreeHost = hostname.endsWith('.free.nf') || hostname.includes('infinityfree')
    if (!isInfinityFreeHost) return null

    const value = new URLSearchParams(window.location.search).get('i')
    return value ? String(value) : null
  } catch {
    return null
  }
}

const buildBaseCandidates = (primaryBaseURL) => {
  const sameOriginFallbacks = isSameOriginAbsoluteUrl(primaryBaseURL)
    ? getSameOriginFallbackBaseURLs()
    : []
  const infinityApiSubdomainFallbacks = getInfinityApiSubdomainBaseURLs()

  return uniqueBaseURLs([
    primaryBaseURL,
    ...sameOriginFallbacks,
    ...infinityApiSubdomainFallbacks,
  ])
}

const isRelativeRequestUrl = (url) => typeof url === 'string' && !/^https?:\/\//i.test(url)

const isJsonLikePayload = (payload) => {
  return Array.isArray(payload) || (payload !== null && typeof payload === 'object')
}

const tryParseJsonString = (payload) => {
  if (typeof payload !== 'string') return null
  const trimmed = payload.trim()
  if (!trimmed) return null
  if (!(trimmed.startsWith('{') || trimmed.startsWith('['))) return null

  try {
    return JSON.parse(trimmed)
  } catch {
    return null
  }
}

const isHtmlLikePayload = (payload) => {
  if (typeof payload !== 'string') return false
  const normalized = payload.trim().toLowerCase()
  if (!normalized) return false

  return (
    normalized.startsWith('<!doctype html') ||
    normalized.startsWith('<html') ||
    normalized.includes('aes.js') ||
    normalized.includes('__test') ||
    normalized.includes('location.href') ||
    normalized.includes('?i=')
  )
}

const isJsonResponse = (response) => {
  const contentType = (response?.headers?.['content-type'] || '').toLowerCase()
  return (
    contentType.includes('application/json') ||
    isJsonLikePayload(response?.data) ||
    tryParseJsonString(response?.data) !== null
  )
}

const normalizeJsonStringResponse = (response) => {
  const parsed = tryParseJsonString(response?.data)
  if (parsed !== null) {
    response.data = parsed
  }
  return response
}

const hasLapanganListPayload = (payload) => {
  if (Array.isArray(payload)) return true
  if (payload && typeof payload === 'object' && Array.isArray(payload.data)) return true
  return false
}

const isLapanganEndpoint = (url) => {
  if (typeof url !== 'string') return false
  const path = url.split('?')[0].toLowerCase()
  return path === '/lapangan' || path.endsWith('/lapangan')
}

const createApiFormatError = (message, response) => {
  const err = new Error(message)
  err.name = 'ApiFormatError'
  err.response = response
  err.config = response?.config
  return err
}

const api = axios.create({
  baseURL,
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
})

// Otomatis sisipkan token di setiap request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token')
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`
    // Shared hosting can strip Authorization; send a fallback header too.
    config.headers['X-Auth-Token'] = token
  }

  const challengeParam = getInfinityChallengeParam()
  if (challengeParam && isRelativeRequestUrl(config?.url)) {
    const params = config.params && typeof config.params === 'object' ? { ...config.params } : {}
    if (typeof params.i === 'undefined') {
      params.i = challengeParam
      config.params = params
    }
  }

  return config
})

const getCurrentBaseURL = (config) => {
  return (config?.baseURL || api.defaults.baseURL || baseURL || '').trim().replace(/\/+$/, '')
}

const ensureBaseCandidatesState = (config) => {
  if (!config) return

  const currentBaseURL = getCurrentBaseURL(config)
  if (!Array.isArray(config._baseCandidates) || config._baseCandidates.length === 0) {
    config._baseCandidates = buildBaseCandidates(currentBaseURL)
  }

  if (typeof config._baseCandidateIndex !== 'number') {
    const index = config._baseCandidates.indexOf(currentBaseURL)
    config._baseCandidateIndex = index >= 0 ? index : 0
  }
}

const retryWithNextBaseCandidate = (config) => {
  if (!config || !isRelativeRequestUrl(config.url)) return null

  ensureBaseCandidatesState(config)

  const nextIndex = (config._baseCandidateIndex ?? 0) + 1
  if (nextIndex >= config._baseCandidates.length) return null

  config._baseCandidateIndex = nextIndex
  config.baseURL = config._baseCandidates[nextIndex]
  return api.request(config)
}

const syncDefaultBaseURL = (config) => {
  const usedBaseURL = (config?.baseURL || '').trim().replace(/\/+$/, '')
  if (usedBaseURL && api.defaults.baseURL !== usedBaseURL) {
    api.defaults.baseURL = usedBaseURL
  }
}

// Shared hosting deployments can accidentally switch between /api and non-/api routes.
// Retry across common hosting prefixes and reject HTML/challenge pages.
api.interceptors.response.use(
  (response) => {
    const requestConfig = response?.config
    normalizeJsonStringResponse(response)

    if (!isRelativeRequestUrl(requestConfig?.url)) {
      return response
    }

    if (isLapanganEndpoint(requestConfig?.url) && !hasLapanganListPayload(response?.data)) {
      // InfinityFree challenge often returns HTML with status 200.
      // Retry one same-origin candidate, then fail with clear message.
      if (isHtmlLikePayload(response?.data)) {
        const retried = retryWithNextBaseCandidate(requestConfig)
        if (retried) {
          return retried
        }

        return Promise.reject(
          createApiFormatError(
            'API hosting mengembalikan halaman challenge, bukan JSON lapangan.',
            response
          )
        )
      }

      return Promise.reject(
        createApiFormatError('Format respons endpoint lapangan bukan list data.', response)
      )
    }

    if (isJsonResponse(response)) {
      syncDefaultBaseURL(requestConfig)
      return response
    }

    const retried = retryWithNextBaseCandidate(requestConfig)
    if (retried) {
      return retried
    }

    if (isHtmlLikePayload(response?.data)) {
      return Promise.reject(
        createApiFormatError(
          'API mengembalikan HTML, bukan JSON. Periksa prefix route backend (/api) di hosting.',
          response
        )
      )
    }

    return Promise.reject(
      createApiFormatError('Format respons API tidak valid.', response)
    )
  },
  async (error) => {
    const originalConfig = error?.config
    const status = error?.response?.status

    const retryableStatusCodes = [404, 502, 503, 504]
    if (!status || retryableStatusCodes.includes(status)) {
      const retried = retryWithNextBaseCandidate(originalConfig)
      if (retried) {
        return retried
      }
    }

    return Promise.reject(error)
  }
)

export default api
