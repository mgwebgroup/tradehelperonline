<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200603163624 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE instrument_list_instrument DROP FOREIGN KEY FK_D11984DC459D692A');
        $this->addSql('CREATE TABLE watchlist (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist_instrument (watchlist_id INT NOT NULL, instrument_id INT NOT NULL, INDEX IDX_4913FA883DD0D94 (watchlist_id), INDEX IDX_4913FA8CF11D9C (instrument_id), PRIMARY KEY(watchlist_id, instrument_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE watchlist_formula (watchlist_id INT NOT NULL, formula_id INT NOT NULL, INDEX IDX_F227007E83DD0D94 (watchlist_id), INDEX IDX_F227007EA50A6386 (formula_id), PRIMARY KEY(watchlist_id, formula_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE formula (id INT AUTO_INCREMENT NOT NULL, formula VARCHAR(2048) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE watchlist_instrument ADD CONSTRAINT FK_4913FA883DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_instrument ADD CONSTRAINT FK_4913FA8CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_formula ADD CONSTRAINT FK_F227007E83DD0D94 FOREIGN KEY (watchlist_id) REFERENCES watchlist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE watchlist_formula ADD CONSTRAINT FK_F227007EA50A6386 FOREIGN KEY (formula_id) REFERENCES formula (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE instrument_list');
        $this->addSql('DROP TABLE instrument_list_instrument');
        $this->addSql('ALTER TABLE instruments CHANGE name name VARCHAR(80) DEFAULT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE exchange exchange VARCHAR(80) DEFAULT NULL');
        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume INT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE open open DOUBLE PRECISION DEFAULT NULL, CHANGE high high DOUBLE PRECISION DEFAULT NULL, CHANGE low low DOUBLE PRECISION DEFAULT NULL, CHANGE close close DOUBLE PRECISION DEFAULT NULL, CHANGE volume volume DOUBLE PRECISION DEFAULT NULL, CHANGE timeinterval timeinterval VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\', CHANGE provider provider VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE watchlist_instrument DROP FOREIGN KEY FK_4913FA883DD0D94');
        $this->addSql('ALTER TABLE watchlist_formula DROP FOREIGN KEY FK_F227007E83DD0D94');
        $this->addSql('ALTER TABLE watchlist_formula DROP FOREIGN KEY FK_F227007EA50A6386');
        $this->addSql('CREATE TABLE instrument_list (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT \'NULL\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE instrument_list_instrument (instrument_list_id INT NOT NULL, instrument_id INT NOT NULL, INDEX IDX_D11984DCCF11D9C (instrument_id), INDEX IDX_D11984DC459D692A (instrument_list_id), PRIMARY KEY(instrument_list_id, instrument_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE instrument_list_instrument ADD CONSTRAINT FK_D11984DC459D692A FOREIGN KEY (instrument_list_id) REFERENCES instrument_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE instrument_list_instrument ADD CONSTRAINT FK_D11984DCCF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE watchlist');
        $this->addSql('DROP TABLE watchlist_instrument');
        $this->addSql('DROP TABLE watchlist_formula');
        $this->addSql('DROP TABLE formula');
        $this->addSql('ALTER TABLE instruments CHANGE name name VARCHAR(80) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE description description VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci, CHANGE exchange exchange VARCHAR(80) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ohlcvhistory CHANGE volume volume INT DEFAULT NULL, CHANGE provider provider VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE open open DOUBLE PRECISION DEFAULT \'NULL\', CHANGE high high DOUBLE PRECISION DEFAULT \'NULL\', CHANGE low low DOUBLE PRECISION DEFAULT \'NULL\', CHANGE close close DOUBLE PRECISION DEFAULT \'NULL\', CHANGE volume volume DOUBLE PRECISION DEFAULT \'NULL\', CHANGE timeinterval timeinterval VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci COMMENT \'(DC2Type:dateinterval)\', CHANGE provider provider VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8mb4_unicode_ci');
    }
}
