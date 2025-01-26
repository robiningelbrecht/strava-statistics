<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250125102412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ActivitySplit (activityId VARCHAR(255) NOT NULL, unitSystem VARCHAR(255) NOT NULL, splitNumber INTEGER NOT NULL, distance INTEGER NOT NULL, elapsedTimeInSeconds INTEGER NOT NULL, movingTimeInSeconds INTEGER NOT NULL, elevationDifference INTEGER NOT NULL, averageSpeed DOUBLE PRECISION NOT NULL, minAverageSpeed DOUBLE PRECISION NOT NULL, maxAverageSpeed INTEGER NOT NULL, paceZone INTEGER NOT NULL, PRIMARY KEY(activityId, unitSystem, splitNumber))');
        $this->addSql('CREATE INDEX ActivitySplit_activityIdUnitSystemIndex ON ActivitySplit (activityId, unitSystem)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE ActivitySplit');
    }
}
