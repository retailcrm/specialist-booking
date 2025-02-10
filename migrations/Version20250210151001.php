<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250210151001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add store code to specialist';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist ADD store_code VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist DROP store_code');
    }
}
