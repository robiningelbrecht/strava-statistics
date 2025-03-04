<?php

namespace App\Tests\Infrastructure\CQRS\Bus;

use App\Infrastructure\CQRS\Command;
use App\Infrastructure\CQRS\CommandHandler;

class RunOperationWithInvalidNameHandler implements CommandHandler
{
    public function handle(Command $command): void
    {
    }
}
