<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Migrations\Factory;


use Symfony\Component\DependencyInjection\ContainerInterface;

interface ContainerAwareMigration
{
    public function setContainer(ContainerInterface $container): void;
}
