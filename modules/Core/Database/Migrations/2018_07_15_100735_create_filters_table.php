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

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('identifier')->index();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->boolean('is_shared')->default(false);
            $table->boolean('is_readonly')->default(false);
            $table->longText('rules');
            $table->string('flag', 50)->unique()->nullable();
            $table->timestamps();
        });
    }

     /**
      * Reverse the migrations.
      *
      * @codeCoverageIgnore
      */
     public function down(): void
     {
        Schema::dropIfExists('filters');
    }
};
