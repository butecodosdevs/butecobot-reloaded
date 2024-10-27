# Buteco Bot Reloaded

⬅ [Back to README.md](../README.md)

This bot was developed using:
- [Laracord](https://laracord.com/)
- [DiscordPHP](https://github.com/discord-php/DiscordPHP)

##  Requirements

- PHP 8.2
- Composer
- Docker
- Make (to process Makefiles)

##  Third Party APIs

- OpenAI

##  Installation

The initial setup é pretty easy:

    git clone https://github.com/butecodosdevs/butecobot-reloaded

Navigate to the created directory and then run:

	cp .env-example .env && composer install && make dev-essentials

You can check if all the containers are running by using:

	docker ps

Before you can install the PHP packages and run the migrations let's create our database that will receive the migrations. Navigate to the PHPMyAdmin, log in it and create a database called:

	butecobot

I would choose **utf8mb4_general_ci** for the collation. But feel free to choose the one you like, just remember it will influence in the sorting of things for example.

For the next commands you'll need to be in the top level of this repository. The .env copied from the example already contain the default credentials for the dev environment so you'll only need to run the migrations. For that you'll need to:

	composer install && php laracord migrate

If everything went up ok you'll can just run the next command and your Bot will be running:

	php laracord

## Useful urls

- [PHPMyAdmin - A web MySQL Client](http://127.0.0.1:8081)
- [AnotherRedisDesktopManager - Self explanatory name](https://github.com/qishibo/AnotherRedisDesktopManager)
