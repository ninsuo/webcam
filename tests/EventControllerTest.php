<?php

declare(strict_types=1);

namespace App\Tests;

use App\Controller\EventController;
use App\DTO\Webcam;
use App\Enum\Size;
use App\Service\MotionService;
use App\Service\WebcamService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class EventControllerTest extends TestCase
{
    public function testEventFramesAreBrowserCacheable() : void
    {
        $webcamService = $this->createMock(WebcamService::class);
        $webcamService->method('image')->willReturn('jpeg-bytes');

        $controller = new EventController($webcamService, $this->createMock(MotionService::class));

        $response = $controller->frame(
            new Webcam('test', ['alain'], '/tmp'),
            Size::THUMBNAIL,
            new Request(['file' => 'images/img-001.jpg'])
        );

        $this->assertSame('jpeg-bytes', $response->getContent());
        // archived frames never change: the browser must not re-render
        // them on every events page visit
        $this->assertTrue($response->headers->getCacheControlDirective('private'));
        $this->assertSame('86400', (string) $response->headers->getCacheControlDirective('max-age'));
        $this->assertTrue($response->headers->has('Symfony-Session-NoAutoCacheControl'));
    }
}
