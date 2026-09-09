<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260909101413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_preference (id SERIAL NOT NULL, account_id INT NOT NULL, crm_user_id INT NOT NULL, calendar_store_code VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FA0E76BF9B6B5FBA ON user_preference (account_id)');
        $this->addSql('CREATE UNIQUE INDEX user_preference_account_user_uniq ON user_preference (account_id, crm_user_id)');
        $this->addSql('ALTER TABLE user_preference ADD CONSTRAINT FK_FA0E76BF9B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_preference DROP CONSTRAINT FK_FA0E76BF9B6B5FBA');
        $this->addSql('DROP TABLE user_preference');
    }
}
