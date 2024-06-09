<?php

use Castor\Attribute\AsTask;

use function Castor\guard_min_version;
use function Castor\import;
use function Castor\io;
use function Castor\notify;
use function docker\about;
use function docker\build;
use function docker\docker_compose_run;
use function docker\generate_certificates;
use function docker\up;

guard_min_version('0.15.0');

import(__DIR__ . '/.castor');

/**
 * @return array<string, mixed>
 */
function create_default_variables(): array
{
    return [
        'project_name' => 'qotd',
        'root_domain' => 'local.qotd.offithings.jolicode.com',
        'registry' => 'ghcr.io/jolicode/qotd',
    ];
}

#[AsTask(description: 'Builds and starts the infrastructure, then install the application (composer, yarn, ...)')]
function start(): void
{
    io()->title('Starting the stack');

    generate_certificates(force: false);
    build();
    up();
    cache_clear();
    install();
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

    migrate();
    fixtures();

    qa\install();
}

#[AsTask(description: 'Clear the application cache', namespace: 'app', aliases: ['cache-clear'])]
function cache_clear(): void
{
    io()->title('Clearing the application cache');

    docker_compose_run('rm -rf var/cache/ && bin/console cache:warmup');
}

#[AsTask(description: 'Migrates database schema', namespace: 'app:db', aliases: ['migrate'])]
function migrate(): void
{
    io()->title('Migrating the database schema');

    docker_compose_run('bin/console doctrine:database:create --if-not-exists');
    docker_compose_run('bin/console doctrine:migration:migrate -n --allow-no-migration');
}

#[AsTask(description: 'Injects fixtures in the database', namespace: 'app:db', aliases: ['fixtures'])]
function fixtures(): void
{
    io()->title('Injects fixtures in the database');

    docker_compose_run('bin/console doctrine:fixture:load -n');
}
