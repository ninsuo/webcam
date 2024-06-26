<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\WebcamService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[AsCommand(name: 'app:cron', description: 'Cron jobs')]
class CronCommand extends Command
{
    private WebcamService $webcamService;
    private Environment $twig;

    public function __construct(WebcamService $webcamService, Environment $twig)
    {
        $this->webcamService = $webcamService;
        $this->twig = $twig;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        foreach ($this->webcamService->list() as $webcam) {
            $script = sprintf('/tmp/cron-%s.sh', $webcam->getName());

            file_put_contents(
                $script,
                $this->twig->render('cron.sh.twig', ['webcam' => $webcam])
            );

            exec(sprintf('sh /tmp/cron-%s.sh', $webcam->getName()));
        }

        return Command::SUCCESS;
    }
}
