# QOTD Bot

Simple bot that check every morning for a new quote of the day and post it to the channel of your choice.

To elect the best QOTD, the bot will search for message with the most reactions. You can customize the searched reaction in the `.env` file.

## Installation

### Configure the Slack Application

Create a new slack application with the manifest located in `doc/slack-manifest.yaml`.

Dont forget to customize the file with your own values.

### Install the PHP application

To make the available locally at the address [http://localhost:8000](http://localhost:8000), first create a `docker-compose.override.yml` file with the following content:

```yaml
version: '3.7'

services:
    frontend:
        ports:
            - "8000:80"
```

>*Note*: Override `APP_DEFAULT_URI` value in a `.env.local` file if you use another port or another domain.

Then run the following commands:

    docker-compose build frontend # this is a dependency for cron container
    docker-compose up -d
    docker-compose run --user=app --rm frontend composer install
    docker-compose run --user=app --rm frontend yarn
    docker-compose run --user=app --rm frontend yarn build
    docker-compose run --user=app --rm frontend bin/db
    # configure remaining parameters in .env.local
    # Enjoy

## Usage

In slack you have one commands

* `/qotd [a date]` to find the QOTD of the day or of the given date;

## Credits

Thanks JoliCode for sponsoring this project.
