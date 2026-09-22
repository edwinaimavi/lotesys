<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_web_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('show_on_web')->default(false)->index();
            $table->boolean('featured_on_home')->default(false)->index();
            $table->string('commercial_status', 30)->default('coming_soon')->index();
            $table->string('cover_image_path')->nullable();
            $table->string('mobile_image_path')->nullable();
            $table->text('short_description')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('badge_text', 80)->nullable();
            $table->unsignedInteger('sort_order')->default(1)->index();
            $table->timestamps();

            $table->index(['show_on_web', 'featured_on_home', 'sort_order'], 'project_web_home_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_web_profiles');
    }
};
