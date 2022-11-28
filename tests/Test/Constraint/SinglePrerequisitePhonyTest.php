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
use Fidry\Makefile\Test\Constraint\SinglePrerequisitePhony;
use stdClass;

/**
 * @covers \Fidry\Makefile\Test\Constraint\SinglePrerequisitePhony
 *
 * @internal
 */
final class SinglePrerequisitePhonyTest extends ConstraintTestCase
{
    /**
     * @dataProvider valueProvider
     *
     * @param mixed $value
     */
    public function test_it_tests_that_a_rule_is_phony_and_has_one_and_only_one_pre_requisite(
        $value,
        ?string $expectedExpectationFailureMessage
    ): void {
        $constraint = new SinglePrerequisitePhony();

        null === $expectedExpectationFailureMessage
            ? self::assertConstraintDoesNotFail($constraint, $value)
            : self::assertConstraintFails($constraint, $value, $expectedExpectationFailureMessage);
    }

    public static function valueProvider(): iterable
    {
        yield 'nominal' => [
            Rule::createPhony(['command']),
            null,
        ];

        yield 'empty PHONY target' => [
            Rule::createPhony([]),
            <<<'EOF'
                Failed asserting that the rule ".PHONY:" has one and only one pre-requisite. 0 pre-requisite found.

                EOF,
        ];

        yield 'PHONY target with multiple targets' => [
            Rule::createPhony(['command1', 'command2']),
            <<<'EOF'
                Failed asserting that the rule ".PHONY: command1 command2" has one and only one pre-requisite. 2 pre-requisites found.

                EOF,
        ];

        yield 'non phony rule' => [
            new Rule('command', ['object']),
            <<<'EOF'
                Failed asserting that the rule "command: object" is not a .PHONY rule.

                EOF,
        ];

        yield 'non rule object' => [
            new stdClass(),
            <<<'EOF'
                Failed asserting that the value to be a "Fidry\Makefile\Rule" instance. Got "stdClass".

                EOF,
        ];
    }
}
