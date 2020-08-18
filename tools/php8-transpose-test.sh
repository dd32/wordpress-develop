#!/bin/sh

# Use a PHP8 compatible PHPunit.
sed -i 's!phpunit:$!phpunit:\n    build:\n      context: .\n      dockerfile: tools/php8-phpunit.dockerFile!i' docker-compose.yml

cat docker-compose.yml