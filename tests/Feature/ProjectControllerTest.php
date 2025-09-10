<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario autenticado
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test get active projects
     */
    public function test_get_active_projects()
    {
        // Crear cliente y cotización
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);

        // Crear proyectos: uno activo, uno futuro, uno pasado
        $activeProject = Project::factory()->create([
            'quote_id' => $quote->id,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(5),
            'name' => 'Proyecto Activo'
        ]);

        $futureProject = Project::factory()->create([
            'quote_id' => $quote->id,
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(20),
            'name' => 'Proyecto Futuro'
        ]);

        $pastProject = Project::factory()->create([
            'quote_id' => $quote->id,
            'start_date' => Carbon::now()->subDays(20),
            'end_date' => Carbon::now()->subDays(10),
            'name' => 'Proyecto Pasado'
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ])
                ->assertJson([
                    'success' => true
                ]);

        // Verificar que solo devuelve el proyecto activo
        $projects = $response->json('data');
        $this->assertCount(1, $projects);
        $this->assertEquals('Proyecto Activo', $projects[0]['name']);
    }

    /**
     * Test get all projects
     */
    public function test_get_all_projects()
    {
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);

        // Crear múltiples proyectos
        Project::factory()->count(3)->create(['quote_id' => $quote->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/all');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message'
                ]);

        $projects = $response->json('data');
        $this->assertCount(3, $projects);
    }

    /**
     * Test get all projects with filters
     */
    public function test_get_all_projects_with_filters()
    {
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);

        $project = Project::factory()->create([
            'quote_id' => $quote->id,
            'name' => 'Proyecto Test',
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-15'
        ]);

        // Test filtro por nombre
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/all?search=Test');

        $response->assertStatus(200);
        $projects = $response->json('data');
        $this->assertCount(1, $projects);
        $this->assertEquals('Proyecto Test', $projects[0]['name']);

        // Test filtro por fecha específica
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/all?date=2025-07-07');

        $response->assertStatus(200);
        $projects = $response->json('data');
        $this->assertCount(1, $projects);
    }

    /**
     * Test get specific project
     */
    public function test_get_specific_project()
    {
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);
        $project = Project::factory()->create(['quote_id' => $quote->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson("/api/projects/{$project->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'name',
                        'start_date',
                        'end_date',
                        'quote' => [
                            'client'
                        ]
                    ],
                    'message'
                ]);
    }

    /**
     * Test get non-existent project
     */
    public function test_get_non_existent_project()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/99999');

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'Proyecto no encontrado'
                ]);
    }

    /**
     * Test advanced project search
     */
    public function test_advanced_project_search()
    {
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);

        $project = Project::factory()->create([
            'quote_id' => $quote->id,
            'name' => 'Proyecto Búsqueda',
            'start_date' => '2025-07-01',
            'end_date' => '2025-07-31',
            'location' => 'Lima, Perú'
        ]);

        // Test búsqueda por múltiples parámetros
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/search?' . http_build_query([
            'name' => 'Búsqueda',
            'client_id' => $client->id,
            'start_date_from' => '2025-07-01',
            'start_date_to' => '2025-07-31',
            'location' => 'Lima'
        ]));

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data',
                    'message',
                    'total_found',
                    'filters_applied'
                ]);

        $projects = $response->json('data');
        $this->assertCount(1, $projects);
        $this->assertEquals('Proyecto Búsqueda', $projects[0]['name']);
    }

    /**
     * Test search with invalid parameters
     */
    public function test_search_with_invalid_parameters()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/search?' . http_build_query([
            'start_date_from' => 'invalid-date',
            'client_id' => 99999
        ]));

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['start_date_from', 'client_id']);
    }

    /**
     * Test unauthorized access
     */
    public function test_unauthorized_access()
    {
        $response = $this->getJson('/api/projects');
        $response->assertStatus(401);
    }

    /**
     * Test projects with status filter
     */
    public function test_projects_with_status_filter()
    {
        $client = Client::factory()->create();
        $quote = Quote::factory()->create(['client_id' => $client->id]);

        // Crear proyectos con diferentes estados
        $activeProject = Project::factory()->create([
            'quote_id' => $quote->id,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(5)
        ]);

        $upcomingProject = Project::factory()->create([
            'quote_id' => $quote->id,
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);

        // Test filtro de proyectos activos
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/all?status=active');

        $response->assertStatus(200);
        $projects = $response->json('data');
        $this->assertCount(1, $projects);

        // Test filtro de proyectos futuros
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token
        ])->getJson('/api/projects/all?status=upcoming');

        $response->assertStatus(200);
        $projects = $response->json('data');
        $this->assertCount(1, $projects);
    }
}
