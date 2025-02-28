<?php

namespace App\Tests\Infrastructure\CQRS\Bus\RunAnOperation;

use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;

final readonly class RunAnOperationCommandHandler implements CommandHandler
{
    public function handle(Command $command): void
    {
        assert($command instanceof RunAnOperation);
        throw new \RuntimeException('This is a test command and it is called');
        // Waw, such empty.
    }
}
