<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230329094117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix qotd.voter_ids column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ALTER voter_ids DROP NOT NULL');
        $this->addSql('ALTER TABLE qotd ALTER voter_ids TYPE jsonb USING voter_ids::jsonb');
        $this->addSql('UPDATE qotd SET voter_ids = NULL WHERE voter_ids::text = \'[]\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ALTER voter_ids SET NOT NULL');
        $this->addSql('ALTER TABLE qotd ALTER voter_ids TYPE json');
    }
}
