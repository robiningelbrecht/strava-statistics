<?php

namespace App\Tests\Infrastructure\CQRS\Bus;

use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;

final readonly class RunOperationWithoutACommandCommandHandler implements CommandHandler
{
    public function handle(Command $command): void
    {
    }
}
