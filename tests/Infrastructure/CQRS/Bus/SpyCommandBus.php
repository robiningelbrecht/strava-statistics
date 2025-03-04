<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\CQRS\Bus;

use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\CQRS\Command;

class SpyCommandBus implements CommandBus
{
    /** @var \App\Infrastructure\CQRS\DomainCommand[] */
    private array $commandsToDispatch = [];

    public function dispatch(Command $command): void
    {
        $this->commandsToDispatch[] = $command;
    }

    /**
     * @return \App\Infrastructure\CQRS\DomainCommand[]
     */
    public function getDispatchedCommands(): array
    {
        $commandsToDispatch = $this->commandsToDispatch;
        $this->commandsToDispatch = [];

        return $commandsToDispatch;
    }

    public function getAvailableCommands(): array
    {
        return [];
    }
}
