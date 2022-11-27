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

namespace Fidry\Makefile\Test;

use Fidry\Makefile\Rule;
use Fidry\Makefile\Test\Constraint\NoDuplicateTarget;
use Fidry\Makefile\Test\Constraint\SinglePrerequisitePhony;
use Fidry\Makefile\Test\Constraint\ValidCommandDeclaration;
use PHPUnit\Framework\Assert as PHPUnitAssert;
use PHPUnit\Framework\ExpectationFailedException;

final class Assert extends PHPUnitAssert
{
    /**
     * Asserts that the rule is a .PHONY rule with one and only one pre-requisite.
     *
     * @throws ExpectationFailedException
     */
    public static function assertSinglePrerequisitePhony(Rule $rule, string $message = ''): void
    {
        $constraint = new SinglePrerequisitePhony();

        self::assertThat($rule, $constraint, $message);
    }

    /**
     * Asserts that the rules have valid PHONY target declarations, i.e. it
     * declares a single target, is optionally followed by a comment line and
     * then a rule to declare the command itself.
     *
     * @param list<Rule> $rules
     */
    public static function assertHasValidPhonyTargetDeclarations(
        array $rules,
        string $message = ''
    ): void {
        $constraint = new ValidCommandDeclaration();

        self::assertThat($rules, $constraint, $message);
    }

    /**
     * Asserts that the a rule target is not declared twice.
     *
     * @param list<Rule> $rules
     */
    public static function assertNoDuplicateTarget(
        array $rules,
        string $message = ''
    ): void {
        $constraint = new NoDuplicateTarget();

        self::assertThat($rules, $constraint, $message);
    }
}
