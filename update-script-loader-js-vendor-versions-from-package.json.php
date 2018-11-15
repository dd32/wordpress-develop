<?php

/**
 * Hacky proof of concept for updating static versions in script-loader for `js/dist/(vendor/)?` libraries.
 */

$file = file( 'src/wp-includes/script-loader.php' );

$packagejson = json_decode( file_get_contents( 'package.json' ) );

foreach ( $file as $i => $line ) {
	if ( false === stripos( $line, 'wp-includes/js/dist/' ) ) {
		continue;
	}

	if ( ! preg_match( '!wp-includes/js/dist/(vendor/)?([^$.]+)[$.]!i', $line, $m ) || ! $m ) {
		continue;
	}
	$package = $m[2];

	$version = false;
	if ( isset( $packagejson->dependencies->{"$package"} ) ) {
		$version = trim( $packagejson->dependencies->{"$package"}, '^' );
	} elseif ( isset( $packagejson->dependencies->{"@wordpress/$package"} ) ) {
		$version = trim( $packagejson->dependencies->{"@wordpress/$package"}, '^' );
	}

	if ( $version ) {
		$line = preg_replace( '!,[^,]+;!i', ", '$version' );", $line );
	}

	echo $line . "\n";

	$file[ $i ] = $line;
}

file_put_contents( 'src/wp-includes/script-loader.php', implode( '', $file ) );