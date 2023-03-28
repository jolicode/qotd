# QOTD Bot

Simple bot that check every morning for a new quote of the day and post it to the channel of your choice.

To elect the best QOTD, the bot will search for message with the most reactions. You can customize the searched reaction in the `.env` file.

## Installation

### Configure the Slack Application

Create a new slack application with the manifest located in `doc/slack-manifest.yaml`.

Don't forget to customize the file with your own values.

### Install the PHP application

    docker-compose build frontend # this is a dependency for cron container
    docker-compose up -d
    docker-compose run --user=app --rm frontend composer install
    docker-compose run --user=app --rm frontend bin/db
    yarn install # to install all frontend stuff
    yarn build # to build all frontend stuff
    # configure remaining parameters in .env.local
    # Enjoy

## Usage

In slack you have one command

* `/qotd [a date]` to find the QOTD of the day or of the given date;

## Credits

Thanks JoliCode for sponsoring this project.
