<template>
  <div>

    <!-- Stats grid -->
    <div class="stats-grid">
      <StatCard
        label="Total tasks"
        :value="statsStore.stats?.total_tasks ?? 0"
        :sub="statsStore.stats ? '+' + statsStore.stats.in_progress_tasks + ' in progress' : ''"
      />
      <StatCard
        label="Completed"
        :value="statsStore.stats?.completed_tasks ?? 0"
        :show-bar="true"
        :bar-percent="statsStore.completionRate"
        bar-color="#1d9e75"
        :sub="statsStore.completionRate + '% completion rate'"
        sub-class="stat-sub--success"
      />
      <StatCard
        label="Todo"
        :value="statsStore.stats?.todo_tasks ?? 0"
        :sub="statsStore.stats?.total_projects + ' active projects'"
      />
      <StatCard
        label="Overdue"
        :value="statsStore.stats?.overdue_tasks ?? 0"
        :value-color="statsStore.hasOverdue ? '#ef4444' : '#111827'"
        :sub="statsStore.hasOverdue ? 'Needs attention' : 'All on track'"
        :sub-class="statsStore.hasOverdue ? 'stat-sub--danger' : 'stat-sub--success'"
      />
    </div>

    <!-- Two column layout: projects + create modal trigger -->
    <div class="dashboard-grid">

      <!-- Project list column -->
      <div>
        <div class="section-header">
          <h2 class="section-title">Your projects</h2>
          <button class="btn-primary" @click="showCreateModal = true">
            + New project
          </button>
        </div>

        <div v-if="projectStore.loading" class="loading-msg">
          Loading projects...
        </div>

        <div v-else-if="projectStore.error" class="error-msg">
          {{ projectStore.error }}
        </div>

        <div v-else-if="projectStore.sortedProjects.length === 0" class="empty-state">
          No projects yet. Create your first project to get started.
        </div>

        <div v-else class="projects-grid">
          <NuxtLink
            v-for="project in projectStore.sortedProjects"
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
              <span class="task-count">{{ project.tasks_count ?? 0 }} tasks</span>
              <span class="project-date">{{ formatDate(project.created_at) }}</span>
            </div>
          </NuxtLink>
        </div>
      </div>

      <!-- Progress column -->
      <div>
        <div class="section-header">
          <h2 class="section-title">Progress by project</h2>
        </div>

        <div v-if="statsStore.loading" class="loading-msg">
          Loading stats...
        </div>

        <div v-else-if="statsStore.sortedProjectStats.length === 0" class="empty-state">
          No project stats yet.
        </div>

        <div v-else class="progress-list">
          <ProjectProgressCard
            v-for="stat in statsStore.sortedProjectStats"
            :key="stat.id"
            :stat="stat"
          />
        </div>
      </div>

    </div>

    <!-- Create project modal -->
    <div
      v-if="showCreateModal"
      class="modal-backdrop"
      @click.self="showCreateModal = false"
    >
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
            <button
              type="button"
              class="btn-ghost"
              @click="showCreateModal = false"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="btn-primary"
              :disabled="creating"
            >
              {{ creating ? 'Creating...' : 'Create project' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { useProjectStore } from '~/stores/projects'
import { useStatsStore }   from '~/stores/stats'

definePageMeta({
  layout:     'default',
  middleware: 'auth',
})

const projectStore    = useProjectStore()
const statsStore      = useStatsStore()
const showCreateModal = ref(false)
const creating        = ref(false)

const form = reactive({
  name:        '',
  description: '',
})

onMounted(async () => {
  // DSA — parallel fetching using Promise.all:
  // Both requests are sent simultaneously rather than sequentially.
  // Sequential: O(t1 + t2) total wait time.
  // Parallel:   O(max(t1, t2)) total wait time.
  // Same concept as parallel array processing — do independent
  // operations simultaneously rather than one after another.
  await Promise.all([
    projectStore.fetchProjects(),
    statsStore.fetchStats(),
  ])
})

async function handleCreate(): Promise<void> {
  if (!form.name.trim()) return
  creating.value = true

  try {
    await projectStore.createProject({
      name:        form.name,
      description: form.description || undefined,
    })

    // Refresh stats after creating a project
    await statsStore.fetchStats()

    showCreateModal.value = false
    form.name        = ''
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
    day:   'numeric',
    year:  'numeric',
  }).format(new Date(dateString))
}
</script>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
  margin-bottom: 24px;
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 20px;
  align-items: start;
}

@media (max-width: 900px) {
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.section-title {
  font-size: 14px;
  font-weight: 500;
  color: #111827;
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
  gap: 12px;
}

.project-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 14px;
  display: block;
  transition: border-color 0.15s;
}

.project-card:hover {
  border-color: #d1d5db;
}

.project-card-header {
  display: flex;
  align-items: center;
  gap: 7px;
  margin-bottom: 6px;
}

.project-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #378add;
  flex-shrink: 0;
}

.project-name {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
}

.project-description {
  font-size: 12px;
  color: #9ca3af;
  line-height: 1.5;
  margin-bottom: 10px;
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

.progress-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.loading-msg,
.error-msg {
  padding: 16px 0;
  font-size: 13px;
  color: #9ca3af;
}

.error-msg { color: #dc2626; }

.empty-state {
  padding: 30px;
  text-align: center;
  color: #d1d5db;
  border: 1px dashed #e5e7eb;
  border-radius: 8px;
  font-size: 13px;
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
  transition: opacity 0.15s;
}

.btn-primary:hover:not(:disabled) {
  opacity: 0.85;
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