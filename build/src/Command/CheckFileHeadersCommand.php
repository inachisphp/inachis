<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework
 */

namespace Inachis\Build\Command;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
	name: 'inachis:build:check-headers',
	description: 'Checks PHP files contain the required Inachis file header.',
)]
final class CheckFileHeadersCommand extends Command
{
	private const HEADER = <<<'PHP'
/**
 * This file is part of the inachis framework.
 */
PHP;

	protected function configure(): void
	{
		$this->addOption(
			'fix',
			null,
			InputOption::VALUE_NONE,
			'Fix invalid file headers instead of reporting them.',
		);
	}

	protected function execute(
		InputInterface $input,
		OutputInterface $output,
	): int {
		$fix = (bool) $input->getOption('fix');

		$directories = [
			dirname(__DIR__, 3) . '/src',
			dirname(__DIR__, 3) . '/tests',
			dirname(__DIR__, 3) . '/migrations',
		];

		$failed = false;

		foreach ($directories as $directory) {
			if (!is_dir($directory)) {
				continue;
			}

			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($directory)
			);

			foreach ($files as $file) {
				if (!$file instanceof SplFileInfo) {
					continue;
				}

				if ($file->getExtension() !== 'php') {
					continue;
				}

				if ($this->processFile($file->getPathname(), $fix, $output)) {
					$failed = true;
				}
			}
		}

		return $failed ? Command::FAILURE : Command::SUCCESS;
	}

	private function processFile(
		string $filename,
		bool $fix,
		OutputInterface $output,
	): bool {
		$contents = file_get_contents($filename);

		if ($contents === false) {
			return false;
		}

		if ($this->hasValidHeader($contents)) {
			return false;
		}

		if (!$fix) {
			$output->writeln(
				sprintf('<error>Invalid header:</error> %s', $filename)
			);

			return true;
		}

		$contents = $this->fixHeader($contents);

		file_put_contents($filename, $contents);

		$output->writeln(
			sprintf('<info>Fixed:</info> %s', $filename)
		);

		return false;
	}

	private function hasValidHeader(string $contents): bool
	{
		return preg_match(
			'/^<\?php\r?\n\r?\ndeclare\(strict_types=1\);\r?\n\r?\n'
				. preg_quote(self::HEADER, '/')
				. '\r?\n\r?\n/',
			$contents,
		) === 1;
	}

	private function fixHeader(string $contents): string
	{
		$contents = preg_replace(
			'/^<\?php\s*'
				. '(?:declare\(strict_types=1\);\s*)?'
				. '(?:\/\*\*\s*'
				. '\* This file is part of the inachis framework.*?'
				. '\*\/\s*)?/s',
			'',
			$contents,
			1,
		);

		return "<?php\n\ndeclare(strict_types=1);\n\n"
			. self::HEADER
			. "\n\n"
			. ltrim((string) $contents);
	}
}
