<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

final class Config
{
    /** @var array<string, mixed> */
    private static array $ignoredNodes = [];

    public static function new(): self
    {
        return new self();
    }

    /** @param  array<string>  $ignoredNodes */
    public function ignore(array $ignoredNodes): self
    {
        self::$ignoredNodes = array_merge(self::$ignoredNodes, array_flip($ignoredNodes));

        return $this;
    }

    /** @return array<string, mixed> */
    public static function ignoredNodes(): array
    {
        return self::$ignoredNodes;
    }
}
