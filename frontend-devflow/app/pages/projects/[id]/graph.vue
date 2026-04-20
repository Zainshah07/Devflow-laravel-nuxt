<template>
  <div>
    <div class="page-header">
      <div class="header-left">
        <NuxtLink :to="`/projects/${projectId}`" class="back-link">
          <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 2L4 7l5 5"/>
          </svg>
          Back to board
        </NuxtLink>
        <h2 class="page-title">{{ project?.name ?? 'Loading...' }} — Dependency graph</h2>
        <p class="page-sub">
          Arrows show task dependencies. A → B means A must be completed before B can start.
        </p>
      </div>

      <button
        v-if="taskStore.tasks.length > 0"
        class="btn-primary"
        @click="showAddDep = true"
      >
        + Add dependency
      </button>
    </div>

    <!-- Dependency graph -->
    <DependencyGraph
      :key="graphKey"
      :project-id="projectId"
    />

    <!-- Task list for adding dependencies -->
    <div v-if="taskStore.tasks.length > 0" class="tasks-panel">
      <h3 class="panel-title">Tasks in this project</h3>
      <p class="panel-sub">
        Select a task below to manage its dependencies.
      </p>

      <div class="task-list">
        <div
          v-for="task in taskStore.tasks"
          :key="task.id"
          class="task-row"
        >
          <span class="status-dot" :style="{ background: statusColor(task.status) }" />
          <span class="task-title">{{ task.title }}</span>
          <span class="priority-badge" :class="'priority-badge--' + task.priority">
            {{ task.priority }}
          </span>
          <button
            class="dep-btn"
            @click="openAddDep(task.id, task.title)"
          >
            + Dependency
          </button>
        </div>
      </div>
    </div>

    <!-- Add dependency modal -->
    <AddDependencyModal
      v-if="showAddDep && selectedTaskId"
      :task-id="selectedTaskId"
      :task-title="selectedTaskTitle"
      :all-tasks="taskStore.tasks"
      @close="showAddDep = false"
      @added="handleDependencyAdded"
    />
  </div>
</template>

<script setup lang="ts">
import { useProjectStore } from '~/stores/projects'
import { useTaskStore }    from '~/stores/tasks'
import type { Project, TaskStatus } from '~/types'

definePageMeta({
  layout:     'default',
  middleware: 'auth',
})

const route        = useRoute()
const projectStore = useProjectStore()
const taskStore    = useTaskStore()

const projectId        = computed(() => Number(route.params.id))
const project          = ref<Project | null>(null)
const showAddDep       = ref(false)
const selectedTaskId   = ref<number | null>(null)
const selectedTaskTitle = ref('')
const graphKey         = ref(0)  // increment to force graph re-render

onMounted(async () => {
  if (projectStore.projects.length === 0) {
    await projectStore.fetchProjects()
  }
  project.value = projectStore.findById(projectId.value) ?? null

  await taskStore.fetchTasks(projectId.value)
})

onUnmounted(() => {
  taskStore.clearTasks()
})

function openAddDep(taskId: number, taskTitle: string): void {
  selectedTaskId.value    = taskId
  selectedTaskTitle.value = taskTitle
  showAddDep.value        = true
}

function handleDependencyAdded(): void {
  // Increment graphKey to force DependencyGraph to re-fetch and re-render
  // DSA: this triggers a full graph re-traversal O(V + E) to show the new edge
  graphKey.value++
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
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
  gap: 12px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.back-link {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: #9ca3af;
  transition: color 0.1s;
}

.back-link:hover {
  color: #374151;
}

.page-title {
  font-size: 17px;
  font-weight: 500;
  color: #111827;
}

.page-sub {
  font-size: 12px;
  color: #9ca3af;
  line-height: 1.5;
}

.btn-primary {
  padding: 8px 14px;
  background: #111827;
  color: #ffffff;
  border: none;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s;
}

.btn-primary:hover {
  opacity: 0.85;
}

.tasks-panel {
  margin-top: 20px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.panel-title {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
  padding: 12px 16px 4px;
}

.panel-sub {
  font-size: 12px;
  color: #9ca3af;
  padding: 0 16px 10px;
}

.task-list {
  border-top: 1px solid #f3f4f6;
}

.task-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border-bottom: 1px solid #f3f4f6;
  transition: background 0.1s;
}

.task-row:last-child {
  border-bottom: none;
}

.task-row:hover {
  background: #fafafa;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.task-title {
  font-size: 13px;
  color: #111827;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.priority-badge {
  font-size: 10px;
  font-weight: 500;
  padding: 2px 7px;
  border-radius: 99px;
  text-transform: capitalize;
  flex-shrink: 0;
}

.priority-badge--high   { background: #faece7; color: #993c1d; }
.priority-badge--medium { background: #faeeda; color: #854f0b; }
.priority-badge--low    { background: #e1f5ee; color: #0f6e56; }

.dep-btn {
  font-size: 11px;
  padding: 4px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 5px;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.1s;
}

.dep-btn:hover {
  background: #f3f4f6;
  color: #111827;
}
</style>