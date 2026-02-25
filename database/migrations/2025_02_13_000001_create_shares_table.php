<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('short_id', 16)->unique();
            $table->char('pickup_hash', 64);
            $table->string('object_key')->nullable();
            $table->timestamp('expires_at');
            $table->unsignedInteger('max_downloads')->default(1);
            $table->unsignedInteger('download_count')->default(0);
            $table->unsignedInteger('failed_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->json('kdf')->nullable();
            $table->json('crypto_meta')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime', 128)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamps();

            $table->index('expires_at');
            $table->index('short_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shares');
    }
};
