<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250117134908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Flag simpleConnection in Account';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account ADD simple_connection BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE account DROP simple_connection');
    }
}
