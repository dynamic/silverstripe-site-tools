# Silvertripe Site Tools

Tools to build common functionality in SilverStripe sites.

[![CI](https://github.com/dynamic/silverstripe-site-tools/actions/workflows/ci.yml/badge.svg)](https://github.com/dynamic/silverstripe-site-tools/actions/workflows/ci.yml)
[![codecov](https://codecov.io/gh/dynamic/silverstripe-site-tools/branch/master/graph/badge.svg)](https://codecov.io/gh/dynamic/silverstripe-site-tools)

[![Latest Stable Version](https://poser.pugx.org/dynamic/silverstripe-site-tools/v/stable)](https://packagist.org/packages/dynamic/silverstripe-site-tools)
[![Total Downloads](https://poser.pugx.org/dynamic/silverstripe-site-tools/downloads)](https://packagist.org/packages/dynamic/silverstripe-site-tools)
[![Latest Unstable Version](https://poser.pugx.org/dynamic/silverstripe-site-tools/v/unstable)](https://packagist.org/packages/dynamic/silverstripe-site-tools)
[![License](https://poser.pugx.org/dynamic/silverstripe-site-tools/license)](https://packagist.org/packages/dynamic/silverstripe-site-tools)


## Requirements

* SilverStripe ^4.0
* gorriecoe/silverstripe-linkfield ^1.0
* silvershop/silverstripe-hasonefield ^3.0
* symbiote/silverstripe-gridfieldextensions ^3.0
* unclecheese/display-logic ^2.0

## Installation

```
composer require dynamic/silverstripe-site-tools
```

## License
See [License](license.md)

## Upgrading from version 1

Site Tools drops `sheadawson/silverstripe-linkable` usage in favor of `gorriecoe/silverstripe-linkfield`. To avoid data loss, install the `dynamic/silverstripe-link-migrator` module as follows:

```markdown
composer require dynamic/silverstripe-link-migrator
```

Then, run the task "Linkable to SilverStripe Link Migration" via `/dev/tasks`, or cli via:
```markdown
vendor/bin/sake dev/tasks/LinkableMigrationTask
```

This will populate all of the new Link fields with data from the old class.

## Maintainers
 *  [Dynamic](http://www.dynamicagency.com) (<dev@dynamicagency.com>)

## Bugtracker
Bugs are tracked in the issues section of this repository. Before submitting an issue please read over
existing issues to ensure yours is unique.

If the issue does look like a new bug:

 - Create a new issue
 - Describe the steps required to reproduce your issue, and the expected outcome. Unit tests, screenshots
 and screencasts can help here.
 - Describe your environment as detailed as possible: SilverStripe version, Browser, PHP version,
 Operating System, any installed SilverStripe modules.

Please report security issues to the module maintainers directly. Please don't file security issues in the bugtracker.

## Development and contribution
If you would like to make contributions to the module please ensure you raise a pull request and discuss with the module maintainers.
