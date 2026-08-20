<?php

declare(strict_types=1);

namespace App\Tests;

use App\Command\MotionCommand;
use App\DTO\Webcam;
use App\Service\MotionAnalyzer;
use App\Service\MotionService;
use App\Service\WebcamService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MotionCommandTest extends TestCase
{
    private string $dir;

    protected function setUp() : void
    {
        $this->dir = sys_get_temp_dir().'/motion-command-test-'.uniqid();
        mkdir($this->dir.'/images', 0777, true);

        $img = imagecreatetruecolor(1280, 720);
        imagefilledrectangle($img, 0, 0, 1279, 719, imagecolorallocate($img, 220, 220, 220));
        imagejpeg($img, $this->dir.'/images/img-001.jpg');
    }

    protected function tearDown() : void
    {
        exec(sprintf('rm -rf %s', escapeshellarg($this->dir)));
    }

    public function testCommandDetectsMotionForEveryWebcam() : void
    {
        $webcamService = $this->createMock(WebcamService::class);
        $webcamService->method('list')->willReturn([
            new Webcam('test', ['alain'], $this->dir),
        ]);

        $tester = new CommandTester(
            new MotionCommand($webcamService, new MotionService(new MotionAnalyzer()))
        );
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($this->dir.'/images/motion.jsonl');
        $this->assertStringContainsString('test', $tester->getDisplay());
    }
}
