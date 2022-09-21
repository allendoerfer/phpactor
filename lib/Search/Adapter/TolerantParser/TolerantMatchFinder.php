<?php

namespace Phpactor\Search\Adapter\TolerantParser;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Statement\ExpressionStatement;
use Microsoft\PhpParser\Node\Statement\InlineHtml;
use Microsoft\PhpParser\Parser;
use Microsoft\PhpParser\Token;
use Phpactor\Search\Adapter\TolerantParser\Matcher\TokenEqualityMatcher;
use Phpactor\Search\Model\MatchFinder;
use Phpactor\Search\Model\MatchResult;
use Phpactor\Search\Model\MatchToken;
use Phpactor\Search\Model\MatchTokens;
use Phpactor\Search\Model\Matcher;
use Phpactor\Search\Model\DocumentMatches;
use Phpactor\Search\Model\Matcher\ChainMatcher;
use Phpactor\Search\Model\Matcher\PlaceholderMatcher;
use Phpactor\Search\Model\PatternMatch;
use Phpactor\TextDocument\ByteOffsetRange;
use Phpactor\TextDocument\TextDocument;

class TolerantMatchFinder implements MatchFinder
{
    private Parser $parser;

    private Matcher $matcher;

    public function __construct(Parser $parser, Matcher $matcher)
    {
        $this->parser = $parser;
        $this->matcher = $matcher;
    }

    /**
     * Find all nodes matching first node of pattern
     * Within those nodes find immediate children matching secod node of pattern
     */
    public function match(TextDocument $document, string $pattern): DocumentMatches
    {
        $patternNode = $this->parser->parseSourceFile('<?php ' . $pattern);
        $documentNode = $this->parser->parseSourceFile($document);
        $matches = [];
        $patternNode = $this->reducePattern($patternNode);
        $baseNodes = $this->findBaseNodes($documentNode, $patternNode);

        foreach ($baseNodes as $baseNode) {
            $matchTokens = [];
            if ($this->nodeMatches($baseNode, $patternNode, $matchTokens)) {
                $matches[] = new PatternMatch(
                    ByteOffsetRange::fromInts($baseNode->getStartPosition(), $baseNode->getEndPosition()),
                    new MatchTokens($matchTokens)
                );
            }
        }

        return new DocumentMatches($document, $matches);
    }

    public static function createDefault(): self
    {
        return new self(
            new Parser(),
            new ChainMatcher(
                new PlaceholderMatcher(),
                new TokenEqualityMatcher()
            )
        );
    }

    /**
     * @return array<Node>
     * @param array<Node> $matches
     */
    private function findBaseNodes(Node $documentNode, Node $targetNode, array &$matches = []): array
    {
        foreach ($documentNode->getChildNodes() as $childNode) {
            if (get_class($childNode) === get_class($targetNode)) {
                $matches[] = $childNode;
            }

            $this->findBaseNodes($childNode, $targetNode, $matches);
        }

        return $matches;
    }


    /**
     * @param array<string,MatchToken> $matchTokens
     */
    private function nodeMatches(Node $node, Node $template, array &$matchTokens): bool
    {
        if (get_class($node) !== get_class($template)) {
            return false;
        }
        foreach ($template::CHILD_NAMES as $childName) {
            $nodeChild = $node->$childName;
            $templateChild = $template->$childName;
        }

        return true;
    }

    /**
     * @param (Node|Token)|array<Node|Token> $nodeOrTokenOrArray
     * @return array<Node|Token>
     */
    private function normalize($nodeOrTokenOrArray): array
    {
        if (is_array($nodeOrTokenOrArray)) {
            return $nodeOrTokenOrArray;
        }
        return [$nodeOrTokenOrArray];
    }

    /**
     * Remove any unnecessary preceding nodes from the pattern AST (e.g. HTML,
     * Statement)
     */
    private function reducePattern(Node $patternNode): Node
    {
        foreach ($patternNode->getChildNodes() as $childNode) {
            if ($childNode instanceof InlineHtml) {
                continue;
            }

            if ($childNode instanceof ExpressionStatement) {
                $childNode = $childNode->expression;
            }

            return $childNode;
        }

        return $patternNode;
    }

    private function isMatch(Node $template, Token $matchNodeOrToken, Node $node, Token $nodeChild): MatchResult
    {
        $t1 = new MatchToken(
            ByteOffsetRange::fromInts($nodeChild->getStartPosition(), $nodeChild->getEndPosition()),
            (string)$nodeChild->getText($node->getFileContents()),
            $nodeChild->kind
        );

        $t2 = new MatchToken(
            ByteOffsetRange::fromInts($matchNodeOrToken->getStartPosition(), $matchNodeOrToken->getEndPosition()),
            (string)$matchNodeOrToken->getText($template->getFileContents()),
            $matchNodeOrToken->kind
        );

        $m  = $this->matcher->matches($t1, $t2);
        if ($m->isYes()) {
            dump($t1, $t2);
        }
        return $m;
    }
}
