import { defineStore } from 'pinia'
import type { Project } from '~/types'

export const useProjectStore = defineStore('projects', () => {
  const projects = ref<Project[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  // Computed: projects sorted by creation date (most recent first)
  // DSA: this is an array sort — O(n log n) — applied to keep the UI in order
  const sortedProjects = computed(() =>
    [...projects.value].sort(
      (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    )
  )

  async function fetchProjects() {
    loading.value = true
    error.value = null

    try {
      const { get } = useApi()
      const response = await get<{ data: Project[] }>('/projects')
      projects.value = response.data
    } catch (err: any) {
      error.value = err?.data?.message ?? 'Failed to fetch projects'
    } finally {
      loading.value = false
    }
  }

  async function createProject(payload: { name: string; description?: string }) {
    const { post } = useApi()
    const response = await post<{ data: Project }>('/projects', payload)
    projects.value.unshift(response.data)
    return response.data
  }

  function findById(id: number): Project | undefined {
    // DSA: linear search through the projects array — O(n)
    // For Day 1 this is fine. Later (Day 3) you will optimise with a HashMap
    return projects.value.find(p => p.id === id)
  }

  return {
    projects,
    sortedProjects,
    loading,
    error,
    fetchProjects,
    createProject,
    findById,
  }
})