<?php

namespace Tests\Feature\Controller\Api;

use Illuminate\Support\Carbon;
use Modules\Core\Facades\MailableTemplates;
use Tests\TestCase;

class SystemToolsControllerTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_system_tools_endpoints()
    {
        $this->getJson('/api/tools/json-language')->assertUnauthorized();
        $this->getJson('/api/tools/storage-link')->assertUnauthorized();
        $this->getJson('/api/tools/clear-cache')->assertUnauthorized();
        $this->getJson('/api/tools/migrate')->assertUnauthorized();
        $this->getJson('/api/tools/optimize')->assertUnauthorized();
        $this->getJson('/api/tools/seed-mailables')->assertUnauthorized();
    }

    public function test_unauthorized_user_cannot_access_system_tools_endpoints()
    {
        $this->asRegularUser()->signIn();

        $this->getJson('/api/tools/json-language')->assertForbidden();
        $this->getJson('/api/tools/storage-link')->assertForbidden();
        $this->getJson('/api/tools/clear-cache')->assertForbidden();
        $this->getJson('/api/tools/migrate')->assertForbidden();
        $this->getJson('/api/tools/optimize')->assertForbidden();
        $this->getJson('/api/tools/seed-mailables')->assertForbidden();
    }

    public function test_i18_generate_tool_can_be_executed()
    {
        $this->signIn();

        $this->getJson('/api/tools/json-language')->assertOk();

        $this->assertLessThanOrEqual(2, Carbon::parse(filemtime(config('translator.json')))->diffInSeconds());
    }

    public function test_storage_link_tool_can_be_executed()
    {
        $this->signIn();

        $storageLinkCommand = $this->mock("\Illuminate\Foundation\Console\StorageLinkCommand[handle]");
        $storageLinkCommand->shouldReceive('handle')->once();
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($storageLinkCommand);
        $this->getJson('/api/tools/storage-link');
    }

    public function test_clear_cache_tool_can_be_executed()
    {
        $this->signIn();

        $migrateCommand = $this->mock("\App\Console\Commands\ClearCacheCommand[handle]");
        $migrateCommand->shouldReceive('handle')->once();
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($migrateCommand);
        $this->getJson('/api/tools/clear-cache');
    }

    public function test_optimize_tool_can_be_executed()
    {
        $this->signIn();

        $migrateCommand = $this->mock("\App\Console\Commands\OptimizeCommand[handle]");
        $migrateCommand->shouldReceive('handle')->once();
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($migrateCommand);
        $this->getJson('/api/tools/optimize');
    }

    public function test_seed_mailable_templates_tool_can_be_executed()
    {
        $this->signIn();

        MailableTemplates::spy();

        $this->getJson('/api/tools/seed-mailables')->assertOk();

        MailableTemplates::shouldHaveReceived('seedIfRequired')->once();
    }
}
