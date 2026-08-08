<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build\Steps;

use Inachis\Build\BuildStepInterface;
use Inachis\Build\ReleaseWorkspace;
use Inachis\Build\Version\VersionGenerator;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(priority: 250)]
final readonly class GenerateVersionFile implements BuildStepInterface
{
	public function __construct(
		private VersionGenerator $generator,
	) {}

	public function execute(
		ReleaseWorkspace $workspace,
		SymfonyStyle $io,
	): ReleaseWorkspace {
		$io->section('Generating version metadata');

		$configDirectory = $workspace->path
			. DIRECTORY_SEPARATOR
			. 'config';

		if (
			!is_dir($configDirectory) &&
			!mkdir($configDirectory, 0775, true) &&
			!is_dir($configDirectory)
		) {
			throw new \RuntimeException(
				sprintf(
					'Unable to create config directory "%s".',
					$configDirectory
				)
			);
		}

		$metadata = $this->generator->generate();

		$phpFile = $this->generator->writePhp(
			$configDirectory,
			$metadata
		);

		$jsonFile = $this->generator->writeJson(
			$configDirectory,
			$metadata
		);

		$io->success(sprintf(
			'Version generated: %s (%s)',
			$metadata->version,
			$metadata->commit
		));

		return new ReleaseWorkspace(
			path: $workspace->path,
			definition: $workspace->definition,
			metadata: [
				...$workspace->metadata,
				'version' => $metadata->version,
				'commit' => $metadata->commit,
				'version_php' => $phpFile,
				'version_json' => $jsonFile,
			],
		);
	}
}
