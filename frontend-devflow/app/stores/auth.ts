import { defineStore } from 'pinia'
import type { AuthUser, LoginPayload, RegisterPayload, AuthResponse  } from '~/types'

export const useAuthStore = defineStore('auth', () => {

  // Access token stored in memory — NOT in localStorage.
  // localStorage is accessible to any JavaScript on the page.
  // An XSS attack could read it. Memory storage disappears on
  // tab close, so the refresh token cookie handles persistence.
  const accessToken = ref<string | null>(null)
  const user        = ref<AuthUser | null>(null)
  const loading     = ref(false)

  const isAuthenticated = computed(() => !!accessToken.value && !!user.value)

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

      // Store access token in memory
      // DSA — the access token is ephemeral state — like a local
      // variable in a function. It exists only for this session.
      accessToken.value = response.data.access_token
      user.value        = response.data.user
    } finally {
      loading.value = false
    }
  }

  async function logout(): Promise<void> {
    try {
      await $fetch('/auth/logout', {
        baseURL:     useRuntimeConfig().public.apiBase,
        method:      'POST',
        credentials: 'include',
        headers: accessToken.value
          ? { Authorization: `Bearer ${accessToken.value}` }
          : {},
      })
    } catch {
      // Proceed with local cleanup even if the server request fails
    } finally {
      accessToken.value = null
      user.value        = null
    }
  }

  // DSA — Sliding Window refresh:
  // Called when the access token has expired or is missing.
  // Sends the httpOnly cookie (refresh token) to the server.
  // Server verifies it, rotates it, and issues a new access token
  // with a fresh 15-minute window.
  async function silentRefresh(): Promise<boolean> {
    try {
      const response = await $fetch<AuthResponse>('/auth/refresh', {
        baseURL:     useRuntimeConfig().public.apiBase,
        method:      'POST',
        credentials: 'include',
      })

      accessToken.value = response.data.access_token
      user.value        = response.data.user

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