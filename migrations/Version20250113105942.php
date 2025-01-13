<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250113105942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Account entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE account (id SERIAL NOT NULL, url VARCHAR(255) NOT NULL, api_key VARCHAR(255) NOT NULL, client_id VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7D3656A419EB6921 ON account (client_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE account');
    }
}
