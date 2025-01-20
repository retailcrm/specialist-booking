<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250120144126 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Specialty entity';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE specialty (id SERIAL NOT NULL, account_id INT NOT NULL, name VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E066A6EC9B6B5FBA ON specialty (account_id)');
        $this->addSql('CREATE INDEX specialty_account_id_name_idx ON specialty (account_id, name)');
        $this->addSql('COMMENT ON COLUMN specialty.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE specialty ADD CONSTRAINT FK_E066A6EC9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE specialist ADD specialty_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE specialist ADD CONSTRAINT FK_C2274AF49A353316 FOREIGN KEY (specialty_id) REFERENCES specialty (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_C2274AF49A353316 ON specialist (specialty_id)');
        $this->addSql('CREATE INDEX specialist_account_id_ordering_idx ON specialist (account_id, ordering)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist DROP CONSTRAINT FK_C2274AF49A353316');
        $this->addSql('ALTER TABLE specialty DROP CONSTRAINT FK_E066A6EC9B6B5FBA');
        $this->addSql('DROP TABLE specialty');
        $this->addSql('DROP INDEX IDX_C2274AF49A353316');
        $this->addSql('DROP INDEX specialist_account_id_ordering_idx');
        $this->addSql('ALTER TABLE specialist DROP specialty_id');
    }
}
