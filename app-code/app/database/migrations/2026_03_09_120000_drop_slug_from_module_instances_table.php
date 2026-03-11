<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('module_instances', function (Blueprint $table): void {
            $table->dropColumn('slug');
        });
    }

    public function down(): void
    {
        Schema::table('module_instances', function (Blueprint $table): void {
            $table->string('slug', 255)->unique()->nullable(false)->after('name');
        });
    }
};
