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

namespace App;

use Illuminate\Support\Arr;

class PreInstall
{
    public string $envPath;

    public string $envExamplePath;

    /**
     * Initialize new PreInstall instance.
     */
    public function __construct(protected string $rootDir, protected string $url)
    {
        $this->url = $url;
        $this->rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $this->envPath = $this->rootDir.'.env';
        $this->envExamplePath = $this->rootDir.'.env.example';
    }

    /**
     * Check whether the .env file exist.
     */
    private function envFileExist(): bool
    {
        return file_exists($this->envPath);
    }

    /**
     * Check whether the .env.example file exist.
     */
    private function envExampleFileExist(): bool
    {
        return file_exists($this->envExamplePath);
    }

    /**
     * Generate random application key.
     */
    private function generateAppRandomKey(): string
    {
        return 'base64:'.base64_encode(random_bytes(32));
    }

    /**
     * Write the key in the environment file.
     */
    private function writeKeyInEnvironmentFile(string $key): void
    {
        file_put_contents($this->envPath, preg_replace(
            '/^APP_KEY=.*/m',
            'APP_KEY='.$key,
            file_get_contents($this->envPath)
        ));
    }

    /**
     * Write the APP_URL value in the environment file.
     */
    private function writeUrlInEnvironmentFile(string $url): void
    {
        file_put_contents($this->envPath, preg_replace(
            '/^APP_URL=.*/m',
            'APP_URL='.$url,
            file_get_contents($this->envPath)
        ));
    }

    /**
     * Write the IDENTIFICATION_KEY value in the environment file.
     */
    private function writeIdentificationKeyInEnvironmentFile(string $key): void
    {
        file_put_contents($this->envPath, preg_replace(
            '/^IDENTIFICATION_KEY=.*/m',
            'IDENTIFICATION_KEY='.$key,
            file_get_contents($this->envPath)
        ));
    }

    /**
     * Get cached config value.
     */
    private function getCachedConfigValue(string $key): mixed
    {
        if (file_exists($this->rootDir.'bootstrap/cache/config.php')) {
            $config = include $this->rootDir.'bootstrap/cache/config.php';

            if (! empty($config)) {
                return ! empty(Arr::get($config, $key)) ? Arr::get($config, $key) : '';
            }
        }

        return '';
    }

    /**
     * Get config value from .env.
     */
    private function getEnvConfigValue(string $envKey): string
    {
        // Read .env file into $_ENV
        try {
            \Dotenv\Dotenv::create(
                \Illuminate\Support\Env::getRepository(),
                $this->rootDir
            )->load();
        } catch (\Exception) {
            // Do nothing
        }

        return ! empty($_ENV[$envKey]) ? $_ENV[$envKey] : '';
    }

    /**
     * Clear the application config cache.
     */
    private function clearConfigCache(): void
    {
        if (file_exists($this->rootDir.'bootstrap/cache/config.php')) {
            unlink($this->rootDir.'bootstrap/cache/config.php');
        }
    }

    /**
     * Show installer error.
     */
    private function showPreInstallError(string $msg): never
    {
        echo $msg;
        exit;
    }

    /**
     * Create the initial .env file if not exist.
     */
    protected function createEnvFileIfNotExists(): void
    {
        // Check if .env.example exists
        if ($this->envFileExist()) {
            return;
        }

        if (! $this->envExampleFileExist()) {
            $this->showPreInstallError(
                'File <strong>.env.example</strong> not found. Please make sure to copy this file from the downloaded files.'
            );
        }

        // Copy .env.example
        copy($this->envExamplePath, $this->envPath);

        if (! $this->envFileExist()) {
            $this->showPermissionsError();
        }
    }

    /**
     * Init the pre-installation.
     */
    public function init(): void
    {
        $this->createEnvFileIfNotExists();

        $envAppKey = $this->getEnvConfigValue('APP_KEY');
        $cachedAppKey = $this->getCachedConfigValue('app.key');
        $this->makeSureEmptyKeyIsPresent('APP_KEY');

        if ($cachedAppKey && ! $envAppKey) {
            $this->writeKeyInEnvironmentFile($cachedAppKey);
        } elseif ($envAppKey && ! $cachedAppKey) {
            $this->writeKeyInEnvironmentFile($envAppKey);
        } elseif (! $envAppKey) {
            $this->writeKeyInEnvironmentFile($this->generateAppRandomKey());
        }

        $envIdentificationKey = $this->getEnvConfigValue('IDENTIFICATION_KEY');
        $cachedIdentificationKey = $this->getCachedConfigValue('core.key');
        $this->makeSureEmptyKeyIsPresent('IDENTIFICATION_KEY');

        if ($cachedIdentificationKey && ! $envIdentificationKey) {
            $this->writeIdentificationKeyInEnvironmentFile($cachedIdentificationKey);
        } elseif ($envIdentificationKey && ! $cachedIdentificationKey) {
            $this->writeIdentificationKeyInEnvironmentFile($envIdentificationKey);
        } elseif (! $envIdentificationKey) {
            $this->writeIdentificationKeyInEnvironmentFile((string) \Illuminate\Support\Str::uuid());
        }

        $this->makeSureEmptyKeyIsPresent('APP_URL');
        $this->writeUrlInEnvironmentFile($this->url);

        $this->clearConfigCache();
    }

    /**
     * Make sure that the needed keys are present in the .env file
     */
    private function makeSureEmptyKeyIsPresent(string $key): void
    {
        // Add APP_KEY= to the .env file if needed
        // Without APP_KEY= the key will not be saved
        if (! preg_match('/^'.$key.'=/m', file_get_contents($this->envPath))) {
            if (! file_put_contents($this->envPath, PHP_EOL.$key.'=', FILE_APPEND)) {
                $this->showPermissionsError();
            }
        }
    }

    /**
     * Helper function to show permissions error
     */
    private function showPermissionsError(): never
    {
        $rootDirNoSlash = rtrim($this->rootDir, DIRECTORY_SEPARATOR);

        $this->showPreInstallError('<div style="font-size:18px;">Web installer could not write data into <strong>'.$this->envPath.'</strong> file. Please give your web server user (<strong>'.get_current_process_user().'</strong>) write permissions in <code><pre style="background: #f0f0f0;
            padding: 15px;
            width: 50%;
            margin-top:0px;
            border-radius: 4px;">
sudo chown '.get_current_process_user().':'.get_current_process_user().' -R '.$rootDirNoSlash.'
sudo find '.$rootDirNoSlash.' -type d -exec chmod 755 {} \;
sudo find '.$rootDirNoSlash.' -type f -exec chmod 644 {} \;
</pre></code>');
    }
}
