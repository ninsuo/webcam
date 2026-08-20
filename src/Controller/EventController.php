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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class EventController extends AbstractController
{
    public const PER_PAGE = 24;

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
                'count' => count($passage->getFrames()),
            ],
            array_reverse($this->motionService->getPassages($webcam))
        );

        return $this->render('events.html.twig', [
            'webcam' => $webcam,
            'passages' => $passages,
        ]);
    }

    #[Route('/events/{webcam}/passage/{start}', name: 'event_passage', requirements: ['start' => '\d+'])]
    public function passage(
        #[ValueResolver(WebcamValueResolver::class)] Webcam $webcam,
        int $start,
        Request $request
    ) : Response {
        $passage = $this->motionService->getPassage($webcam, $start)
            ?? throw new NotFoundHttpException('Passage not found');

        $page = min(max((int) $request->get('page', 1), 1), $passage->countPages(self::PER_PAGE));

        return $this->render('passage.html.twig', [
            'webcam' => $webcam,
            'start' => $start,
            'passage' => $passage,
            'page' => $page,
            'pages' => $passage->countPages(self::PER_PAGE),
            'frames' => array_map(
                fn($frame) => $this->relative($webcam, $frame['file']),
                $passage->getFramesPage($page, self::PER_PAGE)
            ),
        ]);
    }

    #[Route('/events/{webcam}/frame/{start}', name: 'event_frame_view', requirements: ['start' => '\d+'])]
    public function frameView(
        #[ValueResolver(WebcamValueResolver::class)] Webcam $webcam,
        int $start,
        Request $request
    ) : Response {
        $passage = $this->motionService->getPassage($webcam, $start)
            ?? throw new NotFoundHttpException('Passage not found');

        $file = $webcam->getPath().'/'.$request->get('file');
        if (!$passage->hasFrame($file)) {
            throw new NotFoundHttpException('Frame not found');
        }

        $prev = $passage->getPrevFrame($file);
        $next = $passage->getNextFrame($file);

        return $this->render('frame.html.twig', [
            'webcam' => $webcam,
            'start' => $start,
            'file' => $this->relative($webcam, $file),
            'page' => $passage->getFramePage($file, self::PER_PAGE),
            'prev' => $prev ? $this->relative($webcam, $prev) : null,
            'next' => $next ? $this->relative($webcam, $next) : null,
        ]);
    }

    #[Route('/event/{webcam}-{size}.jpg', name: 'event_frame')]
    public function frame(
        #[ValueResolver(WebcamValueResolver::class)] Webcam $webcam,
        Size $size,
        Request $request
    ) : Response {
        return new JpegResponse(
            $this->webcamService->image($webcam, (string) $request->get('file'), $size),
            cacheable: true
        );
    }

    private function relative(Webcam $webcam, string $file) : string
    {
        return substr($file, strlen($webcam->getPath()) + 1);
    }
}
