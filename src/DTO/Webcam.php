<?php

namespace App\DTO;

final readonly class Webcam
{
    private string $name;
    private array $users;
    private string $path;
    private string $lastImageCommand;
    private string $listImagesCommand;
    private string $listArchivesCommand;
    private string $cleanImagesCommand;
    private string $cleanArchivesCommand;

    public function __construct(
        string $name,
        array $users,
        string $path,
        string $lastImageCommand,
        string $listImagesCommand,
        string $listArchivesCommand,
        string $cleanImagesCommand,
        string $cleanArchivesCommand)
    {
        $this->name = $name;
        $this->users = $users;
        $this->path = $path;
        $this->lastImageCommand = $lastImageCommand;
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
            $config['last_image_command'],
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

    public function getLastImageCommand() : string
    {
        return str_replace('{path}', $this->path, $this->lastImageCommand);
    }

    public function getListImagesCommand() : string
    {
        return str_replace('{path}', $this->path, $this->listImagesCommand);
    }

    public function getListArchivesCommand() : string
    {
        return str_replace('{path}', $this->path, $this->listArchivesCommand);
    }

    public function getCleanImagesCommand() : string
    {
        return str_replace('{path}', $this->path, $this->cleanImagesCommand);
    }

    public function getCleanArchivesCommand() : string
    {
        return str_replace('{path}', $this->path, $this->cleanArchivesCommand);
    }
}