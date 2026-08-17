<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('accepted_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable()->after('paid_at');
            $table->index(['status', 'payment_type']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'payment_type']);
            $table->dropConstrainedForeignId('accepted_by');
            $table->dropColumn('accepted_at');
        });
    }
};
