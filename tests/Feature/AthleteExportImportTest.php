<?php

namespace Tests\Feature;

use App\Models\Athlete;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AthleteExportImportTest extends TestCase
{
    use RefreshDatabase;

    private function createEnterprise(): int
    {
        return DB::table('enterprises')->insertGetId([
            'name' => 'Teste',
            'fantasy_name' => 'Teste Fantasia',
            'owner_name' => 'Dono Teste',
            'social_reason' => 'Teste LTDA',
            'document' => '00000000000191',
            'foundation_date' => '2000-01-01',
            'address' => 'Rua Teste',
            'number' => '123',
            'neighborhood' => 'Bairro Teste',
            'city' => 'Cidade Teste',
            'state' => 'SP',
            'zip_code' => '12345678',
            'phone' => '11999999999',
            'email' => 'teste@teste.com',
            'active' => true,
        ]);
    }

    private function manager(int $enterpriseId): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'enterprise_id' => $enterpriseId,
        ]);
    }

    public function test_index_returns_pagination_meta(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = $this->manager($enterpriseId);

        Athlete::factory()->count(20)->create(['enterprise_id' => $enterpriseId, 'owner_id' => $manager->id]);

        $response = $this->actingAs($manager, 'api')->getJson('/api/athletes');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 20)
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2);

        $this->assertCount(15, $response->json('data'));
    }

    public function test_index_second_page_returns_remaining(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = $this->manager($enterpriseId);

        Athlete::factory()->count(20)->create(['enterprise_id' => $enterpriseId, 'owner_id' => $manager->id]);

        $response = $this->actingAs($manager, 'api')->getJson('/api/athletes?page=2');

        $response->assertStatus(200)->assertJsonPath('meta.current_page', 2);
        $this->assertCount(5, $response->json('data'));
    }

    public function test_manager_can_export_xlsx(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = $this->manager($enterpriseId);

        Athlete::factory()->count(3)->create(['enterprise_id' => $enterpriseId, 'owner_id' => $manager->id]);

        $response = $this->actingAs($manager, 'api')->get('/api/athletes/export');

        $response->assertStatus(200);
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('content-type') ?? ''
        );
    }

    public function test_non_admin_cannot_export(): void
    {
        $enterpriseId = $this->createEnterprise();
        $employee = User::factory()->create([
            'is_admin' => false,
            'enterprise_id' => $enterpriseId,
        ]);

        $this->actingAs($employee, 'api')->get('/api/athletes/export')->assertStatus(403);
    }

    public function test_manager_can_download_template(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = $this->manager($enterpriseId);

        $this->actingAs($manager, 'api')->get('/api/athletes/import/template')->assertStatus(200);
    }

    public function test_import_creates_valid_rows_and_reports_errors(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = $this->manager($enterpriseId);

        // Cabeçalhos iguais ao template + 1 linha válida e 1 inválida (CPF ruim).
        $csv = "Nome completo,Data de nascimento,CPF,Gênero,Estado civil,Posição,Celular,Telefone secundário,E-mail,CEP,Endereço,Número,Bairro,Cidade,Estado (UF),Nome da mãe,Nome do pai,Observações\n"
            . "Maria Souza,10/05/2004,715.176.840-05,feminino,solteiro,Meia,11988887777,,maria@teste.com,01001-000,Rua A,10,Centro,São Paulo,SP,Ana,Carlos,\n"
            . "João Inválido,10/05/2004,111.111.111-11,masculino,solteiro,Meia,11988887777,,joao@teste.com,01001-000,Rua A,10,Centro,São Paulo,SP,Ana,Carlos,\n";

        $path = tempnam(sys_get_temp_dir(), 'import') . '.csv';
        file_put_contents($path, $csv);
        $file = new UploadedFile($path, 'import.csv', 'text/csv', null, true);

        $response = $this->actingAs($manager, 'api')->post('/api/athletes/import', ['file' => $file]);

        $response->assertStatus(200)
            ->assertJsonPath('imported', 1)
            ->assertJsonPath('skipped', 1);

        $this->assertDatabaseHas('athletes', ['email' => 'maria@teste.com']);
        $this->assertDatabaseMissing('athletes', ['email' => 'joao@teste.com']);
    }

    public function test_import_detects_existing_document(): void
    {
        $enterpriseId = $this->createEnterprise();
        $manager = $this->manager($enterpriseId);

        Athlete::factory()->create([
            'enterprise_id' => $enterpriseId,
            'owner_id' => $manager->id,
            'document' => '71517684005',
            'email' => 'existente@teste.com',
        ]);

        $csv = "Nome completo,Data de nascimento,CPF,Gênero,Estado civil,Posição,Celular,Telefone secundário,E-mail,CEP,Endereço,Número,Bairro,Cidade,Estado (UF),Nome da mãe,Nome do pai,Observações\n"
            . "Maria Souza,10/05/2004,715.176.840-05,feminino,solteiro,Meia,11988887777,,maria@teste.com,01001-000,Rua A,10,Centro,São Paulo,SP,Ana,Carlos,\n";

        $path = tempnam(sys_get_temp_dir(), 'import') . '.csv';
        file_put_contents($path, $csv);
        $file = new UploadedFile($path, 'import.csv', 'text/csv', null, true);

        $response = $this->actingAs($manager, 'api')->post('/api/athletes/import', ['file' => $file]);

        $response->assertStatus(200)->assertJsonPath('imported', 0)->assertJsonPath('skipped', 1);
    }

    public function test_non_admin_cannot_import(): void
    {
        $enterpriseId = $this->createEnterprise();
        $employee = User::factory()->create([
            'is_admin' => false,
            'enterprise_id' => $enterpriseId,
        ]);

        $path = tempnam(sys_get_temp_dir(), 'import') . '.csv';
        file_put_contents($path, "Nome completo\nTeste\n");
        $file = new UploadedFile($path, 'import.csv', 'text/csv', null, true);

        $this->actingAs($employee, 'api')->post('/api/athletes/import', ['file' => $file])->assertStatus(403);
    }
}
