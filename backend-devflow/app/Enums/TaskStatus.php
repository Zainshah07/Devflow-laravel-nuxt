<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Todo       = 'todo';
    case InProgress = 'in_progress';
    case Done       = 'done';

    public function canTransitionTo(TaskStatus $next): bool
    {
        return match($this) {
            self::Todo       => $next === self::InProgress,
            self::InProgress => $next === self::Done,
            self::Done       => false,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
