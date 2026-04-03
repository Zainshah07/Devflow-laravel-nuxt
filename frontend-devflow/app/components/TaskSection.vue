<template>
  <div class="task-section">
    <div class="section-header">
      <div class="section-title">
        <span class="status-dot" :style="{ background: color }" />
        {{ title }}
        <span class="count-badge">{{ tasks.length }}</span>
      </div>
    </div>

    <div v-if="tasks.length === 0" class="empty-section">
      No tasks here.
    </div>

    <div v-else class="task-list">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="task-card"
      >
        <div class="task-card-top">
          <span class="task-title">{{ task.title }}</span>
          <span
            class="priority-badge"
            :class="`priority-badge--${task.priority}`"
          >
            {{ task.priority }}
          </span>
        </div>

        <p v-if="task.description" class="task-desc">
          {{ task.description }}
        </p>

        <div class="task-card-footer">
          <span v-if="task.due_date" class="due-date" :class="{ overdue: isOverdue(task.due_date) }">
            Due {{ formatDate(task.due_date) }}
          </span>

          <div class="status-actions">
            <button
              v-if="task.status !== 'todo'"
              class="action-btn"
              @click="$emit('status-change', task.id, prevStatus(task.status))"
            >
              ← Back
            </button>
            <button
              v-if="task.status !== 'done'"
              class="action-btn action-btn--forward"
              @click="$emit('status-change', task.id, nextStatus(task.status))"
            >
              {{ task.status === 'todo' ? 'Start' : 'Complete' }} →
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Task, TaskStatus } from '~/types'

defineProps<{
  title: string
  color: string
  tasks: Task[]
}>()

defineEmits<{
  'status-change': [taskId: number, newStatus: TaskStatus]
}>()

// DSA concept: nextStatus and prevStatus are graph traversal helpers.
// They move forward and backward along the directed edges of the status graph.
// Todo → InProgress → Done is the only valid forward path.
// This is a linear graph (chain) — each node has at most one forward and one backward edge.
function nextStatus(current: TaskStatus): TaskStatus {
  const map: Record<TaskStatus, TaskStatus> = {
    todo:        'in_progress',
    in_progress: 'done',
    done:        'done',
  }
  return map[current]
}

function prevStatus(current: TaskStatus): TaskStatus {
  const map: Record<TaskStatus, TaskStatus> = {
    todo:        'todo',
    in_progress: 'todo',
    done:        'in_progress',
  }
  return map[current]
}

function isOverdue(dateString: string): boolean {
  return new Date(dateString) < new Date()
}

function formatDate(dateString: string): string {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
  }).format(new Date(dateString))
}
</script>

<style scoped>
.task-section {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.section-header {
  padding: 12px 16px;
  border-bottom: 1px solid #f3f4f6;
  background: #fafafa;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 500;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  flex-shrink: 0;
}

.count-badge {
  font-size: 11px;
  background: #e5e7eb;
  color: #6b7280;
  padding: 1px 6px;
  border-radius: 99px;
  font-weight: 400;
}

.empty-section {
  padding: 16px;
  font-size: 12px;
  color: #d1d5db;
  text-align: center;
}

.task-list {
  padding: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.task-card {
  padding: 12px;
  border: 1px solid #f3f4f6;
  border-radius: 8px;
  background: #ffffff;
  transition: border-color 0.15s;
}

.task-card:hover {
  border-color: #e5e7eb;
}

.task-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 4px;
}

.task-title {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
  line-height: 1.4;
}

.priority-badge {
  font-size: 10px;
  font-weight: 500;
  padding: 2px 6px;
  border-radius: 99px;
  text-transform: capitalize;
  flex-shrink: 0;
}

.priority-badge--high   { background: #faece7; color: #993c1d; }
.priority-badge--medium { background: #faeeda; color: #854f0b; }
.priority-badge--low    { background: #e1f5ee; color: #0f6e56; }

.task-desc {
  font-size: 12px;
  color: #9ca3af;
  line-height: 1.5;
  margin-bottom: 8px;
}

.task-card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 8px;
}

.due-date {
  font-size: 11px;
  color: #9ca3af;
}

.due-date.overdue {
  color: #dc2626;
  font-weight: 500;
}

.status-actions {
  display: flex;
  gap: 6px;
  margin-left: auto;
}

.action-btn {
  font-size: 11px;
  padding: 3px 8px;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  transition: background 0.1s;
}

.action-btn:hover {
  background: #f3f4f6;
  color: #111827;
}

.action-btn--forward {
  border-color: #111827;
  color: #111827;
}

.action-btn--forward:hover {
  background: #111827;
  color: #ffffff;
}
</style>