<?php

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\SubClient;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Feature\CreatesAuthenticatedUser;
use Tests\TestCase;

class SubClientApiTest extends TestCase
{
    use CreatesAuthenticatedUser, DatabaseTransactions;

    protected array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $auth = $this->createAuthenticatedUser();
        $this->headers = [
            'Authorization' => 'Bearer '.$auth['token'],
            'Accept' => 'application/json',
        ];
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $response = $this->getJson('/api/sub-clients/search');
        $response->assertStatus(401);

        $response2 = $this->getJson('/api/sub-clients/data');
        $response2->assertStatus(401);
    }

    public function test_authenticated_user_can_search_sub_clients_by_name(): void
    {
        $client = Client::factory()->create();

        $target = SubClient::factory()->create([
            'client_id' => $client->id,
            'name' => 'Planta Callao Molinos '.uniqid(),
            'description' => 'Sede Callao',
            'location' => 'Av. Faucett 123',
        ]);

        $other = SubClient::factory()->create([
            'client_id' => $client->id,
            'name' => 'Sede Arequipa Centro '.uniqid(),
            'description' => 'Sede Arequipa',
            'location' => 'Av. Bolognesi 456',
        ]);

        $response = $this->getJson('/api/sub-clients/search?search=Callao', $this->headers);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'SubClients retrieved',
            ])
            ->assertJsonFragment([
                'id' => $target->id,
                'name' => $target->name,
            ])
            ->assertJsonMissing([
                'name' => $other->name,
            ]);
    }

    public function test_can_filter_sub_clients_by_client_id(): void
    {
        $client1 = Client::factory()->create();
        $client2 = Client::factory()->create();

        $sub1 = SubClient::factory()->create(['client_id' => $client1->id, 'name' => 'Sub1 '.uniqid()]);
        $sub2 = SubClient::factory()->create(['client_id' => $client2->id, 'name' => 'Sub2 '.uniqid()]);

        $response = $this->getJson("/api/sub-clients/search?client_id={$client1->id}", $this->headers);

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $sub1->id, 'name' => $sub1->name])
            ->assertJsonMissing(['id' => $sub2->id, 'name' => $sub2->name]);
    }

    public function test_data_endpoint_returns_consistent_structure(): void
    {
        $client = Client::factory()->create();
        $sub = SubClient::factory()->create(['client_id' => $client->id, 'name' => 'Central '.uniqid()]);

        $response = $this->getJson('/api/sub-clients/data?search=Central', $this->headers);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'clientId',
                        'name',
                        'client',
                    ],
                ],
                'pagination' => [
                    'total',
                    'perPage',
                    'currentPage',
                    'lastPage',
                ],
            ]);
    }
}
