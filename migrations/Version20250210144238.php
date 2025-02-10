<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250210144238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add settings for choose store and city';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD setting_choose_store BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE account ADD setting_choose_city BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account DROP setting_choose_store');
        $this->addSql('ALTER TABLE account DROP setting_choose_city');
    }
}
