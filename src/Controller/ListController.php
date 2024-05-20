<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Webcam;
use App\Enum\Size;
use App\HttpFoundation\JpegResponse;
use App\Service\WebcamService;
use App\ValueResolver\WebcamValueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class ListController extends AbstractController
{
    private WebcamService $webcamService;

    public function __construct(WebcamService $webcamService)
    {
        $this->webcamService = $webcamService;
    }

    #[Route('/', name: 'home')]
    public function index() : Response
    {
        return $this->render('index.html.twig', [
            'webcams' => $this->webcamService->list(),
        ]);
    }

    #[Route('/last/{webcam}-{size}.jpg', name: 'last')]
    public function last(
        #[ValueResolver(WebcamValueResolver::class)] Webcam $webcam,
        Size $size
    ) : Response {
        return new JpegResponse(
            $this->webcamService->last($webcam, $size)
        );
    }
}
