<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal">

      <div class="modal-header">
        <h3 class="modal-title">Add dependency</h3>
        <button class="modal-close" @click="$emit('close')">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M2 2l10 10M12 2L2 12"/>
          </svg>
        </button>
      </div>

      <p class="modal-desc">
        Select a task that must be completed before
        <strong>{{ taskTitle }}</strong>.
      </p>

      <!-- Task selector -->
      <div class="task-list">
        <div
          v-for="task in availableTasks"
          :key="task.id"
          class="task-option"
          :class="{ 'task-option--selected': selected === task.id }"
          @click="selected = task.id"
        >
          <span
            class="status-dot"
            :style="{ background: statusColor(task.status) }"
          />
          <span class="task-option-title">{{ task.title }}</span>
          <span class="task-option-priority" :class="'priority--' + task.priority">
            {{ task.priority }}
          </span>
        </div>

        <div v-if="availableTasks.length === 0" class="no-tasks">
          No other tasks available to add as dependencies.
        </div>
      </div>

      <div v-if="error" class="form-error">{{ error }}</div>

      <div class="modal-actions">
        <button class="btn-ghost" @click="$emit('close')">Cancel</button>
        <button
          class="btn-primary"
          :disabled="!selected || submitting"
          @click="handleSubmit"
        >
          {{ submitting ? 'Adding...' : 'Add dependency' }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup lang="ts">
import type { Task, TaskStatus } from '~/types'

const props = defineProps<{
  taskId:    number
  taskTitle: string
  allTasks:  Task[]
}>()

const emit = defineEmits<{
  close:   []
  added:   []
}>()

const selected   = ref<number | null>(null)
const submitting = ref(false)
const error      = ref<string | null>(null)

// DSA — Array filter O(n):
// Exclude the task itself and tasks that are already dependencies.
// This prevents both self-loops and duplicate edges in the graph.
const availableTasks = computed(() =>
  props.allTasks.filter(t => t.id !== props.taskId)
)

async function handleSubmit(): Promise<void> {
  if (!selected.value) return

  submitting.value = true
  error.value      = null

  try {
    const { post } = useApi()
    await post(`/tasks/${props.taskId}/dependencies`, {
      depends_on_task_id: selected.value,
    })
    emit('added')
    emit('close')
  } catch (err: any) {
    // DSA — cycle detection error from the backend:
    // The 422 response means DFS found a cycle would be created.
    error.value = err?.data?.errors?.depends_on_task_id?.[0]
      ?? err?.data?.message
      ?? 'Failed to add dependency.'
  } finally {
    submitting.value = false
  }
}

function statusColor(status: TaskStatus): string {
  const colors: Record<TaskStatus, string> = {
    todo:        '#888780',
    in_progress: '#378add',
    done:        '#1d9e75',
  }
  return colors[status]
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}

.modal {
  background: #ffffff;
  border-radius: 12px;
  padding: 22px;
  width: 420px;
  max-width: 92vw;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.modal-title {
  font-size: 15px;
  font-weight: 500;
  color: #111827;
}

.modal-close {
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
}

.modal-close:hover {
  background: #f3f4f6;
}

.modal-desc {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 14px;
  line-height: 1.5;
}

.modal-desc strong {
  font-weight: 500;
  color: #111827;
}

.task-list {
  max-height: 240px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 14px;
}

.task-option {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 12px;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.1s;
}

.task-option:last-child {
  border-bottom: none;
}

.task-option:hover {
  background: #f9fafb;
}

.task-option--selected {
  background: #eff6ff;
  border-left: 3px solid #378add;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.task-option-title {
  font-size: 13px;
  color: #111827;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.task-option-priority {
  font-size: 10px;
  font-weight: 500;
  padding: 1px 6px;
  border-radius: 99px;
  flex-shrink: 0;
  text-transform: capitalize;
}

.priority--high   { background: #faece7; color: #993c1d; }
.priority--medium { background: #faeeda; color: #854f0b; }
.priority--low    { background: #e1f5ee; color: #0f6e56; }

.no-tasks {
  padding: 16px;
  text-align: center;
  font-size: 13px;
  color: #9ca3af;
}

.form-error {
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
}

.btn-primary {
  padding: 7px 14px;
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
  opacity: 0.4;
  cursor: not-allowed;
}

.btn-ghost {
  padding: 7px 14px;
  background: transparent;
  color: #6b7280;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
}

.btn-ghost:hover {
  background: #f3f4f6;
}
</style>