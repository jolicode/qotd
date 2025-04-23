<?php

namespace App\Doctrine\SchemaManager;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Sequence;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Schema\UniqueConstraint;
use Doctrine\DBAL\Schema\View;

/**
 * @extends AbstractSchemaManager<AbstractPlatform>
 */
class SchemaFilterSchemaManager extends AbstractSchemaManager
{
    private const array TO_DROP = [
        'qotd' => [
            'columns' => ['message_ts'],
            'indexes' => ['qotd_message_trigram', 'qotd_message_ts'],
        ],
    ];

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $decorated
     */
    public function __construct(
        private readonly AbstractSchemaManager $decorated,
    ) {
    }

    public function listDatabases(): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listSchemaNames(): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listSequences(): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listTableColumns(string $table): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listTableIndexes(string $table): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function tablesExist(array $names): bool
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function tableExists(string $tableName): bool
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listTableNames(): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listTables(): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function introspectTable(string $name): Table
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listViews(): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function listTableForeignKeys(string $table): array
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropDatabase(string $database): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropSchema(string $schemaName): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropTable(string $name): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropIndex(string $index, string $table): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropForeignKey(string $name, string $table): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropSequence(string $name): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropUniqueConstraint(string $name, string $tableName): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropView(string $name): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createSchemaObjects(Schema $schema): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createDatabase(string $database): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createTable(Table $table): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createSequence(Sequence $sequence): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createIndex(Index $index, string $table): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createForeignKey(ForeignKeyConstraint $foreignKey, string $table): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createUniqueConstraint(UniqueConstraint $uniqueConstraint, string $tableName): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createView(View $view): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function dropSchemaObjects(Schema $schema): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function alterSchema(SchemaDiff $schemaDiff): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function migrateSchema(Schema $newSchema): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function alterTable(TableDiff $tableDiff): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function renameTable(string $name, string $newName): void
    {
        $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function introspectSchema(): Schema
    {
        /** @var Schema $schema */
        $schema = $this->decorated->{__FUNCTION__}(...\func_get_args());

        $tables = [];
        foreach ($schema->getTables() as $table) {
            $config = self::TO_DROP[$table->getName()] ?? null;
            if (!$config) {
                $tables[] = $table;

                continue;
            }

            $columns = [];
            foreach ($table->getColumns() as $column) {
                if (!\in_array($column->getName(), $config['columns'], true)) {
                    $columns[] = $column;

                    continue;
                }
            }

            $indexes = [];

            foreach ($table->getIndexes() as $index) {
                if (!\in_array($index->getName(), $config['indexes'], true)) {
                    $indexes[] = $index;

                    continue;
                }
            }

            $tables[] = new Table(
                $table->getName(),
                $columns,
                $indexes,
                $table->getUniqueConstraints(),
                $table->getForeignKeys(),
                $table->getOptions(),
            );
        }

        return new Schema(
            $tables,
            $schema->getSequences(),
            // @phpstan-ignore property.notFound
            (fn () => $this->_schemaConfig)->bindTo($schema, Schema::class)(),
            $schema->getNamespaces(),
        );
    }

    public function createSchemaConfig(): SchemaConfig
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    public function createComparator(): Comparator
    {
        return $this->decorated->{__FUNCTION__}(...\func_get_args());
    }

    protected function selectTableNames(string $databaseName): Result
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function selectTableColumns(string $databaseName, ?string $tableName = null): Result
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function selectIndexColumns(string $databaseName, ?string $tableName = null): Result
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function selectForeignKeyColumns(string $databaseName, ?string $tableName = null): Result
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function fetchTableOptionsByTable(string $databaseName, ?string $tableName = null): array
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function _getPortableTableColumnDefinition(array $tableColumn): Column
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function _getPortableTableDefinition(array $table): string
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function _getPortableViewDefinition(array $view): View
    {
        throw new \LogicException('Not implemented, not needed!');
    }

    protected function _getPortableTableForeignKeyDefinition(array $tableForeignKey): ForeignKeyConstraint
    {
        throw new \LogicException('Not implemented, not needed!');
    }
}
