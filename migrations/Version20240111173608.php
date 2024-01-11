<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240111173608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add videos to QOTD';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ADD videos JSONB');
        $this->addSql('UPDATE qotd SET videos = \'[]\'');
        $this->addSql('ALTER TABLE qotd ALTER videos SET NOT NULL');
        $this->addSql('ALTER TABLE qotd ALTER images DROP DEFAULT');
        $this->addSql('ALTER TABLE qotd ALTER images SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
    }
}
