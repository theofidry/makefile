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

use Fidry\Makefile\Rule;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Fidry\Makefile\Rule
 *
 * @internal
 */
final class RuleTest extends TestCase
{
    public function test_it_can_be_instantiated(): void
    {
        $target = ' targets ';
        $prerequisites = [' $(objects)', 'prog.o'];

        $rule = new Rule($target, $prerequisites);

        self::assertStateIs(
            $target,
            $prerequisites,
            $rule,
        );
    }

    public function test_it_can_merge_prerequisites(): void
    {
        $target = ' targets ';
        $prerequisites = [' $(objects)', 'prog.o'];
        $extraPrerequisites = ['prog.o', 'bom'];

        $rule = new Rule($target, $prerequisites);
        $newRule = $rule->withAdditionalPrerequisites($extraPrerequisites);

        self::assertStateIs(
            $target,
            $prerequisites,
            $rule,
        );
        self::assertStateIs(
            $target,
            [' $(objects)', 'prog.o', 'prog.o', 'bom'],
            $newRule,
        );
    }

    /**
     * @dataProvider phonyTargetProvider
     */
    public function test_it_can_detect_if_a_target_is_phony(Rule $rule, bool $expected): void
    {
        self::assertSame($expected, $rule->isPhony());
    }

    public static function phonyTargetProvider(): iterable
    {
        yield 'standard target' => [
            new Rule('command', []),
            false,
        ];

        yield 'PHONY target' => [
            new Rule('.PHONY', []),
            true,
        ];

        yield 'PHONY like target (extra space)' => [
            new Rule('.PHONY ', []),
            false,
        ];

        yield 'PHONY like target (missing dot)' => [
            new Rule('PHONY', []),
            false,
        ];

        yield 'PHONY like target (invalid case)' => [
            new Rule('.phony', []),
            false,
        ];
    }

    /**
     * @dataProvider makefileCommentProvider
     */
    public function test_it_can_detect_the_rule_is_a_target_with_a_makefile_comment(
        Rule $rule,
        bool $expected
    ): void {
        self::assertSame($expected, $rule->isComment());
    }

    public static function makefileCommentProvider(): iterable
    {
        yield 'rule' => [
            new Rule('command', ['progA', 'progB']),
            false,
        ];

        yield 'PHONY rule' => [
            Rule::createPhony(['progA', 'progB']),
            false,
        ];

        yield 'rule with Makefile comment' => [
            new Rule('command', ['#progA progB']),
            true,
        ];

        yield 'rule with command comment' => [
            new Rule('command', ['##progA progB']),
            true,
        ];

        yield 'rule with extra comment marker' => [
            new Rule('command', ['###progA progB']),
            true,
        ];

        yield 'PHONY rule with Makefile comment' => [
            Rule::createPhony(['#progA progB']),
            true,
        ];

        yield 'rule with no pre-requisite' => [
            new Rule('command', []),
            false,
        ];
    }

    /**
     * @dataProvider commandCommentProvider
     */
    public function test_it_can_detect_the_rule_is_a_target_with_a_command_comment(
        Rule $rule,
        bool $expected
    ): void {
        self::assertSame($expected, $rule->isCommandComment());
    }

    public static function commandCommentProvider(): iterable
    {
        yield 'rule' => [
            new Rule('command', ['progA', 'progB']),
            false,
        ];

        yield 'PHONY rule' => [
            Rule::createPhony(['progA', 'progB']),
            false,
        ];

        yield 'rule with Makefile comment' => [
            new Rule('command', ['#progA progB']),
            false,
        ];

        yield 'rule with command comment' => [
            new Rule('command', ['##progA progB']),
            true,
        ];

        yield 'rule with extra comment marker' => [
            new Rule('command', ['###progA progB']),
            true,
        ];

        yield 'PHONY rule with Makefile comment' => [
            Rule::createPhony(['#progA progB']),
            false,
        ];

        yield 'rule with no pre-requisite' => [
            new Rule('command', []),
            false,
        ];
    }

    /**
     * @param list<string> $expectedPrerequisites
     */
    private static function assertStateIs(
        string $expectedTarget,
        array $expectedPrerequisites,
        Rule $rule
    ): void {
        self::assertSame($expectedTarget, $rule->getTarget());
        self::assertSame($expectedPrerequisites, $rule->getPrerequisites());
    }
}
