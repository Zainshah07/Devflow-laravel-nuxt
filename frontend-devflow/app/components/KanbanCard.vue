<template>
  <div
    class="task-card"
    :data-task-id="task.id"
    :class="{ 'task-card--overdue': task.is_overdue }"
  >
    <!-- Card top row -->
    <div class="card-top">
      <span class="task-title">{{ task.title }}</span>
      <button class="delete-btn" @click.stop="$emit('delete', task.id)">
        <svg width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M2 2l8 8M10 2L2 10"/>
        </svg>
      </button>
    </div>

    <!-- Description preview -->
    <p v-if="task.description" class="task-desc">
      {{ task.description }}
    </p>

    <!-- Footer row: priority badge + due date -->
    <div class="card-footer">
      <span class="priority-badge" :class="`priority-badge--${task.priority}`">
        {{ task.priority }}
      </span>

      <span v-if="task.due_date" class="due-date" :class="{ 'due-date--overdue': task.is_overdue }">
        {{ task.is_overdue ? 'Overdue' : formatDate(task.due_date) }}
      </span>
    </div>

    <!-- Assignee avatars -->
    <div v-if="task.assignees && task.assignees.length" class="assignees">
      <div
        v-for="user in task.assignees.slice(0, 3)"
        :key="user.id"
        class="assignee-avatar"
        :title="user.name"
      >
        {{ initials(user.name) }}
      </div>
      <span v-if="task.assignees.length > 3" class="more-assignees">
        +{{ task.assignees.length - 3 }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Task } from '~/types'

defineProps<{
  task: Task
}>()

defineEmits<{
  delete: [taskId: number]
}>()

function formatDate(dateString: string): string {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day:   'numeric',
  }).format(new Date(dateString))
}

function initials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map(n => n[0])
    .join('')
    .toUpperCase()
}
</script>

<style scoped>
.task-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 10px 12px;
  cursor: grab;
  user-select: none;
  transition: border-color 0.15s;
}

.task-card:hover {
  border-color: #d1d5db;
}

.task-card:active {
  cursor: grabbing;
}

.task-card--overdue {
  border-left: 3px solid #ef4444;
}

.card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 4px;
}

.task-title {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
  line-height: 1.4;
  flex: 1;
}

.delete-btn {
  flex-shrink: 0;
  width: 20px;
  height: 20px;
  border: none;
  background: transparent;
  color: #d1d5db;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: background 0.1s, color 0.1s;
}

.delete-btn:hover {
  background: #fee2e2;
  color: #ef4444;
}

.task-desc {
  font-size: 12px;
  color: #9ca3af;
  line-height: 1.5;
  margin-bottom: 8px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.card-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 8px;
}

.priority-badge {
  font-size: 10px;
  font-weight: 500;
  padding: 2px 7px;
  border-radius: 99px;
  text-transform: capitalize;
}

.priority-badge--high   { background: #faece7; color: #993c1d; }
.priority-badge--medium { background: #faeeda; color: #854f0b; }
.priority-badge--low    { background: #e1f5ee; color: #0f6e56; }

.due-date {
  font-size: 11px;
  color: #9ca3af;
}

.due-date--overdue {
  color: #ef4444;
  font-weight: 500;
}

.assignees {
  display: flex;
  align-items: center;
  margin-top: 8px;
  gap: -4px;
}

.assignee-avatar {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #e6f1fb;
  color: #185fa5;
  font-size: 9px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px solid #ffffff;
  margin-left: -4px;
}

.assignee-avatar:first-child {
  margin-left: 0;
}

.more-assignees {
  font-size: 10px;
  color: #9ca3af;
  margin-left: 4px;
}
</style>