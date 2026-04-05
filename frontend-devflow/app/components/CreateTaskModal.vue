<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <h3 class="modal-title">Create task</h3>
        <button class="close-btn" @click="$emit('close')">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M2 2l10 10M12 2L2 12"/>
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="form-group">
          <label class="form-label">Title <span class="required">*</span></label>
          <input
            ref="titleInput"
            v-model="form.title"
            class="form-input"
            :class="{ 'form-input--error': errors.title }"
            placeholder="What needs to be done?"
            required
          />
          <span v-if="errors.title" class="field-error">{{ errors.title[0] }}</span>
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea
            v-model="form.description"
            class="form-input"
            rows="3"
            placeholder="Optional details..."
          />
        </div>

        <div class="form-row-2">
          <div class="form-group">
            <label class="form-label">Priority <span class="required">*</span></label>
            <select v-model="form.priority" class="form-input">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Due date</label>
            <input
              v-model="form.due_date"
              class="form-input"
              type="date"
              :min="today"
            />
          </div>
        </div>

        <div v-if="apiError" class="api-error">
          {{ apiError }}
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-ghost" @click="$emit('close')">
            Cancel
          </button>
          <button type="submit" class="btn-primary" :disabled="submitting">
            {{ submitting ? 'Creating...' : 'Create task' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { TaskStatus, ValidationError } from '~/types'
import { useTaskStore } from '~/stores/tasks'

const props = defineProps<{
  projectId: number
  defaultStatus?: TaskStatus
}>()

const emit = defineEmits<{
  close:   []
  created: []
}>()

const taskStore  = useTaskStore()
const titleInput = ref<HTMLInputElement>()

const form = reactive({
  title:       '',
  description: '',
  priority:    'medium',
  due_date:    '',
})

const errors    = ref<Record<string, string[]>>({})
const apiError  = ref<string | null>(null)
const submitting = ref(false)

// Auto-focus the title input when modal opens
onMounted(() => {
  titleInput.value?.focus()
})

const today = computed(() =>
  new Date().toISOString().split('T')[0]
)

async function handleSubmit(): Promise<void> {
  if (!form.title.trim()) return

  submitting.value = true
  errors.value     = {}
  apiError.value   = null

  try {
    await taskStore.createTask(props.projectId, {
      title:       form.title,
      description: form.description || undefined,
      priority:    form.priority,
      due_date:    form.due_date || undefined,
    })

    emit('created')
    emit('close')
  } catch (err: any) {
    // DSA: the errors object is a hash map — field name → array of messages.
    // Laravel's 422 response structure is: { message, errors: { field: [msg] } }
    // We read from it like a hash map: errors['title'] gives the title errors.
    if (err?.status === 422) {
      const data = err.data as ValidationError
      errors.value = data.errors ?? {}
      apiError.value = data.message ?? null
    } else {
      apiError.value = 'Something went wrong. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  width: 440px;
  max-width: 92vw;
  box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.modal-title {
  font-size: 15px;
  font-weight: 500;
  color: #111827;
}

.close-btn {
  width: 28px;
  height: 28px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: transparent;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.1s;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #111827;
}

.form-group {
  margin-bottom: 14px;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.form-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #6b7280;
  margin-bottom: 5px;
}

.required {
  color: #ef4444;
}

.form-input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
  color: #111827;
  background: #f9fafb;
  outline: none;
  transition: border-color 0.15s, background 0.15s;
  resize: vertical;
}

.form-input:focus {
  border-color: #6b7280;
  background: #ffffff;
}

.form-input--error {
  border-color: #ef4444;
}

.field-error {
  font-size: 11px;
  color: #ef4444;
  margin-top: 3px;
  display: block;
}

.api-error {
  font-size: 12px;
  color: #ef4444;
  background: #fee2e2;
  border-radius: 6px;
  padding: 8px 10px;
  margin-bottom: 12px;
}

.modal-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  padding-top: 4px;
}

.btn-primary {
  padding: 8px 16px;
  background: #111827;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.15s;
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-ghost {
  padding: 8px 16px;
  background: transparent;
  color: #6b7280;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.1s;
}

.btn-ghost:hover {
  background: #f3f4f6;
}
</style>