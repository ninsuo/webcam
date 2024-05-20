<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Webcam;
use App\ValueResolver\WebcamValueResolver;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;

class LiveController extends AbstractController
{
    #[Route('/live/{webcam}', name: 'live')]
    public function index(#[ValueResolver(WebcamValueResolver::class)] Webcam $webcam) : Response
    {
        return $this->render('live.html.twig');
    }
}
