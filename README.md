# QOTD Application

This application contains a slack bot that post a quote of the day to a slack
channel.

The bot looks every morning for a new quote of the day and post it to the
channel of your choice.

To elect the best QOTD, the bot will search for message with the most reactions.
You can customize the searched reaction in the `.env` file.

This application is a fun project to learn how to use the following technologies:

* Symfony
* Symfony UX and some third party UX components
* Advanced Doctrine and PostgreSQL usages (CTE, Window functions, Native
  Queries, Full Text Search, Pagination)

## Installation

### Configure the Slack Application

Create a new slack application with the manifest located in
`doc/slack-manifest.yaml`.

Dont forget to customize the file with your own values.

### Configure the Google application

You'll need a pair of Google API keys to connect via oAuth. You'll need to
configure the following URLs as a callback:
`https://local.qotd.internal.jolicode.com/connect/google/check`. You'll also
need to configure the emails domains allowed.

```
GOOGLE_CLIENT_ID=FIXME
GOOGLE_CLIENT_SECRET=FIXME
APP_ALLOWED_EMAIL_DOMAINS='["jolicode.com", "premieroctet.com"]'
```

But if you don't want to connect with google, you can use the
`YoloAuthenticator`, see `config/packages/security.yaml` file.

### Install the PHP application

A Docker environment is provided (NGINX, PHP, PostgreSQL, Traefik, a cron
service and a builder container with Composer). It requires these tools on your
machine:

* Docker
* Bash
* [Castor](https://github.com/jolicode/castor#installation)

Make the domain point to your Docker daemon (first time only):

    echo '127.0.0.1 local.qotd.internal.jolicode.com' | sudo tee -a /etc/hosts

Then start the stack:

    castor start
    # If you want to load some fixtures
    # castor fixtures
    # configure remaining parameters in .env.local
    # Enjoy

The application is now available at
[https://local.qotd.internal.jolicode.com](https://local.qotd.internal.jolicode.com).
SSL certificates are generated on the first start (with `mkcert` if it is
installed, so that they are trusted by your browser).

> [!NOTE]
> Override `APP_DEFAULT_URI` value in a `.env.local` file if you use
> another domain.

### Development

Run `castor` to list the available tasks. The main ones:

    castor builder             # opens a shell with PHP and Composer
    castor qa                  # coding standards, PHPStan and PHPUnit
    castor stop                # stops the stack

[Git worktrees](https://git-scm.com/docs/git-worktree) are supported: a
`castor start` inside a worktree runs a fully isolated stack (project name,
volumes, ports, see `castor docker:ports`).

## Production

The application ships as two self-contained Docker images, built from the
"production stages" of `infrastructure/docker/services/php/Dockerfile`:

* `php`: php-fpm listening on the unix socket `/var/run/php/php-fpm.sock`,
  with the code, the vendors and the compiled assets baked in, `APP_ENV=prod`,
  running as a non-root user. It is also the CLI image: the cron job
  (`bin/console qotd:run`) and the database migrations run with it;
* `nginx`: the official nginx image, the compiled `public/` directory and the
  site configuration, forwarding PHP requests to that socket.

Both use the php-fpm and nginx configuration of the dev container
(`services/php/php/` and `services/php/nginx/`); what production does
differently is in `services/php/php/mods-available/app-prod.ini`. Everything
else (secrets, database, Slack and Google credentials) is provided through
environment variables at runtime, and `public/uploads` (medias downloaded from
Slack) must be a volume shared by all the containers.

On every push to `main` (and on every git tag), the "Build and push production
images" workflow pushes both images to `ghcr.io/<repository>/php` and
`ghcr.io/<repository>/nginx`, tagged with the short commit sha, `latest` on
`main`, and the tag name when there is one.

### Testing the production images locally

The `prod` castor context runs the usual tasks on a dedicated compose stack
(`docker-compose.prod.yml`: postgres + the two images, no bind mount, no
router, no cron), independent from the development one:

    castor build -c prod       # builds the php and nginx images
    castor start -c prod       # starts the stack and runs the migrations
    # -> http://127.0.0.1:8000 (HTTP_PORT=18000 castor start -c prod to change the port)
    castor builder -c prod     # opens a shell in the php image
    castor destroy -c prod     # removes the containers and the volumes

It uses dummy secrets: to test a real Google login or a real Slack workspace,
put the corresponding variables in a `.env.prod.local` file at the root of the
repository (ignored by git, loaded by the php container). `APP_DEFAULT_URI`
and the Google callback URL must then match your local URL.

To push images from your machine instead of waiting for the CI (you need to be
logged in to the registry with `docker login ghcr.io`, and a buildx builder
able to export a registry cache, e.g. `docker buildx create --use`):

    castor docker:push -c prod --tag=my-test
    # or, to another registry (a fork for example)
    REGISTRY=ghcr.io/<org>/<repo> castor docker:push -c prod --tag=my-test

## Usage

In slack you have one commands

* `/qotd [a date]` to find the QOTD of the day or of the given date;

## Credits

Thanks JoliCode for sponsoring this project.
