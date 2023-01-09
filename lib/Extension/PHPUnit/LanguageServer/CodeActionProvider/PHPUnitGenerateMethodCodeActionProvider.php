<?php

namespace Phpactor\Extension\PHPUnit\LanguageServer\CodeActionProvider;

use Amp\CancellationToken;
use Amp\Promise;
use Generator;
use Phpactor\LanguageServerProtocol\CodeAction;
use Phpactor\LanguageServerProtocol\Command;
use Phpactor\LanguageServerProtocol\Range;
use Phpactor\LanguageServerProtocol\TextDocumentItem;
use Phpactor\LanguageServer\Core\CodeAction\CodeActionProvider;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClassLike;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use function Amp\call;

class PHPUnitGenerateMethodCodeActionProvider implements CodeActionProvider
{
    public const KIND = 'quickfix.add_method';

    public function __construct(private SourceCodeReflector $reflector)
    {
    }
    public function provideActionsFor(TextDocumentItem $textDocument, Range $range, CancellationToken $cancel): Promise
    {
        return call(function () use ($textDocument) {
            $codeActions = [];
            foreach ($this->reflector->reflectClassesIn($textDocument->text) as $reflectionClass) {
                if ($reflectionClass->isInstanceOf(ClassName::fromString('PHPUnit\Framework\TestCase'))) {
                    foreach ($this->codeActions($reflectionClass) as $codeAction) {
                        $codeActions[] = $codeAction;
                    }
                }
            }

            return $codeActions;
        });
    }

    public function kinds(): array
    {
    }
    /**
     * @return Generator<CodeAction>
     */
    private function codeActions(ReflectionClassLike $reflectionClass): Generator
    {
        foreach (['setUp', 'tearDown'] as $method) {
            if (!$reflectionClass->methods()->has($method)) {
                yield new CodeAction(
                    title: sprintf('Add %s method', $method),
                    kind: self::KIND,
                    diagnostics: null,
                    isPreferred: null,
                    edit: null,
                    command: new Command(
                        title: sprintf('Add %s method', $method),
                        command: 'add_method' /** $command string */,
                        arguments: [
                            $reflectionClass->sourceCode()->uri(),
                            $reflectionClass->name()->__toString(),
                            'protected',
                            $method,
                            'void',
                        ]
                    )
                );
            }
        }
    }
}
