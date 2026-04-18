<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class TaskCacheService
{
    // Cache TTL in seconds — 5 minutes
    private const TTL = 300;

    // ─────────────────────────────────────────────────────────────────
    // DSA — Hash Map cache-aside:
    // The cache key is a hash of all parameters that affect the result.
    // Different filter combinations produce different keys, so each
    // unique query result gets its own cache entry.
    // md5(serialize($params)) maps variable-length input to a
    // fixed-length key — same concept as a hash function in DSA.
    // ─────────────────────────────────────────────────────────────────
    public function buildKey(int $projectId, array $queryParams): string
    {
        // Remove page/cursor from the key components that affect data
        // but include filter, sort, and include params
        $relevant = array_filter($queryParams, fn($key) => in_array($key, [
            'filter', 'sort', 'include', 'cursor',
        ]), ARRAY_FILTER_USE_KEY);

        ksort($relevant); // sort keys so same params always produce same hash

        return 'tasks:project:' . $projectId . ':' . md5(serialize($relevant));
    }

    // DSA — Hash Map O(1) read:
    // Cache::tags(['project-X'])->remember() checks Redis first.
    // On hit: return cached value immediately — O(1).
    // On miss: execute $callback (DB query), store result, return it.
    public function remember(string $key, int $projectId, callable $callback): mixed
    {
        return Cache::tags(['project-' . $projectId])
            ->remember($key, self::TTL, $callback);
    }

    // DSA — Hash Map bulk delete by tag:
    // Cache tags group related keys. Flushing a tag deletes all entries
    // in that group regardless of their individual keys.
    // Without tags you would need to track every key individually.
    public function invalidateProject(int $projectId): void
    {
        Cache::tags(['project-' . $projectId])->flush();
    }
}