<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('inquiry_responses', function (Blueprint $table): void {
            $table->string('subject')->after('inquiry_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_responses', function (Blueprint $table): void {
            $table->dropColumn('subject');
        });
    }
};
