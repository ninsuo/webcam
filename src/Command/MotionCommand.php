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
        // backfilling a full day takes minutes; the every-minute cron must
        // not pile up concurrent runs racing on the same motion files
        $lock = fopen(self::getLockFile(), 'c');
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $output->writeln('already running, skipping');

            return Command::SUCCESS;
        }

        foreach ($this->webcamService->list() as $webcam) {
            try {
                $scored = $this->motionService->detect($webcam);

                $output->writeln(sprintf('%s: %d new image(s) scored', $webcam->getName(), $scored));
            } catch (\Throwable $e) {
                // one broken webcam must not block the others
                $output->writeln(sprintf('%s: error (%s)', $webcam->getName(), $e->getMessage()));
            }
        }

        flock($lock, LOCK_UN);
        fclose($lock);

        return Command::SUCCESS;
    }

    public static function getLockFile() : string
    {
        return sys_get_temp_dir().'/app-motion.lock';
    }
}
