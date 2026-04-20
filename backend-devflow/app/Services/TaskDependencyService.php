<?php

namespace App\Services;

use App\Models\Task;

class TaskDependencyService
{
    /**
     * Check whether adding a dependency from $taskId → $proposedDepId
     * would create a cycle in the dependency graph.
     *
     * DSA — DFS cycle detection on a directed graph:
     * This is the exact same algorithm as LeetCode #207 Course Schedule.
     *
     * We want to know: if we add edge taskId → proposedDepId,
     * can we reach taskId again by following existing edges
     * starting from proposedDepId?
     *
     * If yes → adding this edge creates a cycle → reject.
     *
     * Example cycle: A → B → C → A
     * Checking: "would adding A → B create a cycle?"
     * DFS from B: B visits C, C visits A — A is the target → CYCLE FOUND.
     *
     * Time complexity:  O(V + E) where V = tasks, E = dependencies
     * Space complexity: O(V) for the visited hash set
     */
    public function wouldCreateCycle(int $taskId, int $proposedDepId): bool
    {
        // If trying to make a task depend on itself — instant cycle
        if ($taskId === $proposedDepId) {
            return true;
        }

        // DSA — Hash Set for visited nodes:
        // Using an associative array as a hash set — isset() is O(1).
        // Without this, we could revisit nodes and loop forever.
        $visited = [];

        return $this->dfs($proposedDepId, $taskId, $visited);
    }

    /**
     * DFS traversal following dependency edges.
     * Returns true if $targetId is reachable from $currentId.
     *
     * @param int   $currentId  The node we are currently visiting
     * @param int   $targetId   The node we are searching for (the original task)
     * @param array $visited    Hash set of already-visited node IDs
     */
    private function dfs(int $currentId, int $targetId, array &$visited): bool
    {
        // Found the target — a cycle exists
        if ($currentId === $targetId) {
            return true;
        }

        // DSA — Hash Set O(1) membership check:
        // If we have already visited this node, skip it.
        // This prevents infinite loops on graphs that already have
        // valid paths leading back to visited nodes.
        if (isset($visited[$currentId])) {
            return false;
        }

        // Mark current node as visited
        $visited[$currentId] = true;

        // Load the dependencies of the current task
        // These are the outgoing edges from this node
        $dependencies = Task::find($currentId)
            ?->dependencies()
            ->pluck('depends_on_task_id')
            ->toArray() ?? [];

        // Recursively visit each neighbor
        foreach ($dependencies as $neighborId) {
            if ($this->dfs($neighborId, $targetId, $visited)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all tasks a given task depends on, resolved recursively.
     * Returns a flat array of task IDs in dependency order.
     *
     * DSA — BFS traversal for full dependency resolution:
     * Level 0: the task itself
     * Level 1: its direct dependencies
     * Level 2: their dependencies
     * ...and so on until the leaves of the dependency tree.
     */
    public function resolveAllDependencies(int $taskId): array
    {
        $resolved = [];
        $queue    = [$taskId];
        $visited  = [];

        while (!empty($queue)) {
            $currentId = array_shift($queue);

            if (isset($visited[$currentId])) {
                continue;
            }

            $visited[$currentId] = true;

            $deps = Task::find($currentId)
                ?->dependencies()
                ->pluck('depends_on_task_id')
                ->toArray() ?? [];

            foreach ($deps as $depId) {
                if (!isset($visited[$depId])) {
                    $resolved[] = $depId;
                    $queue[]    = $depId;
                }
            }
        }

        return array_unique($resolved);
    }
}