# LEMP Stack

## Powered by Docker containers

This is a LEMP stack that is powered by Docker containers.

## Create a new Application

To create a new application, just run `new.sh -a <app_name>` and it will install everything needed to get your app up and running.

### Requirements

* Docker for MacOS

### Example

```shell
./new.sh -a localhost
```

This will install your application and make it viewable by the `localhost` domain.

```shell
./new.sh -a my-web-app.test
```

### Domain Resolving

To have these fake domains work as if they were online, you need to edit your `hosts` file or use a service list `dnsmasq`.

## NGINX

Using the latest version of NGINX on the `alpine` OS - to keep it small!

It also includes configurations from the [h5bp NGINX configs](https://github.com/h5bp/server-configs-nginx) to make it more secure and easily fine tuned to your liking.

## PHP-FPM

Using the latest version of PHP, also on the `alpine` OS. This version was be modified though using environment variables.

If you don't want the latest version of PHP, just change the `PHP_VERSION` variable in the `.env` file.

### Extensions

By default, there are some extensions installed and configured on build

* OPCache
* Mcrypt
* SSH2

*These can be removed in the `Dockerfile` under `build/php`*

_Note: the `mcrypt` extension does not need to be loaded with PHP versions > 7.1_

More can easily be added by running the `docker-ext-php-install` script.

## MariaDB

Using the latest version of MariaDB.

Environment variables must be set-up if you wish to use specific values for the database name or the password etc.

By default, only the `MYSQL_ROOT_PASSWORD` and `MYSQL_DATABASE` variables are available but you can add more if needed. Default user is `root`.

*Note: This can be switched to MySQL just by changing the image name*
