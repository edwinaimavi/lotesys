<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_web_profiles', function (Blueprint $table) {
            $table->string('plan_image_path')->nullable()->after('mobile_image_path');
            $table->string('plan_mobile_image_path')->nullable()->after('plan_image_path');
            $table->string('plan_image_alt')->nullable()->after('plan_mobile_image_path');
            $table->string('plan_caption', 180)->nullable()->after('plan_image_alt');
        });
    }

    public function down(): void
    {
        Schema::table('project_web_profiles', function (Blueprint $table) {
            $table->dropColumn(['plan_image_path', 'plan_mobile_image_path', 'plan_image_alt', 'plan_caption']);
        });
    }
};
