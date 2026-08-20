<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\MotionAnalyzer;
use PHPUnit\Framework\TestCase;

class MotionAnalyzerTest extends TestCase
{
    private array $files = [];

    protected function tearDown() : void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        $this->files = [];
    }

    public function testIdenticalImagesProduceNoEvent() : void
    {
        $a = $this->createImage();
        $b = $this->createImage();

        $score = (new MotionAnalyzer())->compare($a, $b);

        $this->assertSame(0, $score->getChangedPixels());
        $this->assertFalse($score->isEvent());
    }

    public function testCompactBlobIsAnEvent() : void
    {
        $a = $this->createImage();
        $b = $this->createImage(function ($img) {
            // a cat-sized dark blob on the floor
            imagefilledrectangle($img, 600, 500, 800, 650, imagecolorallocate($img, 30, 30, 30));
        });

        $score = (new MotionAnalyzer())->compare($a, $b);

        $this->assertGreaterThanOrEqual(MotionAnalyzer::MIN_CHANGED_PIXELS, $score->getChangedPixels());
        $this->assertTrue($score->isEvent());
    }

    public function testGlobalChangeIsNotAnEvent() : void
    {
        $a = $this->createImage();
        $b = $this->createImage(function ($img) {
            // day/night mode switch: the whole frame changes
            imagefilledrectangle($img, 0, 0, 1279, 719, imagecolorallocate($img, 80, 80, 80));
        });

        $score = (new MotionAnalyzer())->compare($a, $b);

        $this->assertGreaterThan(0, $score->getChangedPixels());
        $this->assertFalse($score->isEvent());
    }

    public function testScatteredSparseChangesAreNotAnEvent() : void
    {
        $a = $this->createImage();
        $b = $this->createImage(function ($img) {
            // small changes in opposite corners: enough pixels, but spread
            // over the whole frame (lighting shift), not a compact object
            $dark = imagecolorallocate($img, 30, 30, 30);
            imagefilledrectangle($img, 0, 0, 48, 48, $dark);
            imagefilledrectangle($img, 1231, 671, 1279, 719, $dark);
        });

        $score = (new MotionAnalyzer())->compare($a, $b);

        $this->assertGreaterThanOrEqual(MotionAnalyzer::MIN_CHANGED_PIXELS, $score->getChangedPixels());
        $this->assertFalse($score->isEvent());
    }

    private function createImage(?callable $draw = null) : string
    {
        $img = imagecreatetruecolor(1280, 720);
        imagefilledrectangle($img, 0, 0, 1279, 719, imagecolorallocate($img, 220, 220, 220));

        if ($draw) {
            $draw($img);
        }

        $file = tempnam(sys_get_temp_dir(), 'motion-test-').'.jpg';
        imagejpeg($img, $file);
        $this->files[] = $file;

        return $file;
    }
}
