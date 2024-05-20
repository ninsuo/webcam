<?php

namespace App\Service;

use App\DTO\Webcam;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurationService
{
    private TokenStorageInterface $tokenStorage;

    /**
     * @var array|Webcam[]
     */
    private array $webcams = [];

    public function __construct(TokenStorageInterface $tokenStorage,
        #[Autowire('%kernel.project_dir%')] $dir)
    {
        $this->tokenStorage = $tokenStorage;

        $config = Yaml::parseFile(sprintf('%s/config.yaml', $dir));

        foreach ($config['webcams'] as $name => $array) {
            $object = Webcam::loadFromConfig($name, $array);
            if (in_array($this->tokenStorage->getToken()?->getUserIdentifier(), $object->getUsers())) {
                $this->webcams[] = $object;
            }
        }
    }

    /**
     * @return array|Webcam[]
     */
    public function getWebcams() : array
    {
        return $this->webcams;
    }
}