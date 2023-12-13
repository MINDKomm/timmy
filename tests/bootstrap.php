<?php

use Yoast\WPTestUtils\WPIntegration;
use Timmy\Timmy;

require_once dirname(__DIR__) . '/vendor/yoast/wp-test-utils/src/WPIntegration/bootstrap-functions.php';

$_tests_dir = Yoast\WPTestUtils\WPIntegration\get_path_to_wp_test_dir();

if (!is_file("{$_tests_dir}/includes/functions.php")) {
    echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit(1);
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __FILE__ ) . '/../vendor/autoload.php';

	if (version_compare(Timber\Timber::$version, '2.0.0', '>=')) {
	    Timber\Timber::init();
	} else {
		new Timber\Timber();
	}

	Timmy::init();

	require dirname( __FILE__ ) . '/../wp-content/plugins/advanced-custom-fields/acf.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/*
 * Bootstrap WordPress. This will also load the Composer autoload file, the PHPUnit Polyfills
 * and the custom autoloader for the TestCase and the mock object classes.
 */
WPIntegration\bootstrap_it();

require_once 'timmy-sizes.php';
