<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\MotionPassage;
use App\DTO\Webcam;
use App\Enum\Size;
use App\HttpFoundation\JpegResponse;
use App\Service\MotionService;
use App\Service\WebcamService;
use App\ValueResolver\WebcamValueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    private WebcamService $webcamService;
    private MotionService $motionService;

    public function __construct(WebcamService $webcamService, MotionService $motionService)
    {
        $this->webcamService = $webcamService;
        $this->motionService = $motionService;
    }

    #[Route('/events/{webcam}', name: 'events')]
    public function index(#[ValueResolver(WebcamValueResolver::class)] Webcam $webcam) : Response
    {
        $passages = array_map(
            fn(MotionPassage $passage) => [
                'start' => $passage->getStart(),
                'end' => $passage->getEnd(),
                'best' => $this->relative($webcam, $passage->getBestFrame()),
                'frames' => array_map(
                    fn($frame) => $this->relative($webcam, $frame['file']),
                    $passage->getFrames()
                ),
            ],
            array_reverse($this->motionService->getPassages($webcam))
        );

        return $this->render('events.html.twig', [
            'webcam' => $webcam,
            'passages' => $passages,
        ]);
    }

    #[Route('/event/{webcam}-{size}.jpg', name: 'event_frame')]
    public function frame(
        #[ValueResolver(WebcamValueResolver::class)] Webcam $webcam,
        Size $size,
        Request $request
    ) : Response {
        return new JpegResponse(
            $this->webcamService->image($webcam, (string) $request->get('file'), $size)
        );
    }

    private function relative(Webcam $webcam, string $file) : string
    {
        return substr($file, strlen($webcam->getPath()) + 1);
    }
}
