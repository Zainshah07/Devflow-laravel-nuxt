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
      :default-zoom="0.8"
      :min-zoom="0.2"
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
          <!-- 
            REQUIRED FOR ARROWS: 
            Target handle (Inbound) on the left, Source handle (Outbound) on the right 
          -->
          <Handle type="target" :position="Position.Left" />
          <Handle type="source" :position="Position.Right" />

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
import { ref, computed, onMounted } from 'vue'
import { VueFlow, type Node, type Edge, Handle, Position } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import type { TaskGraphNode, TaskStatus } from '~/types'

// Core Styles
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'

const props = defineProps<{
  projectId: number
}>()

const nodes = ref<TaskGraphNode[]>([])
const loading = ref(true)

// Transform API nodes to Vue Flow nodes
const vfNodes = computed((): Node[] => {
  return nodes.value.map((task, index) => ({
    id: String(task.id), // Vue Flow IDs should be strings
    type: 'task',
    position: computePosition(index, nodes.value.length),
    data: task,
  }))
})

// Transform dependencies to Vue Flow edges
const vfEdges = computed((): Edge[] => {
  const edges: Edge[] = []

  for (const task of nodes.value) {
    if (!task.dependencies) continue
    
    for (const depId of task.dependencies) {
      edges.push({
        id: `edge-${depId}-${task.id}`,
        source: String(depId), // MUST match node ID string exactly
        target: String(task.id),
        type: 'smoothstep',
        animated: task.status !== 'done',
        style: { stroke: '#94a3b8', strokeWidth: 2 },
        markerEnd: { type: 'arrowclosed', color: '#94a3b8' },
      })
    }
  }
  return edges
})

function computePosition(index: number, total: number) {
  const cols = Math.ceil(Math.sqrt(total))
  const col = index % cols
  const row = Math.floor(index / cols)
  return { x: col * 250 + 50, y: row * 160 + 50 }
}

async function fetchGraph(): Promise<void> {
  loading.value = true
  try {
    const { get } = useApi()
    const response = await get<{ data: TaskGraphNode[] }>(
      `/projects/${props.projectId}/dependency-graph`
    )
    nodes.value = response.data || []
  } catch (err) {
    console.error('Failed to load dependency graph:', err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchGraph)

// Helper formatters
function statusColor(status: TaskStatus): string {
  const colors: Record<TaskStatus, string> = {
    todo: '#888780',
    in_progress: '#378add',
    done: '#1d9e75',
  }
  return colors[status] ?? '#888780'
}

function formatStatus(status: TaskStatus): string {
  const labels: Record<TaskStatus, string> = {
    todo: 'Todo',
    in_progress: 'In Progress',
    done: 'Done',
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
  height: 600px;
}

.graph-empty, .graph-loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 14px;
  color: #9ca3af;
}

.devflow-graph {
  width: 100%;
  height: 100%;
}

/* Custom Node Styling */
.task-node {
  background: #ffffff;
  border: 1.5px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px;
  min-width: 180px;
  box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
  position: relative;
}

/* Hide the circular handles but keep them functional for the lines */
.vue-flow__handle {
  opacity: 0;
}

.task-node--in_progress { border-color: #378add; }
.task-node--done { opacity: 0.7; border-color: #1d9e75; background: #f0fdf4; }
.task-node--overdue { border-left: 4px solid #ef4444; }

.node-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.node-status-dot { width: 8px; height: 8px; border-radius: 50%; }
.node-priority { font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px; text-transform: uppercase; }
.node-priority--high { background: #fee2e2; color: #991b1b; }
.node-priority--medium { background: #fef3c7; color: #92400e; }
.node-priority--low { background: #dcfce7; color: #166534; }

.node-title { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 4px; }
.node-status-label { font-size: 11px; color: #64748b; }

.graph-legend {
  position: absolute;
  bottom: 15px;
  left: 15px;
  display: flex;
  gap: 15px;
  background: white;
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #475569; }
.legend-dot { width: 8px; height: 8px; border-radius: 50%; }
.legend-dot--overdue { background: #ef4444; }
</style>