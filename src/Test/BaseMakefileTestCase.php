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

use Fidry\Makefile\Parser;
use PHPUnit\Framework\TestCase;
use Safe\Exceptions\ExecException;
use Throwable;
use function current;
use function Safe\file_get_contents;
use function implode;
use function Safe\shell_exec;
use function Safe\sprintf;

abstract class BaseMakefileTestCase extends TestCase
{
    /**
     * @readonly
     * @var list<array{string, list<string>}>|null
     */
    protected static ?array $parsedMakefile = null;

    abstract protected static function getMakefilePath(): string;

    abstract protected function getExpectedHelpOutput(): string;

    public static function setUpBeforeClass(): void
    {
        try {
            static::$parsedMakefile = Parser::parse(
                file_get_contents(static::getMakefilePath()),
            );
        } catch (Throwable $throwable) {
            // Continue
            static::$parsedMakefile = null;
        }
    }

    public static function tearDownAfterClass(): void
    {
        static::$parsedMakefile = null;
    }

    final public function test_it_has_a_help_command(): void
    {
        try {
            shell_exec('command -v timeout');
            $timeout = 'timeout 2s';
        } catch (ExecException $execException) {
            $timeout = '';
        }

        $output = shell_exec(
            sprintf(
                '%s make help --silent --file %s 2>&1',
                $timeout,
                static::getMakefilePath(),
            ),
        );
        $expectedOutput = $this->getExpectedHelpOutput();

        self::assertSame($expectedOutput, $output);
    }

    final public function test_the_makefile_can_be_parsed(): void
    {
        self::assertTrue(isset(static::$parsedMakefile));
    }

    final public function test_phony_targets_are_correctly_declared(): void
    {
        $phony = null;
        $targetComment = false;
        $matchedPhony = true;

        foreach (static::$parsedMakefile as [$target, $dependencies]) {
            if ('.PHONY' === $target) {
                self::assertCount(
                    1,
                    $dependencies,
                    sprintf(
                        'Expected one target to be declared as .PHONY. Found: "%s"',
                        implode('", "', $dependencies)
                    )
                );

                $previousPhony = $phony;
                $phony = current($dependencies);

                self::assertTrue(
                    $matchedPhony,
                    sprintf(
                        '"%s" has been declared as a .PHONY target but no such target could '
                        .'be found',
                        $previousPhony
                    )
                );

                $targetComment = false;
                $matchedPhony = false;

                continue;
            }

            if ([] !== $dependencies && str_starts_with($dependencies[0], '#')) {
                self::assertStringStartsWith(
                    '## ',
                    $dependencies[0],
                    'Expected the target comment to be a documented comment'
                );

                self::assertSame(
                    $phony,
                    $target,
                    'Expected the declared target to match the previous declared .PHONY'
                );

                self::assertFalse(
                    $targetComment,
                    sprintf(
                        'Did not expect to find twice the target comment line for "%s"',
                        $target
                    )
                );

                self::assertFalse(
                    $matchedPhony,
                    sprintf(
                        'Did not expect to find the target comment line before its target '
                        .'definition for "%s"',
                        $target
                    )
                );

                $targetComment = true;

                continue;
            }

            if (null !== $phony && false === $matchedPhony) {
                $matchedPhony = true;

                self::assertSame(
                    $phony,
                    $target,
                    'Expected the declared target to match the previous declared .PHONY'
                );

                continue;
            }

            $phony = null;
            $targetComment = false;
            $matchedPhony = false;
        }
    }

    final public function test_no_target_is_being_declared_twice(): void
    {
        $targetCounts = [];

        foreach (static::$parsedMakefile as [$target, $dependencies]) {
            if ('.PHONY' === $target) {
                continue;
            }

            if ([] !== $dependencies && str_starts_with($dependencies[0], '## ')) {
                continue;
            }

            if (array_key_exists($target, $targetCounts)) {
                ++$targetCounts[$target];
            } else {
                $targetCounts[$target] = 1;
            }
        }

        foreach ($targetCounts as $target => $count) {
            self::assertSame(
                1,
                $count,
                sprintf('Expected to find only one declaration for the target "%s"', $target)
            );
        }
    }
}
