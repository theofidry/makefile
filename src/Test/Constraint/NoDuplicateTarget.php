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
use function array_key_exists;
use function get_debug_type;
use function is_array;
use function sprintf;

final class NoDuplicateTarget extends BaseConstraint
{
    public function toString(): string
    {
        return 'is not declared twice';
    }

    public function checkOther($other, string $description): void
    {
        self::checkIsArrayOfRules($other);
        self::checkDuplicates($other);
    }

    /**
     * @psalm-assert Rule[] $other
     * @throws MatchingFailure
     */
    private static function checkIsArrayOfRules(mixed $other): void
    {
        if (!is_array($other)) {
            throw new MatchingFailure(
                sprintf(
                    'the value to be an array of "%s" instances. Got "%s"',
                    Rule::class,
                    get_debug_type($other),
                ),
            );
        }

        foreach ($other as $index => $value) {
            if (!($value instanceof Rule)) {
                throw new MatchingFailure(
                    sprintf(
                        'the value to be an array of "%s" instances. Got "%s" for the item of index "%s"',
                        Rule::class,
                        get_debug_type($value),
                        $index,
                    ),
                );
            }
        }
    }

    /**
     * @param Rule[] $rules
     */
    private static function checkDuplicates(array $rules): void
    {
        $targetCounts = self::collectTargetCounts($rules);

        foreach ($targetCounts as $target => $count) {
            if ($count > 1) {
                throw new MatchingFailure(
                    sprintf(
                        'the target "%s" is declared only once. Found %d declarations',
                        $target,
                        $count,
                    ),
                );
            }
        }
    }

    /**
     * @param Rule[] $rules
     *
     * @return array<string, positive-int>
     */
    private static function collectTargetCounts(array $rules): array
    {
        $targetCounts = [];

        foreach ($rules as $rule) {
            if ($rule->isPhony() || $rule->isComment()) {
                continue;
            }

            $target = $rule->getTarget();

            if (array_key_exists($target, $targetCounts)) {
                ++$targetCounts[$target];
            } else {
                $targetCounts[$target] = 1;
            }
        }

        return $targetCounts;
    }
}
