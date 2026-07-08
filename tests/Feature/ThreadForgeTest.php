<?php

use App\Jobs\ProcessRawContentJob;
use App\Models\Blueprint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// ============================================================
// TEST 1 — Login
// ============================================================

test('login returns a token with valid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('password123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email'    => $user->email,
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'token',
                 'user' => ['id', 'name', 'email'],
             ]);
});

test('login returns 401 with invalid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password123'),]);

    $response = $this->postJson('/api/login', [
        'email'    => $user->email,
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401);
});

// ============================================================
// TEST 2 — Protected route
// ============================================================

test('GET /api/blueprints returns 401 without token', function () {
    $response = $this->getJson('/api/blueprints');

    $response->assertStatus(401);
});

test('GET /api/blueprints returns 200 with valid token', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/blueprints');

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data',
             ]);
});

// ============================================================
// TEST 3 — Validation
// ============================================================

test('POST /api/blueprints returns 422 when name is missing', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/blueprints', [
        'tone'           => 'professionnel',
        'max_hashtags'   => 1,
        'max_characters' => 280,
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name']);
});

// ============================================================
// TEST 4 — Async job dispatch
// ============================================================

test('POST /api/content/repurpose returns 202 and dispatches job', function () {
    Queue::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $blueprint = Blueprint::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->postJson('/api/content/repurpose', [
        'blueprint_id' => $blueprint->id,
        'content'      => 'Ceci est un contenu de test suffisamment long pour passer la validation minimale.',
    ]);

    $response->assertStatus(202);

    Queue::assertPushed(ProcessRawContentJob::class);
});