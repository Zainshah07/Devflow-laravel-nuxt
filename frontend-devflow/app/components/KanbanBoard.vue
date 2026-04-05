<template>
  <div class="kanban">
    <div
      v-for="column in columns"
      :key="column.status"
      class="kanban-column"
    >
      <!-- Column header -->
      <div class="column-header">
        <div class="column-title">
          <span class="column-dot" :style="{ background: column.color }" />
          <span>{{ column.label }}</span>
          <span class="column-count">{{ column.tasks.length }}</span>
        </div>
        <button class="add-btn" @click="$emit('add-task', column.status)">
          +
        </button>
      </div>

      <!-- Draggable task list -->
      <!-- DSA: VueDraggable renders the tasks array for this column.      -->
      <!-- When a card is dropped into a new column, the onUpdate handler  -->
      <!-- fires. We call updateStatus which performs an optimistic array  -->
      <!-- mutation (O(1) find by reference + status reassignment) then    -->
      <!-- sends the PATCH request. This is the same as a swap operation   -->
      <!-- in array sorting — move the element to its new position.        -->
      <VueDraggable
        v-model="column.tasks"
        group="tasks"
        class="task-list"
        ghost-class="task-ghost"
        drag-class="task-dragging"
        :animation="150"
        @end="(evt) => handleDrop(evt, column.status)"
      >
        <KanbanCard
          v-for="task in column.tasks"
          :key="task.id"
          :task="task"
          @delete="$emit('delete-task', $event)"
        />
      </VueDraggable>

      <!-- Empty state -->
      <div v-if="column.tasks.length === 0" class="empty-column">
        No tasks
      </div>

      <!-- Add task shortcut at the bottom of each column -->
      <button class="add-task-row" @click="$emit('add-task', column.status)">
        <span class="plus-icon">+</span> Add task
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { VueDraggable } from 'vue-draggable-plus'
import type { Task, TaskStatus } from '~/types'
import { useTaskStore } from '~/stores/tasks'

const props = defineProps<{
  projectId: number
}>()

const emit = defineEmits<{
  'add-task':    [status: TaskStatus]
  'delete-task': [taskId: number]
}>()

const taskStore = useTaskStore()

// DSA — Derived data structure:
// columns is computed from the tasks array. Each column is a filtered
// view of the same underlying array. This is the same as partitioning
// an array into k groups by a key — here the key is status.
// Time complexity: 3 × O(n) filters = O(n) total.
// Space complexity: O(n) — no data is duplicated, just references.
const columns = computed(() => [
  {
    status: 'todo' as TaskStatus,
    label:  'Todo',
    color:  '#888780',
    tasks:  taskStore.todoTasks,
  },
  {
    status: 'in_progress' as TaskStatus,
    label:  'In Progress',
    color:  '#378add',
    tasks:  taskStore.inProgressTasks,
  },
  {
    status: 'done' as TaskStatus,
    label:  'Done',
    color:  '#1d9e75',
    tasks:  taskStore.doneTasks,
  },
])

// Called when a card is dropped into any column
// evt.item carries the dragged DOM element — we read the task ID from its data attribute
// Then we call updateStatus which does the optimistic update + API call
async function handleDrop(evt: any, newStatus: TaskStatus): Promise<void> {
  const taskId = Number(evt.item?.dataset?.taskId)
  if (!taskId) return

  const task = taskStore.tasks.find(t => t.id === taskId)
  if (!task || task.status === newStatus) return

  try {
    await taskStore.updateStatus(taskId, newStatus)
  } catch (err: any) {
    // The store already rolled back the optimistic update
    // Show a toast or alert if you have one, otherwise log
    console.error('Status update failed:', err?.data?.message ?? err.message)
  }
}
</script>

<style scoped>
.kanban {
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
  line-height: 1;
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
  min-height: 40px;
}

.task-ghost {
  opacity: 0.4;
  background: #dbeafe;
  border-radius: 8px;
}

.task-dragging {
  transform: rotate(1.5deg);
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
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
  border-color: #9ca3af;
}

.plus-icon {
  font-size: 14px;
  line-height: 1;
}
</style>