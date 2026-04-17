<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\ActivityLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskAssignmentController extends Controller
{
    private const MAX_ASSIGNEES = 10;

    public function store(AssignTaskRequest $request, Task $task): TaskResource|JsonResponse
    {
        $this->authorize('view', $task->project);

        $userId = $request->validated('user_id');

        // ─────────────────────────────────────────────────────────────
        // DSA — Atomicity (Transaction as all-or-nothing):
        // DB::transaction() wraps multiple operations into a single
        // atomic unit. Either all succeed or none do — the database
        // state machine moves as a single step.
        //
        // Without a transaction: if the ActivityLog insert fails after
        // the assignment insert, you have a partially written state —
        // the user is assigned but no activity is recorded.
        // With a transaction: both succeed or both roll back.
        //
        // This is the same guarantee as atomic compare-and-swap in
        // concurrent programming — the state transitions atomically.
        // ─────────────────────────────────────────────────────────────
        try {
            DB::transaction(function () use ($task, $userId, $request) {

                // Check: is the user already assigned?
                // DSA: exists() is O(log n) — uses the unique index on
                // (task_id, user_id) in task_assignments table.
                $alreadyAssigned = $task->assignees()
                    ->where('user_id', $userId)
                    ->exists();

                if ($alreadyAssigned) {
                    // Throw inside the transaction to trigger rollback
                    throw new \InvalidArgumentException('User is already assigned to this task.');
                }

                // Check: has the assignment limit been reached?
                // DSA: count() is O(1) with the index — MySQL counts
                // index entries without touching the table.
                $assigneeCount = $task->assignees()->count();

                if ($assigneeCount >= self::MAX_ASSIGNEES) {
                    throw new \OverflowException('Task has reached the maximum of ' . self::MAX_ASSIGNEES . ' assignees.');
                }

                // Insert the assignment
                $task->assignees()->attach($userId, [
                    'assigned_at' => now(),
                ]);

                // Log the activity — if this fails the assignment rolls back
                ActivityLog::create([
                    'task_id' => $task->id,
                    'user_id' => $request->user()->id,
                    'action'  => 'assigned',
                    'meta'    => [
                        'assigned_user_id' => $userId,
                        'task_title'       => $task->title,
                    ],
                ]);
            });

        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\OverflowException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new TaskResource(
            $task->fresh()->load(['assignees', 'creator', 'project'])
        );
    }

    public function destroy(Request $request, Task $task, User $user): JsonResponse
    {
        $this->authorize('view', $task->project);

        // DSA — Atomicity: wrap unassign + activity log in a transaction
        DB::transaction(function () use ($task, $user, $request) {
            $task->assignees()->detach($user->id);

            ActivityLog::create([
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'action'  => 'unassigned',
                'meta'    => [
                    'unassigned_user_id' => $user->id,
                ],
            ]);
        });

        return response()->json(null, 204);
    }
}