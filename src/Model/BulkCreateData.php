<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model;

use DateTimeImmutable;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Data for bulk creating pages
 */
class BulkCreateData
{
    /**
     * Creates a new instance of the BulkCreateData
     * 
     * @param string $title
     * @param DateTimeImmutable $startDate
     * @param DateTimeImmutable $endDate
     * @param bool $addDayNumber
     * @param string $seriesId
     * @param array<string> $tags
     * @param array<string> $categories
     */
    public function __construct(
        public string $title,
        public DateTimeImmutable $startDate,
        public DateTimeImmutable $endDate,
        public bool $addDayNumber,
        public string $seriesId,
        public array $tags = [],
        public array $categories = [],
    ) {}

    /**
     * Creates a new instance of the BulkCreateData from a request
     * 
     * @param Request $request
     * @return self
     * @throws InvalidArgumentException
     */
    public static function fromRequest(Request $request): self
    {
        /** @var array{
         *   title?: string,
         *   seriesId?:string,
         *   startDate?: string,
         *   endDate?: string,
         *   tags?:array<string>,
         *   categories?:array<string>,
         *   addDay?:int
         * } $form */
        $form = $request->request->all();

        if (!$form) {
            throw new InvalidArgumentException('Form data is missing.');
        }

        if (empty($form['title'])) {
            throw new InvalidArgumentException('Title is required.');
        }

        if (empty($form['startDate']) || empty($form['endDate'])) {
            throw new InvalidArgumentException('Start and end dates are required.');
        }

        $start = DateTimeImmutable::createFromFormat('d/m/Y', $form['startDate']);
        $end   = DateTimeImmutable::createFromFormat('d/m/Y', $form['endDate']);

        if (!$start || !$end) {
            throw new InvalidArgumentException('Invalid date format, expected d/m/Y.');
        }

        return new self(
            title: $form['title'],
            startDate: $start,
            endDate: $end,
            addDayNumber: !empty($form['addDay']),
            seriesId: $form['seriesId'] ?? '',
            tags: $form['tags'] ?? [],
            categories: $form['categories'] ?? [],
        );
    }
}
