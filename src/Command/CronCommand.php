<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\WebcamService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:cron', description: 'Cron jobs')]
class CronCommand extends Command
{
    private WebcamService $webcamService;

    public function __construct(WebcamService $webcamService)
    {
        $this->webcamService = $webcamService;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        foreach ($this->webcamService->list() as $webcam) {
            $output->writeln('Archiving old images for '.$webcam->getName());
            exec($webcam->getArchiveImagesCommand());

            $output->writeln('Cleaning old images for '.$webcam->getName());
            exec($webcam->getCleanImagesCommand());

            $output->writeln('Cleaning old archives for '.$webcam->getName());
            exec($webcam->getCleanArchivesCommand());

            $output->writeln('Cleaning empty directories for '.$webcam->getName());
            exec($webcam->getCleanEmptyDirectoriesCommand());
        }

        return Command::SUCCESS;
    }
}
