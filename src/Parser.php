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

namespace Fidry\Makefile;

use RuntimeException;
use function array_filter;
use function array_map;
use function array_pop;
use function array_reduce;
use function array_values;
use function count;
use function error_clear_last;
use function explode;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use const PHP_EOL;

final class Parser
{
    private const COMMENT_LINE = '/(?<nonCommentPart>.*?)(?<commentPart>#.*)/';
    private const MULTILINE_DELIMITER = '\\';

    /**
     * @return list<Rule>
     */
    public static function parse(string $makeFileContents): array
    {
        $multiline = false;
        $ignoreNextLinesOfMultiline = false;

        return array_reduce(
            explode(PHP_EOL, $makeFileContents),
            static function (array $parsedRules, string $line) use (&$multiline, &$ignoreNextLinesOfMultiline) {
                return self::parseLine(
                    $parsedRules,
                    $line,
                    $multiline,
                    $ignoreNextLinesOfMultiline,
                );
            },
            [],
        );
    }

    /**
     * @param Rule[] $parsedRules
     *
     * @return list<Rule>
     */
    private static function parseLine(
        array $parsedRules,
        string $line,
        bool &$multiline,
        bool &$ignoreNextLinesOfMultiline,
    ): array {
        $parsedRules = array_values($parsedRules);
        $line = mb_rtrim($line);

        if (!self::isRule($line, $multiline)) {
            return $parsedRules;
        }

        $previousMultiline = $multiline;
        $multiline = self::isMultiline($line);

        if (false === $previousMultiline) {
            $targetParts = explode(':', $line);

            if (2 !== count($targetParts)) {
                return $parsedRules;
            }

            [$target, $prerequisites] = $targetParts;

            $rule = new Rule(
                mb_rtrim($target),
                self::parsePrerequisites($prerequisites, $multiline, $ignoreNextLinesOfMultiline),
            );
        } else {
            /** @var Rule $rule */
            $rule = array_pop($parsedRules);

            $rule = $rule->withAdditionalPrerequisites(
                self::parsePrerequisites($line, $multiline, $ignoreNextLinesOfMultiline),
            );
        }

        $parsedRules[] = $rule;

        return $parsedRules;
    }

    private static function isRule(string $line, bool $previousMultiline): bool
    {
        return (
            !str_starts_with($line, '#')
            && !str_starts_with($line, "\t")
            && 0 === self::safePregMatch('/\S+=.+/', $line)
        ) || $previousMultiline;
    }

    /**
     * @return list<string>
     */
    private static function parsePrerequisites(
        string $dependencies,
        bool $multiline,
        bool &$ignoreNextLinesOfMultiline,
    ): array {
        if (($ignoreNextLinesOfMultiline && $multiline)
            || '' === $dependencies
        ) {
            return [];
        }

        if ($multiline) {
            $dependenciesParts = explode(self::MULTILINE_DELIMITER, $dependencies);

            return [
                ...self::parsePrerequisites($dependenciesParts[0], false, $ignoreNextLinesOfMultiline),
                // There cannot be more parts by design
                ...self::parsePrerequisites($dependenciesParts[1] ?? '', false, $ignoreNextLinesOfMultiline),
            ];
        }

        if (str_starts_with($dependencies, '#')) {
            return [$dependencies];
        }

        if (str_contains($dependencies, '#')) {
            [$nonCommentPart, $commentPart] = self::splitComment($dependencies);

            return [
                ...self::parsePrerequisites($nonCommentPart, false, $ignoreNextLinesOfMultiline),
                ...self::parsePrerequisites($commentPart, false, $ignoreNextLinesOfMultiline),
            ];
        }

        $semicolonPosition = mb_strpos($dependencies, ';');

        if (false !== $semicolonPosition) {
            $dependencies = mb_substr($dependencies, 0, $semicolonPosition);
            $ignoreNextLinesOfMultiline = true;
        }

        return array_values(
            array_filter(
                array_map(
                    static fn (string $dependency) => mb_ltrim($dependency, "\t"),
                    explode(' ', $dependencies),
                ),
            ),
        );
    }

    private static function isMultiline(string $line): bool
    {
        $lineWithoutComment = mb_rtrim(self::trimComment($line));

        return str_ends_with($lineWithoutComment, self::MULTILINE_DELIMITER);
    }

    private static function trimComment(string $line): string
    {
        return self::splitComment($line)[0];
    }

    /**
     * @psalm-suppress InvalidArrayAccess, PossiblyNullArrayAccess
     *
     * @return array{string, string}
     */
    private static function splitComment(string $line): array
    {
        return self::safePregMatch(self::COMMENT_LINE, $line, $matches)
            ? [
                $matches['nonCommentPart'],
                $matches['commentPart'],
            ]
            : [$line, ''];
    }

    /**
     * @psalm-suppress ArgumentTypeCoercion
     */
    private static function safePregMatch(string $pattern, string $subject, ?iterable &$matches = null, int $flags = 0, int $offset = 0): int
    {
        error_clear_last();
        $safeResult = preg_match($pattern, $subject, $matches, $flags, $offset);
        if (false === $safeResult) {
            throw new RuntimeException(
                sprintf(
                    'An error occurred while executing the regular expression "%s" on subject "%s".',
                    $pattern,
                    $subject,
                ),
            );
        }

        return $safeResult;
    }
}
