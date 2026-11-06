<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261106205250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the my_top table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE my_top (qotd_ids JSONB NOT NULL, user_identifier VARCHAR(255) NOT NULL, PRIMARY KEY (user_identifier))');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE my_top');
    }
}
