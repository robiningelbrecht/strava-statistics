<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations\Factory;

use App\Infrastructure\CQRS\Bus\CommandBus;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @codeCoverageIgnore
 */
readonly class ContainerAwareMigrationFactory implements MigrationFactory
{
    public function __construct(
        private MigrationFactory $migrationFactory,
        private ContainerInterface $container
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $migration = $this->migrationFactory->createVersion($migrationClassName);
        if ($migration instanceof ContainerAwareMigration) {
            $migration->setContainer($this->container);
        }

        return $migration;
    }
}
