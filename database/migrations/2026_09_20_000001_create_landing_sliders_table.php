<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('eyebrow', 150)->nullable();
            $table->string('title', 220);
            $table->text('description')->nullable();
            $table->string('secondary_text', 200)->nullable();
            $table->string('button_text', 80)->nullable();
            $table->string('button_url')->nullable();
            $table->string('image_path');
            $table->string('mobile_image_path')->nullable();
            $table->string('image_alt', 180)->nullable();
            $table->unsignedInteger('sort_order')->default(1)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();

        DB::table('landing_sliders')->insert([
            [
                'eyebrow' => 'TIERRA PARA SOÑAR. ESPACIO PARA CRECER.',
                'title' => 'Lotes en las mejores ubicaciones del Perú.',
                'description' => 'Tu casa, tu inversión, tu próximo gran paso.',
                'secondary_text' => 'Un lugar propio. Un mundo de posibilidades.',
                'button_text' => 'Encuentra tu próximo lote',
                'button_url' => '#proyectos',
                'image_path' => 'vendor/adminlte/dist/img/fondolote1.jpg',
                'mobile_image_path' => null,
                'image_alt' => null,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'eyebrow' => 'PROYECTOS QUE CRECEN CONTIGO.',
                'title' => 'Invierte hoy en el lugar donde crecerá tu futuro.',
                'description' => 'Conoce proyectos con excelente ubicación, acceso y gran proyección de valorización.',
                'secondary_text' => 'Espacios pensados para vivir, crecer e invertir.',
                'button_text' => 'Conoce nuestros proyectos',
                'button_url' => '#proyectos',
                'image_path' => 'vendor/adminlte/dist/img/fondolote.jpg',
                'mobile_image_path' => null,
                'image_alt' => null,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'eyebrow' => 'OPORTUNIDADES A TU MEDIDA.',
                'title' => 'Haz realidad tu terreno con planes a tu medida.',
                'description' => 'Opciones de pago pensadas para familias e inversionistas.',
                'secondary_text' => 'Tu próxima inversión empieza hoy.',
                'button_text' => 'Quiero más información',
                'button_url' => 'https://wa.me/51972873511?text=Hola%2C%20quiero%20informaci%C3%B3n%20sobre%20sus%20proyectos.',
                'image_path' => 'vendor/adminlte/dist/img/fondolote2.jpg',
                'mobile_image_path' => null,
                'image_alt' => null,
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_sliders');
    }
};
