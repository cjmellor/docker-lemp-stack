# Table of Contents

- [Introduction](#introduction)
  - [Prerequisites](#prerequisites)
  - [Configuring DNSMasq](#configuring-dnsmasq)
- [Why Saber?](#why-saber)
- [Installation](#installation)
  - [Automatically](#automatically)
  - [Manually](#manually)
  - [Available installation options](#available-installation-options)
- [Create an App](#create-an-app)
  - [Database Management](#database-management)
  - [HTTPS](#https)
  - [Config](#config)
  - [H5BP](#h5bp)
- [Remove an App](#remove-an-app)
- [Uninstall](#uninstall)
- [Contribute](#contribute)
- [Contact](#contact)

# Introduction

Hello :wave:

Saber is a tool that allows you to set-up a fully functioning LEMP stack using Docker :whale:

Just a single command to run and you're up and running!

```shell
saber new your-site
```

Each part of the LEMP stack is constructed in a separate container.

- NGINX
- PHP
- MySQL / MariaDB

When the application is set-up, you can access your new app by visiting it in your browser at `http://your-site.test`

A self-signed certificate can be assigned to the stack and uses the latest TLS security features.

## Prerequisites

- MacOS
- [Docker / Docker Compose](https://hub.docker.com/editions/community/docker-ce-desktop-mac)
- [Homebrew](https://brew.sh/)
- DNSMasq

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

The installation consists of moving all files into `~/.config/saber`.

The `saber` executable is symlinked to `/usr/local/bin` for global usage.

There are two methods for installing Saber:

## Method #1

First, you need to install the package.

```shell
composer require cjmellor/saber
```

then run

```shell
saber install
```

and install the dependencies

```shell
composer update
```

## Method #2

Clone the repo

```shell
git clone git@github.com:cjmellor/saber.git
```

Install using the command

```shell
./saber install
```

and get the dependencies

```shell
composer update
```

The Saber executable will then be available globally.

## Available installation options

| Option | Default        | Description                                            |
| ------ | -------------- | ------------------------------------------------------ |
| php    | 7.2            | Just a version number (e.g. _5.6_, _7.4_)              |
| db     | mariadb:latest | _db:version_ (e.g. _mysql:5.7_). `latest` also allowed |

# Create an App

```shell
saber new my-site
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
saber new my-secure-site --tls
```

This will create a self-signed certificate. Using the latest security protocols, the application can run on the latest version of TLS (1.3).

**Currently there is no way to switch SSL's on or off, they must be added on the creation of a new app. This will be rectified in newer versions - PR's welcome!**

## Configuration

Each app created with Saber is configurable. You can enable or disable features that matter to you and how you want your environment setup.

When a new app is created, all the NGINX and PHP config is stored in a `lemp` folder within' the root (`~/.config/saber`)

The NGINX config for each app can be found in:

- `lemp/nginx/config/conf.d/`

The PHP config for each app can be found in:

- `lemp/php/config`

> Don't forget to restart the containers if you make changes to these files

## H5BP

Saber utilises the awesome [H5BP](https://github.com/h5bp/server-configs-nginx) project which keeps the application secure and up-to-date with the latest fixes.

# Remove an App

```shell
saber remove app-name
```

You can remove an app if you no longer require it.

This will remove the certificates, the PHP and NGINX configurations and the code folder.

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
