# Relevanssi PHPUnit test suite

A work-in-progress set of unit tests for Relevanssi and Relevanssi Premium.

## Installation

1. Set up WP dev environment, either using [WP.org instructions](https://make.wordpress.org/core/handbook/testing/automated-testing/phpunit/) or [rask's wp-test-framework](https://github.com/rask/wp-test-framework).
1. Place Relevanssi somewhere.
1. Copy the test suite to the `tests` folder in the Relevanssi folder.
1. Copy either `bootstrap-rask.php` or `bootstrap-wporg.php` in the `tests` folder as `bootstrap.php`. In case you're using WP.org version, adjust the `WP_TESTS_DIR` path in the file.
1. Set your api key with `export RELEVANSSI_KEY="your key"`.
1. Test using `phpunit`.

## Feedback
Any feedback on the test suite is welcome: suggestions for new tests, either in the form of tests or ideas of what to test, are most welcome. Just post an issue.

## Sources

Useful links:

- [Pippin's guide](https://pippinsplugins.com/series/unit-tests-wordpress-plugins/)
- [Unit test factories](https://core.trac.wordpress.org/browser/trunk/tests/phpunit/includes/factory?order=name)
- [Codesymphony: Writing WP Plugin Unit Tests](https://codesymphony.co/writing-wordpress-plugin-unit-tests/)
- [WP CLI Handbook](https://make.wordpress.org/cli/handbook/plugin-unit-tests/)
- [Ben Lobaugh's guide](https://ben.lobaugh.net/blog/84669/how-to-add-unit-testing-and-continuous-integration-to-your-wordpress-plugin)
- [Intro from Smashing Magazine](https://www.smashingmagazine.com/2017/12/automated-testing-wordpress-plugins-phpunit/)
