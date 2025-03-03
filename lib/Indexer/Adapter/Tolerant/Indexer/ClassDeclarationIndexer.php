<?php

namespace Phpactor\Indexer\Adapter\Tolerant\Indexer;

use Microsoft\PhpParser\MissingToken;
use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\Attribute;
use Microsoft\PhpParser\Node\Statement\ClassDeclaration;
use Phpactor\Indexer\Model\Exception\CannotIndexNode;
use Phpactor\Indexer\Model\Index;
use Phpactor\Indexer\Model\Name\FullyQualifiedName;
use Phpactor\Indexer\Model\Record\ClassRecord;
use Phpactor\TextDocument\TextDocument;

class ClassDeclarationIndexer extends AbstractClassLikeIndexer
{
    public function canIndex(Node $node): bool
    {
        return $node instanceof ClassDeclaration;
    }

    public function index(Index $index, TextDocument $document, Node $node): void
    {
        assert($node instanceof ClassDeclaration);
        if ($node->name instanceof MissingToken) {
            throw new CannotIndexNode(sprintf(
                'Class name is missing (maybe a reserved word) in: %s',
                $document->uri()?->path() ?? '?',
            ));
        }
        $record = $this->getClassLikeRecord(ClassRecord::TYPE_CLASS, $node, $index, $document);

        $this->removeImplementations($index, $record);
        $record->clearImplemented();

        $this->indexClassInterfaces($index, $record, $node);
        $this->indexBaseClass($index, $record, $node);

        $this->indexAttributes($record, $node);

        $index->write($record);
    }

    public function indexAttributes(ClassRecord $record, ClassDeclaration $node): void
    {
        $attributes = $node->attributes ?? [];
        if (count($attributes) === 0) {
            return;
        }

        foreach ($attributes as $attributeGroup) {
            foreach ($attributeGroup->attributes->children as $attribute) {
                /** @var Attribute $attribute */
                if ($attribute->name->getText() === 'Attribute') {
                    $record->addFlag(ClassRecord::FLAG_ATTRIBUTE);
                    return;
                }
            }
        }
    }

    private function indexClassInterfaces(Index $index, ClassRecord $classRecord, ClassDeclaration $node): void
    {
        // @phpstan-ignore-next-line because ClassInterfaceClause _can_ (and has been) be NULL
        if (null === $interfaceClause = $node->classInterfaceClause) {
            return;
        }

        if (null == $interfaceList = $interfaceClause->interfaceNameList) {
            return;
        }

        $this->indexInterfaceList($interfaceList, $classRecord, $index);
    }

    private function indexBaseClass(Index $index, ClassRecord $record, ClassDeclaration $node): void
    {
        // @phpstan-ignore-next-line because classBaseClause _can_ be NULL
        if (null === $baseClause = $node->classBaseClause) {
            return;
        }

        // @phpstan-ignore-next-line because classBaseClause _can_ be NULL
        if (null === $baseClass = $baseClause->baseClass) {
            return;
        }

        /** @phpstan-ignore-next-line */
        if ($baseClass instanceof MissingToken) {
            return;
        }

        $name = $baseClass->getResolvedName();
        $record->addImplements(FullyQualifiedName::fromString((string)$name));
        $baseClassRecord = $index->get(ClassRecord::fromName($name));
        assert($baseClassRecord instanceof ClassRecord);
        $baseClassRecord->addImplementation($record->fqn());
        $index->write($baseClassRecord);
    }
}
