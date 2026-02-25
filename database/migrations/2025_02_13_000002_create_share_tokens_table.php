<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('share_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('share_id')->constrained('shares')->cascadeOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('share_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('share_tokens');
    }
};
