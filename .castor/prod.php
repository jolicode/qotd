<?php

namespace prod;

use Castor\Attribute\AsTask;
use Castor\Context;

use function Castor\capture;
use function Castor\context;
use function Castor\io;
use function Castor\variable;
use function docker\docker_compose;

/**
 * @param list<string> $subCommand
 * @param list<string> $profiles
 */
function docker_compose_prod(array $subCommand, array $profiles = [], ?Context $c = null): \Symfony\Component\Process\Process
{
    $c = ($c ?? context())
        ->withData(
            [
                'docker_compose_files' => [
                    'docker-compose.prod.yml',
                ],
            ],
            recursive: false,
        )
        ->withEnvironment([
            'APP_ENV' => 'prod',
            'PHP_VERSION' => variable('php_version'),
            // Docker compose resolves additional build contexts relative to the
            // current working directory, so we always pass an absolute path
            'APP_BUILD_CONTEXT' => variable('root_dir'),
        ])
    ;

    return docker_compose($subCommand, c: $c, profiles: $profiles);
}

#[AsTask(description: 'Builds the production images', namespace: 'prod')]
function build(bool $pull = false): void
{
    io()->title('Building production images');

    $command = ['build'];

    if ($pull) {
        $command[] = '--pull';
    }

    docker_compose_prod([...$command], profiles: ['*']);
}

#[AsTask(description: 'Builds and pushes the production images to the registry', namespace: 'prod', aliases: ['prod-push'])]
function push(): void
{
    io()->title('Building and pushing production images');

    $tag = getenv('TAG') ?: capture(['git', 'describe', '--always', '--dirty'], allowFailure: true) ?: 'latest';

    $c = context()->withEnvironment(['TAG' => $tag]);

    docker_compose_prod(['build', '--push'], profiles: ['*'], c: $c);
}

#[AsTask(description: 'Runs database migrations (one-shot container)', namespace: 'prod')]
function migrate(): void
{
    io()->title('Running database migrations');

    docker_compose_prod(['run', '--rm', 'migrate'], profiles: ['tools']);
}

#[AsTask(description: 'Starts the production stack', namespace: 'prod', aliases: ['prod-up'])]
function up(): void
{
    io()->title('Starting production stack');

    docker_compose_prod(['up', '--detach', '--wait'], profiles: ['default']);
}
