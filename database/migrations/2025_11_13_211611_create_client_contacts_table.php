<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('client_contacts', function (Blueprint $table) {
            $table->id();


            // FK al cliente principal
            $table->foreignId('client_id')->constrained()->onDelete('cascade');

            // Tipo de contacto: domicilio, trabajo, referencia, otro
            $table->enum('contact_type', ['Domicilio', 'Trabajo', 'Referencia', 'Otro'])->default('Domicilio');

            // Dirección / localización
            $table->string('address', 255)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('reference', 255)->nullable(); // punto de referencia

            // Contacto directo
            $table->string('phone', 20)->nullable();
            $table->string('alt_phone', 20)->nullable();
            $table->string('email', 120)->nullable();

            // Datos de la persona de referencia (si aplica)
            $table->string('contact_name', 120)->nullable();
            $table->string('relationship', 50)->nullable();

            // Si es el contacto principal
            $table->boolean('is_primary')->default(false);

            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_contacts');
    }
};
