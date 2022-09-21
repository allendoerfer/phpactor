<?php

namespace Phpactor\Search;

use Phpactor\Search\Model\DocumentMatches;
use Phpactor\Search\Model\MatchFilter;
use Phpactor\Search\Model\MatchFinder;
use Phpactor\Search\Model\TokenConstraints;
use Phpactor\TextDocument\TextDocument;

class Search
{
    private MatchFinder $matchFinder;

    private MatchFilter $filter;

    public function __construct(MatchFinder $matchFinder, MatchFilter $filter)
    {
        $this->matchFinder = $matchFinder;
        $this->filter = $filter;
    }

    public function search(
        TextDocument $document,
        string $template,
        TokenConstraints $constraints
    ): DocumentMatches {
        $matches = $this->matchFinder->match($document, $template);
        $matches = $this->filter->filter($matches, $constraints);

        return $matches;
    }
}
