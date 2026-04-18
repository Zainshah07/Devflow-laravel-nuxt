import { defineStore } from 'pinia'
import type { Project } from '~/types'

export const useProjectStore = defineStore('projects', () => {

  const projects = ref<Project[]>([])
  const loading  = ref(false)
  const error    = ref<string | null>(null)

  // DSA — Hash Map O(1) lookup:
  // projectsById computed builds a Record<id, Project> once.
  // Every findById call is O(1) hash lookup instead of O(n) scan.
  const projectsById = computed((): Record<number, Project> => {
    const map: Record<number, Project> = {}
    for (const project of projects.value) {
      map[project.id] = project
    }
    return map
  })

  // DSA — Array Sort O(n log n):
  // Spread to avoid mutating the source ref — defensive copy.
  const sortedProjects = computed((): Project[] =>
    [...projects.value].sort(
      (a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime()
    )
  )

  async function fetchProjects(): Promise<void> {
    loading.value = true
    error.value   = null

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
    name:         string
    description?: string
  }): Promise<Project> {
    const { post }  = useApi()
    const response  = await post<{ data: Project }>('/projects', payload)
    const newProject = response.data

    // DSA — Array prepend O(n): new project appears first in sorted list
    projects.value.unshift(newProject)

    return newProject
  }

  async function updateProject(
    projectId: number,
    payload: { name?: string; description?: string }
  ): Promise<Project> {
    const { patch } = useApi()
    const response  = await patch<{ data: Project }>(`/projects/${projectId}`, payload)

    // DSA — O(n) find + O(1) replace
    const index = projects.value.findIndex(p => p.id === projectId)
    if (index !== -1) {
      projects.value[index] = response.data
    }

    return response.data
  }

  async function deleteProject(projectId: number): Promise<void> {
    const { destroy } = useApi()
    await destroy(`/projects/${projectId}`)

    // DSA — Array filter O(n): rebuild array excluding deleted node
    projects.value = projects.value.filter(p => p.id !== projectId)
  }

  // DSA — O(1) Hash Map lookup replacing O(n) linear search
  function findById(id: number): Project | undefined {
    return projectsById.value[id]
  }

  return {
    projects,
    projectsById,
    sortedProjects,
    loading,
    error,
    fetchProjects,
    createProject,
    updateProject,
    deleteProject,
    findById,
  }
})