<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260901125812 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE account ADD setting_timezone VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE specialist ADD work_times JSONB DEFAULT NULL');
        $this->addSql('ALTER TABLE specialist ADD non_working_days JSONB DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE specialist DROP work_times');
        $this->addSql('ALTER TABLE specialist DROP non_working_days');
        $this->addSql('ALTER TABLE account DROP setting_timezone');
    }
}
