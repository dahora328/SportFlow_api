<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Enterprise;
use App\Models\Athlete;

class AthleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_athlete()
    {
        $enterpriseId = \Illuminate\Support\Facades\DB::table('enterprises')->insertGetId([
            'name' => 'Teste',
            'social_reason' => 'Teste LTDA',
            'document' => '00000000000191',
            'email' => 'teste@teste.com',
            'active' => true,
        ]);
        
        $manager = User::factory()->create([
            'is_admin' => true,
            'enterprise_id' => $enterpriseId
        ]);

        $response = $this->actingAs($manager, 'api')->postJson('/api/athletes', [
            'full_name' => 'Atleta Teste',
            'birth_date' => '2000-01-01',
            'marital_status' => 'Solteiro',
            'gender' => 'Masculino',
            'document' => '05971485078', // Valid CPF
            'address' => 'Rua Teste',
            'number' => '123',
            'neighborhood' => 'Bairro Teste',
            'zip_code' => '12345678',
            'state' => 'SP',
            'city' => 'Cidade Teste',
            'mobile_phone' => '11999999999',
            'email' => 'atleta@teste.com'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('athletes', ['email' => 'atleta@teste.com']);
    }
}
