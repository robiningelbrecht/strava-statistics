<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;
use App\Infrastructure\ValueObject\Measurement\UnitSystem;

final readonly class ImportAthleteWeightCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteWeightsFromEnvFile $athleteWeightsFromEnvFile,
        private AthleteWeightRepository $athleteWeightRepository,
        private UnitSystem $unitSystem,
    ) {
    }

    public function handle(Command $command): void
    {
        assert($command instanceof ImportAthleteWeight);
        $command->getOutput()->writeln('Importing weights...');

        $this->athleteWeightRepository->removeAll();

        $athleteWeights = $this->athleteWeightsFromEnvFile->getAll();
        if ($athleteWeights->isEmpty()) {
            $command->getOutput()->writeln('No athlete weights found. Will not be able to calculate relative power outputs');

            return;
        }

        /** @var \App\Domain\Strava\Athlete\Weight\AthleteWeight $weight */
        foreach ($athleteWeights as $weight) {
            $this->athleteWeightRepository->save($weight);

            $convertedWeight = $weight->getWeightInKg()->toUnitSystem($this->unitSystem);
            $command->getOutput()->writeln(sprintf(
                '  => Imported weight set on %s (%s %s)...',
                $weight->getOn()->format('d-m-Y'),
                $convertedWeight->toFloat(),
                $convertedWeight->getSymbol()
            ));
        }
    }
}
