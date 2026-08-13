<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\Page\Setting\UpdateController;
use Inachis\Exception\Updater\IncompatibleVersionException;
use Inachis\Exception\Updater\NoUpdateAvailableException;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageView;
use Inachis\Repository\Waste\WasteRepository;
use Inachis\Service\System\VersionService;
use Inachis\Updater\Downloader\Downloader;
use Inachis\Updater\Planner\UpdatePlanner;
use Inachis\Updater\Provider\GithubReleaseProvider;
use Inachis\Updater\Release\Manifest;
use Inachis\Updater\ReleaseCleaner;
use Inachis\Updater\ReleaseInstaller;
use Inachis\Updater\ReleaseLocator;
use Inachis\Updater\ReleaseRollback;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class UpdateControllerTest extends TestCase
{
    private string $tempDir;
    private string $dummyZipPath;
    private string $dummyZipSha256;
    private VersionService $versionService;
    private CacheInterface&MockObject $cache;
    private object $downloader;
    private GithubReleaseProvider $releaseProvider;
    private UpdatePlanner $updatePlanner;
    private ReleaseLocator $releaseLocator;
    private object $extractor;
    private object $verifier;
    private object $symlinkManager;
    private object $migrationRunner;
    private object $maintenanceManager;
    private ReleaseInstaller $releaseInstaller;
    private ReleaseCleaner $releaseCleaner;
    private object $releaseRollback;

    private ContainerInterface&MockObject $container;
    private Environment&MockObject $twig;
    private RouterInterface&MockObject $router;
    private CsrfTokenManagerInterface&MockObject $csrfTokenManager;
    private RequestStack $requestStack;
    private FlashBag $flashBag;

    private UpdateController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/inachis_update_ctrl_test_' . uniqid('', true);
        mkdir($this->tempDir . '/releases', 0777, true);
        mkdir($this->tempDir . '/shared', 0777, true);

        $versionFile = $this->tempDir . '/version.php';
        file_put_contents($versionFile, "<?php return ['version' => '1.0.0'];");

        // Create a real dummy zip file in tempDir
        $this->dummyZipPath = $this->tempDir . '/dummy-release.zip';
        $zip = new \ZipArchive();
        if (true === $zip->open($this->dummyZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            $zip->addFromString('version.txt', '2.0.0');
            $zip->close();
        }
        $this->dummyZipSha256 = hash_file('sha256', $this->dummyZipPath) ?: '';

        $this->versionService = new VersionService($versionFile);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->downloader = $this->createMockOrInstanceFromType(GithubReleaseProvider::class, 'downloader');
        if ($this->downloader instanceof MockObject) {
            $this->downloader->method('download')->willReturnCallback(
                function (string $url, string $destination): void {
                    if (file_exists($this->dummyZipPath)) {
                        copy($this->dummyZipPath, $destination);
                    } else {
                        file_put_contents($destination, 'dummy_archive_data');
                    }
                }
            );
        }

        $this->releaseProvider = new GithubReleaseProvider(
            owner: 'inachisphp',
            repository: 'inachis',
            downloader: $this->downloader,
            cache: $this->cache,
        );

        $this->updatePlanner = new UpdatePlanner();
        $this->releaseLocator = new ReleaseLocator($this->tempDir);

        $this->extractor = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'extractor');
        $this->verifier = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'verifier');
        $this->symlinkManager = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'symlinkManager');
        $this->migrationRunner = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'migrationRunner');
        $this->maintenanceManager = $this->createMockOrInstanceFromType(ReleaseInstaller::class, 'maintenanceManager');

        $this->releaseInstaller = new ReleaseInstaller(
            locator: $this->releaseLocator,
            extractor: $this->extractor,
            verifier: $this->verifier,
            symlinkManager: $this->symlinkManager,
            migrationRunner: $this->migrationRunner,
            maintenanceManager: $this->maintenanceManager,
        );

        $this->releaseCleaner = new ReleaseCleaner($this->releaseLocator);

        if (class_exists(ReleaseRollback::class)) {
            $this->releaseRollback = new ReleaseRollback(
                $this->releaseLocator,
                $this->symlinkManager,
                $this->maintenanceManager,
            );
        } else {
            $this->releaseRollback = $this->createMockOrInstance(ReleaseRollback::class);
        }

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $params = $this->createMock(ParameterBagInterface::class);
        $security = $this->createMock(Security::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $wasteRepository = $this->createMock(WasteRepository::class);

        $pageView = (new \ReflectionClass(PageView::class))->newInstanceWithoutConstructor();
        $pageViewFactory = $this->createMock(PageViewFactory::class);
        $pageViewFactory->method('createAdmin')->willReturn($pageView);
        $pageViewFactory->method('create')->willReturn($pageView);

        $this->flashBag = new FlashBag();
        $session = new Session(new MockArraySessionStorage(), null, $this->flashBag);
        $this->requestStack = new RequestStack();
        $currentRequest = Request::create('/');
        $currentRequest->setSession($session);
        $this->requestStack->push($currentRequest);

        $this->twig = $this->createMock(Environment::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->router->method('generate')->willReturn('/incp/settings/updater');
        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->willReturnCallback(
            static fn (string $id): bool => in_array($id, [
                'twig',
                'router',
                'security.csrf.token_manager',
                'request_stack',
            ], true)
        );
        $this->container->method('get')->willReturnCallback(
            fn (string $id): object => match ($id) {
                'twig' => $this->twig,
                'router' => $this->router,
                'security.csrf.token_manager' => $this->csrfTokenManager,
                'request_stack' => $this->requestStack,
            }
        );

        $this->controller = new UpdateController(
            $this->versionService,
            $this->releaseProvider,
            $this->updatePlanner,
            $this->releaseInstaller,
            $this->releaseCleaner,
            $this->releaseLocator,
            $this->releaseRollback,
            $this->downloader,
            $entityManager,
            $params,
            $security,
            $translator,
            $wasteRepository,
            $pageViewFactory,
            $this->requestStack,
        );
        $this->controller->setContainer($this->container);
    }

    protected function tearDown(): void
    {
        $this->removeTempDirRecursive($this->tempDir);
        parent::tearDown();
    }

    #[Test]
    public function indexDisplaysAvailableUpdate(): void
    {
        $manifest = $this->createManifest(version: '2.0.0', minimumVersion: '1.0.0');
        $this->cache->method('get')->willReturn($manifest);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('inadmin/page/settings/update.html.twig', self::callback(
                static fn (array $params): bool => $params['currentVersion'] === '1.0.0'
                    && $params['isUpToDate'] === false
                    && $params['error'] === null
                    && $params['latestManifest'] !== null
                    && $params['updatePlan'] !== null
            ))
            ->willReturn('<html>Update View</html>');

        $request = Request::create('/incp/settings/updater', 'GET');
        $response = $this->controller->index($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('<html>Update View</html>', $response->getContent());
    }

    #[Test]
    public function indexHandlesUpToDateStatus(): void
    {
        $this->cache->method('get')
            ->willThrowException(new NoUpdateAvailableException('System is up to date.'));

        $this->twig->expects(self::once())
            ->method('render')
            ->with('inadmin/page/settings/update.html.twig', self::callback(
                static fn (array $params): bool => $params['isUpToDate'] === true
                    && $params['error'] === null
                    && $params['updatePlan'] === null
            ))
            ->willReturn('<html>Up to date</html>');

        $request = Request::create('/incp/settings/updater', 'GET');
        $response = $this->controller->index($request);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function indexHandlesIncompatibleVersionError(): void
    {
        $manifest = $this->createManifest(version: '2.0.0', minimumVersion: '1.5.0');
        $this->cache->method('get')->willReturn($manifest);

        $this->twig->expects(self::once())
            ->method('render')
            ->with('inadmin/page/settings/update.html.twig', self::callback(
                static fn (array $params): bool => $params['isUpToDate'] === false
                    && is_string($params['error'])
                    && str_contains($params['error'], '1.0.0')
            ))
            ->willReturn('<html>Incompatible Error</html>');

        $request = Request::create('/incp/settings/updater', 'GET');
        $response = $this->controller->index($request);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function indexHandlesGeneralCheckFailure(): void
    {
        $this->cache->method('get')
            ->willThrowException(new \RuntimeException('GitHub API down'));

        $this->twig->expects(self::once())
            ->method('render')
            ->with('inadmin/page/settings/update.html.twig', self::callback(
                static fn (array $params): bool => $params['isUpToDate'] === false
                    && $params['error'] === 'Unable to check for updates: GitHub API down'
            ))
            ->willReturn('<html>Error View</html>');

        $request = Request::create('/incp/settings/updater', 'GET');
        $response = $this->controller->index($request);

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function applyFailsWhenCsrfTokenIsInvalid(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);

        $request = Request::create('/incp/settings/updater/apply', 'POST', ['_token' => 'invalid']);
        $response = $this->controller->apply($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/incp/settings/updater', $response->getTargetUrl());

        $flashes = $this->flashBag->get('danger');
        self::assertCount(1, $flashes);
        self::assertSame('Invalid security token. Please try again.', $flashes[0]);
    }

    #[Test]
    public function applyInstallsUpdateSuccessfully(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $manifest = $this->createManifest(version: '2.0.0', minimumVersion: '1.0.0');
        $this->cache->method('get')->willReturn($manifest);

        $request = Request::create('/incp/settings/updater/apply', 'POST', ['_token' => 'valid']);
        $response = $this->controller->apply($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/incp/settings/updater', $response->getTargetUrl());

        $flashes = $this->flashBag->get('success');
        self::assertCount(1, $flashes);
        self::assertSame('Successfully updated Inachis to v2.0.0!', $flashes[0]);
    }

    #[Test]
    public function applyHandlesInstallationFailure(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $manifest = $this->createManifest(version: '2.0.0', minimumVersion: '1.0.0', archiveUrl: '');
        $this->cache->method('get')->willReturn($manifest);

        $request = Request::create('/incp/settings/updater/apply', 'POST', ['_token' => 'valid']);
        $response = $this->controller->apply($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/incp/settings/updater', $response->getTargetUrl());

        $flashes = $this->flashBag->get('danger');
        self::assertCount(1, $flashes);
        self::assertStringContainsString('Update failed:', $flashes[0]);
    }

    #[Test]
    public function rollbackFailsWhenCsrfTokenIsInvalid(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(false);

        $request = Request::create('/incp/settings/updater/rollback', 'POST', ['_token' => 'invalid']);
        $response = $this->controller->rollback($request);

        self::assertInstanceOf(RedirectResponse::class, $response);

        $flashes = $this->flashBag->get('danger');
        self::assertCount(1, $flashes);
        self::assertSame('Invalid security token. Please try again.', $flashes[0]);
    }

    #[Test]
    public function rollbackRevertsToPreviousReleaseSuccessfully(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $oldRelease = $this->tempDir . '/releases/20260101000000-0.9.0';
        $currentRelease = $this->tempDir . '/releases/20260102000000-1.0.0';
        mkdir($oldRelease, 0777, true);
        mkdir($currentRelease, 0777, true);
        symlink($currentRelease, $this->tempDir . '/current');

        $request = Request::create('/incp/settings/updater/rollback', 'POST', ['_token' => 'valid']);
        $response = $this->controller->rollback($request);

        self::assertInstanceOf(RedirectResponse::class, $response);

        $flashes = $this->flashBag->get('success');
        self::assertCount(1, $flashes);
        self::assertSame('Successfully rolled back to v0.9.0!', $flashes[0]);
    }

    #[Test]
    public function rollbackHandlesRollbackFailure(): void
    {
        $this->csrfTokenManager->method('isTokenValid')->willReturn(true);

        $request = Request::create('/incp/settings/updater/rollback', 'POST', ['_token' => 'valid']);
        $response = $this->controller->rollback($request);

        self::assertInstanceOf(RedirectResponse::class, $response);

        $flashes = $this->flashBag->get('danger');
        self::assertCount(1, $flashes);
        self::assertStringContainsString('Rollback failed:', $flashes[0]);
    }

    private function createManifest(
        string $version = '2.0.0',
        string $minimumVersion = '1.0.0',
        string $package = 'inachis-v2.0.0.zip',
        ?string $publishedAt = '2026-08-10 12:00:00',
        ?string $archiveUrl = null,
        ?string $packageSha256 = null,
    ): Manifest {
        $reflection = new \ReflectionClass(Manifest::class);
        $manifest = $reflection->newInstanceWithoutConstructor();

        $properties = [
            'version' => $version,
            'minimumVersion' => $minimumVersion,
            'package' => $package,
            'packageSha256' => $packageSha256 ?? ($this->dummyZipSha256 ?? hash('sha256', 'dummy_archive_data')),
            'migrations' => [],
            'preserve' => [],
            'replace' => [],
            'archiveUrl' => null !== $archiveUrl ? $archiveUrl : ($this->dummyZipPath ?? 'https://example.com/inachis-v2.0.0.zip'),
            'type' => 'core',
            'releaseNotes' => 'Release notes for testing',
            'publishedAt' => $publishedAt,
        ];

        foreach ($properties as $name => $value) {
            if ($reflection->hasProperty($name)) {
                $prop = $reflection->getProperty($name);
                $prop->setValue($manifest, $value);
            }
        }

        return $manifest;
    }

    private function createMockOrInstanceFromType(string $targetClass, string $paramName): object
    {
        if (!class_exists($targetClass)) {
            return new \stdClass();
        }

        $reflection = new \ReflectionClass($targetClass);
        if (!$reflection->hasMethod('__construct')) {
            return new \stdClass();
        }

        $constructor = $reflection->getMethod('__construct');
        foreach ($constructor->getParameters() as $param) {
            if ($param->getName() === $paramName) {
                $type = $param->getType();
                if ($type instanceof \ReflectionNamedType) {
                    return $this->createMockOrInstance($type->getName());
                }
            }
        }

        return new \stdClass();
    }

    private function createMockOrInstance(string $className): object
    {
        if (!class_exists($className) && !interface_exists($className)) {
            return new \stdClass();
        }

        $reflection = new \ReflectionClass($className);

        if (!$reflection->isFinal()) {
            return $this->createMock($className);
        }

        $object = $reflection->newInstanceWithoutConstructor();
        $this->initializeTypedProperties($object);

        return $object;
    }

    private function initializeTypedProperties(object $object): void
    {
        $reflection = new \ReflectionClass($object);
        $current = $reflection;

        while (false !== $current) {
            foreach ($current->getProperties() as $prop) {
                if ($prop->isStatic() || $prop->isInitialized($object)) {
                    continue;
                }

                $type = $prop->getType();
                if (null === $type) {
                    continue;
                }

                if ($type->allowsNull()) {
                    $prop->setValue($object, null);
                    continue;
                }

                $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : null;

                if ('string' === $typeName) {
                    $prop->setValue($object, '');
                } elseif ('int' === $typeName) {
                    $prop->setValue($object, 0);
                } elseif ('bool' === $typeName) {
                    $prop->setValue($object, false);
                } elseif ('array' === $typeName) {
                    $prop->setValue($object, []);
                } elseif ($typeName && (class_exists($typeName) || interface_exists($typeName))) {
                    $depRef = new \ReflectionClass($typeName);
                    if ($depRef->isFinal()) {
                        $depObj = $depRef->newInstanceWithoutConstructor();
                        $this->initializeTypedProperties($depObj);
                        $prop->setValue($object, $depObj);
                    } else {
                        $prop->setValue($object, $this->createMock($typeName));
                    }
                }
            }
            $current = $current->getParentClass();
        }
    }

    private function removeTempDirRecursive(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_link($path)) {
                unlink($path);
            } elseif (is_dir($path)) {
                $this->removeTempDirRecursive($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }
}
