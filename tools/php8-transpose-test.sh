#!/bin/sh

pwd;

# Use a PHP8 compatible PHPunit.
sed -i 's!phpunit:$!phpunit:\n    build:\n      dockerfile: tools/php8-phpunit.dockerFile!i' docker-compose.yml