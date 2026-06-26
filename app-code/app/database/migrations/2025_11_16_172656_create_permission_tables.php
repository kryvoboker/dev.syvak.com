<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config('permission.teams');
        $table_names = config('permission.table_names');
        $column_names = config('permission.column_names');
        $pivot_role = $column_names['role_pivot_key'] ?? 'role_id';
        $pivot_permission = $column_names['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($table_names), Exception::class, 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($column_names['team_foreign_key'] ?? null), Exception::class, 'Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');

        Schema::create($table_names['permissions'], static function (Blueprint $table) {
            // $table->engine('InnoDB');
            $table->bigIncrements('id');  // permission id
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($table_names['roles'], static function (Blueprint $table) use ($teams, $column_names) {
            // $table->engine('InnoDB');
            $table->bigIncrements('id'); // role id
            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($column_names['team_foreign_key'])->nullable();
                $table->index($column_names['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$column_names['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($table_names['model_has_permissions'], static function (Blueprint $table) use ($table_names, $column_names, $pivot_permission, $teams) {
            $table->unsignedBigInteger($pivot_permission);

            $table->string('model_type');
            $table->unsignedBigInteger($column_names['model_morph_key']);
            $table->index([$column_names['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivot_permission)
                ->references('id') // permission id
                ->on($table_names['permissions'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($column_names['team_foreign_key']);
                $table->index($column_names['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary(
                    [$column_names['team_foreign_key'], $pivot_permission, $column_names['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary',
                );
            } else {
                $table->primary(
                    [$pivot_permission, $column_names['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary',
                );
            }
        });

        Schema::create($table_names['model_has_roles'], static function (Blueprint $table) use ($table_names, $column_names, $pivot_role, $teams) {
            $table->unsignedBigInteger($pivot_role);

            $table->string('model_type');
            $table->unsignedBigInteger($column_names['model_morph_key']);
            $table->index([$column_names['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivot_role)
                ->references('id') // role id
                ->on($table_names['roles'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($column_names['team_foreign_key']);
                $table->index($column_names['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary(
                    [$column_names['team_foreign_key'], $pivot_role, $column_names['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary',
                );
            } else {
                $table->primary(
                    [$pivot_role, $column_names['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary',
                );
            }
        });

        Schema::create($table_names['role_has_permissions'], static function (Blueprint $table) use ($table_names, $pivot_role, $pivot_permission) {
            $table->unsignedBigInteger($pivot_permission);
            $table->unsignedBigInteger($pivot_role);

            $table->foreign($pivot_permission)
                ->references('id') // permission id
                ->on($table_names['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivot_role)
                ->references('id') // role id
                ->on($table_names['roles'])
                ->onDelete('cascade');

            $table->primary([$pivot_permission, $pivot_role], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $table_names = config('permission.table_names');

        throw_if(empty($table_names), Exception::class, 'Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');

        Schema::drop($table_names['role_has_permissions']);
        Schema::drop($table_names['model_has_roles']);
        Schema::drop($table_names['model_has_permissions']);
        Schema::drop($table_names['roles']);
        Schema::drop($table_names['permissions']);
    }
};
