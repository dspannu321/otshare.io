<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_access_logs', function (Blueprint $table) {
            $table->string('admin_name')->nullable()->after('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('admin_access_logs', function (Blueprint $table) {
            $table->dropColumn('admin_name');
        });
    }
};
