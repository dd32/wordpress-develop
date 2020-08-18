#!/bin/sh

pwd

# Make a PHP8 compatible phpunit file.
echo "FROM wordpressdevelop/phpunit:8.0-fpm

# Use PHPUnit 9
RUN curl -sL https://phar.phpunit.de/phpunit-9.phar > /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit
" > php8.dockerFile

# Use a PHP8 compatible PHPunit.
sed -i 's!phpunit:$!phpunit:\n    build:\n      context: .\n      dockerfile: php8.dockerFile!i' docker-compose.yml

# We need to use some PHP 7.2+ syntax for PHPUnit 9

pwd

# Out bootstrap limits it to PHPUnit 7..
sed -i 's/8.0/10.0/' tests/phpunit/includes/bootstrap.php

# these functions must be return void as of PHPUnit8
for void_function in setUpBeforeClass setUp assertPreConditions assertPostConditions tearDown tearDownAfterClass onNotSuccessfulTest
do
  echo Converting ${void_function}
  grep "function\s*${void_function}()\s*{" tests/phpunit/ -rli
  grep "function\s*${void_function}()\s*{" tests/phpunit/ -rli | xargs -I% sed -i "s!function\s*${void_function}()\s*{!function ${void_function}(): void /* PHP8 transpose */!gi" %
done
