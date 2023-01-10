<?php

/*
 * This code is licensed under the BSD 3-Clause License.
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
use Fidry\Makefile\Test\Constraint\NoDuplicateTarget;
use stdClass;

/**
 * @covers \Fidry\Makefile\Test\Constraint\NoDuplicateTarget
 *
 * @internal
 */
final class NoDuplicateTargetTest extends ConstraintTestCase
{
    /**
     * @dataProvider valueProvider
     *
     * @param mixed $value
     */
    public function test_it_tests_that_no_target_is_declared_twice(
        $value,
        ?string $expectedExpectationFailureMessage
    ): void {
        $constraint = new NoDuplicateTarget();

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

        yield 'target declared twice but with one occurrence being a comment' => [
            [
                new Rule('command', ['## Command Help']),
                new Rule('command', ['progA', 'progB']),
            ],
            null,
        ];

        yield 'two distinct targets' => [
            [
                new Rule('command1', ['progA', 'progB']),
                new Rule('command2', ['progA', 'progC']),
            ],
            null,
        ];

        yield 'target declared 3 times' => [
            [
                new Rule('command1', ['progA']),
                new Rule('command1', ['progB']),
                new Rule('command1', ['progC']),
            ],
            <<<'EOF'
                Failed asserting that the target "command1" is declared only once. Found 3 declarations.

                EOF,
        ];

        yield 'PHONY target declared twice' => [
            [
                Rule::createPhony(['command1']),
                Rule::createPhony(['command1']),
            ],
            null,
        ];

        yield 'target declared twice with other rules in-between' => [
            [
                new Rule('command1', ['progA']),
                new Rule('command2', ['progB']),
                new Rule('command1', ['progC']),
            ],
            <<<'EOF'
                Failed asserting that the target "command1" is declared only once. Found 2 declarations.

                EOF,
        ];

        yield 'target declared twice with other rules before' => [
            [
                new Rule('command2', ['progB']),
                new Rule('command1', ['progA']),
                new Rule('command1', ['progC']),
            ],
            <<<'EOF'
                Failed asserting that the target "command1" is declared only once. Found 2 declarations.

                EOF,
        ];

        yield 'target declared twice with a comment in-between' => [
            [
                new Rule('command1', ['progA']),
                new Rule('command2', ['## progB']),
                new Rule('command1', ['progC']),
            ],
            <<<'EOF'
                Failed asserting that the target "command1" is declared only once. Found 2 declarations.

                EOF,
        ];
    }
}
