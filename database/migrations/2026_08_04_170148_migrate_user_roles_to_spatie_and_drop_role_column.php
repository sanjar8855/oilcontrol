<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ROLES = ['superadmin', 'director', 'manager', 'employee'];

    private const MODEL_TYPE = 'App\\Models\\User';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        foreach (self::ROLES as $role) {
            DB::table('roles')->insertOrIgnore([
                'name' => $role,
                'guard_name' => 'web',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $roleIds = DB::table('roles')->where('guard_name', 'web')->pluck('id', 'name');

        DB::table('users')->select('id', 'role')->whereNotNull('role')->orderBy('id')->each(function ($user) use ($roleIds) {
            if (!isset($roleIds[$user->role])) {
                return;
            }

            DB::table('model_has_roles')->insertOrIgnore([
                'role_id' => $roleIds[$user->role],
                'model_type' => self::MODEL_TYPE,
                'model_id' => $user->id,
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', self::ROLES)->default('employee')->after('email_verified_at');
            $table->index('role');
        });

        $roleNames = DB::table('roles')->where('guard_name', 'web')->pluck('name', 'id');

        DB::table('model_has_roles')
            ->where('model_type', self::MODEL_TYPE)
            ->select('model_id', 'role_id')
            ->orderBy('model_id')
            ->each(function ($assignment) use ($roleNames) {
                if (!isset($roleNames[$assignment->role_id])) {
                    return;
                }

                DB::table('users')->where('id', $assignment->model_id)->update([
                    'role' => $roleNames[$assignment->role_id],
                ]);
            });
    }
};
