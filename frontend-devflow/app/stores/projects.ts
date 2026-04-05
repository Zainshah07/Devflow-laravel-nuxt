import { defineStore } from 'pinia'
import type { Project } from '~/types'

export const useProjectStore = defineStore('projects', () => {

  const projects = ref<Project[]>([])
  const loading  = ref(false)
  const error    = ref<string | null>(null)

  // DSA — Array Sort O(n log n)
  // Sorted by creation date, newest first.
  // Using spread [...projects.value] to avoid mutating the original ref —
  // same defensive copy pattern used in immutable array problems.
  const sortedProjects = computed((): Project[] =>
    [...projects.value].sort(
      (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    )
  )

  async function fetchProjects(): Promise<void> {
    loading.value = true
    error.value = null

    try {
      const { get } = useApi()
      const response = await get<{ data: Project[] }>('/projects')
      projects.value = response.data
    } catch (err: any) {
      error.value = err?.data?.message ?? 'Failed to load projects.'
    } finally {
      loading.value = false
    }
  }

  async function createProject(payload: {
    name: string
    description?: string
  }): Promise<Project> {
    const { post } = useApi()
    const response = await post<{ data: Project }>('/projects', payload)

    projects.value.unshift(response.data)

    return response.data
  }

  async function deleteProject(projectId: number): Promise<void> {
    const { destroy } = useApi()
    await destroy(`/projects/${projectId}`)

    // DSA: filter is O(n) — removes the deleted node from the array
    projects.value = projects.value.filter(p => p.id !== projectId)
  }

  // DSA — Linear Search O(n)
  // Scans the array for the first matching id.
  // On Day 3 this will be replaced with a HashMap lookup O(1)
  // when we add a projectsById computed: Record<number, Project>
  function findById(id: number): Project | undefined {
    return projects.value.find(p => p.id === id)
  }

  return {
    projects,
    sortedProjects,
    loading,
    error,
    fetchProjects,
    createProject,
    deleteProject,
    findById,
  }
})