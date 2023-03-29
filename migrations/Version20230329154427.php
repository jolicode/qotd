<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230329154427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add images to qotd';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ADD images JSONB NOT NULL DEFAULT \'[]\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd DROP images');
    }
}
