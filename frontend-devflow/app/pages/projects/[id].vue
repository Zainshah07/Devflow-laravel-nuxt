<template>
  <div>
    <div v-if="loading" class="loading-state">Loading project...</div>

    <div v-else-if="error" class="error-state">{{ error }}</div>

    <div v-else-if="project">
      <div class="project-header">
        <div>
          <h2 class="project-title">{{ project.name }}</h2>
          <p class="project-desc">{{ project.description ?? 'No description.' }}</p>
        </div>
        <div class="header-actions">
          <span class="task-count-badge">{{ taskStore.tasks.length }} tasks</span>
          <button class="btn-primary" @click="showCreateTask = true">+ New task</button>
        </div>
      </div>

      <!-- Task stats bar -->
      <div class="stats-row">
        <div class="stat-item">
          <span class="stat-num">{{ taskStore.todoTasks.length }}</span>
          <span class="stat-label">To do</span>
        </div>
        <div class="stat-item">
          <span class="stat-num" style="color:#378add">{{ taskStore.inProgressTasks.length }}</span>
          <span class="stat-label">In progress</span>
        </div>
        <div class="stat-item">
          <span class="stat-num" style="color:#1d9e75">{{ taskStore.doneTasks.length }}</span>
          <span class="stat-label">Done</span>
        </div>
      </div>

      <!-- Task list grouped by status -->
      <div class="task-sections">
        <TaskSection
          title="To do"
          color="#888780"
          :tasks="taskStore.todoTasks"
          @status-change="handleStatusChange"
        />
        <TaskSection
          title="In progress"
          color="#378add"
          :tasks="taskStore.inProgressTasks"
          @status-change="handleStatusChange"
        />
        <TaskSection
          title="Done"
          color="#1d9e75"
          :tasks="taskStore.doneTasks"
          @status-change="handleStatusChange"
        />
      </div>
    </div>

    <!-- Create task modal -->
    <div v-if="showCreateTask" class="modal-backdrop" @click.self="showCreateTask = false">
      <div class="modal">
        <h3 class="modal-title">Create task</h3>

        <form @submit.prevent="handleCreateTask">
          <div class="form-group">
            <label class="form-label">Title</label>
            <input
              v-model="taskForm.title"
              class="form-input"
              placeholder="What needs to be done?"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea
              v-model="taskForm.description"
              class="form-input"
              rows="3"
              placeholder="Optional details..."
            />
          </div>

          <div class="form-row-2">
            <div class="form-group">
              <label class="form-label">Priority</label>
              <select v-model="taskForm.priority" class="form-input">
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Due date</label>
              <input
                v-model="taskForm.due_date"
                class="form-input"
                type="date"
              />
            </div>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-ghost" @click="showCreateTask = false">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="creatingTask">
              {{ creatingTask ? 'Creating...' : 'Create task' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useProjectStore } from '~/stores/projects'
import { useTaskStore } from '~/stores/tasks'
import type { Project, TaskStatus } from '~/types'

definePageMeta({ layout: 'default' })

const route = useRoute()
const projectStore = useProjectStore()
const taskStore = useTaskStore()

const project = ref<Project | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const showCreateTask = ref(false)
const creatingTask = ref(false)

const taskForm = reactive({
  title: '',
  description: '',
  priority: 'medium',
  due_date: '',
})

onMounted(async () => {
  const projectId = Number(route.params.id)

  try {
    await projectStore.fetchProjects()
    project.value = projectStore.findById(projectId) ?? null

    await taskStore.fetchTasks(projectId)
  } catch (err: any) {
    error.value = 'Failed to load project'
  } finally {
    loading.value = false
  }
})

onUnmounted(() => {
  taskStore.clearTasks()
})

async function handleCreateTask() {
  if (!taskForm.title.trim() || !project.value) return
  creatingTask.value = true

  try {
    await taskStore.createTask(project.value.id, {
      title: taskForm.title,
      description: taskForm.description || undefined,
      priority: taskForm.priority,
      due_date: taskForm.due_date || undefined,
    })
    showCreateTask.value = false
    taskForm.title = ''
    taskForm.description = ''
    taskForm.priority = 'medium'
    taskForm.due_date = ''
  } catch (err) {
    console.error('Failed to create task:', err)
  } finally {
    creatingTask.value = false
  }
}

async function handleStatusChange(taskId: number, newStatus: TaskStatus) {
  try {
    await taskStore.updateStatus(taskId, newStatus)
  } catch (err) {
    console.error('Status transition failed:', err)
  }
}
</script>

<style scoped>
.project-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 12px;
}

.project-title {
  font-size: 18px;
  font-weight: 500;
  margin-bottom: 4px;
}

.project-desc {
  font-size: 13px;
  color: #6b7280;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.task-count-badge {
  font-size: 12px;
  background: #f3f4f6;
  color: #6b7280;
  padding: 4px 10px;
  border-radius: 99px;
}

.stats-row {
  display: flex;
  gap: 24px;
  margin-bottom: 24px;
  padding: 14px 16px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.stat-item {
  display: flex;
  align-items: baseline;
  gap: 6px;
}

.stat-num {
  font-size: 20px;
  font-weight: 500;
}

.stat-label {
  font-size: 12px;
  color: #9ca3af;
}

.task-sections {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.loading-state,
.error-state {
  padding: 20px;
  font-size: 13px;
  color: #6b7280;
}

.error-state { color: #dc2626; }

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  width: 440px;
  max-width: 90vw;
}

.modal-title {
  font-size: 15px;
  font-weight: 500;
  margin-bottom: 16px;
}

.form-group {
  margin-bottom: 12px;
}

.form-row-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.form-label {
  display: block;
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: #6b7280;
  margin-bottom: 4px;
}

.form-input {
  width: 100%;
  padding: 7px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
  background: #f9fafb;
  color: #111827;
  outline: none;
  resize: vertical;
}

.form-input:focus {
  border-color: #6b7280;
  background: #ffffff;
}

.modal-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 16px;
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
}

.btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-ghost {
  padding: 7px 14px;
  background: transparent;
  color: #6b7280;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  cursor: pointer;
}

.btn-ghost:hover { background: #f3f4f6; }
</style>