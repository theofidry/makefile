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

namespace Fidry\Makefile\Tests\Test\Constraint;

use Fidry\Makefile\Rule;
use Fidry\Makefile\Test\Constraint\ValidCommandDeclaration;
use stdClass;

/**
 * @covers \Fidry\Makefile\Test\Constraint\ValidCommandDeclaration
 *
 * @internal
 */
final class ValidCommandDeclarationTest extends ConstraintTestCase
{
    /**
     * @dataProvider valueProvider
     *
     * @param mixed $value
     */
    public function test_it_tests_that_the_phony_declarations_declare_a_command(
        $value,
        ?string $expectedExpectationFailureMessage
    ): void {
        $constraint = new ValidCommandDeclaration();

        null === $expectedExpectationFailureMessage
            ? self::assertConstraintDoesNotFail($constraint, $value)
            : self::assertConstraintFails($constraint, $value, $expectedExpectationFailureMessage);
    }

    public static function valueProvider(): iterable
    {
        yield 'non array value' => [
            new stdClass(),
            <<<'EOF'
                Failed asserting that the value to be an array of "Fidry\Makefile\Rule" instances. Got "stdClass".

                EOF,
        ];

        yield 'no rules' => [
            [],
            null,
        ];

        yield 'array of non-rule value' => [
            [
                new Rule('foo', []),
                new stdClass(),
            ],
            <<<'EOF'
                Failed asserting that the value to be an array of "Fidry\Makefile\Rule" instances. Got "stdClass" for the item of index "1".

                EOF,
        ];

        yield 'single command' => [
            [
                Rule::createPhony(['command']),
                new Rule('command', ['## Command Help']),
                new Rule('command', ['progA', 'progB']),
            ],
            null,
        ];

        yield 'two commands' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command1', ['## Command1 Help']),
                new Rule('command1', ['progA', 'progB']),

                Rule::createPhony(['command2']),
                new Rule('command2', ['## Command2 Help']),
                new Rule('command2', ['progA', 'progC']),
            ],
            null,
        ];

        yield 'PHONY declaration with no pre-requisites' => [
            [
                Rule::createPhony([]),
            ],
            <<<'EOF'
                Failed asserting that the rule ".PHONY: " has one and only one pre-requisite. 0 pre-requisite found.

                EOF,
        ];

        yield 'PHONY declaration referencing a different target that the command declared thereafter' => [
            [
                Rule::createPhony(['command2']),
                new Rule('command1', ['## Command1 Help']),
                new Rule('command1', ['progA', 'progB']),
            ],
            <<<'EOF'
                Failed asserting that the rule "command1: ## Command1 Help" has the same target as the previous PHONY declaration.
                --- Expected
                +++ Actual
                @@ @@
                -command2
                +command1

                EOF,
        ];

        yield 'command declaration without a help comment' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command1', ['progA', 'progB']),
            ],
            null,
        ];

        // TODO: add tests for PHONY with comments: first PHONY is a comment, 2nd rule is a comment but a PHONY one...
        yield 'two PHONY targets' => [
            [
                Rule::createPhony(['command1']),
                Rule::createPhony(['command2']),
            ],
            <<<'EOF'
                Failed asserting that the rule ".PHONY: command2" is valid. Cannot have multiple PHONY targets mixed up in a command declaration.

                EOF,
        ];

        yield 'two PHONY targets with the 2nd being a comment' => [
            [
                Rule::createPhony(['command1']),
                Rule::createPhony(['## command2']),
            ],
            <<<'EOF'
                Failed asserting that the rule ".PHONY: ## command2" is valid. Cannot have multiple PHONY targets mixed up in a command declaration.

                EOF,
        ];

        yield 'two PHONY targets with the 2nd being where a command should be' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command1', ['## Command1 Help']),
                Rule::createPhony(['command1']),
            ],
            <<<'EOF'
                Failed asserting that the rule ".PHONY: command1" is valid. Cannot have multiple PHONY targets mixed up in a command declaration.

                EOF,
        ];

        yield 'PHONY declaration with a help comment but no command' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command1', ['## Command1 Help']),
            ],
            <<<'EOF'
                Failed asserting that the rule ".PHONY: command1" is valid. No rule found after its declaration.

                EOF,
        ];

        yield 'PHONY declaration without a help comment neither a command' => [
            [
                Rule::createPhony(['command1']),
            ],
            <<<'EOF'
                Failed asserting that the rule ".PHONY: command1" is valid. No rule found after its declaration.

                EOF,
        ];

        yield 'command declaration with the help comment designating the wrong target' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command2', ['## Command2 Help']),
                new Rule('command1', ['progA', 'progB']),
            ],
            <<<'EOF'
                Failed asserting that the rule "command2: ## Command2 Help" has the same target as the previous PHONY declaration.
                --- Expected
                +++ Actual
                @@ @@
                -command1
                +command2

                EOF,
        ];

        yield 'command declaration with the command designating the wrong target' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command1', ['## Command1 Help']),
                new Rule('command2', ['progA', 'progB']),
            ],
            <<<'EOF'
                Failed asserting that the rule "command2: progA progB" has the same target as the previous PHONY declaration.
                --- Expected
                +++ Actual
                @@ @@
                -command1
                +command2

                EOF,
        ];

        yield 'the command comment is not a command comment' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command1', ['# Command1 Help']),
                new Rule('command1', ['progA', 'progB']),
            ],
            <<<'EOF'
                Failed asserting that the rule "command1: # Command1 Help" is a command help comment. It should either start with "##" to be one or made into a simple Makefile comment (without the target declaration).

                EOF,
        ];

        yield 'command with two help commands' => [
            [
                Rule::createPhony(['command1']),
                new Rule('command1', ['## Command1 Help1']),
                new Rule('command1', ['## Command1 Help2']),
                new Rule('command1', ['progA', 'progB']),
            ],
            <<<'EOF'
                Failed asserting that the rule "command1: ## Command1 Help2" is a command rule. A command should either have no help comment or only one. More than one found.

                EOF,
        ];

        yield 'non PHONY command' => [
            [
                new Rule('command', ['## Command Help']),
                new Rule('command', ['progA', 'progB']),
            ],
            null,
        ];

        yield 'non PHONY command with invalid comment' => [
            [
                new Rule('command1', ['## Command1 Help']),
                new Rule('command', ['progA', 'progB']),
            ],
            null,
        ];
    }
}
