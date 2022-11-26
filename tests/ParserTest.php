<?php

/*
 * This code is licensed under the BSD 3-Clause License.e
 *
 * Copyright (c) 2022, Théo FIDRY <theo.fidry@gmail.com>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * * Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 * * Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * * Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

namespace Fidry\Makefile\Tests;

use Fidry\Makefile\Parser;
use Fidry\Makefile\Rule;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fidry\Makefile\Parser
 * @internal
 */
final class ParserTest extends TestCase
{
    /**
     * @dataProvider makefileContentProvider
     */
    public function test_it_can_parse_makefiles(string $content, array $expected): void
    {
        $actual = Parser::parse($content);

        self::assertEquals($expected, $actual);
    }

    public static function makefileContentProvider(): iterable
    {
        yield 'empty' => [
            <<<'MAKEFILE'

                MAKEFILE,
            [],
        ];

        yield 'simple command with multiple pre-requisites' => [
            <<<'MAKEFILE'
                .PHONY: command
                command: foo bar $(DEP)
                    @echo 'Hello'
                MAKEFILE,
            [
                new Rule('.PHONY', ['command']),
                new Rule('command', ['foo', 'bar', '$(DEP)']),
            ],
        ];

        yield 'double command with tabs' => [
            <<<'MAKEFILE'
                .PHONY: command1
                command1:
                	@echo "hello"

                .PHONY: command2
                command2:
                	@echo "world!"
                MAKEFILE,
            [
                new Rule('.PHONY', ['command1']),
                new Rule('command1', []),
                new Rule('.PHONY', ['command2']),
                new Rule('command2', []),
            ],
        ];

        yield 'simple command with a help comment' => [
            <<<'MAKEFILE'
                .PHONY: command
                command:    ## Comment
                command: foo bar $(DEP)
                    @echo 'Hello'
                MAKEFILE,
            [
                new Rule('.PHONY', ['command']),
                new Rule('command', ['## Comment']),
                new Rule('command', ['foo', 'bar', '$(DEP)']),
            ],
        ];

        yield 'simple command with multi-line pre-requisites' => [
            <<<'MAKEFILE'
                .PHONY: command
                command: foo \
                         bar \
                         $(DEP)
                    @echo 'Hello'
                MAKEFILE,
            [
                new Rule('.PHONY', ['command']),
                new Rule('command', ['foo', 'bar', '$(DEP)']),
            ],
        ];

        yield 'simple command with multi-line pre-requisites missing the last pre-requisite' => [
            <<<'MAKEFILE'
                .PHONY: command
                command: foo \
                         bar \
                         $(DEP)
                    @echo 'Hello'
                MAKEFILE,
            [
                new Rule('.PHONY', ['command']),
                new Rule('command', ['foo', 'bar', '$(DEP)']),
            ],
        ];

        yield 'simple command with multi-line pre-requisites with trailing spaces' => [
            <<<'MAKEFILE'
                .PHONY: command
                command: foo \  
                         bar \  
                         $(DEP)
                    @echo 'Hello'
                MAKEFILE,
            [
                new Rule('.PHONY', ['command']),
                new Rule('command', ['foo', '\\']),
            ],
        ];

        yield 'multiple commands' => [
            <<<'MAKEFILE'
                .PHONY: command1 command2 command3

                .PHONY: command2
                command2: foo \
                         bar \
                         $(DEP)
                    @echo 'Hello'

                .PHONY: command1
                command1: foo \
                         bar

                .PHONY: command2
                command1: foo
                MAKEFILE,
            [
                new Rule('.PHONY', ['command1', 'command2', 'command3']),
                new Rule('.PHONY', ['command2']),
                new Rule('command2', ['foo', 'bar', '$(DEP)']),
                new Rule('.PHONY', ['command1']),
                new Rule('command1', ['foo', 'bar']),
                new Rule('.PHONY', ['command2']),
                new Rule('command1', ['foo']),
            ],
        ];

        yield 'variables' => [
            <<<'MAKEFILE'
                .DEFAULT_GOAL := help
                BOX=box
                # TODO: message
                FLOCK := flock
                MAKEFILE,
            [],
        ];

        yield 'simple rule' => [
            <<<'MAKEFILE'
                foo: bar
                	@echo "foo:bar"
                MAKEFILE,
            [
                new Rule('foo', ['bar']),
            ],
        ];

        yield 'simple rule with tabs' => [
            <<<'MAKEFILE'
                foo: bar
                	@echo "foo:bar"
                foz: baz
                	@echo "foz:baz"
                MAKEFILE,
            [
                new Rule('foo', ['bar']),
                new Rule('foz', ['baz']),
            ],
        ];

        yield 'variable' => [
            <<<'MAKEFILE'
                FOO_URL="https://ex.co"
                MAKEFILE,
            [],
        ];

        yield 'UTF-8 variable' => [
            <<<'MAKEFILE'
                EMOJI = 🥶
                MAKEFILE,
            [],
        ];

        yield 'conditional example' => [
            <<<'MAKEFILE'
                .PHONY: cs
                cs: ## Runs PHP-CS-Fixer
                cs: $(PHP_CS_FIXER_BIN)
                ifndef SKIP_CS
                    $(PHP_CS_FIXER)
                endif
                MAKEFILE,
            [
                new Rule('.PHONY', ['cs']),
                new Rule('cs', ['## Runs PHP-CS-Fixer']),
                new Rule('cs', ['$(PHP_CS_FIXER_BIN)']),
            ],
        ];

        yield 'comment with leading whitespace' => [
            <<<'MAKEFILE'
                  # foo
                MAKEFILE,
            [],
        ];

        yield 'variable assignment with leading whitespace' => [
            <<<'MAKEFILE'
                  FOO = BAR
                MAKEFILE,
            [],
        ];

        yield 'variable & comment with command' => [
            <<<'MAKEFILE'
                FLOCK := flock
                # comment

                foo: bar
                MAKEFILE,
            [
                new Rule('foo', ['bar']),
            ],
        ];

        yield from self::officialDocumentationExamples();
    }

    private static function officialDocumentationExamples(): iterable
    {
        // See https://www.gnu.org/software/make/manual/html_node/Rule-Syntax.html
        yield 'simple rule syntax' => [
            <<<'MAKEFILE'
                targets : prerequisites
                    recipe
                    …
                MAKEFILE,
            [
                // TODO: should trim target here
                new Rule('targets ', ['prerequisites']),
            ],
        ];

        yield 'multiple targets and pre-requisites' => [
            <<<'MAKEFILE'
                target1 target2 : prerequisite1 prerequisite2
                    recipe
                    …
                MAKEFILE,
            [
                // TODO: should trim target here
                new Rule('target1 target2 ', ['prerequisite1', 'prerequisite2']),
            ],
        ];

        // See https://www.gnu.org/software/make/manual/html_node/Rule-Syntax.html
        yield 'alternative simple rule syntax' => [
            <<<'MAKEFILE'
                targets : prerequisites ; recipe
                    recipe
                    …
                MAKEFILE,
            [
                // TODO: should trim target here
                // TODO: should not include ; recipe
                new Rule('targets ', ['prerequisites', ';', 'recipe']),
            ],
        ];

        // TODO: add support or add clear error about .RECIPEPREFIX
        //   see https://www.gnu.org/software/make/manual/html_node/Special-Variables.html
        yield '.RECIPEPREFIX example' => [
            <<<'MAKEFILE'
                .RECIPEPREFIX = >
                all:
                > @echo Hello, world
                MAKEFILE,
            [
                new Rule('all', []),
            ],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Wildcard-Examples.html
        yield 'wildcard example in recipe' => [
            <<<'MAKEFILE'
                clean:
                    rm -f *.o
                MAKEFILE,
            [
                new Rule('clean', []),
            ],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Wildcard-Examples.html
        yield 'wildcard example in pre-requisite' => [
            <<<'MAKEFILE'
                print: *.c
                    lpr -p $?
                    touch print
                MAKEFILE,
            [
                new Rule('print', ['*.c']),
            ],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Wildcard-Examples.html
        yield 'variable with wildcard expansion' => [
            <<<'MAKEFILE'
                objects = *.o
                MAKEFILE,
            [],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Wildcard-Examples.html
        yield 'variable with expanded wildcard expansion' => [
            <<<'MAKEFILE'
                objects := $(wildcard *.o)
                MAKEFILE,
            [],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Splitting-Recipe-Lines.html
        yield 'split recipe (example #1)' => [
            <<<'MAKEFILE'
                all :
                        @echo no\
                space
                        @echo no\
                        space
                        @echo one \
                        space
                        @echo one\
                         space

                MAKEFILE,
            [
                // TODO: this is incorrect
                new Rule('all ', ['space', 'space', 'space', 'space']),
            ],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Splitting-Recipe-Lines.html
        yield 'split recipe (example #2)' => [
            <<<'MAKEFILE'
                all : ; @echo 'hello \
                    world' ; echo "hello \
                world"

                MAKEFILE,
            [
                // TODO: this is incorrect
                new Rule('all ', [';', '@echo', '\'hello', 'world\'', ';', 'echo', '"hello', 'world"']),
            ],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Target_002dspecific.html
        yield 'target specific variable' => [
            <<<'MAKEFILE'
                prog : CFLAGS = -g
                prog : prog.o foo.o bar.o

                MAKEFILE,
            [
                // TODO: this is incorrect
                new Rule('prog ', ['CFLAGS', '=', '-g']),
                new Rule('prog ', ['prog.o', 'foo.o', 'bar.o']),
            ],
        ];

        // https://www.gnu.org/software/make/manual/html_node/Reference.html
        yield 'basic variable reference (example #1)' => [
            <<<'MAKEFILE'
                objects = program.o foo.o utils.o
                program : $(objects)
                        cc -o program $(objects)

                $(objects) : defs.h

                MAKEFILE,
            [
                new Rule('program ', ['$(objects)']),
                new Rule('$(objects) ', ['defs.h']),
            ],
        ];
    }
}
