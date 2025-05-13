<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

use GraphQL\Language\AST\DirectiveNode;
use Nuwave\Lighthouse\Schema\Values\FieldValue;

class Collector
{
    private function __construct()
    {
    }

    public static function reset(): void
    {
        $filePath = self::filePath();

        if (! file_exists($filePath)) {
            return;
        }

        unlink($filePath);
    }

    /** @return array<int, string> */
    public static function parseResult(): array
    {
        $data = file_get_contents(self::filePath());
        assert(is_string($data));

        return explode(PHP_EOL, $data);
    }

    public static function addResult(FieldValue $fieldValue): void
    {
        if (Config::shouldIgnoreDeprecatedFields() && self::isFieldDeprecated($fieldValue)) {
            return;
        }

        $filePath = self::filePath();
        $stream = fopen($filePath, 'a');
        assert(is_resource($stream));

        fwrite($stream, sprintf(
            '%s.%s%s',
            $fieldValue->getParentName(),
            $fieldValue->getFieldName(),
            PHP_EOL
        ));

        fclose($stream);
    }

    public static function filePath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            dirname(__DIR__),
            '.temp',
            'gql-coverage.php',
        ]);
    }

    private static function isFieldDeprecated(FieldValue $fieldValue): bool
    {
        foreach ($fieldValue->getField()->directives as $directive) {
            /** @var DirectiveNode $directive */
            if ($directive->name->value === 'deprecated') {
                return true;
            }
        }

        return false;
    }
}
