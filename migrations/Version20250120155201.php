<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250120155201 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set null to specialist.speciality on speciality delete';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist DROP CONSTRAINT FK_C2274AF49A353316');
        $this->addSql('ALTER TABLE specialist ADD CONSTRAINT FK_C2274AF49A353316 FOREIGN KEY (specialty_id) REFERENCES specialty (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE specialist DROP CONSTRAINT fk_c2274af49a353316');
        $this->addSql('ALTER TABLE specialist ADD CONSTRAINT fk_c2274af49a353316 FOREIGN KEY (specialty_id) REFERENCES specialty (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
