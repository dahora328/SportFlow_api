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
            $table->string('full_name')->comment('Nome completo do atleta');
            $table->date('birth_date')->comment('Data de nascimento');
            $table->string('marital_status')->comment('Estado civil (solteiro, casado, divorciado, viúvo)');
            $table->string('gender')->comment('Sexo/gênero (masculino, feminino, outro)');
            $table->string('document', 18)->unique()->comment('CPF ou CNPJ (documento único de identificação)');
            $table->string('address')->comment('Logradouro (rua, avenida, etc.)');
            $table->string('number', 10)->comment('Número do endereço');
            $table->string('neighborhood')->comment('Bairro');
            $table->string('zip_code', 12)->comment('CEP (código postal brasileiro)');
            $table->string('state', 2)->comment('Estado (sigla UF com 2 caracteres)');
            $table->string('city')->comment('Cidade');
            $table->string('mobile_phone', 20)->comment('Telefone celular principal (com DDD)');
            $table->string('secondary_phone', 20)->nullable()->comment('Telefone secundário ou fixo');
            $table->string('email')->nullable()->comment('E-mail para contato');
            $table->string('mother_name')->nullable()->comment('Nome completo da mãe');
            $table->string('father_name')->nullable()->comment('Nome completo do pai');
            $table->unsignedBigInteger('owner_id')->comment('ID do usuário proprietário do registro');
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade')->comment('Chave estrangeira para o usuário proprietário do registro');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('athletes', function (Blueprint $table) {
            $table->dropForeign(['owner_id']); // drop da FK
        });
        Schema::dropIfExists('athletes');
    }
};
