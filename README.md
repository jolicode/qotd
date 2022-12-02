# QOTD Bot

Simple bot that check every morning for a new quote of the day and post it to the channel of your choice.

To elect the best QOTD, the bot will search for message with the most reactions. You can customize the searched reaction in the `.env` file.

## Installation

### Configure the Slack Application

Create a new slack application with the manifest located in `doc/slack-manifest.yaml`.

Dont forget to customize the file with your own values.

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
