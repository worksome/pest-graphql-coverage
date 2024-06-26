<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

final class Config
{
    /** @return array<int, string> */
    private static array $ignoredNodes = [];

    private static bool $ignorePaginatorInfo = false;

    public static function new(): self
    {
        return new self();
    }

    /** @param array<int, string> $ignoredNodes */
    public function ignore(array $ignoredNodes): self
    {
        self::$ignoredNodes = [
            ...self::$ignoredNodes,
            ...$ignoredNodes,
        ];

        return $this;
    }

    public function ignorePaginatorInfo(bool $ignorePaginatorInfo = true): self
    {
        self::$ignorePaginatorInfo = $ignorePaginatorInfo;

        return $this;
    }

    /** @return array<int, string> */
    public static function ignoredNodes(): array
    {
        return [
            ...self::$ignoredNodes,
            ...self::getPaginatorInfoNodes(),
        ];
    }

    /** @return array<int, string> */
    private static function getPaginatorInfoNodes(): array
    {
        return self::$ignorePaginatorInfo ? [
            '*.paginatorInfo',
            'PaginatorInfo.*',
            'SimplePaginatorInfo.*',
            'PageInfo.*',
        ] : [];
    }
}
