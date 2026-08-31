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
        if (!User::where('email', 'rodrigoliveira328@hotmail.com')->exists()) {
            User::create([
                'name' => 'Rodrigo da Hora Oliveira',
                'email' => 'rodrigoliveira328@hotmail.com',
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
        User::where('email', 'rodrigoliveira328@hotmail.com')->delete();
    }
};
