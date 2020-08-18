#!/bin/sh

# The docker changes shouldn't be needed, https://github.com/WordPress/wpdev-docker-images/blob/master/update.php#L185 needs updating.

# Make a PHP8 PHPUnit 9 dockerfile.
echo "FROM wordpressdevelop/phpunit:8.0-fpm

# Use PHPUnit 9
RUN curl -sL https://phar.phpunit.de/phpunit-9.phar > /usr/local/bin/phpunit && chmod +x /usr/local/bin/phpunit
" > php8.dockerFile

# Use the PHP8 + PHPUnit9 dockerfile
sed -i 's!phpunit:$!phpunit:\n    build:\n      context: .\n      dockerfile: php8.dockerFile!i' docker-compose.yml


# Our bootstrap limits it to PHPUnit 7.. hacky but understandable from core.
sed -i 's/8.0/10.0/' tests/phpunit/includes/bootstrap.php

# Make the needed syntax alterations to the unit tests.
# This would ideally be done not using regex, but they're so simple statements that it's not too horrible.
# I looked at using https://github.com/rectorphp/rector but that won't handle our assertContains changes needed.

# these functions must be return void as of PHPUnit8
for void_function in setUpBeforeClass setUp assertPreConditions assertPostConditions tearDown tearDownAfterClass onNotSuccessfulTest
do
	echo Converting ${void_function}..
	grep "function\s*${void_function}()\s*{" tests/phpunit/ -rli || echo No affected files.
	grep "function\s*${void_function}()\s*{" tests/phpunit/ -rli | xargs -I% sed -i "s!function\s*${void_function}()\s*{!function ${void_function}(): void /* PHP8 transpose */ {!gi" %
	echo
done

# PHPUnit lost a few functions, stub them back in..
cat tests/phpunit/includes/abstract-testcase.php | head -n-1 > tests/phpunit/includes/abstract-testcase.php.tmp
echo '
	public static function assertInternalType( $type, $var ): void {
		if ( "integer" === $type ) {
			$type = "int";
		}

		$method = "assertIs$type";

		$this->$method( $var );
	}

	public static function assertNotInternalType( $type, $var ): void {
		if ( "integer" === $type ) {
			$type = "int";
		}

		$method = "assertIsNot$type";

		$this->$method( $var );
	}

	// cannot do assertContains() as it must match the parent syntax that requires $b to be iterable.
	public function WPassertContains( $a, $b, $c = null ): void {
		if ( is_scalar( $b ) ) {
			$this->assertStringContainsString( $a, $b, $c );
		} else {
			$this->assertContains( $a, $b, $c );
		}
	}

	// Avoid PHPUnit warnings.
	public assertFileNotExists( string $file, string $message = '' ): void {
		$this->assertFileDoesNotExist( $file, $message );
	}

}' >> tests/phpunit/includes/abstract-testcase.php.tmp
mv tests/phpunit/includes/abstract-testcase.php.tmp tests/phpunit/includes/abstract-testcase.php

# PHPUnit lost a few functions. Convert them over.
# Migrated to being handled above..
#grep assertInternalType tests/phpunit/ -rli | xargs -I% sed -i -E 's~assertInternalType\( .(\w+).,~assert\1(~' %
#grep assertNotInternalType tests/phpunit/ -rli | xargs -I% sed -i -E 's~assertNotInternalType\( .(\w+).,~assertIsNot\1(~' %

# assertContains - https://github.com/sebastianbergmann/phpunit/issues/3425
# assertContains() no longer handles non-iterables, middleware it as WPassertContains().
grep assertContains tests/phpunit/ -rli | xargs -I% sed -i 's~assertContains~WPassertContains~' %

# Output a diff of the modifications for reference.
git diff .