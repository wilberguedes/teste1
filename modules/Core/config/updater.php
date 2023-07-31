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

return [
    /*
    |--------------------------------------------------------------------------
    | Version installed
    |--------------------------------------------------------------------------
    |
    | Application current installed version.
    */

    'version_installed' => \Modules\Core\Application::VERSION,

    /*
    |--------------------------------------------------------------------------
    | General configuration for the updater
    |--------------------------------------------------------------------------
    */

    'archive_url' => env('UPDATER_ARCHIVE_URL', 'https://archive.concordcrm.com'),
    'patches_archive_url' => env('PATCHES_ARCHIVE_URL', 'https://archive.concordcrm.com/patches'),
    'purchase_key' => env('PURCHASE_KEY', ''),
    'download_path' => env('UPDATER_DOWNLOAD_PATH', storage_path('updater')),

    /*
    |--------------------------------------------------------------------------
    | Exclude files from update
    |--------------------------------------------------------------------------
    |
    | Specify files which should not be updated and will be skipped during the
    | update process.
    |
    */
    'exclude_files' => [
        'public/.htaccess',
        'public/web.config',
        'public/robots.txt',
        'public/favicon.ico',
    ],

    /*
    |--------------------------------------------------------------------------
    | Exclude folders from update
    |--------------------------------------------------------------------------
    |
    | Specify folders which should not be updated and will be skipped during the
    | update process.
    |
    */
    'exclude_folders' => [
        '.git',
        '.idea',
        '__MACOSX',
        'node_modules',
        'bootstrap/cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions checker configuration
    |--------------------------------------------------------------------------
    |
    | Specify folders which should be excluded from the permissions checker.
    |
    */
    'permissions' => [
        'exclude_folders' => [
            'node_modules',
            'tests/coverage',
            'concord_crm',
            'storage/app',
            'storage/framework',
            'storage/debugbar',
            'storage/logs',
            'storage/excel',
            'public/storage',
            'storage/html-purifier-cache',
            'vendor/concordcrm/hosted',

            // old, dev files
            'resources/js',
            'app/Innoclapps',
        ],
    ],

    /*
    |---------------------------------------------------------------------------
    | Indicates whether to restart the queue when finalizing the update.
    |---------------------------------------------------------------------------
    */

    'restart_queue' => true,

    /*
    |---------------------------------------------------------------------------
    | The command used to optimize the application when finalizing the update.
    |---------------------------------------------------------------------------
    */
    'optimize' => null,

    /*
    |---------------------------------------------------------------------------
    | Register custom post and finalize update artisan commands.
    |---------------------------------------------------------------------------
    */
    'commands' => [
        'post_update' => [
            //
        ],
        'finalize' => [
            //
        ],
    ],
];
