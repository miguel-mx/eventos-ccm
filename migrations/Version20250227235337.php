<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227235337 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event_seminar (id INT AUTO_INCREMENT NOT NULL, seminar_id INT NOT NULL, location VARCHAR(255) NOT NULL, start DATETIME NOT NULL, end DATETIME NOT NULL, speaker VARCHAR(255) NOT NULL, institution VARCHAR(255) NOT NULL, title VARCHAR(255) NOT NULL, abstract LONGTEXT DEFAULT NULL, organizers VARCHAR(255) NOT NULL, slug VARCHAR(255) NOT NULL, url VARCHAR(255) DEFAULT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_3AAA8527735A6AB8 (seminar_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE event_seminar ADD CONSTRAINT FK_3AAA8527735A6AB8 FOREIGN KEY (seminar_id) REFERENCES seminario (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_seminar DROP FOREIGN KEY FK_3AAA8527735A6AB8');
        $this->addSql('DROP TABLE event_seminar');
    }
}
