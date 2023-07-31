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

namespace Modules\Core\Resource\Import;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\Models\Import;

class SkipFileGenerator implements FromArray
{
    /**
     * Skip reason heading name
     */
    const SKIP_REASON_HEADING = '--Skip Reason--';

    /**
     * Initialize new SkipFileGenerator instance
     *
     * @param  \Illuminate\Support\Collection  $failures
     * @param  \Illuminate\Support\Collection  $mappings
     */
    public function __construct(protected Import $import, protected $failures, protected $mappings)
    {
    }

    /**
     * Creates the import skip file.
     */
    public function array(): array
    {
        return [
            $this->headings(),
            ...$this->rows(),
        ];
    }

    /**
     * Store the skip file in storage.
     */
    public function store(): string
    {
        $path = $this->import->storagePath($this->filename());

        Excel::store($this, $path, $this->import::disk());

        return $path;
    }

    /**
     * Get the skip file filename.
     */
    public function filename(): string
    {
        $filename = basename($this->import->file_path);

        if (! str_starts_with($filename, 'skip-file-')) {
            $filename = 'skip-file-'.$filename;
        }

        return $filename;
    }

    /**
     * Group all of the validation errors grouped per row.
     */
    protected function errors(): array
    {
        $grouped = [];

        foreach ($this->failures as $failure) {
            $grouped[$failure->row()] = array_merge(
                $grouped[$failure->row()] ?? [],
                $failure->errors()
            );
        }

        return $grouped;
    }

    /**
     * Get the skip file headings.
     */
    public function headings(): array
    {
        return $this->mappings->pluck('original')
            ->forget(static::SKIP_REASON_HEADING)
            ->prepend(static::SKIP_REASON_HEADING)
            ->all();
    }

    /**
     * Get the skip file rows.
     */
    public function rows(): array
    {
        $errors = $this->errors();

        return $this->failures->unique(fn (Failure $failure) => $failure->row())
            ->map(function (Failure $failure) use ($errors) {
                return [
                    implode(PHP_EOL, $errors[$failure->row()]),
                    ...array_values($failure->values()['_original']),
                ];
            })->all();
    }
}
