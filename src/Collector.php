<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

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
        $fieldName = sprintf('%s.%s', $fieldValue->getParentName(), $fieldValue->getFieldName());

        $filePath = self::filePath();
        $stream = fopen($filePath, 'a');
        assert(is_resource($stream));

        fwrite($stream, $fieldName . PHP_EOL);

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
}
