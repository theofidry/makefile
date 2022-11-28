# Makefile

If you are not familiar with Makefiles, I recommend you [this blog article] which
is a nice introduction.

I am a big fan of Makefiles and after using it for years in almost every project
I had my hand in, private or public, OSS or not, I adopted some conventions on
how to write a Makefile.

This library is about providing some helpers as to build some convetion checks
as well as provide a few built-in ones.

A bare-bone Makefile that I may use will look like this:

```Makefile
# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

.DEFAULT_GOAL := default


#
# Commands
#---------------------------------------------------------------------------

# Provide a help command. In OSS projects where there is more contributors I tend to make this the
# default as it's a better entry point for newcomers.
# The command itself is a bit cryptic, but the result is simple: list all commands. See the following
# command declarations to see how I do it.
.PHONY: help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'

# Technically this could be "inlined". I like to have it, but it's really up to you. When I do not
# have it, I tend to have an "all" command that executes _every_ checks including CS fixing.
.PHONY: default
default:   ## Runs the default task
default: cs test

# ... Declare your commands here. I often combine very specific commands with a few "meta" commands.
# For example with the CS, you likely want a `php_cs_fixer` command, maybe you use `ergebnis/composer-normalize`
# in which case you can have a `composer_normalize` command. Then, I have a meta command, e.g. "cs"
# that executes them all.
#
# You can find another example bellow where I have two distinct test steps: the composer validate
# and executing PHPUnit, and a final "test" meta command that does it all.

# This is how a "documented" command is declared:
# The first line is the PHONY target to make sure it will executed regardless of whether a file or
#   directory with that name does exist (here if the directory "test" exists, you likely want to
#   execute the _command_ test still.
# The second line is the "comment" line, this is optional and when added it will include the command
#   in the "make help" output.
# The third line is the actual rule declaration.
.PHONY: test
test:   ## Executes all the tests
test: composer_validate phpunit

.PHONY: composer_validate
composer_validate:  ## Validates the composer.json
composer_validate:
	composer validate --strict

.PHONY: phpunit
phpunit:   ## Runs PHPUnit
phpunit: $(PHPUNIT_BIN) vendor
	$(PHPUNIT)

#
# Rules
#---------------------------------------------------------------------------

# Vendor does not depend on the composer.lock since the later is not tracked
# or committed (this is not true if you have an application).
vendor: composer.json
	$(COMPOSER) update --no-scripts
	touch -c $@
	touch -c $(PHPUNIT_BIN)

$(PHPUNIT_BIN): vendor
	touch -c $@

```


## Usage

With the simple Makefile above, there is a few things that can easily go wrong
still:

- The 2 or 3 lines to declare a command may not be in sync
- A command may be declared more than once
- The output of the help command matters to you (e.g. for your contributors) so
  you want to make sure it looks nice.

If this is of matter to you, then you can easily create the following test:

```php

<?php declare(strict_types=1);

namespace Acme;

use Fidry\Makefile\Test\BaseMakefileTestCase;

/**
 * @coversNothing
 */
class MakefileTest extends BaseMakefileTestCase
{
    protected static function getMakefilePath(): string
    {
        return __DIR__.'/../Makefile';
    }

    protected function getExpectedHelpOutput(): string
    {
        // It looks a bit ugly due to the coloring, but in practice still remains easy to update.
        // If you find it tedious to do it manually, I recommend to manually check the output
        // with `make help` and then copy it, e.g. via `make help | pbcopy` and then paste it here.
        return <<<'EOF'
            [33mUsage:[0m
              make TARGET
            
            [32m#
            # Commands
            #---------------------------------------------------------------------------[0m
            [33mdefault:[0m Runs the default task
            [33mtest:[0m	  Runs all the tests
            [33mcomposer_validate:[0m  Validates the Composer package
            [33mphpunit:[0m    Runs PHPUnit

            EOF;
    }
}
```

## Going further

Under the hood this package provides a simple [`Parser`] which parses the Makefile
content into a list of `Rule`s (which represent a [Makefile rule]).

From this it is easy to leverage the parsed output to implement some more custom
checks tailored to your needs. To check in more details, you can check the
[`BaseMakefileTestCase`] itself which makes use of it (there is no magic!).


[`BaseMakefileTestCase`]: src/Test/BaseMakefileTestCase.php
[Makefile rule]: https://www.gnu.org/software/make/manual/html_node/Rules.html
[`Parser`]: src/Parser.php
[this blog article]: https://localheinz.com/articles/2018/01/24/makefile-for-lazy-developers/
