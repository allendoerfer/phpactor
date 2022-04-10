<?php

namespace Phpactor\WorseReflection\Core\Inference\Resolver;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Expression\BinaryExpression;
use Microsoft\PhpParser\Node\Expression\UnaryExpression;
use Microsoft\PhpParser\Token;
use Microsoft\PhpParser\TokenKind;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Inference\NodeContext;
use Phpactor\WorseReflection\Core\Inference\NodeContextFactory;
use Phpactor\WorseReflection\Core\Inference\Resolver;
use Phpactor\WorseReflection\Core\Inference\NodeContextResolver;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\TypeFactory;
use Phpactor\WorseReflection\Core\Type\BitwiseOperable;
use Phpactor\WorseReflection\Core\Type\BooleanType;
use Phpactor\WorseReflection\Core\Type\Comparable;
use Phpactor\WorseReflection\Core\Type\Concatable;
use Phpactor\WorseReflection\Core\Type\MissingType;
use Phpactor\WorseReflection\TypeUtil;

class BinaryExpressionResolver implements Resolver
{
    public function resolve(NodeContextResolver $resolver, Frame $frame, Node $node): NodeContext
    {
        assert($node instanceof BinaryExpression);

        $operator = $node->operator->kind;

        $context = NodeContextFactory::create(
            $node->getText(),
            $node->getStartPosition(),
            $node->getEndPosition(),
            [
            ]
        );

        $left = $resolver->resolveNode($frame, $node->leftOperand);
        $right = $resolver->resolveNode($frame, $node->rightOperand);

        switch ($operator) {
            case TokenKind::InstanceOfKeyword:
                return $this->resolveInstanceOf($context, $resolver, $frame, $node->leftOperand, $right);
        }

        $context = $context->withTypeAssertions(
            $left->typeAssertions()->merge($right->typeAssertions())
        );
        $type = $this->walkBinaryExpression($left->type(), $right->type(), $operator);

        if ($type instanceof BooleanType) {
            if (false === $type->isTrue()) {
                $context = $context->withTypeAssertions($context->typeAssertions()->negate());
            }
        }

        return $context->withType($type);
    }

    private function walkBinaryExpression(
        Type $left,
        Type $right,
        int $operator
    ): Type {
        if ($left instanceof Concatable) {
            switch ($operator) {
                case TokenKind::DotToken:
                case TokenKind::DotEqualsToken:
                    return $left->concat($right);
            }
        }

        if ($left instanceof Comparable) {
            switch ($operator) {
                case TokenKind::EqualsEqualsEqualsToken:
                    return $left->identical($right);
                case TokenKind::EqualsEqualsToken:
                    return $left->equal($right);
                case TokenKind::GreaterThanToken:
                    return $left->greaterThan($right);
                case TokenKind::GreaterThanEqualsToken:
                    return $left->greaterThanEqual($right);
                case TokenKind::LessThanToken:
                    return $left->lessThan($right);
                case TokenKind::LessThanEqualsToken:
                    return $left->lessThanEqual($right);
                case TokenKind::ExclamationEqualsToken:
                    return $left->notEqual($right);
                case TokenKind::ExclamationEqualsEqualsToken:
                    return $left->notIdentical($right);
            }
        }

        switch ($operator) {
            case TokenKind::OrKeyword:
            case TokenKind::BarBarToken:
                return TypeUtil::toBool($left)->or(TypeUtil::toBool($right));
            case TokenKind::AndKeyword:
            case TokenKind::AmpersandAmpersandToken:
                return TypeUtil::toBool($left)->and(TypeUtil::toBool($right));
            case TokenKind::XorKeyword:
                return TypeUtil::toBool($left)->xor(TypeUtil::toBool($right));
            case TokenKind::PlusToken:
                return TypeUtil::toNumber($left)->plus(TypeUtil::toNumber($right));
            case TokenKind::MinusToken:
                return TypeUtil::toNumber($left)->minus(TypeUtil::toNumber($right));
            case TokenKind::AsteriskToken:
                return TypeUtil::toNumber($left)->multiply(TypeUtil::toNumber($right));
            case TokenKind::SlashToken:
                return TypeUtil::toNumber($left)->divide(TypeUtil::toNumber($right));
            case TokenKind::PercentToken:
                return TypeUtil::toNumber($left)->modulo(TypeUtil::toNumber($right));
            case TokenKind::AsteriskAsteriskToken:
                return TypeUtil::toNumber($left)->exp(TypeUtil::toNumber($right));
        }

        if ($left instanceof BitwiseOperable) {
            switch ($operator) {
                case TokenKind::AmpersandToken:
                    return $left->bitwiseAnd($right);
                case TokenKind::BarToken:
                    return $left->bitwiseOr($right);
                case TokenKind::CaretToken:
                    return $left->bitwiseXor($right);
                case TokenKind::LessThanLessThanToken:
                    return $left->shiftLeft($right);
                case TokenKind::GreaterThanGreaterThanToken:
                    return $left->shiftRight($right);
            }
        }

        return new MissingType();
    }

    private function resolveInstanceOf(
        NodeContext $context,
        NodeContextResolver $resolver,
        Frame $frame,
        Node $leftOperand,
        NodeContext $right
    ): NodeContext {
        // work around for https://github.com/Microsoft/tolerant-php-parser/issues/19#issue-201714377
        // the left hand side of instanceof should be parsed as a variable but it's not.
        if ($leftOperand instanceof UnaryExpression) {
            $left = $resolver->resolveNode($frame, $leftOperand->operand);
        } else {
            $left = $resolver->resolveNode($frame, $leftOperand);
        }

        $context = $context->withTypeAssertionForSubject($left, $right->type());
        return $context->withType(TypeFactory::boolLiteral(true));
    }
}
