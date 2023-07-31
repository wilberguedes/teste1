<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.2.2
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2023 KONKORD DIGITAL
 */

namespace Modules\Core\Tests\Feature\Updater;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Updater\Exceptions\HasWrongPermissionsException;
use Modules\Core\Updater\Exceptions\InvalidPurchaseKeyException;
use Modules\Core\Updater\Exceptions\MinPHPVersionRequirementException;
use Modules\Core\Updater\Exceptions\PurchaseKeyEmptyException;
use Modules\Core\Updater\Exceptions\PurchaseKeyUsedException;
use Modules\Core\Updater\Exceptions\ReleaseDoesNotExistsException;
use Modules\Core\Updater\Updater;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * @group updater
 */
class UpdaterTest extends TestCase
{
    use TestsUpdater;

    public function test_can_properly_retrieve_and_parse_releases_from_archive()
    {
        settings()->set([
            '_installed_date' => date('Y-m-d H:i:s'),
            '_last_updated_date' => date('Y-m-d H:i:s'),
            '_server_ip' => '127.0.01',
            '_db_driver_version' => '1',
            '_db_driver' => 'mysql',
        ])->save();

        $updater = $this->createUpdaterInstance(new Response(200, [], $this->archiveResponse()));
        $releases = $updater->getAvailableReleases();

        $this->assertCount(2, $releases);

        // Newest are first
        $this->assertEquals('1.1.0', $releases[0]->getVersion());
        $this->assertEquals('1.0.0', $releases[1]->getVersion());

        // Test zipball URL
        $this->assertStringStartsWith(config('updater.archive_url'), $releases[0]->getDownloadUrl());
        $this->assertStringStartsWith(config('updater.archive_url'), $releases[1]->getDownloadUrl());

        // Test url params
        $downloadUrl = $releases[0]->getDownloadUrl();

        $this->assertStringContainsString('identification_key', $downloadUrl);
        $this->assertStringContainsString('app_url', $downloadUrl);
        $this->assertStringContainsString('installed_version', $downloadUrl);
        $this->assertStringContainsString('server_ip', $downloadUrl);
        $this->assertStringContainsString('installed_date', $downloadUrl);
        $this->assertStringContainsString('last_updated_date', $downloadUrl);
        $this->assertStringContainsString('locale', $downloadUrl);
        $this->assertStringContainsString('php_version', $downloadUrl);
        $this->assertStringContainsString('database_driver_version', $downloadUrl);
        $this->assertStringContainsString('database_driver', $downloadUrl);
    }

    public function test_the_installed_version_can_be_retrieved()
    {
        $updater = $this->createUpdaterInstance(new Response(200, [], $this->archiveResponse()), ['version_installed' => '1.0.0']);

        $this->assertEquals('1.0.0', $updater->getVersionInstalled());
    }

    public function test_latest_available_version_can_be_retrieved()
    {
        $updater = $this->createUpdaterInstance(new Response(200, [], $this->archiveResponse()));

        $this->assertEquals('1.1.0', $updater->getVersionAvailable());
    }

    public function test_it_can_properly_find_a_release()
    {
        $updater = $this->createUpdaterInstance(new Response(200, [], $this->archiveResponse()));
        $this->assertEquals('1.0.0', $updater->find('1.0.0')->getVersion());

        $updater = $this->createUpdaterInstance(new Response(200, [], $this->archiveResponse()));
        // When release not available, returns the latest one
        $this->assertEquals('1.1.0', $updater->find('non-existent')->getVersion());
    }

    public function test_it_can_determine_whether_new_version_is_available()
    {
        $this->assertTrue(
            $this->createUpdaterInstance(new Response(200, [], $this->archiveResponse()), ['version_installed' => '1.0.0'])->isNewVersionAvailable()
        );

        $this->assertFalse(
            $this->createUpdaterInstance(new Response(200, [], $this->archiveResponse()), ['version_installed' => '1.1.0'])->isNewVersionAvailable()
        );
    }

    public function test_cannot_perform_update_when_min_php_requirement_is_not_met()
    {
        $this->expectException(MinPHPVersionRequirementException::class);

        $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(Updater::MIN_PHP_VERSION_REQUIREMENT_CODE),
        ])->fetch();
    }

    public function test_cannot_perform_update_when_release_does_not_exists()
    {
        $this->expectException(ReleaseDoesNotExistsException::class);

        $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(Updater::RELEASE_DOES_NOT_EXISTS_CODE),
        ])->fetch();
    }

    public function test_cannot_perform_update_when_purchase_key_is_invalid()
    {
        $this->expectException(InvalidPurchaseKeyException::class);

        $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(Updater::INVALID_PURCHASE_KEY_CODE),
        ])->fetch();
    }

    public function test_cannot_perform_update_when_purchase_key_is_already_used()
    {
        $this->expectException(PurchaseKeyUsedException::class);

        $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(Updater::PURCHASE_KEY_USED_CODE),
        ])->fetch();
    }

    public function test_cannot_perform_update_when_purchase_key_is_empty()
    {
        $this->expectException(PurchaseKeyEmptyException::class);

        $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(Updater::PURCHASE_KEY_EMPTY_CODE),
        ])->fetch();
    }

    public function test_exception_is_thrown_when_no_archive_url_provided()
    {
        $this->expectException(\Exception::class);

        $this->createUpdaterInstance(new Response, ['archive_url' => ''])->getAvailableReleases();
    }

    public function test_it_can_download_new_a_release()
    {
        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ]);

        $updater->fetch();

        $this->assertFileExists(storage_path('updater/1.1.0.zip'));
    }

    public function test_it_does_not_download_release_if_the_archive_exists()
    {
        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles(storage_path('updater/1.1.0.zip')))),
        ]);

        $updater->fetch();

        try {
            $updater->fetch();
            $this->assertTrue(true);
        } catch (\OutOfBoundsException $e) {
            $this->assertFalse(true, 'A release was fetched, but it was not supposed to.');
        }
    }

    public function test_can_perform_update_and_extract_files()
    {
        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ]);

        $release = $updater->fetch();

        $updater->update($release);
        $this->assertFileExists(config_path('test-config.php'));
        $this->assertFileExists(app_path('TestModel.php'));
        $this->assertFileExists(app_path('UpdateNewFeature/DummyClass.php'));
        $this->assertFileExists(base_path('routes/test-routes.php'));
    }

    public function test_purchase_key_is_added_as_bearer_authorization_header_in_request()
    {
        $purchaseKey = 'f327aafb-21af-4ddc-9148-a82b7b7dd027';

        config(['updater.purchase_key' => $purchaseKey]);

        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ]);

        $release = $updater->fetch();

        $updater->update($release);

        $this->assertEquals(
            'Bearer '.$purchaseKey,
            $this->guzzleMock->getLastRequest()->getHeaders()['Authorization'][0]
        );
    }

    public function test_update_can_exclude_specified_folders()
    {
        config([
            'updater.exclude_folders' => ['excluded'],
        ]);

        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ]);

        $release = $updater->fetch();

        $updater->update($release);

        $this->assertDirectoryDoesNotExist(base_path('excluded'));
    }

    public function test_update_can_exclude_specified_files()
    {
        config(['updater.exclude_files' => [
            'test-root-file.php',
            'config/test-config.php',
        ],
        ]);

        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ]);

        $release = $updater->fetch();

        $updater->update($release);

        $this->assertFileDoesNotExist(base_path('test-root-file.php'));
        $this->assertFileDoesNotExist(base_path('config/test-config.php'));
    }

    public function test_cannot_perform_update_with_invalid_files_permissions()
    {
        Updater::checkPermissionsUsing(fn ($finder, $path, $excludedFolders) => false);

        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ]);

        $this->expectException(HasWrongPermissionsException::class);

        $release = $updater->fetch();

        $updater->update($release);
    }

    public function test_it_can_retrieve_the_release_version()
    {
        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ]);

        $release = $updater->fetch();

        $updater->update($release);

        $this->assertSame('1.1.0', $release->getVersion());
    }

    public function test_can_perform_update_via_the_console_command()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ]);
        });

        $this->artisan('updater:update')
            ->expectsOutput('Configuring purchase key')
            ->expectsOutput('Updating... this may take several minutes')
            ->expectsOutput('Putting the application into maintenance mode')
            ->expectsOutput('Performing update')
            ->expectsOutput('Bringing the application out of maintenance mode')
            ->doesntExpectOutput('Increasing PHP config values')
            ->doesntExpectOutput('Optimizing application')
            ->assertSuccessful();

        $this->assertTrue(Schema::hasTable('test_update'));
    }

    public function test_update_command_uses_the_provided_purchase_key()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ]);
        });

        $this->artisan('updater:update', [
            '--key' => 'dummy',
        ]);

        $updater = app(Updater::class);

        $this->assertEquals('dummy', $updater->getPurchaseKey());
    }

    public function test_update_command_uses_the_configuration_purchase_key_when_provided_key_is_empty_string()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ], ['purchase_key' => 'user-purchase-key']);
        });

        $this->artisan('updater:update', [
            '--key' => '',
        ]);
        $updater = app(Updater::class);

        $this->assertEquals('user-purchase-key', $updater->getPurchaseKey());
    }

    public function test_update_command_uses_the_configuration_purchase_key_when_provided_key_is_empty_null()
    {
        App::singleton(Updater::class, function () {
            return $this->createUpdaterInstance([
                new Response(200, [], $this->archiveResponse()),
                new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
            ], ['purchase_key' => 'user-purchase-key']);
        });

        $this->artisan('updater:update', [
            '--key' => null,
        ]);
        $updater = app(Updater::class);

        $this->assertEquals('user-purchase-key', $updater->getPurchaseKey());
    }

    public function test_it_does_not_perform_update_if_the_latest_is_already_installed()
    {
        $updater = $this->createUpdaterInstance([
            new Response(200, [], $this->archiveResponse()),
            new Response(200, [], file_get_contents($this->createZipFromFixtureFiles())),
        ], ['version_installed' => '1.1.0']);

        App::singleton(Updater::class, function () use (&$updater) {
            return $updater;
        });

        $this->artisan('updater:update')
            ->expectsOutput('The latest version '.$updater->getVersionInstalled().' is already installed.')
            ->assertSuccessful();
    }

    // public function test_updater_download_folder_is_created_when_not_exists()
    // {
    //     File::cleanDirectory(storage_path('updater'));
    //     rmdir(storage_path('updater'));
    //     $this->assertDirectoryDoesNotExist(storage_path('updater'));

    //     app(Updater::class);

    //     $this->assertDirectoryExists(storage_path('updater'));
    // }

    protected function fixtureFilesPath()
    {
        return module_path('Core', 'Tests/Fixtures/update');
    }

    protected function zipPathForFixtureFiles()
    {
        return storage_path('updater/test-1.1.0.zip');
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Provide fake Finder to the actual fixtures to avoid looping over thousands of files
        // to check whether they have the necessary permissions
        Updater::providePermissionsCheckerFinderUsing(function ($path) {
            return (new Finder)->in(module_path('Core', 'Tests/Fixtures/update'));
        });

        $this->cleanFixturesFiles();
    }

    /**
     * Tear down the tests
     */
    protected function tearDown(): void
    {
        Updater::providePermissionsCheckerFinderUsing(null);
        Updater::checkPermissionsUsing(null);
        $this->guzzleMock = null;
        $this->cleanFixturesFiles();
        parent::tearDown();
    }
}
