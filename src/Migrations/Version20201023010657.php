<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201023010657 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Make Volume column on OHLCV\History as BigInt and Volum column of OHLCV\Quote as Int';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume BIGINT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE volume volume INT DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume INT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE volume volume DOUBLE PRECISION DEFAULT \'NULL\'');

    }
}
