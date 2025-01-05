<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

final readonly class Monolog implements \Stringable
{
    /** @var string[] */
    private array $logs;

    public function __construct(
        string ...$logs,
    ) {
        $this->logs = $logs;
    }

    public function __toString(): string
    {
        return implode(' - ', $this->logs);
    }
}
