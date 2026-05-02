<template>
  <div class="kanban">
    <div
      v-for="column in columns"
      :key="column.status"
      class="kanban-column"
    >
      <div class="column-header">
        <div class="column-title">
          <span class="column-dot" :style="{ background: column.color }" />
          <span>{{ column.label }}</span>
          <span class="column-count">{{ column.list.value.length }}</span>
        </div>
        <button class="add-btn" @click="$emit('add-task', column.status)">+</button>
      </div>

      <div
        v-draggable="[
          column.list,
          {
            group: 'tasks',
            animation: 150,
            ghostClass: 'task-ghost',
            onEnd: onDragEnd,
          }
        ]"
        class="task-list"
        :data-status="column.status"
      >
        <div
          v-for="task in column.list.value"
          :key="task.id"
          :data-task-id="task.id"
          :data-task-status="task.status"
        >
          <KanbanCard
            :task="task"
            @delete="$emit('delete-task', $event)"
          />
        </div>
      </div>

      <div v-if="column.list.value.length === 0" class="empty-column">
        No tasks
      </div>

      <button class="add-task-row" @click="$emit('add-task', column.status)">
        <span>+</span> Add task
      </button>
    </div>

    <!-- Error toast for invalid transitions -->
    <Transition name="toast">
      <div v-if="transitionError" class="transition-toast">
        {{ transitionError }}
      </div>
    </Transition>
  </div>
</template>

<script setup lang="ts">
import { vDraggable } from 'vue-draggable-plus'
import type { Task, TaskStatus } from '~/types'
import { useTaskStore } from '~/stores/tasks'

defineProps<{ projectId: number }>()

const emit = defineEmits<{
  'add-task':    [status: TaskStatus]
  'delete-task': [taskId: number]
}>()

const taskStore       = useTaskStore()
const transitionError = ref<string | null>(null)

// DSA — Directed Graph adjacency list: valid status transitions
const validTransitions: Record<TaskStatus, TaskStatus[]> = {
  todo:        ['in_progress'],
  in_progress: ['done'],
  done:        [],
}

// Writable refs for each column
// v-draggable needs actual refs it can mutate, not computed arrays
const todoList       = ref<Task[]>([])
const inProgressList = ref<Task[]>([])
const doneList       = ref<Task[]>([])

// Sync from store whenever tasks change
// The spread () => [...taskStore.tasks] ensures Vue detects splice mutations
watch(
  () => [...taskStore.tasks],
  (newTasks) => {
    todoList.value       = newTasks.filter(t => t.status === 'todo')
    inProgressList.value = newTasks.filter(t => t.status === 'in_progress')
    doneList.value       = newTasks.filter(t => t.status === 'done')
  },
  { immediate: true }
)

// columns references the refs directly so the template can use column.list.value
const columns = [
  { status: 'todo'        as TaskStatus, label: 'Todo',        color: '#888780', list: todoList },
  { status: 'in_progress' as TaskStatus, label: 'In Progress', color: '#378add', list: inProgressList },
  { status: 'done'        as TaskStatus, label: 'Done',        color: '#1d9e75', list: doneList },
]

function syncFromStore(): void {
  const all            = taskStore.tasks
  todoList.value       = all.filter(t => t.status === 'todo')
  inProgressList.value = all.filter(t => t.status === 'in_progress')
  doneList.value       = all.filter(t => t.status === 'done')
}

function showError(message: string): void {
  transitionError.value = message
  setTimeout(() => { transitionError.value = null }, 3000)
}

async function onDragEnd(evt: any): Promise<void> {
  const taskId    = Number(evt.item?.dataset?.taskId)
  const oldStatus = evt.item?.dataset?.taskStatus as TaskStatus
  const newStatus = (evt.to as HTMLElement)?.dataset?.status as TaskStatus

  if (!taskId || !oldStatus || !newStatus) {
    syncFromStore()
    return
  }

  // Same column — nothing to do
  if (oldStatus === newStatus) {
    return
  }

  // DSA — Graph edge check: is this transition valid?
  if (!validTransitions[oldStatus]?.includes(newStatus)) {
    const messages: Record<string, string> = {
      'todo-done':        'Tasks must go to In Progress before Done.',
      'in_progress-todo': 'Tasks cannot move backwards to Todo.',
      'done-todo':        'Completed tasks cannot be moved back.',
      'done-in_progress': 'Completed tasks cannot be moved back.',
    }
    showError(messages[`${oldStatus}-${newStatus}`] ?? 'Invalid transition.')
    // Revert DOM immediately — no API call
    syncFromStore()
    return
  }

  // Valid — update store (handles optimistic update + API + rollback)
  try {
    await taskStore.updateStatus(taskId, newStatus)
  } catch (err: any) {
    showError(err?.data?.message ?? 'Failed to update task.')
    syncFromStore()
  }
}
</script>

<style scoped>
.kanban {
  position: relative;
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 14px;
  align-items: start;
}

.kanban-column {
  background: #f3f4f6;
  border-radius: 10px;
  padding: 10px;
  min-height: 200px;
}

.column-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 10px;
  padding: 0 2px;
}

.column-title {
  display: flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.column-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.column-count {
  font-size: 11px;
  font-weight: 400;
  background: #e5e7eb;
  color: #6b7280;
  padding: 1px 6px;
  border-radius: 99px;
}

.add-btn {
  width: 22px;
  height: 22px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: transparent;
  color: #9ca3af;
  font-size: 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.1s, color 0.1s;
}

.add-btn:hover {
  background: #ffffff;
  color: #111827;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 7px;
  min-height: 60px;
}

.task-ghost {
  opacity: 0.4;
  background: #dbeafe;
  border-radius: 8px;
}

.empty-column {
  text-align: center;
  font-size: 12px;
  color: #d1d5db;
  padding: 12px 0;
}

.add-task-row {
  display: flex;
  align-items: center;
  gap: 5px;
  width: 100%;
  padding: 7px 8px;
  margin-top: 8px;
  border: 1px dashed #d1d5db;
  border-radius: 7px;
  background: transparent;
  color: #9ca3af;
  font-size: 12px;
  cursor: pointer;
  transition: background 0.1s, color 0.1s;
}

.add-task-row:hover {
  background: #ffffff;
  color: #374151;
}

.transition-toast {
  position: fixed;
  bottom: 28px;
  left: 50%;
  transform: translateX(-50%);
  background: #1f2937;
  color: #ffffff;
  font-size: 13px;
  font-weight: 500;
  padding: 10px 22px;
  border-radius: 8px;
  z-index: 9999;
  box-shadow: 0 4px 16px rgba(0,0,0,0.2);
  pointer-events: none;
  white-space: nowrap;
}

.toast-enter-active,
.toast-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(8px);
}
</style>