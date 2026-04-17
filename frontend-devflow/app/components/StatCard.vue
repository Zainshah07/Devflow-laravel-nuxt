<template>
  <div class="stat-card">
    <div class="stat-label">{{ label }}</div>
    <div class="stat-value" :style="{ color: valueColor }">
      {{ value }}
    </div>
    <div v-if="sub" class="stat-sub" :class="subClass">
      {{ sub }}
    </div>
    <div v-if="showBar" class="progress-bar-wrap">
      <div
        class="progress-bar-fill"
        :style="{ width: barPercent + '%', background: barColor }"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  label:       string
  value:       string | number
  sub?:        string
  subClass?:   string
  valueColor?: string
  showBar?:    boolean
  barPercent?: number
  barColor?:   string
}>()
</script>

<style scoped>
.stat-card {
  background: #f9fafb;
  border-radius: 8px;
  padding: 14px 16px;
}

.stat-label {
  font-size: 12px;
  color: #9ca3af;
  margin-bottom: 6px;
}

.stat-value {
  font-size: 24px;
  font-weight: 500;
  color: #111827;
  line-height: 1;
  margin-bottom: 4px;
}

.stat-sub {
  font-size: 11px;
  color: #9ca3af;
  margin-top: 3px;
}

.stat-sub--success { color: #1d9e75; }
.stat-sub--warning { color: #f59e0b; }
.stat-sub--danger  { color: #ef4444; }

.progress-bar-wrap {
  height: 4px;
  background: #e5e7eb;
  border-radius: 2px;
  margin-top: 8px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.4s ease;
}
</style>