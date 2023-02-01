<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

use Nuwave\Lighthouse\Schema\Values\FieldValue;
use Nuwave\Lighthouse\Support\Contracts\FieldMiddleware;

class CollectFieldMiddleware implements FieldMiddleware
{
    public static function definition(): string
    {
        return "";
    }

    public function handleField(FieldValue $fieldValue, \Closure $next)
    {
        Collector::addResult($fieldValue);

        return $next($fieldValue);
    }
}
