<?php

namespace App\Service;

use App\Constants;
use App\DTO\Archive;
use App\DTO\Webcam;
use App\Enum\Size;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WebcamService
{
    private ConfigurationService $configurationService;
    private LoggerInterface $logger;
    private string $projectDir;

    public function __construct(ConfigurationService $configurationService,
        LoggerInterface $logger,
        #[Autowire('%kernel.project_dir%')] string $projectDir)
    {
        $this->configurationService = $configurationService;
        $this->logger = $logger;
        $this->projectDir = $projectDir;
    }

    public function get(string $name) : ?Webcam
    {
        $webcams = $this->list();

        foreach ($webcams as $webcam) {
            if ($webcam->getName() === $name) {
                return $webcam;
            }
        }

        return null;
    }

    /**
     * @return array|Webcam[]
     */
    public function list() : array
    {
        return $this->configurationService->getWebcams();
    }

    public function last(Webcam $webcam, Size $size) : string
    {
        $file = exec($webcam->getLastImageCommand());

        $this->logger->info('Last image', ['file' => $file]);

        return $this->render($file, $size);
    }

    private function render(string $file, Size $size) : string
    {
        $date = new \DateTime();
        $date->setTimestamp(filemtime($file));

        if (!is_readable($file)) {
            $this->logger->error('Cannot read file', ['file' => $file]);

            return $this->error();
        }

        $manager = new ImageManager(new Driver());
        $image = $manager->read($file);
        $image->scale(width: $size->getWidth());
        $image->text(
            $date->format('d/m/Y H:i:s'),
            $size->getTimeX(),
            $size->getTimeY(),
            function (FontFactory $font) use ($size) {
                $font->filename($this->projectDir.'/fonts/Lato/Lato-Regular.ttf');
                $font->size($size->getTimeSize());
                $font->color('ffff00');
                $font->align('right');
                $font->lineHeight(1.6);
            }
        );

        return $image->toJpeg()->toString();
    }

    private function error() : string
    {
        $img = imagecreate(Constants::DEFAULT_WIDTH, Constants::DEFAULT_HEIGHT);
        ob_start();
        imagejpeg($img);

        return ob_get_clean();
    }

    public function history(Webcam $webcam, Size $size, int $n) : string
    {
        $images = $this->listImages($webcam);

        // $n is a number between 1 and 10000 (10000 being Constants::SLIDER)
        // User wants to see image located at $n among the list of images,
        // which is between 1 and count($images). We need to find the closest
        // index in the list of images.
        $index = (int) round($n / Constants::SLIDER * count($images));

        if ($index < 0 || $index >= count($images)) {
            return $this->error();
        }

        $file = $images[$index];

        if (!is_readable($file)) {
            $this->logger->error('Cannot read file', ['file' => $file]);

            return $this->error();
        }

        return $this->render($file, $size);
    }

    private function listImages(Webcam $webcam) : array
    {
        return explode("\n", trim(shell_exec($webcam->getListImagesCommand())));
    }

    public function getArchive(Webcam $webcam, string $name) : Archive
    {
        $archives = $this->listArchives($webcam);

        foreach ($archives as $archive) {
            if ($archive->getName() === $name) {
                return $archive;
            }
        }

        throw new NotFoundHttpException();
    }

    /**
     * @return Archive[]
     */
    public function listArchives(Webcam $webcam) : array
    {
        return array_map(
            fn($filename) => new Archive($filename),
            explode("\n", trim(shell_exec($webcam->getListArchivesCommand())))
        );
    }
}