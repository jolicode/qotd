<?php

namespace docker;

use Castor\Attribute\AsContext;
use Castor\Context;
use Symfony\Component\Process\Process;

use function Castor\cache;
use function Castor\capture;
use function Castor\log;

#[AsContext(default: true)]
function create_default_context(): Context
{
    $data = create_default_variables() + [
        'project_name' => 'app',
        'root_domain' => 'app.test',
        'extra_domains' => [],
        'php_version' => '8.4',
        'registry' => $_SERVER['DS_REGISTRY'] ?? null,
        'docker_compose_files' => [
            'docker-compose.yml',
            'docker-compose.dev.yml',
        ],
        'docker_compose_run_environment' => [],
        'docker_compose_build_profiles' => [
            'default',
            'worker',
            'builder',
        ],
        'macos' => false,
        'power_shell' => false,
        // check if posix_geteuid is available, if not, use getmyuid (windows)
        'user_id' => \function_exists('posix_geteuid') ? posix_geteuid() : getmyuid(),
        'root_dir' => \dirname(__DIR__),
    ];

    if (file_exists($data['root_dir'] . '/infrastructure/docker/docker-compose.override.yml')) {
        $data['docker_compose_files'][] = 'docker-compose.override.yml';
    }

    // We need an empty context to run command, since the default context has
    // not been set in castor, since we ARE creating it right now
    $emptyContext = new Context();

    $data['composer_cache_dir'] = cache('composer_cache_dir', function () use ($emptyContext): string {
        $composerCacheDir = capture(['composer', 'global', 'config', 'cache-dir', '-q'], onFailure: '', context: $emptyContext);
        // If PHP is broken, the output will not be a valid path but an error message
        if (!is_dir($composerCacheDir)) {
            $composerCacheDir = sys_get_temp_dir() . '/castor/composer';
            // If the directory does not exist, we create it. Otherwise, docker
            // will do, as root, and the user will not be able to write in it.
            if (!is_dir($composerCacheDir)) {
                mkdir($composerCacheDir, 0o777, true);
            }
        }

        return $composerCacheDir;
    });

    $platform = strtolower(php_uname('s'));
    if (str_contains($platform, 'darwin')) {
        $data['macos'] = true;
    } elseif (\in_array($platform, ['win32', 'win64', 'windows nt'])) {
        $data['power_shell'] = true;
    }

    if (false === $data['user_id'] || $data['user_id'] > 256000) {
        $data['user_id'] = 1000;
    }

    if (0 === $data['user_id']) {
        log('Running as root? Fallback to fake user id.', 'warning');
        $data['user_id'] = 1000;
    }

    return new Context(
        $data,
        pty: Process::isPtySupported(),
        environment: [
            'BUILDKIT_PROGRESS' => 'plain',
        ]
    );
}

#[AsContext(name: 'ci')]
function create_ci_context(): Context
{
    $c = create_test_context();

    return $c
        ->withData([
            'docker_compose_files' => [
                'docker-compose.yml',
            ],
        ], recursive: false)
        ->withEnvironment([
            'COMPOSE_ANSI' => 'never',
        ])
    ;
}

#[AsContext(name: 'test')]
function create_test_context(): Context
{
    $c = create_default_context();

    return $c
        ->withData([
            'docker_compose_run_environment' => [
                'APP_ENV' => 'test',
            ],
        ])
        ->withEnvironment([
        ])
    ;
}
