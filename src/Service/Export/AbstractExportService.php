<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Export;

use Symfony\Component\TaggedIterator\TaggedIterator;

/**
 * Abstract export service
 */
abstract class AbstractExportService
{
    /** @var iterable<ExportWriterInterface> */
    protected iterable $writers;

    public function __construct(
        #[TaggedIterator('inachis.export_writer')] iterable $writers,
    ) {
        $this->writers = $writers;
    }

    /**
     * Export the collection
     *
     * @param iterable<object> $collection
     * @param string $format
     * @return string
     */
    protected function exportCollection(
        iterable $items,
        string $format,
        ?string $domain = null,
    ): string {
        foreach ($this->writers as $writer) {
            if ($writer->supports($format) && $writer->supportsDomain($domain)) {
                $dtos = [];
                foreach ($items as $item) {
                    $dtos[] = $this->normalise($item);
                }
                return $writer->write($dtos);
            }
        }
        throw new \RuntimeException(sprintf(
            'No export writer for format "%s" and domain "%s"',
            $format,
            $domain ?? 'default'
        ));
    }

    /**
     * Each service must implement its own normalise logic
     */
    abstract protected function normalise(object $entity): object;
}
