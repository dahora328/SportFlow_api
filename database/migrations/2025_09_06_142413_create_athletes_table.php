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
        Schema::create('athletes', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');                       // Nome completo
            $table->date('birth_date');                        // Data de nascimento
            $table->string('marital_status');                  // Estado civil
            $table->string('gender');                          // Sexo
            $table->string('document', 18)->unique();          // CPF/CNPJ
            $table->string('address');                         // Endereço
            $table->string('number', 10);                      // Número
            $table->string('neighborhood');                    // Bairro
            $table->string('zip_code', 12);                    // CEP
            $table->string('state', 2);                        // Estado
            $table->string('city');                            // Cidade
            $table->string('mobile_phone', 20);                // Celular
            $table->string('secondary_phone', 20)->nullable(); // Celular 2 / telefone
            $table->string('email')->nullable();               // E-mail
            $table->string('mother_name')->nullable();         // Nome da mãe
            $table->string('father_name')->nullable();         // Nome do pai
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('athletes');
    }
};
