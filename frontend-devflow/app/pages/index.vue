<template>
  <div>

    <!-- Stats grid -->
    <div class="stats-grid">
      <StatCard
        label="Total tasks"
        :value="statsStore.stats?.total_tasks ?? 0"
        :sub="statsStore.stats ? statsStore.stats.in_progress_tasks + ' in progress' : '—'"
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
        :sub="(statsStore.stats?.total_projects ?? 0) + ' active projects'"
      />
      <StatCard
        label="Overdue"
        :value="statsStore.stats?.overdue_tasks ?? 0"
        :value-color="statsStore.hasOverdue ? '#ef4444' : undefined"
        :sub="statsStore.hasOverdue ? 'Needs attention' : 'All on track'"
        :sub-class="statsStore.hasOverdue ? 'stat-sub--danger' : 'stat-sub--success'"
      />
    </div>

    <div class="dashboard-grid">

      <!-- Left: projects list -->
      <div>
        <div class="section-header">
          <h2 class="section-title">Your projects</h2>
          <button class="btn-primary" @click="openCreateModal">
            + New project
          </button>
        </div>

        <div v-if="projectStore.loading" class="state-msg">
          Loading projects...
        </div>

        <div v-else-if="projectStore.error" class="state-msg state-msg--error">
          {{ projectStore.error }}
        </div>

        <div
          v-else-if="projectStore.sortedProjects.length === 0"
          class="empty-state"
        >
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

      <!-- Right: progress by project -->
      <div>
        <div class="section-header">
          <h2 class="section-title">Progress by project</h2>
        </div>

        <div v-if="statsStore.loading" class="state-msg">
          Loading...
        </div>

        <div
          v-else-if="statsStore.sortedProjectStats.length === 0"
          class="empty-state"
        >
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
      @click.self="closeCreateModal"
    >
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">Create project</h3>
          <button class="modal-close" @click="closeCreateModal">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M2 2l10 10M12 2L2 12"/>
            </svg>
          </button>
        </div>

        <form @submit.prevent="handleCreate">
          <div class="form-group">
            <label class="form-label">Project name <span class="required">*</span></label>
            <input
              ref="nameInputRef"
              v-model="form.name"
              class="form-input"
              :class="{ 'form-input--error': formError }"
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

          <div v-if="formError" class="form-error-msg">
            {{ formError }}
          </div>

          <div class="modal-actions">
            <button
              type="button"
              class="btn-ghost"
              @click="closeCreateModal"
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

const projectStore = useProjectStore()
const statsStore   = useStatsStore()

// ── Modal state ────────────────────────────────────────────────────
const showCreateModal = ref(false)
const creating        = ref(false)
const formError       = ref<string | null>(null)
const nameInputRef    = ref<HTMLInputElement>()

const form = reactive({
  name:        '',
  description: '',
})

// ── Lifecycle ──────────────────────────────────────────────────────
onMounted(async () => {
  // DSA — Parallel fetch: O(max(t1, t2)) instead of O(t1 + t2)
  await Promise.all([
    projectStore.fetchProjects(),
    statsStore.fetchStats(),
  ])
})

// ── Modal helpers ──────────────────────────────────────────────────
function openCreateModal(): void {
  form.name        = ''
  form.description = ''
  formError.value  = null
  showCreateModal.value = true

  // Focus the name input after the modal renders
  nextTick(() => nameInputRef.value?.focus())
}

function closeCreateModal(): void {
  if (creating.value) return  // don't close while submitting
  showCreateModal.value = false
  form.name        = ''
  form.description = ''
  formError.value  = null
}

// ── Create project ─────────────────────────────────────────────────
async function handleCreate(): Promise<void> {
  if (!form.name.trim()) {
    formError.value = 'Project name is required.'
    return
  }

  creating.value  = true
  formError.value = null

  try {
    await projectStore.createProject({
      name:        form.name.trim(),
      description: form.description.trim() || undefined,
    })

    // ── SUCCESS: close modal and refresh stats ──────────────────
    // This is the fix: showCreateModal is set to false immediately
    // after a successful create, then we reset the form.
    showCreateModal.value = false
    form.name             = ''
    form.description      = ''

    // Refresh stats in the background — don't await so UI stays snappy
    statsStore.fetchStats()

  } catch (err: any) {
    // ── FAILURE: show error inside the modal, do NOT close it ───
    formError.value = err?.data?.message
      ?? err?.data?.errors?.name?.[0]
      ?? 'Failed to create project. Please try again.'
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
  grid-template-columns: 1fr 300px;
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
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
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

.state-msg {
  padding: 16px 0;
  font-size: 13px;
  color: #9ca3af;
}

.state-msg--error {
  color: #dc2626;
}

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
  max-width: 92vw;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 18px;
}

.modal-title {
  font-size: 15px;
  font-weight: 500;
  color: #111827;
}

.modal-close {
  width: 28px;
  height: 28px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: transparent;
  color: #9ca3af;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.1s;
}

.modal-close:hover {
  background: #f3f4f6;
  color: #374151;
}

.form-group {
  margin-bottom: 14px;
}

.form-label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  color: #6b7280;
  margin-bottom: 5px;
}

.required {
  color: #ef4444;
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
  transition: border-color 0.15s;
}

.form-input:focus {
  border-color: #6b7280;
  background: #ffffff;
}

.form-input--error {
  border-color: #ef4444;
}

.form-error-msg {
  font-size: 12px;
  color: #ef4444;
  background: #fee2e2;
  border-radius: 6px;
  padding: 8px 10px;
  margin-bottom: 12px;
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
  transition: background 0.1s;
}

.btn-ghost:hover {
  background: #f3f4f6;
}
</style>