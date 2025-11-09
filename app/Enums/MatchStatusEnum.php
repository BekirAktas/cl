<?php

namespace App\Enums;

enum MatchStatusEnum: string
{
    case SCHEDULED = 'scheduled';
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case PLAYED = 'played';
    case CANCELLED = 'cancelled';

    public function isScheduled(): bool
    {
        return $this === self::SCHEDULED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isPlayed(): bool
    {
        return $this === self::PLAYED || $this === self::COMPLETED;
    }
}
