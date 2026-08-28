<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verifica se o email já existe para não dar erro caso a migration rode duas vezes
        if (!User::where('email', 'rdrgdahora@gmail.com')->exists()) {
            User::create([
                'name' => 'Rodrigo da Hora Oliveira',
                'email' => 'rdrgdahora@gmail.com',
                'password' => Hash::make('Dahora@10'), // Coloque uma senha forte aqui
                'is_admin' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('email', 'rdrgdahora@gmail.com')->delete();
    }
};
