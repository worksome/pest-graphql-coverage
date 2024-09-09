<?php

declare(strict_types=1);

namespace Worksome\PestGraphqlCoverage;

use GraphQL\Language\AST\FieldDefinitionNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\NodeKind;
use GraphQL\Language\Parser;
use GraphQL\Language\Visitor;
use Illuminate\Support\Arr;
use Pest\Contracts\Plugins\AddsOutput;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\Plugins\Parallel;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Plugin implements AddsOutput, HandlesArguments
{
    use HandleArguments;

    private const SERVER_VARIABLE_NAME = 'GQL_COVERAGE_ENABLED';

    public int $maxUntestedFieldsCount = 10;

    public string $schemaCommand = 'php artisan lighthouse:print-schema';

    public float $coverageMin = 0.0;

    public function __construct(private readonly OutputInterface $output)
    {
    }

    public static function isEnabled(): bool
    {
        return (bool) (Parallel::getGlobal(self::SERVER_VARIABLE_NAME) ?? false);
    }

    public function handleArguments(array $arguments): array
    {
        $hasOption = $this->hasArgument('--gql-coverage', $arguments);

        if (! $hasOption && ! self::isEnabled()) {
            return $arguments;
        }

        if ($hasOption) {
            $arguments = $this->popArgument('--gql-coverage', $arguments);
        }

        $this->handleEnablingCoverage($arguments);

        Collector::reset();

        $this->handleMinCoverage($arguments);

        $this->handleMaxUntestedFields($arguments);

        $this->handleSchemaCommand($arguments);

        return $arguments;
    }

    private function handleEnablingCoverage(array &$arguments): void
    {
        Parallel::setGlobal(self::SERVER_VARIABLE_NAME, true);
    }

    private function handleMinCoverage(array &$arguments): void
    {
        $coverageMinRegex = /** @lang RegExp */
            '/^--gql-min=(\d+)$/';
        $min = preg_grep($coverageMinRegex, $arguments);

        if ($min === false || count($min) === 0) {
            return;
        }

        unset($arguments[array_keys($min)[0]]);

        preg_match($coverageMinRegex, array_pop($min), $matches);

        if (! isset($matches[1])) {
            return;
        }

        $this->coverageMin = (int) $matches[1];
    }

    private function handleSchemaCommand(array &$arguments): void
    {
        $schemaCommandRegex = /** @lang RegExp */
            '/^--schema-command=(.*)$/';
        $command = preg_grep($schemaCommandRegex, $arguments);

        if ($command === false || count($command) === 0) {
            return;
        }

        unset($arguments[array_keys($command)[0]]);

        preg_match($schemaCommandRegex, array_pop($command), $matches);

        if (! isset($matches[1])) {
            return;
        }

        $this->schemaCommand = $matches[1];
    }

    private function handleMaxUntestedFields(array &$arguments): void
    {
        $maxUntestedFieldsRegex = /** @lang RegExp */
            '/^--gql-untested-count=(.*)$/';
        $command = preg_grep($maxUntestedFieldsRegex, $arguments);

        if ($command === false || count($command) === 0) {
            return;
        }

        unset($arguments[array_keys($command)[0]]);

        preg_match($maxUntestedFieldsRegex, array_pop($command), $matches);

        if (! isset($matches[1])) {
            return;
        }

        $this->maxUntestedFieldsCount = is_numeric($matches[1]) ? (int) $matches[1] : $this->maxUntestedFieldsCount;
    }

    public function addOutput(int $exitCode): int
    {
        if (self::isEnabled() === false) {
            return $exitCode;
        }

        $style = new SymfonyStyle(new ArrayInput([]), $this->output);

        // Get all nodes which were visited.
        $collector = Collector::parseResult();
        $dottedTestedNodes = array_filter(array_unique($collector));
        $dottedTestedNodes = array_combine($dottedTestedNodes, $dottedTestedNodes);

        // Get all nodes in the schema
        $nodes = $this->collectAllNodesFromSchema();
        $dottedNodes = Arr::dot($nodes);

        // Create an array of all untested nodes.
        $untested = array_diff_key($dottedNodes, $dottedTestedNodes);

        // Remove ignored nodes
        foreach ($untested as $node => $tested) {
            foreach (Config::ignoredNodes() as $expression) {
                if (fnmatch($expression, $node)) {
                    unset($untested[$node]);

                    break;
                }
            }
        }

        // Count the nodes and calculate the percentage of tested nodes.
        $totalNodes = count($dottedNodes);
        $totalTestedNodes = $totalNodes - count($untested);
        $percentage = round($totalTestedNodes / $totalNodes, 4) * 100;

        $style->writeln("GraphQL coverage:  $percentage% ($totalTestedNodes/$totalNodes fields)");

        if ($untested !== []) {
            $style->newLine();
            $style->writeln("Untested fields (max. {$this->maxUntestedFieldsCount}): ");
            $style->listing(array_keys(array_slice($untested, 0, $this->maxUntestedFieldsCount)));
        }

        if ($this->coverageMin > $percentage) {
            $style->writeln("Min coverage of $this->coverageMin% not reached!");
            return 1;
        }

        return $exitCode;
    }

    private function collectAllNodesFromSchema(): array
    {
        exec($this->schemaCommand, $output, $code);

        if ($code !== 0) {
            $this->output->writeln("Schema command failed: $code");
            $this->output->writeln($output);

            throw new RuntimeException("invalid command");
        }

        $output = implode(PHP_EOL, $output);
        $schema = Parser::parse($output);

        $nodes = [];

        /** @phpstan-ignore-next-line */
        Visitor::visit($schema->definitions, [
            NodeKind::FIELD_DEFINITION => function (FieldDefinitionNode $node, $key, $parent, $path, $ancestors) use (
                &$nodes
            ) {
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
