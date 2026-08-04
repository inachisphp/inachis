<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Build\Service;

use Inachis\Build\Model\SourceClass;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Symfony\Component\Finder\Finder;

final class SourceClassScanner
{
    private Parser $parser;

    public function __construct(
    ) {
        $this->parser = (new ParserFactory())
            ->createForNewestSupportedVersion();
    }

    /**
     * @return SourceClass[]
     */
    public function scan(
        string $sourceDirectory,
        string $projectDirectory,
        ?string $only = null,
    ): array {
        if ($only !== null) {
            $sourceDirectory .= '/' . trim($only, '/');
        }

        $finder = new Finder();

        $finder
            ->files()
            ->name('*.php')
            ->in($sourceDirectory)
            ->sortByName();

        $classes = [];

        foreach ($finder as $file) {
            $visitor = new SourceClassVisitor(
                $projectDirectory,
                $file->getRealPath(),
                $file->getRelativePathname(),
            );

            $traverser = new NodeTraverser();

            $traverser->addVisitor(
                new NameResolver()
            );

            $traverser->addVisitor($visitor);

            $code = file_get_contents(
                $file->getRealPath()
            );

            if ($code === false) {
                continue;
            }

            try {
                $ast = $this->parser->parse($code);
            } catch (\Throwable) {
                continue;
            }

            if ($ast === null) {
                continue;
            }

            $traverser->traverse($ast);

            foreach ($visitor->getClasses() as $class) {
                $classes[] = $class;
            }
        }

        return $classes;
    }
}
