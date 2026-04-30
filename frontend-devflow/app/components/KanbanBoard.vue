<template>
  <div class="kanban">
    <div
      v-for="column in columns"
      :key="column.status"
      class="kanban-column"
    >
      <!-- Header Section -->
      <div class="column-header">
        <div class="column-title">
          <span class="column-dot" :style="{ background: column.color }" />
          <span>{{ column.label }}</span>
          <span class="column-count">{{ column.tasks.length }}</span>
        </div>
        <button class="add-btn" @click="$emit('add-task', column.status)">+</button>
      </div>

      <!-- Task List Section -->
      <!-- We use a standard div and apply the v-draggable directive -->
      <div
        v-draggable="[column.tasks, { group: 'tasks', animation: 150, onEnd: onDragEnd }]"
        class="task-list"
        :data-status="column.status"
        style="min-height: 100px; display: flex; flex-direction: column; gap: 12px;"
      >
        <div 
          v-for="task in column.tasks" 
          :key="task.id"
          :data-task-id="task.id"
          class="task-item"
        >
          <KanbanCard
            :task="task"
            @delete="$emit('delete-task', $event)"
          />
        </div>

        <!-- Fallback if array has items but they aren't showing -->
        <div v-if="column.tasks.length > 0 && !column.tasks[0].id" style="color: red;">
          Data Error: Task object missing ID
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="column.tasks.length === 0" class="empty-column">
        No tasks
      </div>

      <button class="add-task-row" @click="$emit('add-task', column.status)">
        <span>+</span> Add task
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
// Import the directive instead of the component
import { vDraggable } from 'vue-draggable-plus'
import type { Task, TaskStatus } from '~/types'
import { useTaskStore } from '~/stores/tasks'

const props = defineProps<{ projectId: number }>()
const emit = defineEmits(['add-task', 'delete-task'])
const taskStore = useTaskStore()

// Direct computation ensures the UI reacts the moment taskStore.tasks changes
const columns = computed(() => {
  const allTasks = taskStore.tasks || []
  return [
    { status: 'todo' as TaskStatus, label: 'Todo', color: '#888780', tasks: allTasks.filter(t => t.status === 'todo') },
    { status: 'in_progress' as TaskStatus, label: 'In Progress', color: '#378add', tasks: allTasks.filter(t => t.status === 'in_progress') },
    { status: 'done' as TaskStatus, label: 'Done', color: '#1d9e75', tasks: allTasks.filter(t => t.status === 'done') }
  ]
})

async function onDragEnd(evt: any) {
  const taskId = Number(evt.item?.dataset?.taskId)
  const newStatus = evt.to?.dataset?.status as TaskStatus

  if (!taskId || !newStatus) return

  const task = taskStore.tasks.find(t => t.id === taskId)
  if (!task || task.status === newStatus) return

  const previousStatus = task.status
  task.status = newStatus // Optimistic update

  try {
    await taskStore.updateStatus(taskId, newStatus)
  } catch (err) {
    task.status = previousStatus // Rollback
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

.task-dragging {
  transform: rotate(1.5deg);
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
</style>