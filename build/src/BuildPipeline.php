<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class BuildPipeline
{
    /**
     * @param iterable<BuildStepInterface> $steps
     */
    public function __construct(
        #[AutowireIterator(
            'inachis.build_step',
            defaultPriorityMethod: 'priority',
        )]
        private iterable $steps,
    ) {}

    public function run(
        ReleaseWorkspace $workspace,
        SymfonyStyle $io,
    ): ReleaseWorkspace {
        foreach ($this->steps as $step) {
            $workspace = $step->execute(
                $workspace,
                $io
            );
        }

        return $workspace;
    }
}
