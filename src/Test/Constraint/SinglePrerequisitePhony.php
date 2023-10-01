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

namespace Fidry\Makefile\Test\Constraint;

use Fidry\Makefile\Rule;
use function count;
use function get_debug_type;
use function Safe\sprintf;

final class SinglePrerequisitePhony extends BaseConstraint
{
    public function toString(): string
    {
        return 'is a .PHONY rule with one and only one pre-requisite';
    }

    public function checkOther($other, string $description): void
    {
        self::checkIsRuleInstance($other);
        self::checkIsPhonyRule($other);
        self::checkHasOnePrerequisite($other);
    }

    /**
     * @psalm-assert Rule $other
     * @throws MatchingFailure
     */
    private static function checkIsRuleInstance(mixed $other): void
    {
        if (!($other instanceof Rule)) {
            throw new MatchingFailure(
                sprintf(
                    'the value to be a "%s" instance. Got "%s"',
                    Rule::class,
                    get_debug_type($other),
                ),
            );
        }
    }

    /**
     * @throws MatchingFailure
     */
    private static function checkIsPhonyRule(Rule $rule): void
    {
        if (!$rule->isPhony()) {
            throw new MatchingFailure(
                sprintf(
                    'the rule "%s" is not a .PHONY rule',
                    $rule->toString(),
                ),
            );
        }
    }

    /**
     * @throws MatchingFailure
     */
    private static function checkHasOnePrerequisite(Rule $rule): void
    {
        $prerequisitesCount = count($rule->getPrerequisites());

        if (1 !== $prerequisitesCount) {
            throw new MatchingFailure(
                sprintf(
                    'the rule "%s" has one and only one pre-requisite. %d pre-requisite%s found',
                    $rule->toString(),
                    $prerequisitesCount,
                    $prerequisitesCount > 1 ? 's' : '',
                ),
            );
        }
    }
}
