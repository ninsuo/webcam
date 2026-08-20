<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\MotionService;
use App\Service\WebcamService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:motion', description: 'Score new webcam images for motion events')]
class MotionCommand extends Command
{
    private WebcamService $webcamService;
    private MotionService $motionService;

    public function __construct(WebcamService $webcamService, MotionService $motionService)
    {
        $this->webcamService = $webcamService;
        $this->motionService = $motionService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        foreach ($this->webcamService->list() as $webcam) {
            $scored = $this->motionService->detect($webcam);

            $output->writeln(sprintf('%s: %d new image(s) scored', $webcam->getName(), $scored));
        }

        return Command::SUCCESS;
    }
}
