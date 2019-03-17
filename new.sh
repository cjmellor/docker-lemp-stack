#!/usr/bin/env bash

error=$? # All errors are passed to this variable

usage() {
  echo "
    Usage: $0 [-a app-name]
  "
  exit 0
}

# MKCert is used to allow self-signed SSL's to be used on localhost.
## Recommended installation is via Brew
## Info: https://github.com/FiloSottile/mkcert

if [ ! -d "$HOME/Library/Application Support/mkcert" ];
then
  echo "➡️ Installing MKCert..."

  brew install mkcert
  mkcert -install 2> /dev/null

  echo "MKCert Installed! ✅"
fi

# Command has required and optional properties
## a - App name: supply an app name in domain format

while getopts ":a:h" opt; do
  case $opt in
    a )
      app=$OPTARG ;;
    \? )
      echo "-$OPTARG is not a valid option. Use '-h' for more options";
      exit 1 ;;
    : )
      echo "-$OPTARG requires an argument";
      exit 1 ;;
    h|* )
      usage
      exit 1 ;;
  esac
done

shift $(( OPTIND-1 ))

# If no app name is provided using the 'a' attribute

if [ -z "$app" ]; then
  echo "App name is required"
  exit 1
fi

# Generate certificates
## Creates self-signed certificates for your app

if [ ! -d "certs" ]; then
  mkdir certs
fi

cd certs || exit 1

MAKE_CERT=$(mkcert "${app}")

if [ $error -eq 0 ]; then
  echo "Certificates successfully created!"
else
  printf "Their was an error:\n %s" "$MAKE_CERT"
  exit 1
fi

# Add new certificates to config

cd ../lemp/nginx/config/h5bp/ssl || exit 1
sed -i '.bak' "s#localhost#${app}#g" certificate_files.conf
rm certificate_files.conf.bak

# Generate new site files

cd ../../conf.d || exit 1

# Rename vhost config

mv default.conf "${app}".conf

# Switch all instances of 'localhost' to your app name
## Note: on macOS you must supply a backup file e.g. -i '.bak'
## This will delete the file that gets backed up

sed -i '.bak' "s#localhost#${app}#g" "${app}".conf
rm -f "${app}".conf.bak

# Copy the PHP-FPM config and match it to the app name

cd ../../../php/configs || exit 1
mv www.conf "${app}".conf

# Change 'www' pool name to same as the app name you chose

sed -i '.bak' "s#\[www\]#[${app}]#" "${app}".conf
sed -i '.bak' "s#\[www\]#[${app}]#" docker.conf
sed -i '.bak' "s#\[www\]#[${app}]#" zz-docker.conf
rm -f {"${app}",docker,zz-docker}.conf.bak

# Change the app name in the .env

cd ../../../ || exit 1
sed -i '.bak' "s#localhost#${app}#" .env
rm -f .env.bak

# Build the LEMP stack

docker-compose up -d --build
