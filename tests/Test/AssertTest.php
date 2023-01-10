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

namespace Fidry\Makefile\Tests\Test;

use Fidry\Makefile\Rule;
use Fidry\Makefile\Test\Assert;
use Fidry\Makefile\Tests\Test\Constraint\NoDuplicateTargetTest;
use Fidry\Makefile\Tests\Test\Constraint\SinglePrerequisitePhonyTest;
use Fidry\Makefile\Tests\Test\Constraint\ValidCommandDeclarationTest;
use Fidry\Makefile\Tests\ThrowableToStringMapper;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use function is_array;

/**
 * @covers \Fidry\Makefile\Test\Assert
 *
 * @internal
 */
final class AssertTest extends TestCase
{
    /**
     * @dataProvider singlePrerequisitePhonyProvider
     */
    public function test_it_tests_that_a_rule_is_phony_and_has_one_and_only_one_pre_requisite(
        Rule $rule,
        ?string $expectedAssertionFailureMessage
    ): void {
        $assert = static function () use ($rule): void { Assert::assertSinglePrerequisitePhony($rule); };

        if (null === $expectedAssertionFailureMessage) {
            $assert();

            return;
        }

        self::assertAssertionFails(
            $assert,
            $expectedAssertionFailureMessage,
        );
    }

    public static function singlePrerequisitePhonyProvider(): iterable
    {
        foreach (SinglePrerequisitePhonyTest::valueProvider() as $label => $set) {
            $rule = $set[0];

            if (!$rule instanceof Rule) {
                continue;
            }

            yield $label => $set;
        }
    }

    /**
     * @dataProvider phonyDeclarationsProvider
     *
     * @param Rule[] $rules
     */
    public function test_it_tests_that_the_phony_declarations_declare_a_command(
        $rules,
        ?string $expectedAssertionFailureMessage
    ): void {
        $assert = static function () use ($rules): void { Assert::assertHasValidPhonyTargetDeclarations($rules); };

        if (null === $expectedAssertionFailureMessage) {
            $assert();

            return;
        }

        self::assertAssertionFails(
            $assert,
            $expectedAssertionFailureMessage,
        );
    }

    public static function phonyDeclarationsProvider(): iterable
    {
        foreach (ValidCommandDeclarationTest::valueProvider() as $label => $set) {
            $rules = $set[0];

            if (!is_array($rules)) {
                continue;
            }

            foreach ($rules as $rule) {
                if (!$rule instanceof Rule) {
                    continue 2;
                }
            }

            yield $label => $set;
        }
    }

    /**
     * @dataProvider duplicateTargetProvider
     *
     * @param Rule[] $rules
     */
    public function test_it_tests_that_no_target_is_declared_twice(
        $rules,
        ?string $expectedAssertionFailureMessage
    ): void {
        $assert = static function () use ($rules): void { Assert::assertNoDuplicateTarget($rules); };

        if (null === $expectedAssertionFailureMessage) {
            $assert();

            return;
        }

        self::assertAssertionFails(
            static fn () => Assert::assertNoDuplicateTarget($rules),
            $expectedAssertionFailureMessage,
        );
    }

    public static function duplicateTargetProvider(): iterable
    {
        foreach (NoDuplicateTargetTest::valueProvider() as $label => $set) {
            $rules = $set[0];

            if (!is_array($rules)) {
                continue;
            }

            foreach ($rules as $rule) {
                if (!$rule instanceof Rule) {
                    continue 2;
                }
            }

            yield $label => $set;
        }
    }

    /**
     * @param callable():void $assert
     */
    private static function assertAssertionFails(
        callable $assert,
        string $expectedAssertionFailureMessage
    ): void {
        try {
            $assert();
        } catch (ExpectationFailedException $assertionFailed) {
            // Continue
        }

        if (!isset($assertionFailed)) {
            self::fail('Expected assertion failure.');
        }

        self::assertSame(
            $expectedAssertionFailureMessage,
            ThrowableToStringMapper::map($assertionFailed),
        );
    }
}
