[![StyleCI](https://github.styleci.io/repos/174240042/shield?branch=master)](https://github.styleci.io/repos/174240042)

Hello :wave:

Saber is a tool that allows you to set-up a fully functioning LEMP stack using Docker :whale:

Just a single command to run and you're up and running!

```shell
saber new <app-domain>
```

Each part of the LEMP stack is constructed in a separate container.

-   NGINX
-   PHP
-   MySQL / MariaDB

When the application is set-up, you can access your new app by visiting it in your browser at `http://your-site.test`

A self-signed certificate can be assigned to the stack and uses the latest TLS security features.

# Table of Contents

-   [Prerequisites](#prerequisites)
    -   [Configuring DNSMasq](#configuring-dnsmasq)
-   [Why Saber?](#why-saber)
-   [Installation](#installation)
    -   [Available installation options](#available-installation-options)
-   [Create an App](#create-an-app)
    -   [Database Management](#database-management)
    -   [HTTPS](#https)
    -   [Config](#config)
    -   [H5BP](#h5bp)
-   [Secure an App](#secure-an-app)
-   [Remove an App](#remove-an-app)
-   [Switch PHP Versions](#switch-php-versions)
-   [Upgrade](#upgrade)
-   [Uninstall](#uninstall)
-   [Contribute](#contribute)
-   [Contact](#contact)

## Prerequisites

-   MacOS
-   [Docker / Docker Compose](https://hub.docker.com/editions/community/docker-ce-desktop-mac)
-   [Homebrew](https://brew.sh/)
-   DNSMasq

## Configuring DNSMasq

_Already done this? [Skip ahead](#why-saber)_

DNSMasq is required so that Saber can function correctly. Here's a quick guide on getting it set-up. For a more detailed explanation, I recommend reading [this article](https://passingcuriosity.com/2013/dnsmasq-dev-osx/).

Install DNSMasq - this guide uses **Brew**

```shell
brew install dnsmasq
```

Open `/usr/local/etc/dnsmasq.conf` and anywhere in the file, add this line

```shell
address=/test/127.0.0.1
```

Restart DNSMasq

```shell
brew services restart dnsmasq
```

Then test it by running a `dig` command

```shell
dig my.awesome-website.test @127.0.0.1
```

If you get a response like so, it works!

```shell
;; ANSWER SECTION:
my.awesome-website.test. 0 IN A 127.0.0.1
```

Now you need to configure DNSMasq so it can control the way the DNS queries are performed.

```shell
sudo mkdir -p /etc/resolver

sudo tee /etc/resolver/test > /dev/null << EOF
nameserver 127.0.0.1
EOF
```

Now any site with a `.test` TLD will resolve to your localhost

```shell
$ ping -c 5 saber.is.cool.test
PING saber.is.cool.test (127.0.0.1): 56 data bytes
64 bytes from 127.0.0.1: icmp_seq=0 ttl=64 time=0.039 ms
64 bytes from 127.0.0.1: icmp_seq=1 ttl=64 time=0.084 ms
64 bytes from 127.0.0.1: icmp_seq=2 ttl=64 time=0.209 ms
64 bytes from 127.0.0.1: icmp_seq=3 ttl=64 time=0.200 ms
64 bytes from 127.0.0.1: icmp_seq=4 ttl=64 time=0.142 ms

--- saber.is.cool.test ping statistics ---
5 packets transmitted, 5 packets received, 0.0% packet loss
```

# Why Saber?

Why should you use Saber over something like **Valet** or **Homestead**?

Saber is heavily inspired by Valet. While not a complete fork, it's referenced a lot in the style and format of the code.

Saber was built purely as a fun project to be able to quickly spin up a development environment that can be customised exactly how it is needed to run.

Saber puts customisation ahead of speed when it comes to creating a new development environment. It might take longer to have to pull down Docker images and build them but it means you can have a server specced up to exactly as you want.

# Installation

`composer global require cjmellor/saber`

if you want to run it on a per-project basis, run

```zsh
cd /to/your/path
composer require cjmellor/saber
```

Now run the `install` command to install Saber onto your system. You can choose a PHP and Database version on install, or just omit the options

```zsh
saber install
```

or with options

```zsh
saber install --php=7.4 --db=mysql:8.0
```

## Available installation options

| Option | Default        | Description                                            |
| ------ | -------------- | ------------------------------------------------------ |
| php    | 7.3            | Just a version number (e.g. _5.6_, _7.4_)              |
| db     | mariadb:latest | _db:version_ (e.g. _mysql:5.7_). `latest` also allowed |

# Create an App

```shell
saber new <app-domain>
```

Creating an app will spin up the Dockerized LEMP stack.

Your application can be viewed at its domain of the same name _(i.e. an app named `saber-test` will be viewable at `http://saber-test.test`)_. By default, the TLD will always be `.test`.

**Currently, there is no way to change the TLD using Saber. This will change in future versions - PR's welcome!**

## Database Management

The default credentials are:

|              |          |
| ------------ | -------- |
| **User**     | root     |
| **Pass**     | password |
| **Database** | default  |

You can log in to the database as you normally would, via a command line or a GUI.

```shell
mysql -h 127.0.0.1 -P 3306 -u root -p
```

## HTTPS

Without specifying otherwise, the application will be unsecure (`http`). If you want to run the app more securely, when creating your app, specify the `--tls` tag

```shell
saber new <app-domain> --tls
```

This will create a self-signed certificate. Using the latest security protocols, the application can run on the latest version of TLS (1.3).

You can [secure or unsecure an app](#secure-an-app) by using the native commands available in Saber.

## Configuration

Each app created with Saber is configurable. You can enable or disable features that matter to you and how you want your environment setup.

When a new app is created, all the NGINX and PHP config is stored in a `lemp` folder within' the root

The NGINX config for each app can be found in:

-   `lemp/nginx/config/conf.d/`

The PHP config for each app can be found in:

-   `lemp/php/config`

> Don't forget to restart the containers if you make changes to these files

## H5BP

Saber utilises the awesome [H5BP](https://github.com/h5bp/server-configs-nginx) project which keeps the application secure and up-to-date with the latest fixes.

# Remove an App

```shell
saber remove <app-domain>
```

You can remove an app if you no longer require it.

This will remove the certificates, the PHP and NGINX configurations and the code folder.

# Switch PHP Versions

```shell
saber use 7.3
```

Using the `use` command you can switch PHP versions easily!

By default, Saber encourages you to use stable versions of PHP. You can overwrite this by supplying a `--force` option that will allow you to use other PHP versions.

Example:

```shell
saber use --force 5.6.40

saber use -f 7.4.0RC6-fpm
```

# Secure an App

```shell
saber secure <app-domain>
```

By default, apps aren't secure - they're running on `HTTP`. If you want your app to run over `HTTPS` you can run the `secure` command which will assign an SSL certificate to your app.

> The SSL certificates are self-signed.

If you added an SSL on creation of your app, or you made a mistake and want to start over, you can perform the reverse of this and remove an SSL certificate

```shell
saber unsecure <app-domain>
```

# Upgrade

The images used to build your environment are often updated by the maintainers of the image, resulting in newer versions and secrity fixes. Normally to upgrade a version of PHP or MySQL it would take many hours to upgrade it manually or lots of tedious tickets or phone calls with your hosting provider.

Saber will pull down the newest versions of the images you want to upgrade and rebuild the containers.

```shell
saber upgrade
```

Run the upgrade command, and you'll be presented with a list of images stored locally on your machine

```shell
Select images to upgrade. Select multiple by seperating with a commar, example: 1,3
  [0] composer:latest
  [1] php:7.2-fpm-alpine
  [2] php:7.3-fpm-alpine
  [3] nginx:alpine
  [4] mariadb:latest
 >
```

Choose the image you want to update by typing the corrosponding number and the image will be pulled down and the containers will be rebuilt.

If you want update multiple images, select the image number in a commar separated value, e.g.

```shell
> 2,3,4
```

and those images will be updated.

> Images cannot be updated simultaneously, they will be pulled one at a time before been rebuilt

# Uninstall

Not your cup of tea? That's okay, you can uninstall Saber from your machine - **but you will lose everything!**

```shell
saber uninstall
```

# Contribute

:rotating_light: I encourage you to help me make this tool even more useful! I will be adding more features to it over time.

:bug: There are bound to be some bugs hiding away that have not been counted for - help me get rid of them by creating an issue.

:arrow_down: Please, submit a Pull Request and help me make this tool even better. I'm looking forward to working with other developers on this.

# Contact

Feel free to get in touch! I'm contactable on Twitter [@cmellor](https://twitter.com/cmellor)
