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

use Fidry\Makefile\Tests\ThrowableToStringMapper;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use function get_debug_type;
use function sprintf;

abstract class ConstraintTestCase extends TestCase
{
    /**
     * @param mixed $other
     */
    final protected static function assertConstraintDoesNotFail(Constraint $constraint, $other): void
    {
        $result = $constraint->evaluate($other);

        self::assertNull($result);
    }

    /**
     * @param mixed $other
     */
    final protected static function assertConstraintFails(
        Constraint $constraint,
        $other,
        string $expectedAssertionFailureMessage
    ): void {
        try {
            $result = $constraint->evaluate($other);
        } catch (ExpectationFailedException $expectationFailed) {
            // Continue
        }

        /** @psalm-suppress PossiblyUndefinedVariable */
        if (!isset($expectationFailed)) {
            self::fail(
                sprintf(
                    'Expected assertion failure. The evaluation of the constraint returned "%s"',
                    get_debug_type($result),
                ),
            );
        }

        self::assertSame(
            $expectedAssertionFailureMessage,
            ThrowableToStringMapper::map($expectationFailed),
        );
    }
}
