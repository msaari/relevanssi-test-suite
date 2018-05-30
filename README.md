# Relevanssi PHPUnit test suite

A work-in-progress set of unit tests for Relevanssi and Relevanssi Premium.

## Installation

1. Set up Flywheel Local.
1. Install Relevanssi.
1. Use [setup-phpunit.sh](https://gist.github.com/keesiemeijer/a888f3d9609478b310c2d952644891ba) to set up PHPUnit.
1. Use `wp scaffold plugin relevanssi-premium` to scaffold the tests.
1. Copy the test suite to the `tests` folder.
1. Set your api key with `export RELEVANSSI_KEY="your key"`.
1. Test!

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