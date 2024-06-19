<?php

namespace App\Enum;

use App\Constants;

enum Size: string
{
    case THUMBNAIL = 'thumbnail';
    case ORIGINAL = 'original';

    public function getWidth() : int
    {
        return match ($this) {
            self::THUMBNAIL => Constants::DEFAULT_WIDTH / 3,
            self::ORIGINAL => Constants::DEFAULT_WIDTH,
        };
    }

    public function getHeight() : int
    {
        return match ($this) {
            self::THUMBNAIL => Constants::DEFAULT_HEIGHT / 3,
            self::ORIGINAL => Constants::DEFAULT_HEIGHT,
        };
    }

    public function getTimeX() : int
    {
        return match ($this) {
            self::THUMBNAIL => Constants::DEFAULT_TIME_X / 3,
            self::ORIGINAL => Constants::DEFAULT_TIME_X,
        };
    }

    public function getTimeY() : int
    {
        return match ($this) {
            self::THUMBNAIL => Constants::DEFAULT_TIME_Y / 3,
            self::ORIGINAL => Constants::DEFAULT_TIME_Y,
        };
    }

    public function getTimeSize()
    {
        return match ($this) {
            self::THUMBNAIL => Constants::DEFAULT_TIME_SIZE / 2,
            self::ORIGINAL => Constants::DEFAULT_TIME_SIZE,
        };
    }
}