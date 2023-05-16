# QOTD Application

This application contains a slack bot that post a quote of the day to a slack
channel.

The bot that check every morning for a new quote of the day and post it to the
channel of your choice.

To elect the best QOTD, the bot will search for message with the most reactions.
You can customize the searched reaction in the `.env` file.

This application is fun project to learn how to use the following technologies:

* Symfony
* Symfony UX and some third party UX components
* Advanced Doctrine and PostgreSQL usages (CTE, Window functions, Native
  Queries, Full Text Search, Pagination)

## Installation

### Configure the Slack Application

Create a new slack application with the manifest located in
`doc/slack-manifest.yaml`.

Dont forget to customize the file with your own values.

### Install the PHP application

To make the application available locally at the address
[http://localhost:8000](http://localhost:8000), first create a
`docker-compose.override.yml` file with the following content:

```yaml
version: '3.7'

services:
    frontend:
        ports:
            - "8000:80"
```

>*Note*: Override `APP_DEFAULT_URI` value in a `.env.local` file if you use
>another port or another domain.

Then run the following commands:

    docker-compose up -d
    docker-compose run --rm --user=app frontend composer install
    docker-compose run --rm --user=app frontend yarn
    docker-compose run --rm --user=app frontend yarn build
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
```

## Usage

In slack you have one commands

* `/qotd [a date]` to find the QOTD of the day or of the given date;

## Credits

Thanks JoliCode for sponsoring this project.
