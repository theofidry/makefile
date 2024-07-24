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

namespace Fidry\Makefile\Tests;

use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\PHPTAssertionFailedError;
use PHPUnit\Framework\SelfDescribing;
use Throwable;
use function sprintf;

final class ThrowableToStringMapper
{
    public static function map(Throwable $throwable): string
    {
        if ($throwable instanceof SelfDescribing) {
            $buffer = $throwable->toString();

            if ($throwable instanceof ExpectationFailedException && $throwable->getComparisonFailure()) {
                $buffer .= $throwable->getComparisonFailure()->getDiff();
            }

            /** @psalm-suppress UndefinedClass */
            if ($throwable instanceof PHPTAssertionFailedError) {
                /** @psalm-suppress UndefinedMethod */
                $buffer .= $throwable->diff();
            }

            if (!empty($buffer)) {
                $buffer = trim($buffer)."\n";
            }

            return $buffer;
        }

        return sprintf(
            "%s: %s\n",
            $throwable::class,
            $throwable->getMessage(),
        );
    }

    private function __construct()
    {
    }
}
