<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190612010401 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added Watchlist Entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE instrument_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE instrument_list_instrument (instrument_list_id INT NOT NULL, instrument_id INT NOT NULL, INDEX IDX_D11984DC459D692A (instrument_list_id), INDEX IDX_D11984DCCF11D9C (instrument_id), PRIMARY KEY(instrument_list_id, instrument_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE instrument_list_instrument ADD CONSTRAINT FK_D11984DC459D692A FOREIGN KEY (instrument_list_id) REFERENCES instrument_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE instrument_list_instrument ADD CONSTRAINT FK_D11984DCCF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE instrument_list_instrument DROP FOREIGN KEY FK_D11984DC459D692A');
        $this->addSql('DROP TABLE instrument_list');
        $this->addSql('DROP TABLE instrument_list_instrument');
    }
}
