<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\CQRS\Bus\CommandBus;
use App\Infrastructure\Doctrine\Migrations\Factory\CommandBusAwareMigration;
use App\Infrastructure\Doctrine\Migrations\MigrateToVersion20250118164026\MigrateToVersion20250118164026;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250118164026 extends AbstractMigration implements CommandBusAwareMigration
{
    private ?CommandBus $commandBus = null;

    public function setCommandBus(CommandBus $commandBus): void
    {
        $this->commandBus = $commandBus;
    }

    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Migrate Activity table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__Activity AS SELECT activityId, startDateTime, data, gearId, weather, location, sportType FROM Activity');
        $this->addSql('DROP TABLE Activity');
        $this->addSql('CREATE TABLE Activity (activityId VARCHAR(255) NOT NULL, startDateTime DATETIME NOT NULL --(DC2Type:datetime_immutable)
        , data CLOB DEFAULT NULL --(DC2Type:json)
        , gearId VARCHAR(255) DEFAULT NULL, weather CLOB DEFAULT NULL --(DC2Type:json)
        , location CLOB DEFAULT NULL --(DC2Type:json)
        , sportType VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, distance INTEGER NOT NULL, elevation INTEGER NOT NULL, calories INTEGER DEFAULT NULL, averagePower INTEGER DEFAULT NULL, maxPower INTEGER DEFAULT NULL, averageSpeed DOUBLE PRECISION NOT NULL, maxSpeed DOUBLE PRECISION NOT NULL, averageHeartRate INTEGER DEFAULT NULL, maxHeartRate INTEGER DEFAULT NULL, averageCadence INTEGER DEFAULT NULL, movingTimeInSeconds INTEGER NOT NULL, kudoCount INTEGER NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, totalImageCount INTEGER NOT NULL, localImagePaths CLOB DEFAULT NULL, polyline CLOB DEFAULT NULL, gearName VARCHAR(255) DEFAULT NULL, startingCoordinateLatitude DOUBLE PRECISION DEFAULT NULL, startingCoordinateLongitude DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(activityId))');

        $this->addSql('INSERT INTO Activity (activityId, startDateTime, data, gearId, weather, location, sportType, name, description, distance, elevation, startingCoordinateLatitude, startingCoordinateLongitude, calories,
                        averagePower, maxPower, averageSpeed, maxSpeed, averageHeartRate, maxHeartRate,
                        averageCadence,movingTimeInSeconds, kudoCount, deviceName, totalImageCount, localImagePaths,
                        polyline, gearName) 
                        SELECT activityId, startDateTime, data, gearId, weather, location, sportType, "", "", 0, 0, NULL, NULL, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, "", 0, "", "", "" FROM __temp__Activity');
        $this->addSql('DROP TABLE __temp__Activity');
        $this->addSql('CREATE INDEX Activity_startDateTimeIndex ON Activity (startDateTime)');

        // Migrate Segment table.
        $this->addSql('CREATE TEMPORARY TABLE __temp__Segment AS SELECT segmentId, name, data FROM Segment');
        $this->addSql('DROP TABLE Segment');
        $this->addSql('CREATE TABLE Segment (segmentId VARCHAR(255) NOT NULL, name VARCHAR(255) DEFAULT NULL, sportType VARCHAR(255) NOT NULL, distance INTEGER NOT NULL, maxGradient DOUBLE PRECISION NOT NULL, isFavourite BOOLEAN NOT NULL, deviceName VARCHAR(255) DEFAULT NULL, PRIMARY KEY(segmentId))');
        $this->addSql('INSERT INTO Segment (segmentId, name, sportType, distance, maxGradient, isFavourite, deviceName) SELECT segmentId, name, JSON_EXTRACT(data, "$.sport_type"), CAST(JSON_EXTRACT(data, "$.distance") AS INTEGER), JSON_EXTRACT(data, "$.maximum_grade"), JSON_EXTRACT(data, "$.starred"), JSON_EXTRACT(data, "$.device_name") FROM __temp__Segment');
        $this->addSql('DROP TABLE __temp__Segment');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    public function postUp(Schema $schema): void
    {
        $this->commandBus->dispatch(new MigrateToVersion20250118164026());
    }
}
