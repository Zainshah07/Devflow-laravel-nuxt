export const useApi = () => {
  const config = useRuntimeConfig()
  const baseURL = config.public.apiBase

  const get = async <T>(endpoint: string, params?: Record<string, any>): Promise<T> => {
    return await $fetch<T>(endpoint, {
      baseURL,
      method: 'GET',
      params,
      credentials: 'include',
    })
  }

  const post = async <T>(endpoint: string, body: Record<string, any>): Promise<T> => {
    return await $fetch<T>(endpoint, {
      baseURL,
      method: 'POST',
      body,
      credentials: 'include',
    })
  }

  const patch = async <T>(endpoint: string, body: Record<string, any>): Promise<T> => {
    return await $fetch<T>(endpoint, {
      baseURL,
      method: 'PATCH',
      body,
      credentials: 'include',
    })
  }

  const destroy = async (endpoint: string): Promise<void> => {
    await $fetch(endpoint, {
      baseURL,
      method: 'DELETE',
      credentials: 'include',
    })
  }

  return { get, post, patch, destroy }
}