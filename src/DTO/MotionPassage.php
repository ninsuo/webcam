<?php

namespace App\DTO;

final readonly class MotionPassage
{
    /**
     * @var array of ['file' => absolute path, 'time' => int, 'changed' => int]
     */
    private array $frames;

    public function __construct(array $frames)
    {
        $this->frames = $frames;
    }

    public function getFrames() : array
    {
        return $this->frames;
    }

    public function getStart() : \DateTime
    {
        $date = new \DateTime();
        $date->setTimestamp($this->frames[0]['time']);

        return $date;
    }

    public function getEnd() : \DateTime
    {
        $date = new \DateTime();
        $date->setTimestamp($this->frames[count($this->frames) - 1]['time']);

        return $date;
    }

    public function getBestFrame() : string
    {
        $best = $this->frames[0];
        foreach ($this->frames as $frame) {
            if ($frame['changed'] > $best['changed']) {
                $best = $frame;
            }
        }

        return $best['file'];
    }
}
