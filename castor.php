<?php

use Castor\Attribute\AsTask;

use function Castor\context;
use function Castor\guard_min_version;
use function Castor\import;
use function Castor\io;
use function Castor\load_dot_env;
use function Castor\notify;
use function Castor\variable;
use function docker\about;
use function docker\build;
use function docker\docker_compose_run;
use function docker\generate_certificates;
use function docker\up;

guard_min_version('0.18.0');

import(__DIR__ . '/.castor');

/**
 * @return array{project_name: string, root_domain: string, php_version: string}
 */
function create_default_variables(): array
{
    return [
        'project_name' => 'qotd',
        'root_domain' => 'local.qotd.internal.jolicode.com',
        'registry' => 'ghcr.io/jolicode/qotd',
        'php_version' => '8.3',
    ];
}

#[AsTask(description: 'Builds and starts the infrastructure, then install the application (composer, yarn, ...)')]
function start(): void
{
    io()->title('Starting the stack');

    build();
    install();
    up();
    migrate();

    notify('The stack is now up and running.');
    io()->success('The stack is now up and running.');

    about();
}

#[AsTask(description: 'Installs the application (composer, yarn, ...)', namespace: 'app', aliases: ['install'])]
function install(): void
{
    io()->title('Installing the application');

    io()->section('Installing PHP dependencies');
    docker_compose_run('composer install -n --prefer-dist --optimize-autoloader');

    io()->section('Installing importmap');
    docker_compose_run('bin/console importmap:install');

    if ('prod' === (load_dot_env()['APP_ENV'] ?? 'dev') || 'ci' === context()->name) {
        docker_compose_run('bin/console asset-map:compile');
    }

    qa\install();
}

#[AsTask(description: 'Clear the application cache', namespace: 'app', aliases: ['cache-clear'])]
function cache_clear(): void
{
    io()->title('Clearing the application cache');

    docker_compose_run('rm -rf var/cache/');
    // On the very first run, the vendor does not exist yet
    if (is_dir(variable('root_dir') . '/vendor')) {
        docker_compose_run('bin/console cache:warmup');
    }
}

#[AsTask(description: 'Migrates database schema', namespace: 'app:db', aliases: ['migrate'])]
function migrate(): void
{
    io()->title('Migrating the database schema');

    docker_compose_run('bin/console doctrine:database:create --if-not-exists');
    docker_compose_run('bin/console doctrine:migration:migrate -n --allow-no-migration --all-or-nothing');
}

#[AsTask(description: 'Loads fixtures', namespace: 'app:db', aliases: ['fixtures'])]
function fixtures(?string $env = null): void
{
    io()->title('Loads fixtures');

    $envArgument = $env ? " --env={$env}" : '';

    docker_compose_run('bin/console doctrine:database:create --if-not-exists' . $envArgument);
    docker_compose_run('bin/console doctrine:migration:migrate -n --allow-no-migration --all-or-nothing' . $envArgument);
    docker_compose_run('bin/console doctrine:fixture:load -n' . $envArgument);
}
