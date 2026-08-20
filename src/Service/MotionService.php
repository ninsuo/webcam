<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\MotionPassage;
use App\DTO\Webcam;

/**
 * Scores webcam images for motion and stores the results in a
 * motion.jsonl file next to the images, one line per image. Files
 * already scored are skipped, so detect() can run from a frequent cron.
 */
class MotionService
{
    public const MOTION_FILE = 'motion.jsonl';

    // seconds without motion after which the next event starts a new passage
    public const PASSAGE_GAP = 120;

    // images modified less than this many seconds ago may still be uploading
    public const FRESH_SECONDS = 10;

    private MotionAnalyzer $analyzer;

    public function __construct(MotionAnalyzer $analyzer)
    {
        $this->analyzer = $analyzer;
    }

    public function detect(Webcam $webcam) : int
    {
        $scored = 0;

        foreach ($this->listImagesByFolder($webcam) as $folder => $images) {
            $motionFile = $folder.'/'.self::MOTION_FILE;
            $watermark = $this->lastScoredImage($motionFile);

            $prev = null;
            foreach ($images as $image) {
                if (null !== $watermark && basename($image) <= $watermark) {
                    $prev = $image;
                    continue;
                }

                if (filemtime($image) > time() - self::FRESH_SECONDS) {
                    // still uploading; later images are even fresher, and
                    // scoring past a hole would corrupt the watermark
                    break;
                }

                $prev = $this->score($motionFile, $prev, $image) ? $image : null;
                $scored++;
            }
        }

        return $scored;
    }

    /**
     * Groups event frames into passages: consecutive sightings less than
     * two minutes apart belong to the same passage (one cat walk).
     *
     * @return MotionPassage[]
     */
    public function getPassages(Webcam $webcam) : array
    {
        $events = [];

        $seen = [];
        foreach ($this->listMotionFiles($webcam) as $motionFile) {
            $folder = dirname($motionFile);

            // stream line by line: motion files can grow far beyond the
            // memory limit, loading them whole crashes the events page
            $handle = fopen($motionFile, 'r');
            while (false !== ($line = fgets($handle))) {
                $row = json_decode($line, true);
                if (!is_array($row) || !($row['event'] ?? false)) {
                    continue;
                }
                $row['file'] = $folder.'/'.$row['file'];
                if (!isset($seen[$row['file']])) {
                    $seen[$row['file']] = true;
                    $events[] = $row;
                }
            }
            fclose($handle);
        }

        usort($events, fn($a, $b) => $a['time'] <=> $b['time']);

        $passages = [];
        $frames = [];
        foreach ($events as $event) {
            if ($frames && $event['time'] - $frames[count($frames) - 1]['time'] > self::PASSAGE_GAP) {
                $passages[] = new MotionPassage($frames);
                $frames = [];
            }
            $frames[] = $event;
        }
        if ($frames) {
            $passages[] = new MotionPassage($frames);
        }

        return $passages;
    }

    public function getPassage(Webcam $webcam, int $start) : ?MotionPassage
    {
        foreach ($this->getPassages($webcam) as $passage) {
            if ($passage->getStart()->getTimestamp() === $start) {
                return $passage;
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function listMotionFiles(Webcam $webcam) : array
    {
        $files = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($webcam->getPath(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === self::MOTION_FILE) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Returns false when the image could not be analyzed (e.g. corrupt
     * file); it is still recorded as a non-event so it is never retried.
     */
    private function score(string $motionFile, ?string $prev, string $image) : bool
    {
        $changed = 0;
        $density = 0.0;
        $event = false;
        $analyzed = true;

        if (null !== $prev) {
            try {
                $result = $this->analyzer->compare($prev, $image);
                $changed = $result->getChangedPixels();
                $density = $result->getDensity();
                $event = $result->isEvent();
            } catch (\RuntimeException) {
                $analyzed = false;
            }
        }

        $line = json_encode([
            'file' => basename($image),
            'time' => filemtime($image),
            'changed' => $changed,
            'density' => round($density, 4),
            'event' => $event,
        ]);

        file_put_contents($motionFile, $line."\n", FILE_APPEND | LOCK_EX);

        return $analyzed;
    }

    /**
     * @return array<string, array<string>> sorted images grouped by folder
     */
    private function listImagesByFolder(Webcam $webcam) : array
    {
        $folders = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($webcam->getPath(), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'jpg') {
                $folders[$file->getPath()][] = $file->getPathname();
            }
        }

        ksort($folders);
        foreach ($folders as &$images) {
            sort($images);
        }

        return $folders;
    }

    /**
     * Basename of the last scored image, read from the tail of the motion
     * file: images are scored in filename order, so everything at or below
     * it is already done.
     */
    private function lastScoredImage(string $motionFile) : ?string
    {
        if (!is_readable($motionFile) || ($size = filesize($motionFile)) === 0) {
            return null;
        }

        $handle = fopen($motionFile, 'r');
        fseek($handle, -min($size, 8192), SEEK_END);
        $lines = explode("\n", stream_get_contents($handle));
        fclose($handle);

        for ($i = count($lines) - 1; $i >= 0; $i--) {
            $row = json_decode($lines[$i], true);
            if (isset($row['file'])) {
                return $row['file'];
            }
        }

        return null;
    }
}
