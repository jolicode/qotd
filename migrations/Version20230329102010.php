<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230329102010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Get ride of legacy code';
    }

    public function up(Schema $schema): void
    {
        $rows = $this
            ->connection
            ->executeQuery('SELECT id, voter_ids FROM qotd WHERE voter_ids IS NOT NULL')
            ->fetchAllAssociative()
        ;

        foreach ($rows as $row) {
            $voterIds = json_decode($row['voter_ids'], true);

            $newVoterIds = array_filter($voterIds, function (string $direction): bool {
                return 'unknown' !== $direction;
            });

            if ($newVoterIds === $voterIds) {
                continue;
            }

            $this->addSql('UPDATE qotd SET voter_ids = :voterIds WHERE id = :id', [
                'voterIds' => json_encode($newVoterIds),
                'id' => $row['id'],
            ]);
        }

    }

    public function down(Schema $schema): void
    {
    }
}
