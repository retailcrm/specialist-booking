<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250116084550 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Account settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account RENAME COLUMN locale TO setting_locale');
        $this->addSql('ALTER TABLE account ADD setting_work_times JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE account ADD setting_non_working_days JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE account ADD setting_slot_duration INT DEFAULT 60 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account RENAME COLUMN setting_locale TO locale');
        $this->addSql('ALTER TABLE account DROP setting_work_times');
        $this->addSql('ALTER TABLE account DROP setting_non_working_days');
        $this->addSql('ALTER TABLE account DROP setting_slot_duration');
    }
}
