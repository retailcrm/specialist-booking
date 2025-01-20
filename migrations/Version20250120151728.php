<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250120151728 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop specialist.position';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist DROP "position"');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist ADD "position" VARCHAR(255) DEFAULT NULL');
    }
}
