<template>
  <div class="filters-bar">

    <!-- Search input with debounce -->
    <!-- DSA: 300ms debounce collapses O(k) API calls into O(1) -->
    <div class="search-wrap">
      <svg class="search-icon" width="14" height="14" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="6" cy="6" r="4"/>
        <path d="M10 10l2.5 2.5"/>
      </svg>
      <input
        v-model="filters.search"
        class="search-input"
        placeholder="Search tasks..."
        type="search"
      />
    </div>

    <!-- Status filter -->
    <select v-model="filters.status" class="filter-select">
      <option value="">All statuses</option>
      <option value="todo">Todo</option>
      <option value="in_progress">In progress</option>
      <option value="done">Done</option>
    </select>

    <!-- Priority filter -->
    <select v-model="filters.priority" class="filter-select">
      <option value="">All priorities</option>
      <option value="high">High</option>
      <option value="medium">Medium</option>
      <option value="low">Low</option>
    </select>

    <!-- Sort -->
    <!-- DSA: sort param maps to ORDER BY which uses B-tree index — O(log n) -->
    <select v-model="filters.sort" class="filter-select">
      <option value="-created_at">Newest first</option>
      <option value="created_at">Oldest first</option>
      <option value="due_date">Due date (asc)</option>
      <option value="-due_date">Due date (desc)</option>
      <option value="priority">Priority</option>
      <option value="title">Title A–Z</option>
    </select>

    <!-- Active filter count badge -->
    <span v-if="activeCount > 0" class="active-badge">
      {{ activeCount }} active
    </span>

    <!-- Reset button — only shown when filters are active -->
    <button
      v-if="activeCount > 0"
      class="reset-btn"
      @click="$emit('reset')"
    >
      Clear
    </button>

    <!-- Task count -->
    <span class="task-count">
      {{ totalCount }} task{{ totalCount !== 1 ? 's' : '' }}
    </span>

  </div>
</template>

<script setup lang="ts">
import type { TaskFilters } from '~/types'

const props = defineProps<{
  filters:    TaskFilters
  totalCount: number
}>()

defineEmits<{
  reset: []
}>()

// DSA — Count active filters using array reduce:
// activeCount is O(k) where k is the number of filter fields (constant here).
// Equivalent to counting non-zero elements in an array.
const activeCount = computed(() => {
  let count = 0
  if (props.filters.search)                      count++
  if (props.filters.status)                      count++
  if (props.filters.priority)                    count++
  if (props.filters.sort && props.filters.sort !== '-created_at') count++
  return count
})
</script>

<style scoped>
.filters-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 18px;
  padding: 12px 14px;
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.search-wrap {
  position: relative;
  flex: 1;
  min-width: 160px;
  max-width: 280px;
}

.search-icon {
  position: absolute;
  left: 9px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
  pointer-events: none;
}

.search-input {
  width: 100%;
  padding: 6px 10px 6px 30px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 13px;
  background: #f9fafb;
  color: #111827;
  outline: none;
  transition: border-color 0.15s;
}

.search-input:focus {
  border-color: #9ca3af;
  background: #ffffff;
}

.filter-select {
  padding: 6px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 12px;
  color: #374151;
  background: #f9fafb;
  cursor: pointer;
  outline: none;
  transition: border-color 0.15s;
}

.filter-select:focus {
  border-color: #9ca3af;
}

.active-badge {
  font-size: 11px;
  background: #dbeafe;
  color: #1e40af;
  padding: 2px 8px;
  border-radius: 99px;
  font-weight: 500;
}

.reset-btn {
  font-size: 12px;
  padding: 5px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: transparent;
  color: #6b7280;
  cursor: pointer;
  transition: background 0.1s;
}

.reset-btn:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fca5a5;
}

.task-count {
  margin-left: auto;
  font-size: 12px;
  color: #9ca3af;
}
</style>