<template>
  <div>
    <div v-if="loading" class="state-msg">Loading project...</div>
    <div v-else-if="error" class="state-msg state-msg--error">{{ error }}</div>

    <div v-else-if="project">

      <!-- Page header -->
      <div class="project-header">
        <div class="project-header-left">
          <NuxtLink to="/" class="back-link">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M9 2L4 7l5 5"/>
            </svg>
            Dashboard
          </NuxtLink>
          <h2 class="project-name">{{ project.name }}</h2>
          <p v-if="project.description" class="project-desc">
            {{ project.description }}
          </p>
        </div>
        <button class="btn-primary" @click="openCreateModal('todo')">
          + New task
        </button>
      </div>

      <!-- Stats bar -->
      <div class="stats-bar">
        <div class="stat-item">
          <span class="stat-num">{{ taskStore.totalCount }}</span>
          <span class="stat-label">Total</span>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <span class="stat-num">{{ taskStore.todoTasks.length }}</span>
          <span class="stat-label">Todo</span>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <span class="stat-num" style="color:#378add">
            {{ taskStore.inProgressTasks.length }}
          </span>
          <span class="stat-label">In progress</span>
        </div>
        <div class="stat-divider" />
        <div class="stat-item">
          <span class="stat-num" style="color:#1d9e75">
            {{ taskStore.doneTasks.length }}
          </span>
          <span class="stat-label">Done</span>
        </div>
        <template v-if="taskStore.overdueCount > 0">
          <div class="stat-divider" />
          <div class="stat-item">
            <span class="stat-num" style="color:#ef4444">
              {{ taskStore.overdueCount }}
            </span>
            <span class="stat-label">Overdue</span>
          </div>
        </template>
      </div>

      <!-- Main content grid: kanban + leaderboard sidebar -->
      <div class="content-grid">

        <!-- Kanban board column -->
        <div class="kanban-column">
          <TaskFiltersBar
            :filters="filters"
            :total-count="taskStore.totalCount"
            @reset="resetFilters"
          />

          <div v-if="taskStore.loading" class="loading-overlay">
            Filtering tasks...
          </div>

          <KanbanBoard
            v-else
            :project-id="project.id"
            @add-task="openCreateModal"
            @delete-task="handleDeleteTask"
          />

          <div v-if="taskStore.hasMore" class="load-more-wrap">
            <button
              class="load-more-btn"
              :disabled="taskStore.loadingMore"
              @click="taskStore.fetchMore(project.id, filters)"
            >
              {{ taskStore.loadingMore ? 'Loading...' : 'Load more tasks' }}
            </button>
          </div>
        </div>

        <!-- Leaderboard sidebar -->
        <div class="sidebar-column">
          <Leaderboard :project-id="project.id" />
        </div>

      </div>
    </div>

    <!-- Create task modal -->
    <CreateTaskModal
      v-if="showModal && project"
      :project-id="project.id"
      :default-status="modalDefaultStatus"
      @close="showModal = false"
      @created="showModal = false"
    />
  </div>
</template>

<script setup lang="ts">
import { useProjectStore }  from '~/stores/projects'
import { useTaskStore }     from '~/stores/tasks'
import { useTaskFilters }   from '~/composables/useTaskFilters'
import type { Project, TaskStatus } from '~/types'

definePageMeta({
  layout:     'default',
  middleware: 'auth',
})

const route        = useRoute()
const projectStore = useProjectStore()
const taskStore    = useTaskStore()

const projectId          = computed(() => Number(route.params.id))
const project            = ref<Project | null>(null)
const loading            = ref(true)
const error              = ref<string | null>(null)
const showModal          = ref(false)
const modalDefaultStatus = ref<TaskStatus>('todo')

const { filters, resetFilters } = useTaskFilters(projectId.value)

onMounted(async () => {
  try {
    if (projectStore.projects.length === 0) {
      await projectStore.fetchProjects()
    }

    // DSA — O(1) hash map lookup
    project.value = projectStore.findById(projectId.value) ?? null

    if (!project.value) {
      error.value = 'Project not found.'
      return
    }

    await taskStore.fetchTasks(projectId.value, filters)
  } catch (err: any) {
    error.value = err?.data?.message ?? 'Failed to load project.'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  taskStore.clearTasks()
})

function openCreateModal(status: TaskStatus = 'todo'): void {
  modalDefaultStatus.value = status
  showModal.value = true
}

async function handleDeleteTask(taskId: number): Promise<void> {
  if (!project.value) return
  if (!confirm('Delete this task?')) return

  try {
    await taskStore.deleteTask(project.value.id, taskId)
  } catch (err) {
    console.error('Delete failed:', err)
  }
}
</script>

<style scoped>
.state-msg {
  padding: 20px;
  font-size: 13px;
  color: #9ca3af;
}

.state-msg--error {
  color: #dc2626;
}

.project-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
  gap: 12px;
  flex-wrap: wrap;
}

.project-header-left {
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
  margin-bottom: 2px;
  transition: color 0.1s;
}

.back-link:hover {
  color: #374151;
}

.project-name {
  font-size: 18px;
  font-weight: 500;
  color: #111827;
}

.project-desc {
  font-size: 13px;
  color: #6b7280;
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

.stats-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px 18px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  align-items: baseline;
  gap: 5px;
}

.stat-num {
  font-size: 20px;
  font-weight: 500;
  color: #111827;
}

.stat-label {
  font-size: 12px;
  color: #9ca3af;
}

.stat-divider {
  width: 1px;
  height: 20px;
  background: #e5e7eb;
}

.content-grid {
  display: grid;
  grid-template-columns: 1fr 240px;
  gap: 16px;
  align-items: start;
}

@media (max-width: 960px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .sidebar-column {
    order: -1;
  }
}

.kanban-column {
  min-width: 0;
}

.sidebar-column {
  position: sticky;
  top: 70px;
}

.loading-overlay {
  padding: 40px;
  text-align: center;
  font-size: 13px;
  color: #9ca3af;
}

.load-more-wrap {
  display: flex;
  justify-content: center;
  margin-top: 16px;
}

.load-more-btn {
  padding: 8px 18px;
  border: 1px solid #e5e7eb;
  border-radius: 7px;
  background: #ffffff;
  font-size: 13px;
  color: #374151;
  cursor: pointer;
  transition: background 0.1s;
}

.load-more-btn:hover:not(:disabled) {
  background: #f9fafb;
}

.load-more-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>