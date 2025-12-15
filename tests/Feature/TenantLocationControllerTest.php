<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\Neighborhood;
use App\Models\TenantCountry;
use App\Models\TenantState;
use App\Models\TenantCity;
use App\Models\TenantNeighborhood;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class TenantLocationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $tenant;
    protected $tenantUser;
    protected $country;
    protected $state;
    protected $city;
    protected $neighborhood;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário
        $this->tenant = Tenant::factory()->create();
        $this->tenantUser = TenantUser::factory()->create([
            'tenant_id' => $this->tenant->id
        ]);

        // Criar dados de localização
        $this->country = Country::factory()->create();
        $this->state = State::factory()->create(['country_id' => $this->country->id]);
        $this->city = City::factory()->create([
            'state_id' => $this->state->id,
            'country_id' => $this->country->id
        ]);
        $this->neighborhood = Neighborhood::factory()->create([
            'city_id' => $this->city->id,
            'state_id' => $this->state->id,
            'country_id' => $this->country->id
        ]);
    }

    /** @test */
    public function it_can_list_tenant_countries()
    {
        // Criar associação de país com tenant
        TenantCountry::create([
            'tenant_id' => $this->tenant->id,
            'country_id' => $this->country->id,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->tenantUser, 'sanctum')
            ->getJson('/api/tenant/locations/countries');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'phone_code',
                        'currency',
                        'is_active'
                    ]
                ],
                'pagination'
            ]);
    }

    /** @test */
    public function it_can_add_country_to_tenant()
    {
        $response = $this->actingAs($this->tenantUser, 'sanctum')
            ->postJson('/api/tenant/locations/countries', [
                'country_id' => $this->country->id,
                'is_active' => true
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'País adicionado ao tenant com sucesso'
            ]);

        $this->assertDatabaseHas('tenant_countries', [
            'tenant_id' => $this->tenant->id,
            'country_id' => $this->country->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_cannot_add_duplicate_country_to_tenant()
    {
        // Criar associação existente
        TenantCountry::create([
            'tenant_id' => $this->tenant->id,
            'country_id' => $this->country->id,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->tenantUser, 'sanctum')
            ->postJson('/api/tenant/locations/countries', [
                'country_id' => $this->country->id,
                'is_active' => true
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country_id']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson('/api/tenant/locations/countries');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $response = $this->actingAs($this->tenantUser, 'sanctum')
            ->postJson('/api/tenant/locations/countries', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country_id']);
    }

    /** @test */
    public function it_validates_country_exists()
    {
        $response = $this->actingAs($this->tenantUser, 'sanctum')
            ->postJson('/api/tenant/locations/countries', [
                'country_id' => 99999,
                'is_active' => true
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country_id']);
    }

    /** @test */
    public function it_can_filter_countries_by_search()
    {
        // Criar países com nomes diferentes
        $country1 = Country::factory()->create(['name' => 'Brasil']);
        $country2 = Country::factory()->create(['name' => 'Argentina']);

        // Adicionar ambos ao tenant
        TenantCountry::create([
            'tenant_id' => $this->tenant->id,
            'country_id' => $country1->id,
            'is_active' => true
        ]);

        TenantCountry::create([
            'tenant_id' => $this->tenant->id,
            'country_id' => $country2->id,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->tenantUser, 'sanctum')
            ->getJson('/api/tenant/locations/countries?search=Brasil');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Brasil', $data[0]['name']);
    }

    /** @test */
    public function it_can_filter_by_active_status()
    {
        // Criar associações com status diferentes
        TenantCountry::create([
            'tenant_id' => $this->tenant->id,
            'country_id' => $this->country->id,
            'is_active' => true
        ]);

        $response = $this->actingAs($this->tenantUser, 'sanctum')
            ->getJson('/api/tenant/locations/countries?is_active=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertTrue($data[0]['is_active']);
    }
}
