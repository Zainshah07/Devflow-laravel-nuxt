<template>
  <div class="graph-wrapper">

    <!-- Empty state -->
    <div v-if="!loading && nodes.length === 0" class="graph-empty">
      No tasks yet. Create tasks and add dependencies to see the graph.
    </div>

    <!-- Loading state -->
    <div v-else-if="loading" class="graph-loading">
      Building dependency graph...
    </div>

    <!-- Vue Flow graph -->
    <VueFlow
      v-else
      :nodes="vfNodes"
      :edges="vfEdges"
      :default-zoom="0.9"
      :min-zoom="0.3"
      :max-zoom="2"
      fit-view-on-init
      class="devflow-graph"
    >
      <Background pattern-color="#e5e7eb" :gap="20" />
      <Controls />

      <!-- Custom node template -->
      <template #node-task="{ data }">
        <div
          class="task-node"
          :class="[
            'task-node--' + data.status,
            { 'task-node--overdue': data.is_overdue }
          ]"
        >
          <div class="node-header">
            <span
              class="node-status-dot"
              :style="{ background: statusColor(data.status) }"
            />
            <span class="node-priority" :class="'node-priority--' + data.priority">
              {{ data.priority }}
            </span>
          </div>
          <div class="node-title">{{ data.title }}</div>
          <div class="node-status-label">{{ formatStatus(data.status) }}</div>
        </div>
      </template>
    </VueFlow>

    <!-- Legend -->
    <div v-if="!loading && nodes.length > 0" class="graph-legend">
      <div class="legend-item">
        <span class="legend-dot" style="background:#888780" />
        <span>Todo</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot" style="background:#378add" />
        <span>In progress</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot" style="background:#1d9e75" />
        <span>Done</span>
      </div>
      <div class="legend-item">
        <span class="legend-dot legend-dot--overdue" />
        <span>Overdue</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { VueFlow, type Node, type Edge } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls }   from '@vue-flow/controls'
import type { TaskGraphNode, DependencyEdge, TaskStatus } from '~/types'

import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'

const props = defineProps<{
  projectId: number
}>()

const nodes   = ref<TaskGraphNode[]>([])
const loading = ref(true)

// ─────────────────────────────────────────────────────────────────────
// DSA — Graph transformation O(V + E):
// Transform the API's adjacency list format into Vue Flow's
// node + edge format.
//
// The API returns: [{ id, title, status, dependencies: [id, id] }]
// Vue Flow needs: nodes[] + edges[]
//
// We iterate each node (V) and each dependency edge (E) once.
// Total: O(V + E) — linear in the size of the graph.
// ─────────────────────────────────────────────────────────────────────
const vfNodes = computed((): Node[] => {
  return nodes.value.map((task, index) => ({
    id:       task.id,
    type:     'task',
    position: computePosition(index, nodes.value.length),
    data:     task,
  }))
})

const vfEdges = computed((): Edge[] => {
  const edges: Edge[] = []

  // DSA: iterate every node's outgoing edges — O(E) total
  for (const task of nodes.value) {
    for (const depId of task.dependencies) {
      edges.push({
        id:           `edge-${depId}-${task.id}`,
        source:       depId,    // dependency (must finish first)
        target:       task.id,  // this task (waiting)
        type:         'smoothstep',
        animated:     task.status !== 'done',
        style:        { stroke: '#d1d5db', strokeWidth: 1.5 },
        markerEnd:    { type: 'arrowclosed', color: '#9ca3af' },
      })
    }
  }

  return edges
})

// Simple grid layout — positions nodes left-to-right, top-to-bottom
// DSA: this is a naive O(1) layout calculation per node.
// A proper topological layout would use BFS-based level assignment.
function computePosition(
  index: number,
  total: number
): { x: number; y: number } {
  const cols    = Math.ceil(Math.sqrt(total))
  const col     = index % cols
  const row     = Math.floor(index / cols)
  const xGap    = 220
  const yGap    = 140

  return {
    x: col * xGap + 40,
    y: row * yGap + 40,
  }
}

async function fetchGraph(): Promise<void> {
  loading.value = true

  try {
    const { get } = useApi()
    const response = await get<{ data: TaskGraphNode[] }>(
      `/projects/${props.projectId}/dependency-graph`
    )
    nodes.value = response.data
  } catch (err) {
    console.error('Failed to load dependency graph:', err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchGraph)

function statusColor(status: TaskStatus): string {
  const colors: Record<TaskStatus, string> = {
    todo:        '#888780',
    in_progress: '#378add',
    done:        '#1d9e75',
  }
  return colors[status] ?? '#888780'
}

function formatStatus(status: TaskStatus): string {
  const labels: Record<TaskStatus, string> = {
    todo:        'Todo',
    in_progress: 'In Progress',
    done:        'Done',
  }
  return labels[status] ?? status
}
</script>

<style scoped>
.graph-wrapper {
  position: relative;
  background: #fafafa;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  height: 520px;
}

.graph-empty,
.graph-loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  color: #9ca3af;
}

.devflow-graph {
  width: 100%;
  height: 100%;
  background: transparent;
}

.task-node {
  background: #ffffff;
  border: 1.5px solid #e5e7eb;
  border-radius: 8px;
  padding: 10px 12px;
  min-width: 160px;
  max-width: 200px;
  cursor: default;
  transition: border-color 0.15s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.task-node:hover {
  border-color: #d1d5db;
}

.task-node--in_progress {
  border-color: #bfdbfe;
}

.task-node--done {
  opacity: 0.65;
  border-color: #bbf7d0;
}

.task-node--overdue {
  border-left: 3px solid #ef4444;
}

.node-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 5px;
}

.node-status-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.node-priority {
  font-size: 10px;
  font-weight: 500;
  padding: 1px 5px;
  border-radius: 99px;
  text-transform: capitalize;
}

.node-priority--high   { background: #faece7; color: #993c1d; }
.node-priority--medium { background: #faeeda; color: #854f0b; }
.node-priority--low    { background: #e1f5ee; color: #0f6e56; }

.node-title {
  font-size: 12px;
  font-weight: 500;
  color: #111827;
  line-height: 1.4;
  margin-bottom: 4px;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.node-status-label {
  font-size: 10px;
  color: #9ca3af;
}

.graph-legend {
  position: absolute;
  bottom: 12px;
  left: 12px;
  display: flex;
  gap: 12px;
  background: rgba(255,255,255,0.9);
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  padding: 6px 10px;
  z-index: 10;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  color: #6b7280;
}

.legend-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-dot--overdue {
  background: #ef4444;
}
</style>