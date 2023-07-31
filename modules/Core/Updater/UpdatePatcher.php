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

namespace Modules\Core\Updater;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;

abstract class UpdatePatcher
{
    /**
     * Run the patcher
     */
    abstract public function run(): void;

    /**
     * Check whether the patcher should run
     */
    abstract public function shouldRun(): bool;

    /**
     * Get the version number this patcher is intended for
     */
    public function version(): string
    {
        $versionFromFilename = $this->versionFromFilename();

        // semver
        if (str_contains($versionFromFilename, '.')) {
            return $versionFromFilename;
        }

        // 110 => 1.1.0
        return wordwrap($this->versionFromFilename(), 1, '.', true);
    }

    /**
     * Get the version from the filename.
     */
    public function versionFromFilename(): string
    {
        return Str::after($this->filenameWithoutExtension(), 'Update');
    }

    /**
     * Get the class filename without extension
     */
    protected function filenameWithoutExtension(): string
    {
        return str_replace('.php', '', basename((new ReflectionClass($this))->getFileName()));
    }

    /**
     * Get column indexes
     */
    protected function getColumnIndexes(string $table, string $column): array
    {
        return DB::select(
            DB::raw(
                'SHOW KEYS
                FROM '.DB::getTablePrefix().$table.'
                WHERE Column_name="'.$column.'"'
            )->getValue(DB::connection()->getQueryGrammar())
        );
    }
}
