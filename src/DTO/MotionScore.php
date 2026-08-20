<?php

namespace App\DTO;

use App\Service\MotionAnalyzer;

final readonly class MotionScore
{
    private int $changedPixels;
    private float $density;

    public function __construct(int $changedPixels, float $density)
    {
        $this->changedPixels = $changedPixels;
        $this->density = $density;
    }

    public function getChangedPixels() : int
    {
        return $this->changedPixels;
    }

    public function getDensity() : float
    {
        return $this->density;
    }

    public function isEvent() : bool
    {
        $frame = MotionAnalyzer::WIDTH * MotionAnalyzer::HEIGHT;

        return $this->changedPixels >= MotionAnalyzer::MIN_CHANGED_PIXELS
            && $this->changedPixels <= $frame * MotionAnalyzer::MAX_CHANGED_RATIO
            && $this->density >= MotionAnalyzer::MIN_DENSITY;
    }
}
