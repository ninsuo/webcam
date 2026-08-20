<?php

declare(strict_types=1);

namespace App\Tests;

use App\DTO\MotionPassage;
use PHPUnit\Framework\TestCase;

class MotionPassageTest extends TestCase
{
    private function passage(int $count) : MotionPassage
    {
        $frames = [];
        for ($i = 1; $i <= $count; $i++) {
            $frames[] = ['file' => sprintf('/cam/images/img-%03d.jpg', $i), 'time' => 1000 + $i * 6, 'changed' => $i];
        }

        return new MotionPassage($frames);
    }

    public function testPagination() : void
    {
        $passage = $this->passage(50);

        $this->assertSame(3, $passage->countPages(24));

        $page1 = $passage->getFramesPage(1, 24);
        $this->assertCount(24, $page1);
        $this->assertSame('/cam/images/img-001.jpg', $page1[0]['file']);

        $page3 = $passage->getFramesPage(3, 24);
        $this->assertCount(2, $page3);
        $this->assertSame('/cam/images/img-050.jpg', $page3[1]['file']);

        // out-of-range pages clamp to valid ones
        $this->assertSame($page1, $passage->getFramesPage(0, 24));
        $this->assertSame($page3, $passage->getFramesPage(99, 24));

        // page on which a given frame lives (for back links)
        $this->assertSame(1, $passage->getFramePage('/cam/images/img-024.jpg', 24));
        $this->assertSame(2, $passage->getFramePage('/cam/images/img-025.jpg', 24));
        $this->assertSame(1, $passage->getFramePage('/cam/images/unknown.jpg', 24));
    }

    public function testPrevAndNextFrame() : void
    {
        $passage = $this->passage(3);

        $this->assertNull($passage->getPrevFrame('/cam/images/img-001.jpg'));
        $this->assertSame('/cam/images/img-001.jpg', $passage->getPrevFrame('/cam/images/img-002.jpg'));
        $this->assertSame('/cam/images/img-003.jpg', $passage->getNextFrame('/cam/images/img-002.jpg'));
        $this->assertNull($passage->getNextFrame('/cam/images/img-003.jpg'));

        $this->assertNull($passage->getNextFrame('/cam/images/unknown.jpg'));
    }

    public function testHasFrame() : void
    {
        $passage = $this->passage(2);

        $this->assertTrue($passage->hasFrame('/cam/images/img-002.jpg'));
        $this->assertFalse($passage->hasFrame('/cam/images/img-999.jpg'));
    }
}
