<?php

declare(strict_types=1);

namespace App\Tests;

use App\DTO\Webcam;
use App\Service\MotionAnalyzer;
use App\Service\MotionService;
use PHPUnit\Framework\TestCase;

class MotionServiceTest extends TestCase
{
    private string $dir;

    protected function setUp() : void
    {
        $this->dir = sys_get_temp_dir().'/motion-service-test-'.uniqid();
        mkdir($this->dir.'/20260819/images', 0777, true);
    }

    protected function tearDown() : void
    {
        exec(sprintf('rm -rf %s', escapeshellarg($this->dir)));
    }

    public function testDetectScoresNewImagesAndAppendsToJsonl() : void
    {
        $this->addImage('20260819/images/img-001.jpg');
        $this->addImage('20260819/images/img-002.jpg', withBlob: true);
        $this->addImage('20260819/images/img-003.jpg');

        $count = $this->service()->detect($this->webcam());

        $this->assertSame(3, $count);

        $lines = $this->readJsonl('20260819/images/motion.jsonl');
        $this->assertCount(3, $lines);

        // first frame of a folder has nothing to compare against
        $this->assertFalse($lines[0]['event']);
        // blob appears, then disappears: both are motion
        $this->assertTrue($lines[1]['event']);
        $this->assertTrue($lines[2]['event']);
    }

    public function testDetectIsIncremental() : void
    {
        $this->addImage('20260819/images/img-001.jpg');
        $this->addImage('20260819/images/img-002.jpg');

        $service = $this->service();
        $service->detect($this->webcam());

        $this->addImage('20260819/images/img-003.jpg', withBlob: true);
        $count = $service->detect($this->webcam());

        $this->assertSame(1, $count);

        $lines = $this->readJsonl('20260819/images/motion.jsonl');
        $this->assertCount(3, $lines);
        $this->assertTrue($lines[2]['event']);
    }

    public function testDetectSurvivesCorruptImages() : void
    {
        $this->addImage('20260819/images/img-001.jpg');
        file_put_contents($this->dir.'/20260819/images/img-002.jpg', 'partial camera upload');
        touch($this->dir.'/20260819/images/img-002.jpg', time() - 120);
        $this->addImage('20260819/images/img-003.jpg', withBlob: true);

        $count = $this->service()->detect($this->webcam());

        $this->assertSame(3, $count);

        $lines = $this->readJsonl('20260819/images/motion.jsonl');
        $this->assertCount(3, $lines);
        $this->assertFalse($lines[1]['event']);
    }

    public function testDetectDefersImagesStillBeingWritten() : void
    {
        $this->addImage('20260819/images/img-001.jpg');
        $this->addImage('20260819/images/img-002.jpg', fresh: true);

        $count = $this->service()->detect($this->webcam());

        $this->assertSame(1, $count);

        $lines = $this->readJsonl('20260819/images/motion.jsonl');
        $this->assertCount(1, $lines);
        $this->assertSame('img-001.jpg', $lines[0]['file']);
    }

    public function testDetectStopsAtFirstImageStillBeingWritten() : void
    {
        $this->addImage('20260819/images/img-001.jpg');
        $this->addImage('20260819/images/img-002.jpg', fresh: true);
        $this->addImage('20260819/images/img-003.jpg');

        $count = $this->service()->detect($this->webcam());

        // scanning stops at img-002: scoring img-003 now would break the
        // "everything before the last scored image is done" watermark
        $this->assertSame(1, $count);
        $lines = $this->readJsonl('20260819/images/motion.jsonl');
        $this->assertSame('img-001.jpg', $lines[0]['file']);
    }

    public function testDetectOnlyConsidersImagesAboveTheLastScoredOne() : void
    {
        $this->addImage('20260819/images/img-001.jpg');
        $this->addImage('20260819/images/img-002.jpg');
        $this->addImage('20260819/images/img-003.jpg');
        $this->writeJsonl('20260819/images/motion.jsonl', [
            ['file' => 'img-002.jpg', 'time' => 1000, 'changed' => 0, 'density' => 0.0, 'event' => false],
        ]);

        $count = $this->service()->detect($this->webcam());

        // img-001 sorts below the watermark: never rescored, even though
        // it has no jsonl line of its own
        $this->assertSame(1, $count);
        $lines = $this->readJsonl('20260819/images/motion.jsonl');
        $this->assertCount(2, $lines);
        $this->assertSame('img-003.jpg', $lines[1]['file']);
    }

    public function testPassagesIgnoreDuplicateAndCorruptJsonlLines() : void
    {
        file_put_contents(
            $this->dir.'/20260819/images/motion.jsonl',
            json_encode(['file' => 'img-001.jpg', 'time' => 1000, 'changed' => 50, 'density' => 0.2, 'event' => true])."\n"
            .json_encode(['file' => 'img-001.jpg', 'time' => 1000, 'changed' => 50, 'density' => 0.2, 'event' => true])."\n"
            ."{\"file\":\"img-0\n"
        );

        $passages = $this->service()->getPassages($this->webcam());

        $this->assertCount(1, $passages);
        $this->assertCount(1, $passages[0]->getFrames());
    }

    public function testPassagesGroupEventFramesByTimeProximity() : void
    {
        $this->writeJsonl('20260819/images/motion.jsonl', [
            ['file' => 'img-001.jpg', 'time' => 1000, 'changed' => 0, 'density' => 0.0, 'event' => false],
            ['file' => 'img-002.jpg', 'time' => 1006, 'changed' => 79, 'density' => 0.22, 'event' => true],
            ['file' => 'img-003.jpg', 'time' => 1012, 'changed' => 34, 'density' => 0.12, 'event' => true],
            ['file' => 'img-004.jpg', 'time' => 1018, 'changed' => 0, 'density' => 0.0, 'event' => false],
            // more than 2 minutes later: a distinct passage
            ['file' => 'img-005.jpg', 'time' => 2000, 'changed' => 55, 'density' => 0.18, 'event' => true],
        ]);

        $passages = $this->service()->getPassages($this->webcam());

        $this->assertCount(2, $passages);

        $this->assertCount(2, $passages[0]->getFrames());
        $this->assertSame(1006, $passages[0]->getStart()->getTimestamp());
        $this->assertSame(1012, $passages[0]->getEnd()->getTimestamp());
        $this->assertStringEndsWith('img-002.jpg', $passages[0]->getBestFrame());

        $this->assertCount(1, $passages[1]->getFrames());
        $this->assertStringEndsWith('img-005.jpg', $passages[1]->getBestFrame());
    }

    public function testPassageFramesCarryTheFullImagePath() : void
    {
        $this->writeJsonl('20260819/images/motion.jsonl', [
            ['file' => 'img-001.jpg', 'time' => 1000, 'changed' => 50, 'density' => 0.2, 'event' => true],
        ]);

        $passages = $this->service()->getPassages($this->webcam());

        $this->assertSame(
            $this->dir.'/20260819/images/img-001.jpg',
            $passages[0]->getBestFrame()
        );
    }

    private function writeJsonl(string $relative, array $rows) : void
    {
        file_put_contents(
            $this->dir.'/'.$relative,
            implode("\n", array_map('json_encode', $rows))."\n"
        );
    }

    private function service() : MotionService
    {
        return new MotionService(new MotionAnalyzer());
    }

    private function webcam() : Webcam
    {
        return new Webcam('test', ['alain'], $this->dir);
    }

    private function addImage(string $relative, bool $withBlob = false, bool $fresh = false) : void
    {
        $img = imagecreatetruecolor(1280, 720);
        imagefilledrectangle($img, 0, 0, 1279, 719, imagecolorallocate($img, 220, 220, 220));

        if ($withBlob) {
            imagefilledrectangle($img, 600, 500, 800, 650, imagecolorallocate($img, 30, 30, 30));
        }

        imagejpeg($img, $this->dir.'/'.$relative);

        if (!$fresh) {
            // images are normally older than the "may still be uploading" window
            touch($this->dir.'/'.$relative, time() - 120);
        }
    }

    private function readJsonl(string $relative) : array
    {
        $this->assertFileExists($this->dir.'/'.$relative);

        return array_map(
            fn($line) => json_decode($line, true),
            array_filter(explode("\n", trim(file_get_contents($this->dir.'/'.$relative))))
        );
    }
}
