<?php

namespace App\DTO;

readonly class Archive
{
    private string $name;
    private string $filename;
    private int $size;

    public function __construct(string $filename)
    {
        $this->name = basename($filename);
        $this->filename = $filename;
        $this->size = filesize($filename);
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    public function getSize() : int
    {
        return $this->size;
    }
}