<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250108110543 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE Segment SET data = JSON_SET(Segment.data, "$.device_name",  (SELECT JSON_EXTRACT(Activity.data, "$.device_name") 
FROM Segment child
INNER JOIN SegmentEffort ON child.segmentId = SegmentEffort.segmentId
INNER JOIN Activity ON SegmentEffort.activityId = Activity.activityId
WHERE child.segmentId = Segment.segmentId))');

        $this->addSql('UPDATE Segment SET data = JSON_SET(Segment.data, "$.sport_type",  (SELECT Activity.sportType
FROM Segment child
INNER JOIN SegmentEffort ON child.segmentId = SegmentEffort.segmentId
INNER JOIN Activity ON SegmentEffort.activityId = Activity.activityId
WHERE child.segmentId = Segment.segmentId))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
