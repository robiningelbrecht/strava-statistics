<?php

declare(strict_types=1);

namespace App\Infrastructure\App;

final readonly class AppVersion implements \Stringable
{
    private const int MAJOR = 0;
    private const int MINOR = 4;
    private const int PATCH = 11;

    public function __toString(): string
    {
        return 'v'.implode('.', [
            self::MAJOR,
            self::MINOR,
            self::PATCH,
        ]);
    }
}
