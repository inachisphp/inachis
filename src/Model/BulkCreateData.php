<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Model;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class BulkCreateData
{
    public function __construct(
        public string $title,
        public DateTimeInterface $startDate,
        public DateTimeInterface $endDate,
        public bool $addDayNumber,
        public string $seriesId,
        public array $tags = [],
        public array $categories = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        $form = $request->request->all('form');

        if (!$form) {
            throw new InvalidArgumentException('Form data is missing.');
        }

        if (empty($form['title'])) {
            throw new InvalidArgumentException('Title is required.');
        }

        if (empty($form['startDate']) || empty($form['endDate'])) {
            throw new InvalidArgumentException('Start and end dates are required.');
        }

        $start = DateTime::createFromFormat('d/m/Y', $form['startDate']);
        $end   = DateTime::createFromFormat('d/m/Y', $form['endDate']);

        if (!$start || !$end) {
            throw new InvalidArgumentException('Invalid date format, expected d/m/Y.');
        }

        return new self(
            title: $form['title'],
            startDate: $start,
            endDate: $end,
            addDayNumber: !empty($form['addDay']),
            seriesId: $request->request->get('seriesId'),
            tags: $form['tags'] ?? [],
            categories: $form['categories'] ?? [],
        );
    }
}
