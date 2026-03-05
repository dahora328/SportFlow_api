<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Athlete;

class AthleteAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_update_athlete()
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $athlete = Athlete::create([
            'full_name' => 'Athlete One',
            'birth_date' => '1990-01-01',
            'marital_status' => 'Single',
            'gender' => 'M',
            'document' => '12345678901',
            'address' => 'Rua A',
            'number' => '10',
            'neighborhood' => 'Bairro',
            'zip_code' => '12345678',
            'state' => 'SP',
            'city' => 'Cidade',
            'mobile_phone' => '11999999999',
            'email' => 'athlete1@example.com',
            'mother_name' => 'Mother',
            'father_name' => 'Father',
            'owner_id' => $owner->id,
        ]);

        $this->assertTrue($owner->can('update', $athlete));
    }

    public function test_non_owner_cannot_update_athlete()
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner2@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $other = User::create([
            'name' => 'Other User',
            'email' => 'other@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $athlete = Athlete::create([
            'full_name' => 'Athlete Two',
            'birth_date' => '1992-02-02',
            'marital_status' => 'Single',
            'gender' => 'F',
            'document' => '10987654321',
            'address' => 'Rua B',
            'number' => '20',
            'neighborhood' => 'Bairro 2',
            'zip_code' => '87654321',
            'state' => 'RJ',
            'city' => 'Cidade2',
            'mobile_phone' => '21999999999',
            'email' => 'athlete2@example.com',
            'mother_name' => 'Mother',
            'father_name' => 'Father',
            'owner_id' => $owner->id,
        ]);

        $this->assertFalse($other->can('update', $athlete));
    }

    public function test_admin_override_allows_update()
    {
        $owner = User::create([
            'name' => 'Owner User',
            'email' => 'owner3@example.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        $athlete = Athlete::create([
            'full_name' => 'Athlete Admin',
            'birth_date' => '1995-03-03',
            'marital_status' => 'Single',
            'gender' => 'M',
            'document' => '22233344455',
            'address' => 'Rua C',
            'number' => '3',
            'neighborhood' => 'Bairro 3',
            'zip_code' => '33344455',
            'state' => 'SP',
            'city' => 'Cidade3',
            'mobile_phone' => '1198887777',
            'email' => 'athleteadmin@example.com',
            'mother_name' => 'Mother',
            'father_name' => 'Father',
            'owner_id' => $owner->id,
        ]);

        $this->assertTrue($admin->can('update', $athlete));
    }
}
