<template>
  <div class="leaderboard">

    <div class="lb-header">
      <h3 class="lb-title">Leaderboard</h3>
      <span class="lb-period">Today</span>
    </div>

    <div v-if="loading" class="lb-state">
      Loading...
    </div>

    <div v-else-if="entries.length === 0" class="lb-state lb-state--empty">
      No completions yet today. Complete tasks to appear here.
    </div>

    <div v-else class="lb-list">
      <div
        v-for="(entry, index) in entries"
        :key="entry.user_id"
        class="lb-row"
        :class="{ 'lb-row--me': entry.user_id === currentUserId }"
      >
        <!-- Rank -->
        <span class="lb-rank">
          <span v-if="index === 0" class="lb-medal lb-medal--gold">1</span>
          <span v-else-if="index === 1" class="lb-medal lb-medal--silver">2</span>
          <span v-else-if="index === 2" class="lb-medal lb-medal--bronze">3</span>
          <span v-else class="lb-rank-num">{{ index + 1 }}</span>
        </span>

        <!-- Avatar -->
        <div class="lb-avatar">{{ initials(entry.name) }}</div>

        <!-- Name -->
        <span class="lb-name">
          {{ entry.name }}
          <span v-if="entry.user_id === currentUserId" class="lb-you">(you)</span>
        </span>

        <!-- Score bar + count -->
        <div class="lb-score-wrap">
          <div class="lb-bar-wrap">
            <div
              class="lb-bar-fill"
              :style="{ width: barWidth(entry.score) + '%' }"
            />
          </div>
          <span class="lb-count">{{ entry.score }}</span>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { useIntervalFn } from '@vueuse/core'
import { useAuthStore }  from '~/stores/auth'

const props = defineProps<{
  projectId: number
}>()

interface LeaderboardEntry {
  user_id: number
  name:    string
  score:   number
}

const authStore     = useAuthStore()
const currentUserId = computed(() => authStore.user?.id ?? 0)

const entries = ref<LeaderboardEntry[]>([])
const loading = ref(false)

// DSA — Sorted Set max score for normalising bar widths:
// maxScore is the top entry's score — used to normalise bar widths
// so the top scorer always has a 100% wide bar.
// This is the same max-normalisation used in histogram problems.
const maxScore = computed(() =>
  entries.value.length > 0 ? entries.value[0].score : 1
)

function barWidth(score: number): number {
  return Math.round((score / maxScore.value) * 100)
}

async function fetchLeaderboard(): Promise<void> {
  if (loading.value) return
  loading.value = true

  try {
    const { get } = useApi()
    const response = await get<{ data: LeaderboardEntry[] }>(
      `/projects/${props.projectId}/leaderboard`
    )
    entries.value = response.data
  } catch {
    // Leaderboard is non-critical — fail silently
  } finally {
    loading.value = false
  }
}

// Fetch on mount
onMounted(fetchLeaderboard)

// DSA — Sliding Window on time (polling interval):
// Poll every 30 seconds — only the most recent window of data
// is shown. On Day 10 this will be replaced by WebSocket push
// which is event-driven rather than time-windowed.
useIntervalFn(fetchLeaderboard, 30_000)

function initials(name: string): string {
  return name
    .split(' ')
    .slice(0, 2)
    .map(n => n[0])
    .join('')
    .toUpperCase()
}
</script>

<style scoped>
.leaderboard {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
}

.lb-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  border-bottom: 1px solid #f3f4f6;
}

.lb-title {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
}

.lb-period {
  font-size: 11px;
  color: #9ca3af;
  background: #f3f4f6;
  padding: 2px 8px;
  border-radius: 99px;
}

.lb-state {
  padding: 20px 14px;
  font-size: 12px;
  color: #9ca3af;
  text-align: center;
}

.lb-state--empty {
  line-height: 1.6;
}

.lb-list {
  padding: 8px 0;
}

.lb-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 7px 14px;
  transition: background 0.1s;
}

.lb-row:hover {
  background: #fafafa;
}

.lb-row--me {
  background: #f0f9ff;
}

.lb-rank {
  width: 20px;
  text-align: center;
  flex-shrink: 0;
}

.lb-rank-num {
  font-size: 12px;
  color: #9ca3af;
}

.lb-medal {
  font-size: 12px;
  font-weight: 500;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.lb-medal--gold   { background: #fef3c7; color: #92400e; }
.lb-medal--silver { background: #f3f4f6; color: #374151; }
.lb-medal--bronze { background: #fdf4ee; color: #9a3412; }

.lb-avatar {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  background: #e6f1fb;
  color: #185fa5;
  font-size: 10px;
  font-weight: 500;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.lb-name {
  font-size: 12px;
  font-weight: 500;
  color: #111827;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.lb-you {
  font-size: 11px;
  font-weight: 400;
  color: #9ca3af;
}

.lb-score-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.lb-bar-wrap {
  width: 60px;
  height: 4px;
  background: #f3f4f6;
  border-radius: 2px;
  overflow: hidden;
}

.lb-bar-fill {
  height: 100%;
  background: #1d9e75;
  border-radius: 2px;
  transition: width 0.3s ease;
}

.lb-count {
  font-size: 12px;
  font-weight: 500;
  color: #374151;
  min-width: 16px;
  text-align: right;
}
</style>