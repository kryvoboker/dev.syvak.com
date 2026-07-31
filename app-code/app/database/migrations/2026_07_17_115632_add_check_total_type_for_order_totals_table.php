<?php

declare(strict_types=1);

use App\Enums\Order\TotalTypesEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_prefix = DB::connection()->getTablePrefix();
        $db_pdo = DB::getPdo();
        $quoted_total_types = array_map(
            static fn (string $value): string => $db_pdo->quote($value),
            TotalTypesEnum::values(),
        );
        $total_types = implode(', ', $quoted_total_types);

        $sql = "
            ALTER TABLE `{$table_prefix}order_totals`
                ADD CONSTRAINT `{$table_prefix}order_totals_total_type_check`
                CHECK (`total_type` IN ($total_types))
        ";

        DB::statement($sql);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table_prefix = DB::connection()->getTablePrefix();

        DB::statement("
            ALTER TABLE `{$table_prefix}order_totals`
            DROP CHECK `{$table_prefix}order_totals_total_type_check`
        ");
    }
};
