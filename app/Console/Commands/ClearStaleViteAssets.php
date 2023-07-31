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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class ClearStaleViteAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vite:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear stale Vite assets.';

    /**
     * The name of the manifest file.
     */
    protected string $manifestFilename = 'manifest.json';

    /**
     * The path to the build directory.
     */
    protected string $buildDirectory = 'build';

    /**
     * The path to the build directory.
     */
    protected string $assetsDirectory = 'assets';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $filesystem = new Filesystem;

        $currentAssets = $this->assetsToKeep();

        $staleAssets = collect($filesystem->files($this->assetsPath()))
            ->map(fn (SplFileInfo $file) => $file->getFilename())
            ->reject(fn ($filename) => in_array($filename, $currentAssets))
            ->values()
            ->map(fn ($filename) => $this->assetsPath($filename))
            ->all();

        $filesystem->delete($staleAssets);
    }

    /**
     * Get the assets to keep.
     */
    protected function assetsToKeep(): array
    {
        return collect($this->getManifest())->values()->map(function ($data) {
            return basename($data['file']);
        })->all();
    }

    /**
     * Get the manifest file contents.
     */
    protected function getManifest(): array
    {
        return json_decode(file_get_contents($this->manifestPath()), true);
    }

    /**
     * Get the assets path.
     */
    protected function assetsPath(string $extra = ''): string
    {
        return public_path($this->buildDirectory.'/'.$this->assetsDirectory.($extra ? DIRECTORY_SEPARATOR.$extra : ''));
    }

    /**
     * Get the path to the manifest file for the given build directory.
     */
    protected function manifestPath(): string
    {
        return public_path($this->buildDirectory.'/'.$this->manifestFilename);
    }
}
