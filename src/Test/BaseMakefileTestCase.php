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

namespace Fidry\Makefile\Test;

use Fidry\Makefile\Parser;
use Fidry\Makefile\Rule;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use function array_filter;
use function chdir;
use function dirname;
use function explode;
use function file_get_contents;
use function getcwd;
use function getenv;
use function implode;
use function shell_exec;
use function sprintf;

abstract class BaseMakefileTestCase extends TestCase
{
    /**
     * @readonly
     * @var list<Rule>|null
     */
    protected static ?array $parsedRules = null;

    abstract protected static function getMakefilePath(): string;

    abstract protected function getExpectedHelpOutput(): string;

    public static function setUpBeforeClass(): void
    {
        static::getParsedRules();
    }

    public static function tearDownAfterClass(): void
    {
        static::$parsedRules = null;
    }

    /**
     * @return list<Rule>
     */
    final protected static function getParsedRules(): array
    {
        if (!isset(static::$parsedRules)) {
            static::$parsedRules = Parser::parse(
                self::safeFileGetContents(static::getMakefilePath()),
            );
        }

        return static::$parsedRules;
    }

    final public function test_it_has_a_help_command(): void
    {
        $output = self::executeMakeCommand('help');
        $expectedOutput = $this->getExpectedHelpOutput();

        self::assertSame($expectedOutput, $output);
    }

    final public function test_phony_targets_are_correctly_declared(): void
    {
        Assert::assertHasValidPhonyTargetDeclarations(
            static::getParsedRules(),
        );
    }

    final public function test_no_target_is_being_declared_multiple_times(): void
    {
        Assert::assertNotNull(
            static::getParsedRules(),
        );
    }

    final protected static function executeMakeCommand(string $commandName): string
    {
        $timeout = self::getTimeout();
        $makefilePath = static::getMakefilePath();

        $makeCommand = sprintf(
            '%s make %s --silent --file %s 2>&1',
            $timeout,
            $commandName,
            $makefilePath,
        );

        return self::executeInDirectory(
            dirname($makefilePath),
            static fn (): string => self::executeCommand($makeCommand),
        );
    }

    final protected static function executeCommand(string $command): string
    {
        $command = sprintf(
            'MAKEFLAGS="%s" %s',
            self::getNonDebugMakeFlags(),
            $command,
        );

        return self::safeShellExec($command);
    }

    final protected static function getNonDebugMakeFlags(): string
    {
        $makeFlags = (string) getenv('MAKEFLAGS');

        $nonDebugFlags = array_filter(
            explode(' ', $makeFlags),
            static fn (string $flag) => !str_starts_with($flag, '--debug='),
        );

        return implode(' ', $nonDebugFlags);
    }

    private static function getTimeout(): string
    {
        try {
            self::executeCommand('command -v timeout');

            return 'timeout 2s';
        } catch (RuntimeException) {
            return '';
        }
    }

    /**
     * @param callable(): string $execute
     */
    private static function executeInDirectory(string $directory, callable $execute): string
    {
        $currentWorkingDirectory = self::safeGetCurrentWorkingDirectory();
        self::safeChdir($directory);

        try {
            return $execute();
        } finally {
            self::safeChdir($currentWorkingDirectory);
        }
    }

    private static function safeChdir(string $directory): void
    {
        $chdirResult = chdir($directory);

        if (!$chdirResult) {
            throw new RuntimeException(
                sprintf(
                    'Could not change the current working directory to "%s".',
                    $directory,
                ),
            );
        }
    }

    private static function safeGetCurrentWorkingDirectory(): string
    {
        $result = getcwd();

        if (false === $result) {
            throw new RuntimeException('Could not get the current working directory.');
        }

        return $result;
    }

    private static function safeFileGetContents(string $filename): string
    {
        $result = file_get_contents($filename);

        if (false === $result) {
            throw new RuntimeException(
                sprintf(
                    'Could not read the contents of the file "%s".',
                    $filename,
                ),
            );
        }

        return $result;
    }

    private static function safeShellExec(string $command): string
    {
        $result = shell_exec($command);

        if (null === $result || false === $result) {
            throw new RuntimeException(
                sprintf(
                    'Could not execute the command "%s".',
                    $command,
                ),
            );
        }

        return $result;
    }
}
