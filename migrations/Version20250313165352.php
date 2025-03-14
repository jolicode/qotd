<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250313165352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add message_rendered column to qotd table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd ADD message_rendered TEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE qotd DROP message_rendered');
    }
}
