export const useApi = () => {
  const config    = useRuntimeConfig()
  const baseURL   = config.public.apiBase
  const authStore = useAuthStore()

  // Build headers with Authorization if a token exists
  // DSA — Hash Map construction:
  // headers is a key-value map built conditionally.
  // Building it is O(1) — constant number of keys.
  function getHeaders(): Record<string, string> {
    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept':       'application/json',
    }

    if (authStore.accessToken) {
      headers['Authorization'] = `Bearer ${authStore.accessToken}`
    }

    return headers
  }

  const get = async <T>(
    endpoint: string,
    params?: Record<string, string>
  ): Promise<T> => {
    return await $fetch<T>(endpoint, {
      baseURL,
      method:      'GET',
      params,
      headers:     getHeaders(),
      credentials: 'include',
    })
  }

  const post = async <T>(
    endpoint: string,
    body: Record<string, any>
  ): Promise<T> => {
    return await $fetch<T>(endpoint, {
      baseURL,
      method:      'POST',
      body,
      headers:     getHeaders(),
      credentials: 'include',
    })
  }

  const patch = async <T>(
    endpoint: string,
    body: Record<string, any>
  ): Promise<T> => {
    return await $fetch<T>(endpoint, {
      baseURL,
      method:      'PATCH',
      body,
      headers:     getHeaders(),
      credentials: 'include',
    })
  }

  const destroy = async (endpoint: string): Promise<void> => {
    await $fetch(endpoint, {
      baseURL,
      method:      'DELETE',
      headers:     getHeaders(),
      credentials: 'include',
    })
  }

  return { get, post, patch, destroy }
}