<?php

declare(strict_types=1);

namespace App\Domain\Strava\Ftp;

use App\Infrastructure\ValueObject\Measurement\Mass\Kilogram;
use App\Infrastructure\ValueObject\Time\SerializableDateTime;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Ftp
{
    private ?Kilogram $athleteWeightInKg = null;

    private function __construct(
        #[ORM\Id, ORM\Column(type: 'date_immutable')]
        private readonly SerializableDateTime $setOn,
        #[ORM\Column(type: 'integer')]
        private readonly FtpValue $ftp,
    ) {
    }

    public static function fromState(
        SerializableDateTime $setOn,
        FtpValue $ftp,
    ): self {
        return new self(
            setOn: $setOn,
            ftp: $ftp
        );
    }

    public function getSetOn(): SerializableDateTime
    {
        return $this->setOn;
    }

    public function getFtp(): FtpValue
    {
        return $this->ftp;
    }

    public function getRelativeFtp(): ?float
    {
        if (!$this->athleteWeightInKg) {
            return null;
        }

        return round($this->getFtp()->getValue() / $this->athleteWeightInKg->toFloat(), 1);
    }

    public function enrichWithAthleteWeight(Kilogram $athleteWeight): void
    {
        $this->athleteWeightInKg = $athleteWeight;
    }
}
