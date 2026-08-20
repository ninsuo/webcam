<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\MotionScore;

/**
 * Compares two consecutive webcam frames and scores how much changed.
 *
 * Frames are downscaled and blurred so that JPEG/sensor noise scores 0;
 * a moving object (the cat) shows up as a compact cluster of changed
 * pixels, while lighting shifts and day/night mode switches are either
 * too spread out (low density) or too large (near-global) to qualify.
 */
class MotionAnalyzer
{
    public const WIDTH = 160;
    public const HEIGHT = 90;

    // per-pixel grayscale delta (0-255) above which a pixel counts as changed
    public const PIXEL_THRESHOLD = 25;

    // event = at least this many changed pixels...
    public const MIN_CHANGED_PIXELS = 20;

    // ...but not more than this share of the frame (day/night switch)...
    public const MAX_CHANGED_RATIO = 0.15;

    // ...and packed at this minimal density in their bounding box (vs diffuse lighting)
    public const MIN_DENSITY = 0.05;

    public function compare(string $prevFile, string $curFile) : MotionScore
    {
        $prev = $this->loadGrayscale($prevFile);
        $cur = $this->loadGrayscale($curFile);

        $changed = 0;
        $minX = self::WIDTH;
        $minY = self::HEIGHT;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < self::HEIGHT; $y++) {
            for ($x = 0; $x < self::WIDTH; $x++) {
                $delta = abs(
                    (imagecolorat($prev, $x, $y) & 0xFF)
                    - (imagecolorat($cur, $x, $y) & 0xFF)
                );

                if ($delta > self::PIXEL_THRESHOLD) {
                    $changed++;
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }
        }

        $density = 0.0;
        if ($changed > 0) {
            $density = $changed / (($maxX - $minX + 1) * ($maxY - $minY + 1));
        }

        return new MotionScore($changed, $density);
    }

    private function loadGrayscale(string $file) : \GdImage
    {
        $source = imagecreatefromjpeg($file);

        $small = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled(
            $small, $source,
            0, 0, 0, 0,
            self::WIDTH, self::HEIGHT,
            imagesx($source), imagesy($source)
        );

        imagefilter($small, IMG_FILTER_GRAYSCALE);
        imagefilter($small, IMG_FILTER_GAUSSIAN_BLUR);

        return $small;
    }
}
