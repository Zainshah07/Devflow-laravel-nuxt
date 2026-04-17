<template>
  <div class="auth-page">
    <div class="auth-card">

      <div class="auth-header">
        <h1 class="auth-title">DevFlow</h1>
        <p class="auth-sub">Create your account</p>
      </div>

      <form @submit.prevent="handleRegister">

        <div class="form-group">
          <label class="form-label">Name</label>
          <input
            v-model="form.name"
            class="form-input"
            :class="{ 'form-input--error': errors.name }"
            type="text"
            placeholder="Ali Khan"
            autocomplete="name"
            required
          />
          <span v-if="errors.name" class="field-error">
            {{ errors.name[0] }}
          </span>
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <input
            v-model="form.email"
            class="form-input"
            :class="{ 'form-input--error': errors.email }"
            type="email"
            placeholder="you@example.com"
            autocomplete="email"
            required
          />
          <span v-if="errors.email" class="field-error">
            {{ errors.email[0] }}
          </span>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <input
            v-model="form.password"
            class="form-input"
            :class="{ 'form-input--error': errors.password }"
            type="password"
            placeholder="Min. 8 characters"
            autocomplete="new-password"
            required
          />
          <span v-if="errors.password" class="field-error">
            {{ errors.password[0] }}
          </span>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm password</label>
          <input
            v-model="form.password_confirmation"
            class="form-input"
            :class="{ 'form-input--error': errors.password_confirmation }"
            type="password"
            placeholder="Repeat your password"
            autocomplete="new-password"
            required
          />
          <span v-if="errors.password_confirmation" class="field-error">
            {{ errors.password_confirmation[0] }}
          </span>
        </div>

        <div v-if="apiError" class="api-error">
          {{ apiError }}
        </div>

        <button
          type="submit"
          class="btn-submit"
          :disabled="authStore.loading"
        >
          {{ authStore.loading ? 'Creating account...' : 'Create account' }}
        </button>

      </form>

      <p class="auth-footer">
        Already have an account?
        <NuxtLink to="/login" class="auth-link">Sign in</NuxtLink>
      </p>

    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'
import type { ValidationError } from '~/types'

definePageMeta({
  layout:     false,
  middleware: 'guest',
})

const authStore = useAuthStore()
const router    = useRouter()

const form = reactive({
  name:                  '',
  email:                 '',
  password:              '',
  password_confirmation: '',
})

const errors   = ref<Record<string, string[]>>({})
const apiError = ref<string | null>(null)

async function handleRegister(): Promise<void> {
  errors.value   = {}
  apiError.value = null

  try {
    await authStore.register({
      name:                  form.name,
      email:                 form.email,
      password:              form.password,
      password_confirmation: form.password_confirmation,
    })

    await router.push('/')
  } catch (err: any) {
    if (err?.status === 422) {
      const data = err.data as ValidationError
      errors.value = data.errors ?? {}
    } else {
      apiError.value = 'Something went wrong. Please try again.'
    }
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f9fafb;
  padding: 24px;
}

.auth-card {
  width: 100%;
  max-width: 380px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 36px 32px;
}

.auth-header {
  text-align: center;
  margin-bottom: 28px;
}

.auth-title {
  font-size: 22px;
  font-weight: 600;
  letter-spacing: -0.4px;
  color: #111827;
}

.auth-sub {
  font-size: 13px;
  color: #9ca3af;
  margin-top: 4px;
}

.form-group {
  margin-bottom: 14px;
}

.form-label {
  display: block;
  font-size: 12px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 5px;
}

.form-input {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #d1d5db;
  border-radius: 7px;
  font-size: 14px;
  color: #111827;
  background: #f9fafb;
  outline: none;
  transition: border-color 0.15s, background 0.15s;
}

.form-input:focus {
  border-color: #6b7280;
  background: #ffffff;
}

.form-input--error {
  border-color: #ef4444;
}

.field-error {
  display: block;
  font-size: 11px;
  color: #ef4444;
  margin-top: 4px;
}

.api-error {
  font-size: 13px;
  color: #ef4444;
  background: #fee2e2;
  border-radius: 7px;
  padding: 9px 12px;
  margin-bottom: 16px;
  text-align: center;
}

.btn-submit {
  width: 100%;
  padding: 10px;
  background: #111827;
  color: #ffffff;
  border: none;
  border-radius: 7px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  margin-top: 4px;
  transition: opacity 0.15s;
}

.btn-submit:hover:not(:disabled) {
  opacity: 0.85;
}

.btn-submit:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.auth-footer {
  text-align: center;
  font-size: 13px;
  color: #9ca3af;
  margin-top: 20px;
}

.auth-link {
  color: #111827;
  font-weight: 500;
  text-decoration: underline;
  text-underline-offset: 2px;
}
</style>