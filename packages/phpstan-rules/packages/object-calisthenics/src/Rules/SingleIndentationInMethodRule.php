<?php

declare(strict_types=1);

namespace Symplify\PHPStanRules\ObjectCalisthenics\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use Symplify\EasyTesting\PHPUnit\StaticPHPUnitEnvironment;
use Symplify\PHPStanRules\CognitiveComplexity\Rules\ClassLikeCognitiveComplexityRule;
use Symplify\PHPStanRules\ObjectCalisthenics\Marker\IndentationMarker;
use Symplify\PHPStanRules\ObjectCalisthenics\NodeTraverserFactory\IndentationNodeTraverserFactory;
use Symplify\PHPStanRules\Rules\AbstractSymplifyRule;
use Symplify\RuleDocGenerator\Contract\ConfigurableRuleInterface;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see https://williamdurand.fr/2013/06/03/object-calisthenics/#1-only-one-level-of-indentation-per-method
 *
 * @see \Symplify\PHPStanRules\ObjectCalisthenics\Tests\Rules\SingleIndentationInMethodRule\SingleIndentationInMethodRuleTest
 *
 * @deprecated This rule is rather academic and does not relate ot complexity. Use
 * @see ClassLikeCognitiveComplexityRule instead
 */
final class SingleIndentationInMethodRule extends AbstractSymplifyRule implements ConfigurableRuleInterface
{
    /**
     * @var string
     */
    public const ERROR_MESSAGE = 'Do not indent more than %dx in class methods';

    /**
     * The depth from nested values inside method, so 2 nestings are from class and method and 1 from inner method
     *
     * @var int
     */
    private const DEFAULT_DEPTH = 5;

    public function __construct(
        private IndentationMarker $indentationMarker,
        private IndentationNodeTraverserFactory $indentationNodeTraverserFactory,
        private int $maxNestingLevel = 1
    ) {
        if (! StaticPHPUnitEnvironment::isPHPUnitRun()) {
            $errorMessage = sprintf(
                '[Deprecated] This rule is rather academic and does not relate ot complexity. Use
     "%s" instead',
                ClassLikeCognitiveComplexityRule::class
            );
            trigger_error($errorMessage);
            sleep(3);
        }
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [ClassMethod::class];
    }

    /**
     * @param ClassMethod $node
     * @return string[]
     */
    public function process(Node $node, Scope $scope): array
    {
        $this->indentationMarker->reset();

        $nodeTraverser = $this->indentationNodeTraverserFactory->create();
        $nodeTraverser->traverse([$node]);

        $limitIndentation = $this->maxNestingLevel + self::DEFAULT_DEPTH;

        if ($this->indentationMarker->getIndentation() < $limitIndentation) {
            return [];
        }

        $errorMessage = sprintf(self::ERROR_MESSAGE, $this->maxNestingLevel);
        return [$errorMessage];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(self::ERROR_MESSAGE, [
            new ConfiguredCodeSample(
                <<<'CODE_SAMPLE'
function someFunction()
{
    if (...) {
        if (...) {
        }
    }
}
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
function someFunction()
{
    if (! ...) {
    }

    if (!...) {
    }
}
CODE_SAMPLE
                ,
                [
                    'maxNestingLevel' => [2],
                ]
            ),
        ]);
    }
}
