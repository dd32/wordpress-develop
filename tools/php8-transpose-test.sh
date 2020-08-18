#!/bin/sh

# Make a PHP8 compatible phpunit file.
echo "FROM wordpressdevelop/phpunit:8.0-fpm

# Use PHPUnit 8
RUN curl -sL https://phar.phpunit.de/phpunit-8.phar > /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit
" > php8.dockerFile

# Use a PHP8 compatible PHPunit.
sed -i 's!phpunit:$!phpunit:\n    build:\n      context: .\n      dockerfile: php8.dockerFile!i' docker-compose.yml

cat docker-compose.yml