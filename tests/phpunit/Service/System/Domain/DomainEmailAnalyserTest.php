<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\System\Domain;

use Inachis\Model\Domain\DomainDnsReport;
use Inachis\Model\Domain\Severity;
use Inachis\Model\Domain\ValidationIssue;
use Inachis\Service\System\Domain\DnsResolverInterface;
use Inachis\Service\System\Domain\DomainEmailAnalyser;
use Inachis\Validator\BimiValidator;
use Inachis\Validator\CaaValidator;
use Inachis\Validator\DkimValidator;
use Inachis\Validator\DmarcValidator;
use Inachis\Validator\MxValidator;
use Inachis\Validator\SpfValidator;
use Inachis\Validator\TlsRptValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DomainEmailAnalyserTest extends TestCase
{
    private DnsResolverInterface&MockObject $dns;
    private DkimValidator $dkimValidator;
    private SpfValidator $spfValidator;
    private DmarcValidator $dmarcValidator;
    private MxValidator $mxValidator;
    private BimiValidator $bimiValidator;
    private TlsRptValidator $tlsRptValidator;
    private CaaValidator $caaValidator;
    private DomainEmailAnalyser $analyser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dns = $this->createMock(DnsResolverInterface::class);
        $this->dkimValidator = $this->createValidatorStub(DkimValidator::class);
        $this->spfValidator = $this->createValidatorStub(SpfValidator::class);
        $this->dmarcValidator = $this->createValidatorStub(DmarcValidator::class);
        $this->mxValidator = $this->createValidatorStub(MxValidator::class);
        $this->bimiValidator = $this->createValidatorStub(BimiValidator::class);
        $this->tlsRptValidator = $this->createValidatorStub(TlsRptValidator::class);
        $this->caaValidator = $this->createValidatorStub(CaaValidator::class);

        $this->analyser = new DomainEmailAnalyser(
            $this->dns,
            $this->dkimValidator,
            $this->spfValidator,
            $this->dmarcValidator,
            $this->mxValidator,
            $this->bimiValidator,
            $this->tlsRptValidator,
            $this->caaValidator,
        );
    }

    public function testAnalyseThrowsExceptionWhenDomainIsEmpty(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Domain cannot be empty');

        $this->analyser->analyse('   ');
    }

    public function testAnalyseThrowsExceptionWhenDomainIsInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid domain.');

        $this->analyser->analyse('invalid_domain_name!!');
    }

    public function testAnalyseNormalisesDomainAndGeneratesReport(): void
    {
        $this->dns->method('getRecords')->willReturnCallback(
            static function (string $host, int $type): array {
                if ('example.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'v=spf1 ip4:192.168.1.1 -all'],
                    ];
                }
                if ('_dmarc.example.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'v=DMARC1; p=reject;'],
                    ];
                }
                if ('default._domainkey.example.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQ...'],
                    ];
                }

                return [];
            },
        );

        $report = $this->analyser->analyse('https://www.EXAMPLE.COM');

        $this->assertInstanceOf(DomainDnsReport::class, $report);
        $this->assertSame('example.com', $this->getReportProperty($report, 'domain'));
        $this->assertSame(['v=spf1 ip4:192.168.1.1 -all'], $this->getReportProperty($report, 'spfRecords'));
        $this->assertSame(['v=DMARC1; p=reject;'], $this->getReportProperty($report, 'dmarcRecords'));
        $this->assertSame(['v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQ...'], $this->getReportProperty($report, 'dkimRecords'));
    }

    public function testAnalyseDetectsDeprecatedSpfMechanismsAndIncludeIssues(): void
    {
        $this->dns->method('getRecords')->willReturnCallback(
            static function (string $host, int $type): array {
                if ('example.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'v=spf1 ptr exists redirect: include:missing-spf.com -all'],
                    ];
                }
                if ('missing-spf.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'some other txt record without spf'],
                    ];
                }

                return [];
            },
        );

        $report = $this->analyser->analyse('example.com');
        $issues = $this->getReportIssues($report);

        $messages = array_map(
            fn (ValidationIssue $issue): string => (string) $this->getIssueProperty($issue, 'message'),
            $issues,
        );

        $this->assertContains('SPF uses deprecated ptr mechanism', $messages);
        $this->assertContains('SPF uses deprecated exists mechanism', $messages);
        $this->assertContains('SPF redirect mechanism missing target', $messages);
        $this->assertContains('SPF include domain missing-spf.com has no SPF record', $messages);
    }

    public function testAnalyseChecksServerIpAuthorizationWhenProvided(): void
    {
        $this->dns->method('getRecords')->willReturnCallback(
            static function (string $host, int $type): array {
                if ('example.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'v=spf1 ip4:10.0.0.0/8 include:spf.example.com -all'],
                    ];
                }
                if ('spf.example.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'v=spf1 ip4:192.168.1.0/24 -all'],
                    ];
                }

                return [];
            },
        );

        // Authorized via included SPF subnet
        $authorizedReport = $this->analyser->analyse('example.com', '192.168.1.50');
        $authorizedMessages = array_map(
            fn (ValidationIssue $issue): string => (string) $this->getIssueProperty($issue, 'message'),
            $this->getReportIssues($authorizedReport),
        );

        $this->assertNotContains('Server IP 192.168.1.50 is NOT authorized in SPF record', $authorizedMessages);

        // Unauthorized IP
        $unauthorizedReport = $this->analyser->analyse('example.com', '203.0.113.1');
        $unauthorizedMessages = array_map(
            fn (ValidationIssue $issue): string => (string) $this->getIssueProperty($issue, 'message'),
            $this->getReportIssues($unauthorizedReport),
        );

        $this->assertContains('Server IP 203.0.113.1 is NOT authorized in SPF record', $unauthorizedMessages);
    }

    public function testAnalyseHandlesInvalidCidrInSpfRecord(): void
    {
        $this->dns->method('getRecords')->willReturnCallback(
            static function (string $host, int $type): array {
                if ('example.com' === $host && DNS_TXT === $type) {
                    return [
                        ['txt' => 'v=spf1 ip4:10.0.0.1/99 -all'],
                    ];
                }

                return [];
            },
        );

        $report = $this->analyser->analyse('example.com', '10.0.0.1');
        $messages = array_map(
            fn (ValidationIssue $issue): string => (string) $this->getIssueProperty($issue, 'message'),
            $this->getReportIssues($report),
        );

        $this->assertContains('Invalid CIDR record: 10.0.0.1/99', $messages);
        $this->assertContains('Server IP 10.0.0.1 is NOT authorized in SPF record', $messages);
    }

    public function testAnalyseHandlesMxHostTlsFailure(): void
    {
        $this->dns->method('getRecords')->willReturnCallback(
            static function (string $host, int $type): array {
                if ('example.com' === $host && DNS_MX === $type) {
                    return [
                        ['target' => '127.0.0.1', 'pri' => 10],
                    ];
                }

                return [];
            },
        );

        $report = $this->analyser->analyse('example.com');
        $issues = $this->getReportIssues($report);

        $tlsIssues = array_values(array_filter(
            $issues,
            fn (ValidationIssue $issue): bool => str_contains((string) $this->getIssueProperty($issue, 'message'), 'TLS'),
        ));

        $this->assertNotEmpty($tlsIssues);
        $this->assertSame('mx', $this->getIssueProperty($tlsIssues[0], 'category'));
        $this->assertSame(Severity::Warning, $this->getIssueProperty($tlsIssues[0], 'severity'));
        $this->assertStringContainsString('127.0.0.1', (string) $this->getIssueProperty($tlsIssues[0], 'message'));
    }

    /**
     * Retrieves validation issues from DomainDnsReport regardless of whether it uses getter or public property.
     *
     * @return list<ValidationIssue>
     */
    private function getReportIssues(DomainDnsReport $report): array
    {
        return $this->getReportProperty($report, 'issues');
    }

    /**
     * Safely reads a property or invokes a getter on ValidationIssue.
     */
    private function getIssueProperty(ValidationIssue $issue, string $propertyName): mixed
    {
        $getter = 'get'.ucfirst($propertyName);
        if (method_exists($issue, $getter)) {
            return $issue->{$getter}();
        }

        $reflection = new \ReflectionClass($issue);

        if ($reflection->hasProperty($propertyName)) {
            $prop = $reflection->getProperty($propertyName);

            return $prop->getValue($issue);
        }

        if ('category' === $propertyName) {
            foreach (['type', 'component', 'source', 'section', 'field', 'name'] as $alt) {
                $altGetter = 'get'.ucfirst($alt);
                if (method_exists($issue, $altGetter)) {
                    return $issue->{$altGetter}();
                }
                if ($reflection->hasProperty($alt)) {
                    return $reflection->getProperty($alt)->getValue($issue);
                }
            }

            $props = $reflection->getProperties();
            if (isset($props[0])) {
                return $props[0]->getValue($issue);
            }
        }

        return null;
    }

    /**
     * Safely reads a property or invokes a getter on DomainDnsReport.
     */
    private function getReportProperty(DomainDnsReport $report, string $propertyName): mixed
    {
        $getter = 'get'.ucfirst($propertyName);
        if (method_exists($report, $getter)) {
            return $report->{$getter}();
        }

        $reflection = new \ReflectionClass($report);
        if ($reflection->hasProperty($propertyName)) {
            $prop = $reflection->getProperty($propertyName);

            return $prop->getValue($report);
        }

        return null;
    }

    /**
     * Helper to safely instantiate or stub validator dependencies whether they are final or normal classes.
     * Injects the mock DNS resolver into final validators to prevent live network lookups.
     */
    private function createValidatorStub(string $className): object
    {
        $reflection = new \ReflectionClass($className);

        if ($reflection->isFinal()) {
            $validator = $reflection->newInstanceWithoutConstructor();

            $current = $reflection;
            while (false !== $current) {
                foreach ($current->getProperties() as $prop) {
                    if ($prop->isStatic()) {
                        continue;
                    }
                    $type = $prop->getType();
                    $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                    if (DnsResolverInterface::class === $typeName || in_array($prop->getName(), ['dns', 'dnsResolver'], true)) {
                        $prop->setValue($validator, $this->dns);
                    }
                }
                $current = $current->getParentClass();
            }

            return $validator;
        }

        $stub = $this->createStub($className);
        if (method_exists($className, 'validate')) {
            $stub->method('validate')->willReturn([]);
        }

        return $stub;
    }
}
