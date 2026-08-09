<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Entity\Media;

use Inachis\Entity\Media\Download;
use Inachis\Entity\Media\DownloadVersion;
use Inachis\Entity\User\User;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class DownloadTest extends TestCase
{
    private Download $download;

    protected function setUp(): void
    {
        parent::setUp();

        $this->download = new Download();
    }

    public function testGetAndSetId(): void
    {
        $uuid = Uuid::uuid1();

        $result = $this->download->setId($uuid);

        $this->assertSame($this->download, $result);
        $this->assertSame($uuid, $this->download->getId());
    }

    public function testGetAndSetTitle(): void
    {
        $result = $this->download->setTitle('Test title');

        $this->assertSame($this->download, $result);
        $this->assertSame('Test title', $this->download->getTitle());
    }

    public function testGetAndSetDescription(): void
    {
        $result = $this->download->setDescription('Test description');

        $this->assertSame($this->download, $result);
        $this->assertSame('Test description', $this->download->getDescription());
    }

    public function testSetDescriptionAllowsNull(): void
    {
        $this->download->setDescription(null);

        $this->assertNull($this->download->getDescription());
    }

    public function testGetAndSetFilename(): void
    {
        $result = $this->download->setFilename('document.pdf');

        $this->assertSame($this->download, $result);
        $this->assertSame('document.pdf', $this->download->getFilename());
    }

    public function testGetAndSetFiletype(): void
    {
        $result = $this->download->setFiletype('application/pdf');

        $this->assertSame($this->download, $result);
        $this->assertSame('application/pdf', $this->download->getFiletype());
    }

    public function testSetFiletypeRejectsInvalidMimeType(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('Invalid file type image/jpeg');

        $this->download->setFiletype('image/jpeg');
    }

    public function testValidFiletypeReturnsTrueForAllowedMimeType(): void
    {
        $this->assertTrue(
            $this->download->isValidFiletype('application/pdf'),
        );
    }

    public function testValidFiletypeReturnsFalseForDisallowedMimeType(): void
    {
        $this->assertFalse(
            $this->download->isValidFiletype('image/jpeg'),
        );
    }

    public function testSetAndGetFilesize(): void
    {
        $result = $this->download->setFilesize(100);

        $this->assertSame($this->download, $result);
        $this->assertSame(100, $this->download->getFilesize());
    }

    public function testSetFilesizeAllowsZero(): void
    {
        $this->download->setFilesize(0);

        $this->assertSame(0, $this->download->getFilesize());
    }

    public function testSetFilesizeRejectsNegativeValue(): void
    {
        $this->expectException(FileException::class);
        $this->expectExceptionMessage('File size must be a positive integer');

        $this->download->setFilesize(-1);
    }

    public function testSetAndGetChecksum(): void
    {
        $result = $this->download->setChecksum('abc123');

        $this->assertSame($this->download, $result);
        $this->assertSame('abc123', $this->download->getChecksum());
    }

    public function testVerifyChecksumReturnsTrueForMatchingChecksum(): void
    {
        $this->download->setChecksum('abc123');

        $this->assertTrue(
            $this->download->verifyChecksum('abc123'),
        );
    }

    public function testVerifyChecksumReturnsFalseForDifferentChecksum(): void
    {
        $this->download->setChecksum('abc123');

        $this->assertFalse(
            $this->download->verifyChecksum('different'),
        );
    }

    public function testGetAndSetAuthor(): void
    {
        $user = new User();

        $result = $this->download->setAuthor($user);

        $this->assertSame($this->download, $result);
        $this->assertSame($user, $this->download->getAuthor());
    }

    public function testSetAuthorAllowsNull(): void
    {
        $this->download->setAuthor();

        $this->assertNull($this->download->getAuthor());
    }

    public function testGetAndSetCreatedAt(): void
    {
        $date = new \DateTimeImmutable('1970-01-02 01:34:56');

        $result = $this->download->setCreatedAt($date);

        $this->assertSame($this->download, $result);
        $this->assertSame($date, $this->download->getCreatedAt());
    }

    public function testGetAndSetUpdatedAt(): void
    {
        $date = new \DateTimeImmutable('1970-01-02 01:34:56');

        $result = $this->download->setUpdatedAt($date);

        $this->assertSame($this->download, $result);
        $this->assertSame($date, $this->download->getUpdatedAt());
    }

    public function testVersionsAreInitiallyEmpty(): void
    {
        $this->assertTrue($this->download->getVersions()->isEmpty());
    }

    public function testGetNextVersionNumberReturnsOneWhenNoVersionsExist(): void
    {
        $this->assertSame(1, $this->download->getNextVersionNumber());
    }

    public function testGetNextVersionNumberReturnsNextVersionNumber(): void
    {
        $version = (new DownloadVersion())
            ->setDownload($this->download)
            ->setVersionNumber(3);

        $this->download->getVersions()->add($version);

        $this->assertSame(4, $this->download->getNextVersionNumber());
    }

    public function testArchiveCurrentPayloadDoesNothingWithoutFilename(): void
    {
        $this->download
            ->setFiletype('application/pdf')
            ->setFilesize(100)
            ->setChecksum('abc123');

        $this->download->archiveCurrentPayload();

        $this->assertCount(0, $this->download->getVersions());
    }

    public function testArchiveCurrentPayloadCreatesVersion(): void
    {
        $user = new User();

        $this->download
            ->setFilename('document.pdf')
            ->setFiletype('application/pdf')
            ->setFilesize(1024)
            ->setChecksum('abc123')
            ->setAuthor($user);

        $this->download->archiveCurrentPayload();

        $this->assertCount(1, $this->download->getVersions());

        /** @var DownloadVersion $version */
        $version = $this->download->getVersions()->first();

        $this->assertSame($this->download, $version->getDownload());
        $this->assertSame(1, $version->getVersionNumber());
        $this->assertSame('document.pdf', $version->getFilename());
        $this->assertSame('application/pdf', $version->getFiletype());
        $this->assertSame(1024, $version->getFilesize());
        $this->assertSame('abc123', $version->getChecksum());
        $this->assertSame($user, $version->getAuthor());
    }

    public function testArchiveCurrentPayloadCreatesNextVersion(): void
    {
        $existingVersion = (new DownloadVersion())
            ->setDownload($this->download)
            ->setVersionNumber(2)
            ->setFilename('old.pdf')
            ->setFiletype('application/pdf')
            ->setFilesize(100)
            ->setChecksum('old-checksum');

        $this->download->getVersions()->add($existingVersion);

        $this->download
            ->setFilename('new.pdf')
            ->setFiletype('application/pdf')
            ->setFilesize(200)
            ->setChecksum('new-checksum');

        $this->download->archiveCurrentPayload();

        $this->assertCount(2, $this->download->getVersions());

        /** @var DownloadVersion $version */
        $version = $this->download->getVersions()->last();

        $this->assertSame(3, $version->getVersionNumber());
        $this->assertSame('new.pdf', $version->getFilename());
        $this->assertSame('application/pdf', $version->getFiletype());
        $this->assertSame(200, $version->getFilesize());
        $this->assertSame('new-checksum', $version->getChecksum());
    }

    public function testArchiveCurrentPayloadUsesEmptyStringsForNullableValues(): void
    {
        $this->download
            ->setFilename('document.pdf')
            ->setFiletype('application/pdf')
            ->setFilesize(100);

        $this->download->archiveCurrentPayload();

        /** @var DownloadVersion $version */
        $version = $this->download->getVersions()->first();

        $this->assertSame('', $version->getChecksum());
    }

    public function testOnPrePersistSetsCreatedAndUpdatedAt(): void
    {
        $before = new \DateTimeImmutable();

        $this->download->onPrePersist();

        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual(
            $before,
            $this->download->getCreatedAt(),
        );
        $this->assertLessThanOrEqual(
            $after,
            $this->download->getCreatedAt(),
        );
        $this->assertGreaterThanOrEqual(
            $before,
            $this->download->getUpdatedAt(),
        );
        $this->assertLessThanOrEqual(
            $after,
            $this->download->getUpdatedAt(),
        );
    }

    public function testOnPrePersistDoesNotOverwriteExistingDates(): void
    {
        $createdAt = new \DateTimeImmutable('2020-01-01 10:00:00');
        $updatedAt = new \DateTimeImmutable('2021-01-01 10:00:00');

        $this->download
            ->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt);

        $this->download->onPrePersist();

        $this->assertSame($createdAt, $this->download->getCreatedAt());
        $this->assertSame($updatedAt, $this->download->getUpdatedAt());
    }

    public function testOnPreUpdateUpdatesUpdatedAt(): void
    {
        $oldUpdatedAt = new \DateTimeImmutable('2020-01-01 10:00:00');

        $this->download->setUpdatedAt($oldUpdatedAt);

        $this->download->onPreUpdate();

        $this->assertNotSame(
            $oldUpdatedAt,
            $this->download->getUpdatedAt(),
        );
        $this->assertGreaterThan(
            $oldUpdatedAt,
            $this->download->getUpdatedAt(),
        );
    }

    public function testAllowedMimeTypes(): void
    {
        $this->assertContains(
            'application/pdf',
            Download::ALLOWED_MIME_TYPES,
        );
        $this->assertContains(
            'application/zip',
            Download::ALLOWED_MIME_TYPES,
        );
        $this->assertContains(
            'text/plain',
            Download::ALLOWED_MIME_TYPES,
        );
    }

    public function testAllowedTypes(): void
    {
        $this->assertContains('.pdf', Download::ALLOWED_TYPES);
        $this->assertContains('.zip', Download::ALLOWED_TYPES);
        $this->assertContains('.docx', Download::ALLOWED_TYPES);
        $this->assertContains('.csv', Download::ALLOWED_TYPES);
    }

    public function testMaxFilesize(): void
    {
        $this->assertSame(50, Download::MAX_FILESIZE);
    }
}
