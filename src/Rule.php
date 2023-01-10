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

use function current;
use function implode;
use function rtrim;
use function sprintf;
use function str_starts_with;

final class Rule
{
    private const PHONY_TARGET = '.PHONY';

    private string $target;

    /**
     * @var list<string>
     */
    private array $prerequisites;

    /**
     * @param list<string> $prerequisites
     */
    public static function createPhony(array $prerequisites): self
    {
        return new self(self::PHONY_TARGET, $prerequisites);
    }

    /**
     * @param list<string> $prerequisites
     */
    public function __construct(string $target, array $prerequisites)
    {
        $this->target = $target;
        $this->prerequisites = $prerequisites;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function isPhony(): bool
    {
        return self::PHONY_TARGET === $this->target;
    }

    /**
     * @param list<string> $prerequisites
     */
    public function withAdditionalPrerequisites(array $prerequisites): self
    {
        return new self(
            $this->target,
            [
                ...$this->prerequisites,
                ...$prerequisites,
            ],
        );
    }

    public function isComment(): bool
    {
        $firstPrerequisite = current($this->prerequisites);

        if (false === $firstPrerequisite) {
            return false;
        }

        return str_starts_with($firstPrerequisite, '#');
    }

    public function isCommandComment(): bool
    {
        $firstPrerequisite = current($this->prerequisites);

        if (false === $firstPrerequisite) {
            return false;
        }

        return str_starts_with($firstPrerequisite, '##');
    }

    /**
     * @return list<string>
     */
    public function getPrerequisites(): array
    {
        return $this->prerequisites;
    }

    public function toString(): string
    {
        return rtrim(
            sprintf(
                '%s: %s',
                $this->target,
                implode(' ', $this->prerequisites),
            ),
        );
    }
}
