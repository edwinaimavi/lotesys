<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_about_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('eyebrow', 150)->nullable();
            $table->string('title', 220);
            $table->text('description');
            $table->string('mission_title', 100);
            $table->text('mission_text');
            $table->string('vision_title', 100);
            $table->text('vision_text');
            $table->string('image_path')->nullable();
            $table->string('image_alt', 180)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_about_sections');
    }
};
