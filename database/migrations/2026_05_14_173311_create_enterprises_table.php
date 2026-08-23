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
        Schema::create('enterprises', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('social_reason', 255);
            $table->string('fantasy_name', 255);
            $table->string('owner_name', 255);
            $table->string('document', 20)->unique();
            $table->date('foundation_date');
            $table->string('IE', 20)->nullable();
            $table->string('address', 255);
            $table->string('number', 10);
            $table->string('complement', 255)->nullable();
            $table->string('neighborhood', 255);
            $table->string('city', 255);
            $table->string('state', 2);
            $table->string('zip_code', 10);
            $table->string('phone', 20);
            $table->string('email', 255)->unique();
            $table->string('logo_path', 255)->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enterprises');
    }
};
