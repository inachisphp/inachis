<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Version;

use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Process\Process;

final readonly class VersionGenerator
{
	public function __construct(
		#[Autowire('%kernel.project_dir%')]
		private string $projectDirectory,
	) {}

	public function generate(): VersionMetadata
	{
		return new VersionMetadata(
			version: $this->getVersion(),
			commit: $this->getCommit(),
			buildDate: (new \DateTimeImmutable())
				->format(DATE_ATOM),
		);
	}

	public function writePhp(
		string $directory,
		VersionMetadata $metadata,
	): string {
		$path = rtrim($directory, DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR
			. 'version.php';

		$content = sprintf(
			"<?php\n\nreturn %s;\n",
			var_export(
				$metadata->toArray(),
				true
			)
		);

		if (file_put_contents($path, $content) === false) {
			throw new RuntimeException(
				sprintf(
					'Unable to write version file "%s".',
					$path
				)
			);
		}

		return $path;
	}

	public function writeJson(
		string $directory,
		VersionMetadata $metadata,
	): string {
		$path = rtrim($directory, DIRECTORY_SEPARATOR)
			. DIRECTORY_SEPARATOR
			. 'version.json';

		$content = json_encode(
			$metadata->toArray(),
			JSON_PRETTY_PRINT |
				JSON_UNESCAPED_SLASHES |
				JSON_THROW_ON_ERROR
		);

		if (file_put_contents($path, $content) === false) {
			throw new RuntimeException(
				sprintf(
					'Unable to write version file "%s".',
					$path
				)
			);
		}

		return $path;
	}

	private function getVersion(): string
	{
		$tag = $this->runGit([
			'describe',
			'--tags',
			'--exact-match',
		]);

		if ($tag !== null) {
			return ltrim($tag, 'v');
		}

		return $this->runGit([
			'describe',
			'--tags',
			'--always',
		]) ?? 'dev';
	}

	private function getCommit(): string
	{
		return $this->runGit([
			'rev-parse',
			'--short',
			'HEAD',
		]) ?? 'unknown';
	}

	/**
	 * @param list<string> $arguments
	 */
	private function runGit(array $arguments): ?string
	{
		$process = new Process(
			array_merge(
				['git'],
				$arguments
			),
			$this->projectDirectory
		);

		$process->setTimeout(10);
		$process->run();

		if (!$process->isSuccessful()) {
			return null;
		}

		$output = trim($process->getOutput());

		return $output !== ''
			? $output
			: null;
	}
}
