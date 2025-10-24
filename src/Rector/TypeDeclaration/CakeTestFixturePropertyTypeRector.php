<?php

declare(strict_types=1);

namespace Cake\Rector\TypeDeclaration;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Adds property type declarations to CakeTestFixture subclasses.
 *
 * Fixes Fatal Error: Type of Child::$fields must be array (as in class CakeTestFixture)
 *
 * @see \Cake\TestSuite\Fixture\CakeTestFixture
 */
final class CakeTestFixturePropertyTypeRector extends AbstractRector
{
    /**
     * Property names to add types for
     */
    private const PROPERTY_NAMES = [
        'name', 'db', 'useDbConfig', 'table', 'created',
        'fields', 'records', 'primaryKey', 'canUseMemory', 'Schema',
    ];

    /**
     * @return RuleDefinition
     * @throws \Symplify\RuleDocGenerator\Exception\PoorDocumentationException
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Add property type declarations to CakeTestFixture subclasses',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
                    class ArticleFixture extends CakeTestFixture
                    {
                        public $fields = [];
                        public $records = [];
                    }
                    CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                    class ArticleFixture extends CakeTestFixture
                    {
                        public array $fields = [];
                        public array $records = [];
                    }
                    CODE_SAMPLE,
                ),
            ],
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // Check if class extends CakeTestFixture
        if ($node->extends === null) {
            return null;
        }

        $parentClassName = $this->getName($node->extends);
        if ($parentClassName !== 'Cake\TestSuite\Fixture\CakeTestFixture') {
            return null;
        }

        $hasChanged = false;

        foreach ($node->stmts as $stmt) {
            if (!$stmt instanceof Property) {
                continue;
            }

            $propertyName = $this->getName($stmt);
            if (!in_array($propertyName, self::PROPERTY_NAMES, true)) {
                continue;
            }

            // Skip if already has type
            if ($stmt->type !== null) {
                continue;
            }

            $phpParserType = $this->getPhpParserTypeForProperty($propertyName);
            if ($phpParserType !== null) {
                $stmt->type = $phpParserType;
                $hasChanged = true;
            }
        }

        return $hasChanged ? $node : null;
    }

    /**
     * Get PhpParser type node for property
     */
    private function getPhpParserTypeForProperty(string $propertyName): Identifier|Name|NullableType|null
    {
        return match ($propertyName) {
            'name', 'table', 'primaryKey' => new NullableType(new Identifier('string')),
            'db' => new NullableType(new Name('Cake\Model\Datasource\DataSource')),
            'useDbConfig' => new Identifier('string'),
            'created', 'fields', 'records' => new Identifier('array'),
            'canUseMemory' => new Identifier('bool'),
            'Schema' => new NullableType(new Name('Cake\Model\CakeSchema')),
            default => null,
        };
    }
}
