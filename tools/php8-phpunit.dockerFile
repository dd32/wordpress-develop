FROM wordpressdevelop/phpunit:8.0-fpm-latest

# Use PHPUnit 8
RUN -c curl -sL https://phar.phpunit.de/phpunit-8.phar > /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit