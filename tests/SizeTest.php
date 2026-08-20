<?php

declare(strict_types=1);

namespace App\Tests;

use App\Enum\Size;
use PHPUnit\Framework\TestCase;

class SizeTest extends TestCase
{
    public function testDimensionsAreComputedWithoutLossyFloatToIntConversion() : void
    {
        set_error_handler(function ($type, $message) {
            throw new \ErrorException($message, 0, $type);
        }, E_DEPRECATED);

        try {
            foreach (Size::cases() as $size) {
                $this->assertIsInt($size->getWidth());
                $this->assertIsInt($size->getHeight());
                $this->assertIsInt($size->getTimeX());
                $this->assertIsInt($size->getTimeY());
                $this->assertGreaterThan(0, $size->getTimeSize());
            }
        } finally {
            restore_error_handler();
        }

        $this->assertSame(426, Size::THUMBNAIL->getWidth());
        $this->assertSame(640, Size::ORIGINAL->getWidth());
    }
}
