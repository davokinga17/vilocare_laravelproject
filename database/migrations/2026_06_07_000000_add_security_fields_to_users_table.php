<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('users', 'email_verified_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->rememberToken();
            });
        }

        if (! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone', 50)->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('must_change_password')->default(false)->after('password');
            });
        }

        if (! Schema::hasColumn('users', 'created_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->after('must_change_password')->constrained('users')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('users', 'last_login_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('last_login_at')->nullable()->after('remember_token');
            });
        }

        if (Schema::hasColumn('users', 'contact')) {
            DB::table('users')
                ->whereNull('phone')
                ->whereNotNull('contact')
                ->update(['phone' => DB::raw('contact')]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'created_by')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropConstrainedForeignId('created_by');
            });
        }

        $columnsToDrop = array_values(array_filter([
            Schema::hasColumn('users', 'phone') ? 'phone' : null,
            Schema::hasColumn('users', 'must_change_password') ? 'must_change_password' : null,
            Schema::hasColumn('users', 'last_login_at') ? 'last_login_at' : null,
            Schema::hasColumn('users', 'remember_token') ? 'remember_token' : null,
            Schema::hasColumn('users', 'email_verified_at') ? 'email_verified_at' : null,
            Schema::hasColumn('users', 'email') ? 'email' : null,
        ]));

        if ($columnsToDrop !== []) {
            Schema::table('users', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }
    }
};
