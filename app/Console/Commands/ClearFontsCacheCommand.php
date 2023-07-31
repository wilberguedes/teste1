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

class ClearFontsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fonts:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear dompdf fonts cache.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $filesystem): void
    {
        $filesystem->deepCleanDirectory(
            $this->laravel['config']->get('dompdf.options.font_cache')
        );

        $this->info('Fonts cache has been flushed!');
    }
}
