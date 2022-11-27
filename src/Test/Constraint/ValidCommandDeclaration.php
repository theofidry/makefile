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

namespace Fidry\Makefile\Test\Constraint;

use Fidry\Makefile\Rule;
use SebastianBergmann\Comparator\ComparisonFailure;
use function array_shift;
use function count;
use function get_debug_type;
use function is_array;
use function Safe\sprintf;

final class ValidCommandDeclaration extends BaseConstraint
{
    public function toString(): string
    {
        return 'is a Makefile "command" declaration';
    }

    public function checkOther($other, string $description): void
    {
        self::checkIsArrayOfRules($other);
        $this->checkRules($other, $description);
    }

    /**
     * @param mixed $other
     * @psalm-assert Rule[] $other
     *
     * @throws MatchingFailure
     */
    private static function checkIsArrayOfRules($other): void
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
    private function checkRules(array $rules, string $description): void
    {
        // TODO: add test that it doesn't mutate the rules
        // TODO: add test for non list rules
        $uncheckedRules = $rules;

        while (count($uncheckedRules) > 0) {
            $rule = array_shift($uncheckedRules);

            if (!$rule->isPhony()) {
                continue;
            }

            (new SinglePrerequisitePhony())->checkOther($rule, $description);
            /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
            $phonyTarget = $rule->getPrerequisites()[0];

            $nextRule = self::getNextRule($rule, $uncheckedRules);

            if (!$nextRule->isComment()) {
                $this->checkCommandRule(
                    $nextRule,
                    $phonyTarget,
                    $description,
                );

                continue;
            }

            $this->checkCommandComment(
                $nextRule,
                $phonyTarget,
                $description,
            );

            $this->checkCommandRule(
                self::getNextRule($rule, $uncheckedRules),
                $phonyTarget,
                $description,
            );
        }
    }

    /**
     * @param Rule[] $rules
     */
    private static function getNextRule(Rule $rule, array &$rules): Rule
    {
        $nextRule = array_shift($rules);

        if (null === $nextRule) {
            throw new MatchingFailure(
                sprintf(
                    'the rule "%s" is valid. No rule found after its declaration',
                    $rule->toString(),
                ),
            );
        }

        if ($nextRule->isPhony()) {
            throw new MatchingFailure(
                sprintf(
                    'the rule "%s" is valid. Cannot have multiple PHONY targets mixed up in a command declaration',
                    $nextRule->toString(),
                ),
            );
        }

        return $nextRule;
    }

    private function checkCommandComment(
        Rule $rule,
        string $phonyTarget,
        string $description
    ): void {
        $this->checkThatTargetIsMatching(
            $phonyTarget,
            $rule->getTarget(),
            sprintf(
                'the rule "%s" has the same target as the previous PHONY declaration',
                $rule->toString(),
            ),
            $description,
        );

        if (!$rule->isCommandComment()) {
            throw new MatchingFailure(
                sprintf(
                    'the rule "%s" is a command help comment. It should either start with "##" to be one or made into a simple Makefile comment (without the target declaration)',
                    $rule->toString(),
                ),
            );
        }
    }

    private function checkCommandRule(
        Rule $rule,
        string $phonyTarget,
        string $description
    ): void {
        $this->checkThatTargetIsMatching(
            $phonyTarget,
            $rule->getTarget(),
            sprintf(
                'the rule "%s" has the same target as the previous PHONY declaration',
                $rule->toString(),
            ),
            $description,
        );

        // TODO: test for regular comment
        if ($rule->isCommandComment()) {
            throw new MatchingFailure(
                sprintf(
                    'the rule "%s" is a command rule. A command should either have no help comment or only one. More than one found',
                    $rule->toString(),
                ),
            );
        }
    }

    private function checkThatTargetIsMatching(
        string $expectedTarget,
        string $ruleTarget,
        string $failureDescription,
        string $description
    ): void {
        if ($ruleTarget !== $expectedTarget) {
            $this->failureDescription = $failureDescription;

            $comparisonFailure = new ComparisonFailure(
                $expectedTarget,
                $ruleTarget,
                $expectedTarget,
                $ruleTarget,
            );

            $this->fail(null, $description, $comparisonFailure);
        }
    }
}
