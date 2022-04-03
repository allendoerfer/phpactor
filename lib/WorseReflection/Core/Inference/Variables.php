<?php

namespace Phpactor\WorseReflection\Core\Inference;

use ArrayIterator;
use IteratorAggregate;
use Phpactor\WorseReflection\Core\Types;
use Traversable;

/**
 * @implements IteratorAggregate<array-key, Variable>
 */
final class Variables implements IteratorAggregate
{
    /**
     * @var Variable[]
     */
    private array $variables;

    /**
     * @param Variable[] $variables
     */
    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->variables);
    }

    public function combine(): self
    {
        return new self(array_map(
            fn (Variable $v) => $v->withType(TypeCombinator::anihilate($v->type())),
            $this->variables
        ));
    }

    public function add(Variable $variable): self
    {
        if (isset($this->variables[$variable->name()])) {
            $this->variables[$variable->name()] = $this->variables[$variable->name()]->mergeType($variable->type());
            return $this;
        }
        $this->variables[$variable->name()] = $variable;
        return $this;
    }

    public function getOrCreate(string $string): Variable
    {
        foreach ($this->variables as $variable) {
            if ($variable->name() === $string) {
                return $variable;
            }
        }

        return new Variable($string, Types::empty());
    }

    public function and(Variables $andVariables): self
    {
        foreach ($andVariables->variables as $andVariable) {
            if (isset($this->variables[$andVariable->name()])) {
                $this->variables[$andVariable->name()]->withType(
                    TypeCombinator::merge(
                        $this->variables[$andVariable->name()]->type(),
                        $andVariable->type()
                    )
                );
                continue;
            }
            $this->variables[$andVariable->name()] = $andVariable;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function names(): array
    {
        return array_map(fn(Variable $v) => $v->name(), $this->variables);
    }
}
