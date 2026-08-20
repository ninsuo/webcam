<?php

declare(strict_types=1);

namespace App\Tests;

use App\DTO\Webcam;
use PHPUnit\Framework\TestCase;

class WebcamTest extends TestCase
{
    private string $dir;

    protected function setUp() : void
    {
        $this->dir = realpath(sys_get_temp_dir()).'/webcam-test-'.uniqid();
        mkdir($this->dir.'/20260819/images', 0777, true);
        touch($this->dir.'/20260819/images/img-001.jpg');
        touch($this->dir.'/20260819/images/motion.jsonl');
        touch($this->dir.'/secret.jpg');
    }

    protected function tearDown() : void
    {
        exec(sprintf('rm -rf %s', escapeshellarg($this->dir)));
    }

    public function testResolveImageReturnsAbsolutePathForExistingImage() : void
    {
        $webcam = new Webcam('test', ['alain'], $this->dir.'/20260819');

        $this->assertSame(
            $this->dir.'/20260819/images/img-001.jpg',
            $webcam->resolveImage('images/img-001.jpg')
        );
    }

    public function testResolveImageRejectsPathsOutsideTheWebcamFolder() : void
    {
        $webcam = new Webcam('test', ['alain'], $this->dir.'/20260819');

        $this->assertNull($webcam->resolveImage('../secret.jpg'));
        $this->assertNull($webcam->resolveImage('/etc/passwd'));
    }

    public function testResolveImageRejectsNonJpegFiles() : void
    {
        $webcam = new Webcam('test', ['alain'], $this->dir.'/20260819');

        $this->assertNull($webcam->resolveImage('images/motion.jsonl'));
    }

    public function testResolveImageRejectsMissingFiles() : void
    {
        $webcam = new Webcam('test', ['alain'], $this->dir.'/20260819');

        $this->assertNull($webcam->resolveImage('images/img-999.jpg'));
    }
}
