import { defineStore } from 'pinia'
import type { Project } from '~/types'

export const useProjectStore = defineStore('projects', () => {

  const projects = ref<Project[]>([])
  const loading  = ref(false)
  const error    = ref<string | null>(null)

  // DSA — Hash Map O(1) lookup:
  // projectsById is a computed Record<number, Project> — a hash map
  // keyed by project ID. Looking up a project by ID is now O(1)
  // instead of O(n) linear scan with Array.find().
  //
  // This replaces the findById linear search from Day 2.
  // Same tradeoff as using a hash map vs an array in Two Sum:
  // O(n) extra space to build the map, O(1) every lookup after that.
  // Worth it when the same project is looked up many times.
  const projectsById = computed((): Record<number, Project> => {
    const map: Record<number, Project> = {}
    for (const project of projects.value) {
      map[project.id] = project
    }
    return map
  })


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