<?php

namespace App\Doctrine\SchemaManager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\SchemaManagerFactory as DoctrineSchemaManagerFactory;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

#[AsDecorator('doctrine.dbal.default_schema_manager_factory')]
class SchemaManagerFactory implements DoctrineSchemaManagerFactory
{
    public function __construct(
        private DoctrineSchemaManagerFactory $decorated,
    ) {
    }

    /**
     * @return AbstractSchemaManager<AbstractPlatform>
     */
    public function createSchemaManager(Connection $connection): AbstractSchemaManager
    {
        return new SchemaFilterSchemaManager(
            $this->decorated->createSchemaManager($connection),
        );
    }
}
