<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class LeaderboardController extends Controller
{
    public function index(Request $request, int $projectId): JsonResponse
    {
        $this->authorize('view', \App\Models\Project::findOrFail($projectId));

        $key = 'leaderboard:project:' . $projectId;

        // ─────────────────────────────────────────────────────────────
        // DSA — Sorted Set ZREVRANGE:
        // Returns top-10 members in descending score order.
        // Time complexity: O(log N + M) where N is total members
        // and M is the number of results returned (10 here).
        // The WITHSCORES option returns [member, score, member, score...]
        // ─────────────────────────────────────────────────────────────
        $raw = Redis::zrevrange($key, 0, 9, 'WITHSCORES');

        if (empty($raw)) {
            return response()->json(['data' => []]);
        }

        // DSA — Hash Map construction O(n):
        // Build userId → score map from the interleaved array.
        // Redis returns [userId, score, userId, score...] pairs.
        $scores = [];
        foreach ($raw as $userId => $score) {
            $scores[(int) $userId] = (int) $score;
        }

        // Hydrate user records for all IDs in one query — prevents N+1
        // DSA: whereIn() generates a single IN(...) query.
        // Then index by ID for O(1) lookup in the map below.
        $users = User::whereIn('id', array_keys($scores))
            ->get()
            ->keyBy('id');  // creates a hash map: id => User

        // Build the leaderboard array, preserving the sorted order
        $leaderboard = array_map(function ($userId, $score) use ($users) {
            $user = $users->get($userId);
            return [
                'user_id'  => $userId,
                'name'     => $user?->name ?? 'Unknown',
                'score'    => $score,
            ];
        }, array_keys($scores), array_values($scores));

        return response()->json(['data' => $leaderboard]);
    }
}