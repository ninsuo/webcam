<?php

namespace App\DTO;

final readonly class Webcam
{
    const LAST_IMAGE_COMMAND = 'find {path} -type f -name "*.jpg" | sort | tac | head -n 1';
    const LIST_IMAGES_COMMAND = 'find {path} -type f -name "*.jpg" | sort';
    const LIST_ARCHIVES_COMMAND = 'find {path} -type f -name "*.tar.gz"';

    private string $name;
    private array $users;
    private string $path;

    public function __construct(string $name, array $users, string $path)
    {
        $this->name = $name;
        $this->users = $users;
        $this->path = $path;
    }

    static public function loadFromConfig(string $name, array $config) : self
    {
        return new self($name, $config['users'], $config['path']);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getUsers() : array
    {
        return $this->users;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getLastImageCommand() : string
    {
        return str_replace('{path}', $this->path, self::LAST_IMAGE_COMMAND);
    }

    public function getListImagesCommand() : string
    {
        return str_replace('{path}', $this->path, self::LIST_IMAGES_COMMAND);
    }

    public function getListArchivesCommand() : string
    {
        return str_replace('{path}', $this->path, self::LIST_ARCHIVES_COMMAND);
    }
}