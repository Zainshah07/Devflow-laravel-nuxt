import { defineStore } from 'pinia'
import type { DashboardStats } from '~/types'

export const useStatsStore = defineStore('stats', () => {

  const stats   = ref<DashboardStats | null>(null)
  const loading = ref(false)
  const error   = ref<string | null>(null)

  async function fetchStats(): Promise<void> {
    loading.value = true
    error.value   = null

    try {
      const { get } = useApi()
      const response = await get<{ data: DashboardStats }>('/stats')
      stats.value = response.data
    } catch (err: any) {
      error.value = err?.data?.message ?? 'Failed to load stats.'
    } finally {
      loading.value = false
    }
  }

  // DSA — derived computed values from the stats hash map:
  // Each computed reads a single key from stats — O(1) access.
  const completionRate = computed(() =>
    stats.value?.completion_rate ?? 0
  )

  const hasOverdue = computed(() =>
    (stats.value?.overdue_tasks ?? 0) > 0
  )

  // DSA — Array sort O(n log n):
  // Sort project stats by total_tasks descending so the most
  // active projects appear first in the list.
  const sortedProjectStats = computed(() =>
    [...(stats.value?.project_stats ?? [])].sort(
      (a, b) => b.total_tasks - a.total_tasks
    )
  )

  function clearStats(): void {
    stats.value = null
    error.value = null
  }

  return {
    stats,
    loading,
    error,
    completionRate,
    hasOverdue,
    sortedProjectStats,
    fetchStats,
    clearStats,
  }
})