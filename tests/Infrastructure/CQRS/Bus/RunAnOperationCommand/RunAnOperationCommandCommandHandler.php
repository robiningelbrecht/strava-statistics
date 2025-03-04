<?php

namespace App\Tests\Infrastructure\CQRS\Bus\RunAnOperationCommand;

use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;

final readonly class RunAnOperationCommandCommandHandler implements CommandHandler
{
    public function handle(Command $command): void
    {
    }
}
