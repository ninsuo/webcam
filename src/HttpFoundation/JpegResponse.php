<?php

namespace App\HttpFoundation;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\EventListener\AbstractSessionListener;

class JpegResponse extends Response
{
    /**
     * Live images must never be cached (the same URL always serves the
     * latest frame); archived event frames never change, so the browser
     * may cache them instead of triggering a new render on every visit.
     */
    public function __construct(string $bytes, bool $cacheable = false)
    {
        if ($cacheable) {
            parent::__construct($bytes, headers: [
                'Content-Type' => 'image/jpeg',
                // private: these images sit behind authentication
                'Cache-Control' => 'private, max-age=86400',
                // without this, the session listener resets Cache-Control
                // to max-age=0 on every authenticated response
                AbstractSessionListener::NO_AUTO_CACHE_CONTROL_HEADER => 'true',
            ]);

            return;
        }

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
