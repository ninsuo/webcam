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

    public function countPages(int $perPage) : int
    {
        return (int) ceil(count($this->frames) / $perPage);
    }

    public function getFramesPage(int $page, int $perPage) : array
    {
        $page = min(max($page, 1), $this->countPages($perPage));

        return array_slice($this->frames, ($page - 1) * $perPage, $perPage);
    }

    public function getFramePage(string $file, int $perPage) : int
    {
        return intdiv($this->getFrameIndex($file) ?? 0, $perPage) + 1;
    }

    public function hasFrame(string $file) : bool
    {
        return null !== $this->getFrameIndex($file);
    }

    public function getPrevFrame(string $file) : ?string
    {
        $index = $this->getFrameIndex($file);

        return null !== $index && $index > 0 ? $this->frames[$index - 1]['file'] : null;
    }

    public function getNextFrame(string $file) : ?string
    {
        $index = $this->getFrameIndex($file);

        return null !== $index && $index < count($this->frames) - 1 ? $this->frames[$index + 1]['file'] : null;
    }

    private function getFrameIndex(string $file) : ?int
    {
        foreach ($this->frames as $index => $frame) {
            if ($frame['file'] === $file) {
                return $index;
            }
        }

        return null;
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
