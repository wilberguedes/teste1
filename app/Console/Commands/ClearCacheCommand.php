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

class ClearCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'concord:clear-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the application cache.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->call('clear-compiled');
        $this->call('cache:clear');
        $this->call('view:clear');
        $this->call('route:clear');
        $this->call('config:clear');
        $this->call('html-purifier:clear');
        $this->call('fonts:clear');
    }
}
