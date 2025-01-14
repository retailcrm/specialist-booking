<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250114152226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Specialist entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE specialist (id SERIAL NOT NULL, account_id INT NOT NULL, name VARCHAR(255) NOT NULL, position VARCHAR(255) DEFAULT NULL, ordering INT NOT NULL, photo VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C2274AF49B6B5FBA ON specialist (account_id)');
        $this->addSql('ALTER TABLE specialist ADD CONSTRAINT FK_C2274AF49B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist DROP CONSTRAINT FK_C2274AF49B6B5FBA');
        $this->addSql('DROP TABLE specialist');
    }
}
