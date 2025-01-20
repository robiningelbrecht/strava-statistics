<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations\Factory;

use App\Infrastructure\CQRS\Bus\CommandBus;

interface CommandBusAwareMigration
{
    public function setCommandBus(CommandBus $commandBus): void;
}
