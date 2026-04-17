<template>
  <div>
    <div v-if="store.loading" class="loading-state">
      Loading projects...
    </div>

    <div v-else-if="store.error" class="error-state">
      {{ store.error }}
    </div>

    <div v-else>
      <div class="page-header">
        <h2 class="section-title">Your projects</h2>
        <button class="btn-primary" @click="showCreateModal = true">
          + New project
        </button>
      </div>

      <div v-if="store.sortedProjects.length === 0" class="empty-state">
        <p>No projects yet. Create your first project to get started.</p>
      </div>

      <div v-else class="projects-grid">
        <NuxtLink
          v-for="project in store.sortedProjects"
          :key="project.id"
          :to="`/projects/${project.id}`"
          class="project-card"
        >
          <div class="project-card-header">
            <div class="project-dot" />
            <h3 class="project-name">{{ project.name }}</h3>
          </div>

          <p class="project-description">
            {{ project.description ?? 'No description provided.' }}
          </p>

          <div class="project-footer">
            <span class="task-count">
              {{ project.tasks_count ?? 0 }} tasks
            </span>
            <span class="project-date">
              {{ formatDate(project.created_at) }}
            </span>
          </div>
        </NuxtLink>
      </div>
    </div>

    <!-- Create project modal -->
    <div v-if="showCreateModal" class="modal-backdrop" @click.self="showCreateModal = false">
      <div class="modal">
        <h3 class="modal-title">Create project</h3>

        <form @submit.prevent="handleCreate">
          <div class="form-group">
            <label class="form-label">Project name</label>
            <input
              v-model="form.name"
              class="form-input"
              placeholder="e.g. DevFlow API"
              required
            />
          </div>

          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea
              v-model="form.description"
              class="form-input"
              rows="3"
              placeholder="What is this project about?"
            />
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-ghost" @click="showCreateModal = false">
              Cancel
            </button>
            <button type="submit" class="btn-primary" :disabled="creating">
              {{ creating ? 'Creating...' : 'Create project' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({
  layout:     'default',
  middleware: 'auth',
})
import { useProjectStore } from '~/stores/projects'

const store = useProjectStore()
const showCreateModal = ref(false)
const creating = ref(false)

const form = reactive({
  name: '',
  description: '',
})

onMounted(async () => {
  await store.fetchProjects()
})

async function handleCreate() {
  if (!form.name.trim()) return
  creating.value = true

  try {
    await store.createProject({
      name: form.name,
      description: form.description || undefined,
    })
    showCreateModal.value = false
    form.name = ''
    form.description = ''
  } catch (err) {
    console.error('Failed to create project:', err)
  } finally {
    creating.value = false
  }
}

function formatDate(dateString: string): string {
  return new Intl.DateTimeFormat('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date(dateString))
}
</script>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
}

.section-title {
  font-size: 15px;
  font-weight: 500;
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 14px;
}

.project-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 16px;
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
  display: block;
}

.project-card:hover {
  border-color: #d1d5db;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
}

.project-card-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.project-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #378add;
  flex-shrink: 0;
}

.project-name {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.project-description {
  font-size: 12px;
  color: #6b7280;
  line-height: 1.5;
  margin-bottom: 14px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.project-footer {
  display: flex;
  justify-content: space-between;
  font-size: 11px;
  color: #9ca3af;
}

.task-count {
  font-weight: 500;
  color: #6b7280;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: #9ca3af;
  border: 1px dashed #e5e7eb;
  border-radius: 10px;
  font-size: 13px;
}

.loading-state,
.error-state {
  padding: 20px;
  font-size: 13px;
  color: #6b7280;
}

.error-state {
  color: #dc2626;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.modal {
  background: #ffffff;
  border-radius: 12px;
  padding: 24px;
  width: 420px;
  max-width: 90vw;
}

.modal-title {
  font-size: 15px;
  font-weight: 500;
  margin-bottom: 16px;
}

.form-group {
  margin-bottom: 14px;
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
  padding: 8px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
  color: #111827;
  background: #f9fafb;
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

.btn-primary:disabled {
  opacity: 0.5;
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