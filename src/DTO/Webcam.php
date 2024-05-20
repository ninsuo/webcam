<?php

namespace App\DTO;

final readonly class Webcam
{
    private string $name;
    private array $users;
    private string $path;
    private string $listImagesCommand;
    private string $listArchivesCommand;
    private string $cleanImagesCommand;
    private string $cleanArchivesCommand;

    public function __construct(
        string $name,
        array $users,
        string $path,
        string $listImagesCommand,
        string $listArchivesCommand,
        string $cleanImagesCommand,
        string $cleanArchivesCommand)
    {
        $this->name = $name;
        $this->users = $users;
        $this->path = $path;
        $this->listImagesCommand = $listImagesCommand;
        $this->listArchivesCommand = $listArchivesCommand;
        $this->cleanImagesCommand = $cleanImagesCommand;
        $this->cleanArchivesCommand = $cleanArchivesCommand;
    }

    static public function loadFromConfig(string $name, array $config) : self
    {
        return new self(
            $name,
            $config['users'],
            $config['path'],
            $config['list_images_command'],
            $config['list_archives_command'],
            $config['clean_images_command'],
            $config['clean_archives_command']
        );
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

    public function getListImagesCommand() : string
    {
        return $this->listImagesCommand;
    }

    public function getListArchivesCommand() : string
    {
        return $this->listArchivesCommand;
    }

    public function getCleanImagesCommand() : string
    {
        return $this->cleanImagesCommand;
    }

    public function getCleanArchivesCommand() : string
    {
        return $this->cleanArchivesCommand;
    }
}