<?php

namespace App\Controller;

use App\DTO\Webcam;
use App\Enum\Size;
use App\HttpFoundation\JpegResponse;
use App\Service\WebcamService;
use App\ValueResolver\WebcamValueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class ImageController extends AbstractController
{
    private WebcamService $webcamService;

    public function __construct(WebcamService $webcamService)
    {
        $this->webcamService = $webcamService;
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

    #[Route('/history/{webcam}-{size}.jpg', name: 'history')]
    public function history(
        #[ValueResolver(WebcamValueResolver::class)] Webcam $webcam,
        Size $size,
        Request $request
    ) : Response {
        return new JpegResponse(
            $this->webcamService->history($webcam, $size, $request->get('val'))
        );
    }
}