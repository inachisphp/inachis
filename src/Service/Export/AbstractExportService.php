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
    /** @var array<string, ExportWriterInterface> */
    protected array $writers = [];

    public function __construct(
        #[TaggedIterator('inachis.export_writer')]
        iterable $writers
    ) {
        foreach ($writers as $writer) {
            if (!$writer instanceof ExportWriterInterface) continue;
            foreach (['json','md','xml'] as $format) {
                if ($writer->supports($format)) {
                    $this->writers[$format] = $writer;
                }
            }
        }
    }

    /**
     * Export the collection
     *
     * @param iterable<object> $collection
     * @param string $format
     * @return string
     */
    protected function exportCollection(iterable $collection, string $format): string
    {
        if (!isset($this->writers[$format])) {
            throw new \InvalidArgumentException(sprintf('Unsupported export format: %s', $format));
        }

        $dtos = [];
        foreach ($collection as $item) {
            $dtos[] = $this->normalise($item);
        }

        return $this->writers[$format]->write($dtos);
    }

    /**
     * Each service must implement its own normalise logic
     */
    abstract protected function normalise(object $entity): object;
}
