<?php

declare(strict_types=1);

namespace App\Domain\Strava\Athlete\Weight\ImportAthleteWeight;

use App\Domain\Strava\Athlete\Weight\AthleteWeightRepository;
use App\Infrastructure\CQRS\Bus\Command;
use App\Infrastructure\CQRS\Bus\CommandHandler;

final readonly class ImportAthleteWeightCommandHandler implements CommandHandler
{
    public function __construct(
        private AthleteWeightsFromEnvFile $athleteWeightsFromEnvFile,
        private AthleteWeightRepository $athleteWeightRepository,
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
            $command->getOutput()->writeln(sprintf(
                '  => Imported weight set on %s (%s kg)...',
                $weight->getOn()->format('d-m-Y'),
                $weight->getWeightInKg()
            ));
        }
    }
}