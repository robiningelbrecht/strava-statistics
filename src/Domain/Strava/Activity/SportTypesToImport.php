<?php

declare(strict_types=1);

namespace App\Domain\Strava\Activity;

use App\Domain\Strava\SportType;
use App\Infrastructure\ValueObject\Collection;

final class SportTypesToImport extends Collection
{
    public function getItemClassName(): string
    {
        return SportType::class;
    }

    /**
     * @param string[] $types
     */
    public static function from(array $types): self
    {
        if (0 === count($types)) {
            throw new \InvalidArgumentException('You must import at least one type');
        }

        return self::fromArray(array_map(
            fn ($type) => SportType::from($type),
            $types
        ));
    }
}
