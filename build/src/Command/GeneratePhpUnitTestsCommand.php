<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Build\Command;

use Inachis\Build\Service\PhpUnitSkeletonGenerator;
use Inachis\Build\Service\SourceClassScanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'inachis:build:tests',
    description: 'Generate PHPUnit test skeletons for missing tests.',
)]
final class GeneratePhpUnitTestsCommand extends Command
{
    public function __construct(
        private readonly SourceClassScanner $scanner,
        private readonly PhpUnitSkeletonGenerator $generator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDirectory,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $classes = $this->scanner->scan(
            $this->projectDirectory . '/src',
            $this->projectDirectory,
        );

        $created = 0;
        $skipped = 0;

        foreach ($classes as $class) {
            if (!$class->shouldGenerateTest()) {
                continue;
            }

            if ($class->hasTest()) {
                $skipped++;

                continue;
            }

            $this->createTest(
                $class->getExpectedTestFile(),
                $this->generator->generate($class),
            );

            $output->writeln(sprintf(
                '<info>Created</info> %s',
                $class->getExpectedTestFile(),
            ));

            $created++;
        }

        $output->writeln('');

        $output->writeln(sprintf(
            'Created: %d, Skipped: %d',
            $created,
            $skipped,
        ));

        return Command::SUCCESS;
    }

    private function createTest(
        string $filename,
        string $contents,
    ): void {
        $directory = dirname($filename);

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0755,
                true,
            );
        }

        if (is_file($filename)) {
           return;
        }

        file_put_contents(
            $filename,
            $contents,
        );
    }
}
