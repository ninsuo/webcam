<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FileExtension extends AbstractExtension
{
    public function getFilters() : array
    {
        return [
            new TwigFilter('formatBytes', [$this, 'formatBytes']),
        ];
    }

    public function formatBytes($bytes, $precision = 2) : string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}