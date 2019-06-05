## Introduction

This tool (yet to be properly named) will give you a LEMP stack out of the box, by just running one single command:

```shell
dev new --tls lempstack
```

It uses Docker to build containers for each component of the stack. By default it uses:

- NGINX
- PHP
- Maria DB

The name given to your stack will act as your domain name. Automatically it will be assigned the `.test` TLD.

A self-signed certificate can be assigned to the stack and uses the latest TLS security features.

_Currently, this is for MacOS only - sorry_

## Prerequisites

- MacOS
- Docker / Docker Compose

#### Optional

- DNSMasq

## Getting started

First, you need to install the package.

```shell
composer require <insert path>
```

_Documentation on-going..._
