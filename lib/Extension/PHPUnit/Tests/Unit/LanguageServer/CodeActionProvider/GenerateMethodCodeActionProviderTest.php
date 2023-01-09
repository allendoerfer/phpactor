<?php

namespace Phpactor\Extension\PHPUnit\Tests\Unit\LanguageServer\CodeActionProvider;

use Amp\CancellationTokenSource;
use Closure;
use Generator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Phpactor\Extension\PHPUnit\LanguageServer\CodeActionProvider\GeneratieMethodCodeActionProvider;
use Phpactor\LanguageServerProtocol\CodeAction;
use Phpactor\LanguageServer\Test\ProtocolFactory;
use Phpactor\WorseReflection\ReflectorBuilder;
use function Amp\Promise\wait;

class GenerateMethodCodeActionProviderTest extends TestCase
{
    /**
     * @dataProvider provideCodeActions
     * @param Closure(CodeAction[]): void $closure
     */
    public function testCodeActions(string $content, Closure $closure): void
    {
        $reflector = ReflectorBuilder::create()->addSource('<?php namespace PHPUnit\Framework; class TestCase{}')->build();
        $cancel = (new CancellationTokenSource)->getToken();
        $provider = new GeneratieMethodCodeActionProvider($reflector);
        $codeActions = wait($provider->provideActionsFor(ProtocolFactory::textDocumentItem('file:///foo', $content), ProtocolFactory::range(0, 0, 0, 0), $cancel));
        $closure($codeActions);

    }
    /**
     * @return Generator<string,array{string,Closure(array): void}>
     */
    public function provideCodeActions(): Generator
    {
        yield 'with existing setup method' => [
            <<<'EOT'
                <?php
                namespace Foobar;

                use PHPUnit\Framework\TestCase;

                class FooTest extends TestCase {
                    protected function setUp(): void {}
                    protected function tearDown(): void {}
                }
                EOT
            ,
            function (array $codeActions): void {
                self::assertCount(0, $codeActions);
            }
        ];
        yield 'add methods' => [
            <<<'EOT'
                <?php
                namespace Foobar;

                use PHPUnit\Framework\TestCase;

                class FooTest extends TestCase {
                }
                EOT
            ,
            /** @param CodeAction[] $codeActions */
            function (array $codeActions): void {
                self::assertCount(2, $codeActions);

                $action = $codeActions[0];
                self::assertInstanceOf(CodeAction::class, $action);
                assert($action instanceof CodeAction);
                self::assertNotNull($action->command->arguments);
                self::assertEquals(['setUp'], $action->command->arguments);

                $action = $codeActions[1];
                self::assertInstanceOf(CodeAction::class, $action);
                assert($action instanceof CodeAction);
                self::assertNotNull($action->command->arguments);
                self::assertEquals(['tearDown'], $action->command->arguments);
            }
        ];
    }
}
