<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

interface ImageAdapterInterface
{
    public function read(string $path): void;
    public function autoOrient(): void;
    public function autoRotate(): void;
    public function resize(int $w, int $h): void;
    public function saveJpeg(string $path, int $quality): void;
}
