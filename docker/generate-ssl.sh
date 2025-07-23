#!/bin/bash

mkdir -p /etc/apache2/ssl
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/apache2/ssl/apache.key \
  -out /etc/apache2/ssl/apache.crt \
  -subj "/C=US/ST=Denial/L=Nowhere/O=DirectNav/CN=localhost"
