<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->unsignedInteger('passcode_failed_attempts')->default(0)->after('failed_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('shares', function (Blueprint $table) {
            $table->dropColumn('passcode_failed_attempts');
        });
    }
};
