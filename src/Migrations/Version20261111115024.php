<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261111115024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_6D28840DB03A8386 ON payment (created_by_id)');
        $this->addSql('ALTER TABLE reservation ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_42C84955B03A8386 ON reservation (created_by_id)');

        // Populate existing user rows safely before enforcing NOT NULL
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL, ADD is_active TINYINT DEFAULT NULL');
        $this->addSql('UPDATE user SET created_at = NOW(), is_active = 1 WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL, MODIFY is_active TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840DB03A8386');
        $this->addSql('DROP INDEX IDX_6D28840DB03A8386 ON payment');
        $this->addSql('ALTER TABLE payment DROP created_by_id');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955B03A8386');
        $this->addSql('DROP INDEX IDX_42C84955B03A8386 ON reservation');
        $this->addSql('ALTER TABLE reservation DROP created_by_id');
        $this->addSql('ALTER TABLE user DROP created_at, DROP is_active');
    }
}