<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Visitor;
use Illuminate\Support\Arr;
use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\HandlesArguments;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Plugin implements AddsOutput, HandlesArguments
{
    private const SERVER_VARIABLE_NAME = 'GQL_COVERAGE_ENABLED';
    private const PARALLEL_RUNNER_ARGUMENT = '--runner=\Illuminate\Testing\ParallelRunner';
    private const PARALLEL_ARG = "-d GQL_COVERAGE_ENABLED=1";

    public bool $coverage = false;

    public string $schemaCommand = 'php artisan lighthouse:print-schema';

    /**
     * The minimum coverage.
     */
    public float $coverageMin = 0.0;

    public function __construct(private readonly OutputInterface $output)
    {
    }

    public static function hasNonParallelCoverageEnabled(): bool
    {
        return (bool) ($_SERVER[self::SERVER_VARIABLE_NAME] ?? false);
    }

    public static function hasParallelCoverageEnabled(): bool
    {
        return (bool) array_search(self::PARALLEL_ARG, $_SERVER['argv']);
    }

    public function handleArguments(array $arguments): array
    {
        if (! ($coverageIndex = array_search('--gql-coverage', $arguments))) {
            $this->coverage = false;
            return $arguments;
        }
        $this->handleEnablingCoverage($arguments, $coverageIndex);

        Collector::reset();

        $this->handleMinCoverage($arguments);

        $this->handleSchemaCommand($arguments);

        return $arguments;
    }

    private function handleEnablingCoverage(array &$arguments, int $coverageIndex): void
    {
        unset($arguments[$coverageIndex]);
        $this->coverage = true;

        // Tell non-parallel processes that graphql coverage is enabled.
        $_SERVER[self::SERVER_VARIABLE_NAME] = true;

        // Tell parallel processes that graphql coverage is enabled.
        if (array_search(self::PARALLEL_RUNNER_ARGUMENT, $arguments)) {
            $arguments[] = sprintf('--passthru="%s"', self::PARALLEL_ARG);
        }
    }

    private function handleMinCoverage(array &$arguments): void
    {
        $coverageMinRegex = /** @lang RegExp */ '/^--gql-min=(\d+)$/';
        $min = preg_grep($coverageMinRegex, $arguments);

        if ($min === false || count($min) === 0) {
            return;
        }

        unset($arguments[array_keys($min)[0]]);

        preg_match($coverageMinRegex, array_pop($min), $matches);
        $this->coverageMin = (int) $matches[1];
    }

    private function handleSchemaCommand(array $arguments): void
    {
        $schemaCommandRegex = /** @lang RegExp */ '/^--schema-command="(.*)"$/';
        $command = preg_grep($schemaCommandRegex, $arguments);

        if ($command === false || count($command) === 0) {
            return;
        }

        unset($arguments[array_keys($command)[0]]);

        preg_match($schemaCommandRegex, array_pop($command), $matches);

        $this->schemaCommand = $matches[1];
    }

    public function addOutput(int $testReturnCode): int
    {
        if ($this->coverage === false) {
            return $testReturnCode;
        }

        $style = new SymfonyStyle(new ArrayInput([]), $this->output);

        // Get all nodes which were visited.
        $collector = Collector::parseResult();
        $dottedTestedNodes = array_filter(array_unique($collector));

        // Get all nodes in the schema
        $nodes = $this->collectAllNodesFromSchema();
        $dottedNodes = Arr::dot($nodes);

        // Count the nodes and calculate the percentage of tested nodes.
        $totalNodes = count($dottedNodes);
        $totalTestedNodes = count($dottedTestedNodes);
        $percentage = round($totalTestedNodes / $totalNodes, 4) * 100;


        $style->writeln("GraphQL coverage:  $percentage% ($totalTestedNodes/$totalNodes fields)");

        // Create an array of all untested nodes.
        $untested = array_diff_key($dottedNodes, $dottedTestedNodes);

        if ($untested !== []) {
            $style->newLine();
            $style->writeln("Untested fields (max. 5): ");
            $style->listing(array_keys(array_slice($untested, 0, 5)));
        }

        if ($this->coverageMin > $percentage) {
            $style->writeln("Min coverage of $this->coverageMin% not reached!");
            return 1;
        }

        return $testReturnCode;
    }

    private function collectAllNodesFromSchema(): array
    {
        exec($this->schemaCommand, $output);
        $output = implode(PHP_EOL, $output);
        $schema = Parser::parse($output);

        $nodes = [];
        Visitor::visit($schema->definitions, [
            NodeKind::FIELD_DEFINITION => function ($node, $key, $parent, $path, $ancestors) use (&$nodes) {
                $parentType = $ancestors[1];

                if ($parentType instanceof InterfaceTypeDefinitionNode) {
                    return;
                }

                $nodes[$parentType->name->value][$node->name->value] = true;
            }
        ]);
        return $nodes;
    }
}
