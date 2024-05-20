<?php

namespace App\ValueResolver;

use App\DTO\Webcam;
use App\Service\WebcamService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WebcamValueResolver implements ValueResolverInterface
{
    private WebcamService $webcamService;

    public function __construct(WebcamService $webcamService)
    {
        $this->webcamService = $webcamService;
    }

    public function resolve(Request $request, ArgumentMetadata $argument) : iterable
    {
        if (Webcam::class !== $argument->getType()) {
            return [];
        }

        $webcam = $this->webcamService->get(
            $request->attributes->get('webcam')
        );

        if (null === $webcam) {
            throw new NotFoundHttpException('Webcam not found');
        }

        return [$webcam];
    }
}