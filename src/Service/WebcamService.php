<?php

namespace App\Service;

use App\Constants;
use App\DTO\Webcam;
use App\Enum\Size;

class WebcamService
{
    private ConfigurationService $configurationService;

    public function __construct(ConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
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
        return $this->error();
    }

    private function error() : string
    {
        $img = imagecreate(Constants::DEFAULT_WIDTH, Constants::DEFAULT_HEIGHT);
        ob_start();
        imagejpeg($img);

        return ob_get_clean();
    }
}