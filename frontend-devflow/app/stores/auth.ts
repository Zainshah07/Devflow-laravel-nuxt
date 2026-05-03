import { defineStore } from 'pinia'
import type { AuthUser, LoginPayload, RegisterPayload, AuthResponse } from '~/types'

export const useAuthStore = defineStore('auth', () => {
  const accessToken = ref<string | null>(null)
  const user        = ref<AuthUser | null>(null)
  const loading     = ref(false)

  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)

  function initializeEcho(): void {
    if (!accessToken.value) return
    try {
      const { $initEcho } = useNuxtApp()
      $initEcho(accessToken.value)
    } catch {}
  }

  function teardownEcho(): void {
    try {
      const { $disconnectEcho } = useNuxtApp()
      $disconnectEcho()
    } catch {}
  }

  async function register(payload: RegisterPayload): Promise<void> {
    loading.value = true
    try {
      const response = await $fetch<AuthResponse>('/auth/register', {
        baseURL:     useRuntimeConfig().public.apiBase,
        method:      'POST',
        body:        payload,
        credentials: 'include',
      })
      accessToken.value = response.data.access_token
      user.value        = response.data.user
      initializeEcho()
    } finally {
      loading.value = false
    }
  }

  async function login(payload: LoginPayload): Promise<void> {
    loading.value = true
    try {
      const response = await $fetch<AuthResponse>('/auth/login', {
        baseURL:     useRuntimeConfig().public.apiBase,
        method:      'POST',
        body:        payload,
        credentials: 'include',
      })
      accessToken.value = response.data.access_token
      user.value        = response.data.user
      initializeEcho()
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
  // 1. Stop real-time listeners immediately
  teardownEcho()

  // 2. Clear local state IMMEDIATELY (Optimistic)
  const tokenToRevoke = accessToken.value
  accessToken.value = null
  user.value = null

  // 3. Now tell the server to revoke the tokens in the background
  try {
    await $fetch('/auth/logout', {
      baseURL: useRuntimeConfig().public.apiBase,
      method: 'POST',
      credentials: 'include',
      headers: tokenToRevoke ? { Authorization: `Bearer ${tokenToRevoke}` } : {},
    })
  } catch (err) {
    // We don't usually care if logout fails on the server 
    // because the local tokens are already gone.
    console.error('Server-side logout failed', err)
  }
}

  async function silentRefresh(): Promise<boolean> {
    try {
      const response = await $fetch<AuthResponse>('/auth/refresh', {
        baseURL:     useRuntimeConfig().public.apiBase,
        method:      'POST',
        credentials: 'include',
      })
      accessToken.value = response.data.access_token
      user.value        = response.data.user
      initializeEcho()
      return true
    } catch {
      accessToken.value = null
      user.value        = null
      return false
    }
  }

  async function fetchMe(): Promise<void> {
    if (!accessToken.value) return
    try {
      const response = await $fetch<{ data: AuthUser }>('/auth/me', {
        baseURL:     useRuntimeConfig().public.apiBase,
        method:      'GET',
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${accessToken.value}`,
        },
      })
      user.value = response.data
    } catch {
      accessToken.value = null
      user.value        = null
    }
  }

  return {
    accessToken,
    user,
    loading,
    isAuthenticated,
    register,
    login,
    logout,
    silentRefresh,
    fetchMe,
  }
})