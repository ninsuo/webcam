<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Webcam;
use App\Service\WebcamService;
use App\ValueResolver\WebcamValueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class LiveController extends AbstractController
{
    private WebcamService $webcamService;

    public function __construct(WebcamService $webcamService)
    {
        $this->webcamService = $webcamService;
    }

    #[Route('/live/{webcam}', name: 'live')]
    public function index(#[ValueResolver(WebcamValueResolver::class)] Webcam $webcam) : Response
    {
        return $this->render('live.html.twig', [
            'webcam' => $webcam,
            'archives' => $this->webcamService->listArchives($webcam),
        ]);
    }

    #[Route('/archive/{webcam}/{name}', name: 'archive')]
    public function archive(
        #[ValueResolver(WebcamValueResolver::class)] Webcam $webcam,
        string $name
    ) : StreamedResponse {
        return new StreamedResponse(function () use ($webcam, $name) {
            $stream = fopen($this->webcamService->getArchive($webcam, $name)->getFilename(), 'rb');
            fpassthru($stream);
        }, headers: [
            'Content-Type' => 'application/x-gzip',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }
}
