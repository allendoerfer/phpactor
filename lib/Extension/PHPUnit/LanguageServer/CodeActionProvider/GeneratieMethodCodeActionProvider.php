<?php

namespace Phpactor\Extension\PHPUnit\LanguageServer\CodeActionProvider;

use Amp\CancellationToken;
use Amp\Promise;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Phpactor\LanguageServer\Core\CodeAction\CodeActionProvider;
use Phpactor\WorseReflection\Core\Reflector\ClassReflector;
use function Amp\call;

class GeneratieMethodCodeActionProvider implements CodeActionProvider
{
    public function __construct(private ClassReflector $reflector)
    {
    }
    public function provideActionsFor(TextDocumentItem $textDocument, Range $range, CancellationToken $cancel): Promise
    {
        return call(function () {
            return [];
        });
    }

    public function kinds(): array
    {
    }
}
