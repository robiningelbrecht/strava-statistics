<?php

declare(strict_types=1);

namespace App\Domain\Strava\EFtp;

final class EFtpNumberOfMonths
{
    private function __construct(private int $numberOfMonths)
    {
    }

    public function getNumberOfMonths(): int
    {
        return $this->numberOfMonths;
    }

    public static function from(?int $numberOfMonths): self
    {
        if (null === $numberOfMonths) {
            $numberOfMonths = 0;
        }

        return new self($numberOfMonths);
    }
}
