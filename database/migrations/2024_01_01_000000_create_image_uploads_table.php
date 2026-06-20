<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('disk');
            $table->string('filename');
            $table->string('original_path');
            $table->string('original_url');
            $table->json('variants')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('alt_text')->nullable();
            $table->timestamps();

            $table->index('disk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_uploads');
    }
};
