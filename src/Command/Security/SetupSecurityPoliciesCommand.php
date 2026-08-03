<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Command\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Security\SecurityPolicy;
use Inachis\Enum\Security\AuthenticationPolicy;
use Inachis\Enum\Security\PasswordStrengthLevel;
use Inachis\Enum\Security\SensitiveAction;
use Inachis\Repository\Security\SecurityPolicyRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'inachis:security:setup-policies',
    description: 'Creates or updates the default security policies.'
)]
class SetupSecurityPoliciesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityPolicyRepository $securityPolicyRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'reset',
            null,
            InputOption::VALUE_NONE,
            'Delete all existing security policies before recreating them.'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        if ($input->getOption('reset')) {
            foreach ($this->securityPolicyRepository->findAll() as $policy) {
                $this->entityManager->remove($policy);
            }

            $this->entityManager->flush();
        }

        $output->writeln('<info>Creating security policies...</info>');

        foreach ($this->getDefaultPolicies() as $policyData) {
            $policy = $this->securityPolicyRepository->findOneBy([
                'identifier' => $policyData['identifier'],
            ]);

            if (!$policy instanceof SecurityPolicy) {
                $policy = new SecurityPolicy();
            }

            $policy
                ->setName($policyData['name'])
                ->setIdentifier($policyData['identifier'])
                ->setDescription($policyData['description'])
                ->setReadOnly($policyData['readOnly'])
                ->setActive($policyData['active'])
                ->setMinimumPasswordLength($policyData['minimumPasswordLength'])
                ->setMaximumPasswordLength($policyData['maximumPasswordLength'])
                ->setPasswordStrength($policyData['passwordStrength'])
                ->setRejectCompromisedPasswords($policyData['rejectCompromisedPasswords'])
                ->setPasswordReuseLimit($policyData['passwordReuseLimit'])
                ->setMinimumPasswordAgeDays($policyData['minimumPasswordAgeDays'])
                ->setPasswordLifetimeDays($policyData['passwordLifetimeDays'])
                ->setAdministratorPolicy($policyData['administratorPolicy'])
                ->setSuperAdministratorPolicy($policyData['superAdministratorPolicy'])
                ->setRequireStepUpAuthentication($policyData['requireStepUpAuthentication'])
                ->setStepUpRequiredActions([]);

            foreach ($policyData['stepUpActions'] as $action) {
                $policy->addStepUpRequiredAction($action);
            }

            $this->entityManager->persist($policy);
        }

        $this->entityManager->flush();

        $output->writeln('<info>Security policies updated.</info>');

        return Command::SUCCESS;
    }

    /**
     * @return list<array{
     *     name: string,
     *     identifier: string,
     *     description: string,
     *     readOnly: bool,
     *     active: bool,
     *     minimumPasswordLength: int,
     *     maximumPasswordLength: ?int,
     *     passwordStrength: PasswordStrengthLevel,
     *     rejectCompromisedPasswords: bool,
     *     passwordReuseLimit: int,
     *     minimumPasswordAgeDays: ?int,
     *     passwordLifetimeDays: ?int,
     *     administratorPolicy: AuthenticationPolicy,
     *     superAdministratorPolicy: AuthenticationPolicy,
     *     requireStepUpAuthentication: bool,
     *     stepUpActions: list<SensitiveAction>
     * }>
     */
    private function getDefaultPolicies(): array
    {
        $defaultActions = [
            SensitiveAction::ROLE_MANAGEMENT,
            SensitiveAction::SECURITY_CONFIGURATION_CHANGE,
            SensitiveAction::MFA_RESET,
        ];

        return [
            [
                'name' => 'Default',
                'identifier' => 'default',
                'description' => 'Recommended security policy for most installations.',
                'readOnly' => true,
                'active' => true,
                'minimumPasswordLength' => 14,
                'maximumPasswordLength' => null,
                'passwordStrength' => PasswordStrengthLevel::STANDARD,
                'rejectCompromisedPasswords' => true,
                'passwordReuseLimit' => 5,
                'minimumPasswordAgeDays' => null,
                'passwordLifetimeDays' => null,
                'administratorPolicy' => AuthenticationPolicy::MFA_REQUIRED,
                'superAdministratorPolicy' => AuthenticationPolicy::WEBAUTHN_REQUIRED,
                'requireStepUpAuthentication' => true,
                'stepUpActions' => $defaultActions,
            ],

            [
                'name' => 'Strict',
                'identifier' => 'strict',
                'description' => 'High security policy for sensitive environments.',
                'readOnly' => true,
                'active' => false,
                'minimumPasswordLength' => 18,
                'maximumPasswordLength' => null,
                'passwordStrength' => PasswordStrengthLevel::VERY_STRONG,
                'rejectCompromisedPasswords' => true,
                'passwordReuseLimit' => 12,
                'minimumPasswordAgeDays' => 1,
                'passwordLifetimeDays' => null,
                'administratorPolicy' => AuthenticationPolicy::WEBAUTHN_REQUIRED,
                'superAdministratorPolicy' => AuthenticationPolicy::WEBAUTHN_REQUIRED,
                'requireStepUpAuthentication' => true,
                'stepUpActions' => SensitiveAction::cases(),
            ],

            [
                'name' => 'Custom',
                'identifier' => 'custom',
                'description' => 'Editable security policy.',
                'readOnly' => false,
                'active' => false,
                'minimumPasswordLength' => 14,
                'maximumPasswordLength' => null,
                'passwordStrength' => PasswordStrengthLevel::STANDARD,
                'rejectCompromisedPasswords' => true,
                'passwordReuseLimit' => 5,
                'minimumPasswordAgeDays' => null,
                'passwordLifetimeDays' => null,
                'administratorPolicy' => AuthenticationPolicy::MFA_REQUIRED,
                'superAdministratorPolicy' => AuthenticationPolicy::WEBAUTHN_REQUIRED,
                'requireStepUpAuthentication' => true,
                'stepUpActions' => $defaultActions,
            ],
        ];
    }
}
