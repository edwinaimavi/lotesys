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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

           
            $table->foreignId('user_id')->constrained()->onDelete('cascade');//Usuario que registró o atiende al cliente
            $table->string('document_type', 20);//Tipo de documento
            $table->string('document_number', 20)->unique();//Número de documento
            $table->string('full_name', 150);//Nombre completo del cliente
            $table->string('first_name', 80)->nullable();//Nombres (si se desea desglosar)
            $table->string('last_name', 80)->nullable();//Apellidos
            $table->date('birth_date')->nullable();//Fecha de nacimiento (si es persona natural)
            $table->string('gender', 10)->nullable();//“M”, “F”, “Otro”
            $table->string('marital_status', 20)->nullable();//“Soltero”, “Casado”, “Divorciado”, etc.
            $table->string('occupation', 100)->nullable();//Profesión u ocupación
            $table->string('company_name', 150)->nullable();//Si es persona jurídica (empresa)
            $table->string('ruc', 15)->nullable();//Si es empresa
            $table->string('email', 120)->nullable();//Correo electrónico
            $table->string('phone', 20)->nullable();//Teléfono o celular principal
            $table->string('photo', 255)->nullable();//Foto del cliente (path o URL)
            $table->boolean('status')->default(true);//Activo/Inactivo
            $table->decimal('credit_score', 5, 2)->nullable();//Puntaje crediticio (opcional, para análisis)
  

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
