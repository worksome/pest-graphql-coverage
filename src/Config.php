<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

final class Config
{
    /** @var list<string> */
    private static array $ignoredNodes = [];

    /** @var list<string> */
    private static array $deprecatedFields = [];

    private static bool $ignorePaginatorInfo = false;

    private static bool $ignoreDeprecatedFields = false;

    public static function new(): self
    {
        return new self();
    }

    /** @param list<string> $ignoredNodes */
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

    public function ignoreDeprecatedFields(bool $ignoreDeprecatedFields = true): self
    {
        self::$ignoreDeprecatedFields = $ignoreDeprecatedFields;

        return $this;
    }

    /**
     * @internal
     *
     * @return list<string>
     */
    public static function ignoredNodes(): array
    {
        return [
            ...self::$ignoredNodes,
            ...self::$deprecatedFields,
            ...self::getPaginatorInfoNodes(),
        ];
    }

    /** @internal */
    public static function shouldIgnoreDeprecatedFields(): bool
    {
        return self::$ignoreDeprecatedFields;
    }

    /** @internal */
    public static function addDeprecatedField(string $deprecatedField): void
    {
        self::$deprecatedFields[] = $deprecatedField;
    }

    /** @return list<string> */
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
