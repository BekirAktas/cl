<?php

namespace App\Enums;

enum MatchResultEnum: string
{
    case WIN = 'win';
    case DRAW = 'draw';
    case LOSS = 'loss';

    public function getPoints(): int
    {
        return match($this) {
            self::WIN => 3,
            self::DRAW => 1,
            self::LOSS => 0,
        };
    }
}