<?php

namespace App\DTO;

final readonly class Webcam
{
    const LAST_IMAGE_COMMAND = 'find {path} -type f -name "*.jpg" | sort | tac | head -n 1';
    const LIST_IMAGES_COMMAND = 'find {path} -type f -name "*.jpg" | sort';
    const LIST_ARCHIVES_COMMAND = 'find {path} -type f -name "*.tar.gz"';
    const ARCHIVE_IMAGES_COMMAND = 'find {path} -type f -name "*.jpg" -mtime +1 -exec tar -rf {path}/archive-$(date +%Y%m%d).tar {} \; && cat {path}/archive-$(date +%Y%m%d).tar | gzip -9 > {path}/archive-$(date +%Y%m%d).tar.gz && rm -f {path}/archive-$(date +%Y%m%d).tar';
    const CLEAN_IMAGES_COMMAND = 'find {path} -type f -name "*.jpg" -mtime +1 -exec rm -f {} \;';
    const CLEAN_ARCHIVES_COMMAND = 'find {path} -type f -name "*.tar.gz" -mtime +7 -exec rm -f {} \;';
    const CLEAN_EMPTY_DIRECTORIES_COMMAND = 'find {path}/ -type d -empty -delete';

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

    public function getArchiveImagesCommand() : string
    {
        return str_replace('{path}', $this->path, self::ARCHIVE_IMAGES_COMMAND);
    }

    public function getCleanImagesCommand() : string
    {
        return str_replace('{path}', $this->path, self::CLEAN_IMAGES_COMMAND);
    }

    public function getCleanArchivesCommand() : string
    {
        return str_replace('{path}', $this->path, self::CLEAN_ARCHIVES_COMMAND);
    }

    public function getCleanEmptyDirectoriesCommand() : string
    {
        return str_replace('{path}', $this->path, self::CLEAN_EMPTY_DIRECTORIES_COMMAND);
    }
}