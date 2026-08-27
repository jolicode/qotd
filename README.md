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
`http://localhost:8000/connect/google/check`. You'll also need to configure the
emails domains allowed.

```
GOOGLE_CLIENT_ID=FIXME
GOOGLE_CLIENT_SECRET=FIXME
APP_ALLOWED_EMAIL_DOMAINS='["jolicode.com", "premieroctet.com"]'
```

But if you don't want to connect with google, you can use the
`YoloAuthenticator`, see `config/packages/security.yaml` file.

### Install the PHP application

To make the application available locally at the address
[http://localhost:8000](http://localhost:8000), first create a
`docker-compose.override.yml` file with the following content:

```yaml
services:
    frontend:
        ports:
            - "8000:8080"
```

> [!NOTE]
> Override `APP_DEFAULT_URI` value in a `.env.local` file if you use
> another port or another domain.

Then run the following commands:

    docker-compose up -d
    docker-compose run --rm --user=app frontend composer install
    docker-compose run --rm --user=app frontend bin/console asset-map:compile
    docker-compose run --rm --user=app frontend bin/db
    # If you want to load some fixtures
    # docker-compose run --rm --user=app frontend bin/console doctrine:fixtures:load  --no-interaction
    # configure remaining parameters in .env.local
    # Enjoy

### Development

If you want to contribute, you can edit the `docker-compose.override.yml` file to add:

```yaml
services:
    frontend:
        volumes:
            - .:/app
        ports:
          - 8888:8080
```

## Production

The application ships as two self-contained Docker images, built from the
"production stages" of `infrastructure/docker/services/php/Dockerfile`:

* `php`: php-fpm listening on the unix socket `/var/run/php/php-fpm.sock`,
  with the code, the vendors and the compiled assets baked in, `APP_ENV=prod`.
  It is also the CLI image: the cron job (`bin/console qotd:run`) and the
  database migrations run with it;
* `nginx`: the official nginx image, the compiled `public/` directory and the
  site configuration, forwarding PHP requests to that socket.

Both use the php-fpm and nginx configuration of the dev container
(`services/php/php/` and `services/php/nginx/`); what production does
differently is in `services/php/php/mods-available/app-prod.ini`. Everything
else (secrets, database,
Slack and Google credentials) is provided through environment variables at
runtime, and `public/uploads` (medias downloaded from Slack) must be a volume
shared by all the containers.

On every push to `main` (and on every git tag), the "Build and push production
images" workflow pushes both images to `ghcr.io/<repository>/php` and
`ghcr.io/<repository>/nginx`, tagged with the short commit sha, `latest` on
`main`, and the tag name when there is one.

### Testing the production images locally

The `prod` castor context runs the usual tasks on a dedicated compose stack
(`docker-compose.prod.yml`: postgres + the two images, no bind mount, no
router), independent from the development one:

    castor build -c prod       # builds the php and nginx images
    castor start -c prod       # starts the stack and runs the migrations
    # -> http://127.0.0.1:8080 (HTTP_PORT=18080 castor start -c prod to change the port)
    castor destroy -c prod     # removes the containers and the volumes

It uses dummy secrets: to test a real Google login or a real Slack workspace,
put the corresponding variables in a `.env.prod.local` file at the root of the
repository (ignored by git, loaded by the php container). `APP_DEFAULT_URI`
and the Google callback URL must then match your local URL.

To push images from your machine instead of waiting for the CI (you need to be
logged in to the registry with `docker login ghcr.io`, and a buildx builder
able to export a registry cache, e.g. `docker buildx create --use`):

    REGISTRY=ghcr.io/<org>/<repo> castor docker:push -c prod --tag=my-test

## Usage

In slack you have one commands

* `/qotd [a date]` to find the QOTD of the day or of the given date;

## Credits

Thanks JoliCode for sponsoring this project.
