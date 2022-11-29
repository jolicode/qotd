# QOTD Bot

FIXME

## Installation

### Configure the Slack Application

FIXME

### Install the PHP application

    docker-compose up -d
    docker-compose run --user=app frontend composer install
    docker-compose run --user=app frontend bin/db
    # configure remaining parameters in .env.local
    # Enjoy

## Test

    # Only for the first time
    symfony run bin/db --env=test
    symfony php bin/phpunit

## Usage

In slack you have one commands

* `/qotd [a date]` to find the QOTD of the day or of the given date;

## Credits

Thanks JoliCode for sponsoring this project.
