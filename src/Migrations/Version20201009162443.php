<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201009162443 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create migrations for Instrument, OHLCV/History and OHLCV/Quote Entities';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE instruments (id INT AUTO_INCREMENT NOT NULL, symbol VARCHAR(21) NOT NULL, name VARCHAR(80) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, exchange VARCHAR(80) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ohlcvhistory (id INT AUTO_INCREMENT NOT NULL, instrument_id INT NOT NULL, open DOUBLE PRECISION NOT NULL, high DOUBLE PRECISION NOT NULL, low DOUBLE PRECISION NOT NULL, close DOUBLE PRECISION NOT NULL, volume INT DEFAULT NULL, timeinterval VARCHAR(255) NOT NULL COMMENT \'(DC2Type:dateinterval)\', timestamp DATETIME NOT NULL, provider VARCHAR(255) DEFAULT NULL, INDEX IDX_B5D82CD6CF11D9C (instrument_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ohlcvquote (id INT AUTO_INCREMENT NOT NULL, instrument_id INT NOT NULL, open DOUBLE PRECISION DEFAULT NULL, high DOUBLE PRECISION DEFAULT NULL, low DOUBLE PRECISION DEFAULT NULL, close DOUBLE PRECISION DEFAULT NULL, volume DOUBLE PRECISION DEFAULT NULL, timeinterval VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\', timestamp DATETIME NOT NULL, provider VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_69EF258ECF11D9C (instrument_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ohlcvhistory ADD CONSTRAINT FK_B5D82CD6CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258ECF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory DROP FOREIGN KEY FK_B5D82CD6CF11D9C');
        $this->addSql('ALTER TABLE ohlcvquote DROP FOREIGN KEY FK_69EF258ECF11D9C');
        $this->addSql('DROP TABLE instruments');
        $this->addSql('DROP TABLE ohlcvhistory');
        $this->addSql('DROP TABLE ohlcvquote');
    }
}
