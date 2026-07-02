<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('ukr_poshta_regions', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('region_id')->unique()->nullable();
            $table->string('region_ua', 500)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ukr_poshta_regions');
    }
};
