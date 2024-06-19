<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\WebcamService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
}
