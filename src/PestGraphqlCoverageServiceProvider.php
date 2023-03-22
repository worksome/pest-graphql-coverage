<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class PestGraphqlCoverageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Check if gql coverage is enabled, either via parallel or non-parallel
        if (Plugin::isEnabled()) {
            /** @var Repository $config */
            $config = $this->app->get(Repository::class);
            $config->push('lighthouse.field_middleware', CollectFieldMiddleware::class);
        }
    }
}
