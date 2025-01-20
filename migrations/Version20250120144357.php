<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250120144357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move data from Specialist.position to Specialty.name';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            WITH new_specialties AS (
                INSERT INTO specialty (name, account_id)
                SELECT DISTINCT position, account_id
                FROM specialist
                WHERE position IS NOT NULL
                RETURNING id, name, account_id
            )
            UPDATE specialist s
            SET specialty_id = ns.id
            FROM new_specialties ns
            WHERE s.position = ns.name
            AND s.account_id = ns.account_id
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('
            UPDATE specialist s
            SET position = sp.name,
                specialty_id = NULL
            FROM specialty sp
            WHERE s.specialty_id = sp.id
        ');
    }
}
