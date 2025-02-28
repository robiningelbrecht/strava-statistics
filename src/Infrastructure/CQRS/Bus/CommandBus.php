<?php

declare(strict_types=1);

namespace App\Infrastructure\CQRS\Bus;

use App\Infrastructure\CQRS\Command;

interface CommandBus
{
    /**
     * @return string[]
     */
    public function getAvailableCommands(): array;

    public function dispatch(Command $command): void;
}
