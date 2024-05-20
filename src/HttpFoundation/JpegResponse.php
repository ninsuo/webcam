<?php

namespace App\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;

class JpegResponse extends Response
{
    public function __construct(string $bytes)
    {
        parent::__construct($bytes, headers: [
            'Content-Type' => 'image/jpeg',
            'Pragma-Directive' => 'no-cache',
            'Cache-Directive' => 'no-cache',
            'Cache-Control' => 'no-cache',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}
