<template>
  <NuxtLink :to="`/projects/${stat.id}`" class="project-progress-card">
    <div class="ppc-header">
      <div class="ppc-dot" />
      <span class="ppc-name">{{ stat.name }}</span>
      <span class="ppc-rate">{{ stat.completion_rate }}%</span>
    </div>

    <div class="ppc-bar-wrap">
      <div
        class="ppc-bar-fill"
        :style="{
          width: stat.completion_rate + '%',
          background: barColor
        }"
      />
    </div>

    <div class="ppc-footer">
      <span class="ppc-counts">
        {{ stat.done_tasks }} / {{ stat.total_tasks }} tasks done
      </span>
    </div>
  </NuxtLink>
</template>

<script setup lang="ts">
import type { ProjectStat } from '~/types'

const props = defineProps<{
  stat: ProjectStat
}>()

// DSA — conditional expression (O(1) lookup in a range):
// Map completion rate to a color. This is a range-based lookup —
// the same concept as binary search on a sorted breakpoint array.
const barColor = computed(() => {
  if (props.stat.completion_rate >= 80) return '#1d9e75'
  if (props.stat.completion_rate >= 40) return '#378add'
  return '#f59e0b'
})
</script>

<style scoped>
.project-progress-card {
  display: block;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px 14px;
  transition: border-color 0.15s;
}

.project-progress-card:hover {
  border-color: #d1d5db;
}

.ppc-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.ppc-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #378add;
  flex-shrink: 0;
}

.ppc-name {
  font-size: 13px;
  font-weight: 500;
  color: #111827;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ppc-rate {
  font-size: 12px;
  font-weight: 500;
  color: #6b7280;
  flex-shrink: 0;
}

.ppc-bar-wrap {
  height: 5px;
  background: #f3f4f6;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 6px;
}

.ppc-bar-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.4s ease;
}

.ppc-footer {
  font-size: 11px;
  color: #9ca3af;
}
</style>