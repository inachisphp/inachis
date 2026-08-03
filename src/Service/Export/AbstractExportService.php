<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Export;

use Inachis\Entity\Content\{Category, Page, Series};
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Abstract export service
 */
abstract class AbstractExportService
{
    /** @var iterable<ExportWriterInterface> */
    protected iterable $writers;

    /**
     * Inject the export writers
     *
     * @param iterable<ExportWriterInterface> $writers
     */
    public function __construct(
        #[AutowireIterator('inachis.export_writer')] iterable $writers,
    ) {
        $this->writers = $writers;
    }

    /**
     * Export the collection
     *
     * @param iterable<Category|Page|Series> $items
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
     *
     * @param object $entity
     * @return object
     */
    abstract protected function normalise(Category|Page|Series $entity): object;
}
