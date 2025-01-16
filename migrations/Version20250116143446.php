<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250116143446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Activity and frozen flags for account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD active BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE account ADD frozen BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account DROP active');
        $this->addSql('ALTER TABLE account DROP frozen');
    }
}
