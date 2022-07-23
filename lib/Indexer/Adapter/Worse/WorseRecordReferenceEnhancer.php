<?php

namespace Phpactor\Indexer\Adapter\Worse;

use Phpactor\Indexer\Model\RecordReference;
use Phpactor\Indexer\Model\RecordReferenceEnhancer;
use Phpactor\Indexer\Model\Record\FileRecord;
use Phpactor\Indexer\Model\Record\MemberRecord;
use Phpactor\TextDocument\TextDocumentLocator;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Reflector\SourceCodeReflector;
use Psr\Log\LoggerInterface;
use Safe\Exceptions\FilesystemException;
use function Safe\file_get_contents;

class WorseRecordReferenceEnhancer implements RecordReferenceEnhancer
{
    private SourceCodeReflector $reflector;

    private LoggerInterface $logger;

    private TextDocumentLocator $locator;

    public function __construct(
        SourceCodeReflector $reflector,
        TextDocumentLocator $locator,
        LoggerInterface $logger
    )
    {
        $this->reflector = $reflector;
        $this->logger = $logger;
        $this->locator = $locator;
    }

    public function enhance(FileRecord $record, RecordReference $reference): RecordReference
    {
        if ($reference->type() !== MemberRecord::RECORD_TYPE) {
            return $reference;
        }

        if ($reference->contaninerType()) {
            return $reference;
        }

        try {
            $contents = $this->locator->get(TextDocumentUri::fromString($record->filePath()));
        } catch (FilesystemException $error) {
            $this->logger->warning(sprintf(
                'Record Enhancer: Could not read file "%s": %s',
                $record->filePath(),
                $error->getMessage()
            ));
            return $reference;
        }

        try {
            $offset = $this->reflector->reflectOffset($contents, $reference->offset());
        } catch (NotFound $notFound) {
            $this->logger->debug(sprintf(
                'Record Enhancer: Could not reflect offset %s in file "%s": %s',
                $reference->offset(),
                $record->filePath(),
                $notFound->getMessage()
            ));
            return $reference;
        }

        $containerType = $offset->symbolContext()->containerType();

        if (!($containerType->isDefined())) {
            return $reference;
        }

        return $reference->withContainerType($containerType);
    }
}
