# AGENTS.md

Instructions for AI agents working on this project.

## Fundamental rule: never run PHP/Composer on the host machine

Everything goes through the `builder` container, via Castor:

```bash
castor builder -- bin/console cache:clear    # one-off command
castor builder -- bin/console make:migration # one-off command
```

Host prerequisites only: Docker, Bash, Castor.

## Essential commands

```bash
castor start                                 # build + install + up + migrate
castor stop                                  # stop the stack
castor logs [--service=service]              # logs (frontend, postgres, ...)
castor app:install                           # composer install + importmap:install + qa:install
castor app:db:migrate                        # Doctrine migrations (alias: castor migrate)
castor app:db:fixtures                       # fixtures (alias: castor fixtures)
castor app:cache-clear                       # clears var/cache (alias: castor cache-clear)
castor postgres                              # opens a psql shell (alias: castor pg)
```

Docker / workers:

```bash
castor docker:build [--service=service]
castor docker:up [--service=service]
castor start-workers                         # start workers (worker profile, currently unused — see Stack)
castor stop-workers
```

## Castor contexts

The context changes how tasks are executed (`APP_ENV`, compose files, etc.):

```bash
castor --context=test qa:phpunit             # APP_ENV=test, for tests
castor --context=ci ...                      # like test, tuned for CI
```

Always run tests and anything touching the database with `--context=test`.
Without option, the `default` context applies.

## Stack

- Symfony at the repo root (docroot = `public/`)
- PostgreSQL 16: user/pass/db = `qotd`/`qotd`/`qotd`
- nginx + php-fpm (service `frontend`), Traefik router, HTTPS on `<root_domain>` (see `castor.php`)
- `cron` service for scheduled tasks (e.g. posting the daily quote)
- Symfony AssetMapper for JS/CSS (`importmap.php`) — no Node/yarn build step
- A Messenger `worker` service is defined in `infrastructure/docker/docker-compose.yml` but
  currently commented out (no async transport in use yet)

## QA — before considering a task done

Tools run inside the builder.

```bash
castor qa                                    # everything: cs + phpstan + twig-cs + phpunit
castor qa:cs [--dry-run]                     # PHP-CS-Fixer (.php-cs-fixer.php)
castor qa:phpstan [-b]                       # PHPStan level 8 (phpstan.neon)
castor qa:twig-cs                            # Twig-CS-Fixer
castor qa:phpunit                            # PHPUnit
```

After any PHP/Twig code change: `castor qa:cs --dry-run`,
`castor qa:phpstan`, then `castor qa:phpunit`.

## Conventions

1. **Never invoke `docker compose` by hand**: use the `docker_compose()` /
   `docker_compose_run()` functions from `.castor/docker.php` to write new tasks.
2. **Never hardcode ports or project names**: use `variable('project_name')` etc.
3. New recurring task? Make it a Castor task (`castor.php` or `.castor/*.php`),
   not a shell script.
4. QA tool dependencies live in `tools/<tool>/composer.json`
   (not in the root `composer.json`).
