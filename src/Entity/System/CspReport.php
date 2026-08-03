<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Entity\System;

use Doctrine\ORM\Mapping as ORM;
use Inachis\Enum\System\CspSeverity;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;

/**
 *  @phpstan-type PayloadShape array{csp-report: array{
 *     document-uri:string,
 *     referrer?: string,
 *     violated-directive: string,
 *     effective-directive: string,
 *     original-policy: string,
 *     blocked-uri: string,
 *     status-code: int,...
 * }}|list<array{
 *     age: int,
 *     type: string,
 *     url: string,
 *     user_agent: string,
 *     body: array{
 *         blockedUrl: string,
 *         disposition: string,
 *         effectiveDirective: string,
 *         originalPoliy: string,
 *         statusCode: int
 *     }
 * }|null>
 */
#[ORM\Entity]
#[ORM\Table(indexes: [
    new ORM\Index(name: 'idx_csp_host', columns: ['host']),
    new ORM\Index(name: 'idx_csp_directive', columns: ['effective_directive']),
    new ORM\Index(name: 'idx_csp_severity', columns: ['severity']),
    new ORM\Index(name: 'idx_csp_last_seen', columns: ['last_seen_at']),
])]
class CspReport
{
    /** @var UuidInterface|null Unique identifier for the CSP Report */
    #[ORM\Id]
    #[ORM\Column(type: 'uuid_binary', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $documentUri = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $blockedUri = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $effectiveDirective = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $violatedDirective = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $disposition = null;

    #[ORM\Column(nullable: true)]
    private ?int $statusCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $originalPolicy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sourceFile = null;

    #[ORM\Column(nullable: true)]
    private ?int $lineNumber = null;

    #[ORM\Column(nullable: true)]
    private ?int $columnNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $host = null;

    #[ORM\Column(length: 40, unique: true)]
    private string $fingerprint;

    #[ORM\Column]
    private int $occurrences = 1;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSeenAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referrer = null;

    #[ORM\Column(enumType: CspSeverity::class)]
    private CspSeverity $severity = CspSeverity::Info;

    #[ORM\Column]
    private \DateTimeImmutable $firstSeenAt;

    #[ORM\Column]
    private bool $processed = false;

    /** @var PayloadShape */
    #[ORM\Column(type: 'json')]
    private array $payload = [];

    /**
     * @return UuidInterface|null
     */
    public function getId(): ?UuidInterface
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getDocumentUri(): ?string
    {
        return $this->documentUri;
    }

    /**
     * @param string|null $documentUri
     * @return CspReport
     */
    public function setDocumentUri(?string $documentUri): CspReport
    {
        $this->documentUri = $documentUri;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getBlockedUri(): ?string
    {
        return $this->blockedUri;
    }

    /**
     * @param string|null $blockedUri
     * @return CspReport
     */
    public function setBlockedUri(?string $blockedUri): CspReport
    {
        $this->blockedUri = $blockedUri;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEffectiveDirective(): ?string
    {
        return $this->effectiveDirective;
    }

    /**
     * @param string|null $effectiveDirective
     * @return CspReport
     */
    public function setEffectiveDirective(?string $effectiveDirective): CspReport
    {
        $this->effectiveDirective = $effectiveDirective;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getViolatedDirective(): ?string
    {
        return $this->violatedDirective;
    }

    /**
     * @param string|null $violatedDirective
     * @return CspReport
     */
    public function setViolatedDirective(?string $violatedDirective): CspReport
    {
        $this->violatedDirective = $violatedDirective;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDisposition(): ?string
    {
        return $this->disposition;
    }

    /**
     * @param string|null $disposition
     * @return CspReport
     */
    public function setDisposition(?string $disposition): CspReport
    {
        $this->disposition = $disposition;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    /**
     * @param int|null $statusCode
     * @return CspReport
     */
    public function setStatusCode(?int $statusCode): CspReport
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getOriginalPolicy(): ?string
    {
        return $this->originalPolicy;
    }

    /**
     * @param string|null $originalPolicy
     * @return CspReport
     */
    public function setOriginalPolicy(?string $originalPolicy): CspReport
    {
        $this->originalPolicy = $originalPolicy;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSourceFile(): ?string
    {
        return $this->sourceFile;
    }

    /**
     * @param string|null $sourceFile
     * @return CspReport
     */
    public function setSourceFile(?string $sourceFile): CspReport
    {
        $this->sourceFile = $sourceFile;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }

    /**
     * @param int|null $lineNumber
     * @return CspReport
     */
    public function setLineNumber(?int $lineNumber): CspReport
    {
        $this->lineNumber = $lineNumber;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getColumnNumber(): ?int
    {
        return $this->columnNumber;
    }

    /**
     * @param int|null $columnNumber
     * @return CspReport
     */
    public function setColumnNumber(?int $columnNumber): CspReport
    {
        $this->columnNumber = $columnNumber;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    /**
     * @param string|null $userAgent
     * @return CspReport
     */
    public function setUserAgent(?string $userAgent): CspReport
    {
        $this->userAgent = $userAgent;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @param string|null $host
     * @return CspReport
     */
    public function setHost(?string $host): CspReport
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getFingerprint(): string
    {
        return $this->fingerprint;
    }

    /**
     * @param string $fingerprint
     * @return CspReport
     */
    public function setFingerprint(string $fingerprint): CspReport
    {
        $this->fingerprint = $fingerprint;
        return $this;
    }

    /**
     * @return int
     */
    public function getOccurrences(): int
    {
        return $this->occurrences;
    }

    /**
     * @param int $occurrences
     * @return CspReport
     */
    public function setOccurrences(int $occurrences): CspReport
    {
        $this->occurrences = $occurrences;
        return $this;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getLastSeenAt(): ?\DateTimeImmutable
    {
        return $this->lastSeenAt;
    }

    /**
     * @param \DateTimeImmutable|null $lastSeenAt
     * @return CspReport
     */
    public function setLastSeenAt(?\DateTimeImmutable $lastSeenAt): CspReport
    {
        $this->lastSeenAt = $lastSeenAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getReferrer(): ?string
    {
        return $this->referrer;
    }

    /**
     * @param string|null $referrer
     * @return CspReport
     */
    public function setReferrer(?string $referrer): CspReport
    {
        $this->referrer = $referrer;
        return $this;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getFirstSeenAt(): \DateTimeImmutable
    {
        return $this->firstSeenAt;
    }

    /**
     * @param \DateTimeImmutable $firstSeenAt
     * @return CspReport
     */
    public function setFirstSeenAt(\DateTimeImmutable $firstSeenAt): CspReport
    {
        $this->firstSeenAt = $firstSeenAt;
        return $this;
    }

    /**
     * @return PayloadShape
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @param PayloadShape $payload
     * @return CspReport
     */
    public function setPayload(array $payload): CspReport
    {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Gets the severity of the {@link CspReport}
     *
     * @return CspSeverity
     */
    public function getSeverity(): CspSeverity
    {
        return $this->severity;
    }

    /**
     * Sets the severity of the {@link CspReport}
     *
     * @param CspSeverity $severity
     * @return self
     */
    public function setSeverity(CspSeverity $severity): self
    {
        $this->severity = $severity;

        return $this;
    }

    /**
     * Gets the processing status of the {@link CspReport} - has it been reviewed
     *
     * @return bool
     */
    public function isProcessed(): bool
    {
        return $this->processed;
    }

    /**
     * Sets the processing status of the {@link CspReport}
     *
     * @param boolean $processed
     * @return self
     */
    public function setProcessed(bool $processed): self
    {
        $this->processed = $processed;
        return $this;
    }
}
