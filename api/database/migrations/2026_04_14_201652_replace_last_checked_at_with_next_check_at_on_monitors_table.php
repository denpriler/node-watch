<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('last_checked_at');
            $table->timestamp('next_check_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('monitors', function (Blueprint $table) {
            $table->dropColumn('next_check_at');
            $table->timestamp('last_checked_at')->nullable()->after('is_active');
        });
    }
};
