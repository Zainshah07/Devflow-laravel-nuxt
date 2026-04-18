<?php

namespace App\Observers;

use App\Models\Task;
use App\Enums\TaskStatus;
use Illuminate\Support\Facades\Redis;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        //
    }

      // DSA — Sorted Set (ZINCRBY):
    // When a task is marked done we increment the completing user's
    // score in a Redis Sorted Set keyed by project ID.
    //
    // Redis Sorted Sets keep members sorted by score at all times.
    // ZINCRBY is atomic — safe for concurrent requests incrementing
    // the same user's score simultaneously.
    //
    // Structure: leaderboard:project:{id}
    //   member = user_id (string)
    //   score  = number of tasks completed
    //
    // This is the exact data structure for the top-K problem:
    // efficient insertion with score update O(log N) +
    // efficient top-K retrieval O(log N + K).
    // ─────────────────────────────────────────────────────────────────

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
       // Only act when status transitions to 'done'
        if (
            $task->wasChanged('status') &&
            $task->status === TaskStatus::Done
        ) {
            $key = 'leaderboard:project:' . $task->project_id;

            // Increment the completing user's score by 1
            Redis::zincrby($key, 1, (string) $task->created_by);

            // Set a 24-hour TTL — leaderboard resets daily
            // DSA — Sliding Window on time: only tasks completed
            // within the last 24 hours count toward the leaderboard.
            Redis::expire($key, 86400);
        }
    }
    

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        //
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        //
    }
}
