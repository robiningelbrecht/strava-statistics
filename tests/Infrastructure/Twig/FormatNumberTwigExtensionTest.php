<?php

namespace App\Tests\Infrastructure\Twig;

use App\Infrastructure\Twig\FormatNumberTwigExtension;
use PHPUnit\Framework\TestCase;

class FormatNumberTwigExtensionTest extends TestCase
{
    public function testDoFormat(): void
    {
        $extension = new FormatNumberTwigExtension();

        $this->assertEquals(
            '1 000.33',
            $extension->doFormat(1000.334, 2)
        );

        $this->assertEquals(
            0,
            $extension->doFormat(null, 0)
        );
    }
}
